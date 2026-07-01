<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\AI\Contracts\AIProvider;
use App\Services\AI\Contracts\SupportsStreaming;
use App\Services\AI\DataTransferObjects\CompletionRequest;
use App\Services\AI\DataTransferObjects\CompletionResponse;
use App\Services\AI\DataTransferObjects\StreamChunk;
use App\Services\AI\DataTransferObjects\TokenUsage;
use App\Services\AI\Exceptions\AIProviderException;
use App\Services\AI\Exceptions\ProviderUnavailableException;
use App\Services\AI\Exceptions\QuotaExceededException;
use App\Services\SettingService;
use Generator;
use Illuminate\Container\Container;
use Throwable;

/**
 * The ONLY entry point application code uses to talk to AI providers.
 *
 * Actions / Livewire / Jobs inject AIManager (never a concrete provider).
 * AIManager:
 *   1. Resolves the provider chain from settings + user preference.
 *   2. Asserts cost ceilings before any provider call.
 *   3. Walks the chain, skipping providers tripped by CircuitBreaker.
 *   4. Records every attempt (success or failure) in ai_usage_logs.
 *   5. Streams via Generator when the resolved provider supports it.
 *
 * Spec: docs/AI Provider Contract.txt Section 6.
 */
class AIManager
{
    /**
     * Map provider canonical names to their concrete class binding.
     * The keys here are the strings used in settings (ai.default_provider,
     * ai.fallback_chain). Real providers (OpenAI, Gemini, Claude,
     * OpenRouter) are registered in App\Providers\AIServiceProvider.
     *
     * @var array<string, class-string<AIProvider>>
     */
    private array $registry = [
        NullProvider::NAME => NullProvider::class,
    ];

    public function __construct(
        private CircuitBreaker $circuitBreaker,
        private AIUsageTracker $usageTracker,
        private SettingService $settings,
        private ?Container $container = null,
    ) {}

    /**
     * Register a concrete provider class against its canonical name.
     *
     * Used by AIServiceProvider to wire up real providers without making
     * AIManager itself depend on their concrete classes.
     *
     * @param  class-string<AIProvider>  $providerClass
     */
    public function register(string $name, string $providerClass): void
    {
        $this->registry[$name] = $providerClass;
    }

    /**
     * Resolve a provider by name. Throws if unknown.
     */
    public function driver(?string $name = null): AIProvider
    {
        $name ??= (string) $this->settings->get('ai.default_provider', NullProvider::NAME);

        if (! isset($this->registry[$name])) {
            throw new AIProviderException("Unknown AI provider: {$name}");
        }

        return $this->container()->make($this->registry[$name]);
    }

    /**
     * @return list<string>
     */
    public function registeredProviders(): array
    {
        return array_keys($this->registry);
    }

    /**
     * Synchronous completion with fallback chain, circuit breaker, and usage tracking.
     */
    public function complete(CompletionRequest $request, ?string $preferredProvider = null): CompletionResponse
    {
        $this->assertWithinCostCeiling($request);

        $chain = $this->resolveFallbackChain($preferredProvider);
        $attempted = [];

        foreach ($chain as $providerName) {
            if (! $this->circuitBreaker->allow($providerName)) {
                $attempted[] = "{$providerName} (circuit_open)";
                continue;
            }

            $attempted[] = $providerName;
            $startedAt = (int) (microtime(true) * 1000);

            try {
                $provider = $this->driver($providerName);

                // Each provider speaks a different model dialect — OpenAI
                // names like 'gpt-4o-mini' won't work against Gemini's API,
                // and vice versa. When the request's requested model isn't
                // in the current provider's catalog, substitute with the
                // provider's first known model so the fallback chain
                // actually delivers a usable response instead of failing.
                $requestForProvider = $this->ensureModelCompatibility($request, $provider);

                $response = $provider->complete($requestForProvider);

                $duration = ((int) (microtime(true) * 1000)) - $startedAt;

                $this->circuitBreaker->recordSuccess($providerName);
                $this->usageTracker->record($requestForProvider, $response, $duration);

                return $response;
            } catch (AIProviderException $e) {
                $duration = ((int) (microtime(true) * 1000)) - $startedAt;

                $this->circuitBreaker->recordFailure($providerName, $e);
                $this->usageTracker->recordFailure(
                    request: $request,
                    providerName: $providerName,
                    status: \App\Models\AIUsageLog::STATUS_FAILED,
                    exception: $e,
                    durationMs: $duration,
                );

                continue;
            }
        }

        throw new ProviderUnavailableException(
            message: 'All AI providers in the fallback chain failed or are circuit-broken.',
            attemptedProviders: $attempted,
        );
    }

    /**
     * Streamed completion. No fallback during streaming (cannot resume on
     * another provider mid-stream).
     *
     * @return Generator<int, StreamChunk>
     */
    public function stream(CompletionRequest $request, ?string $preferredProvider = null): Generator
    {
        $this->assertWithinCostCeiling($request);

        $providerName = $preferredProvider ?? (string) $this->settings->get('ai.default_provider', NullProvider::NAME);
        $provider = $this->driver($providerName);
        $request = $this->ensureModelCompatibility($request, $provider);
        $startedAt = (int) (microtime(true) * 1000);

        if (! $provider instanceof SupportsStreaming) {
            // Emulate streaming with a single-chunk pseudo-stream so callers
            // can use a uniform Generator-based API.
            try {
                $response = $provider->complete($request);
                $duration = ((int) (microtime(true) * 1000)) - $startedAt;

                $this->circuitBreaker->recordSuccess($providerName);
                $this->usageTracker->record($request, $response, $duration);

                yield new StreamChunk(
                    delta: $response->content,
                    isFinal: true,
                    usage: $response->usage,
                );

                return;
            } catch (Throwable $e) {
                $duration = ((int) (microtime(true) * 1000)) - $startedAt;
                $this->circuitBreaker->recordFailure($providerName, $e);
                $this->usageTracker->recordFailure(
                    request: $request,
                    providerName: $providerName,
                    status: \App\Models\AIUsageLog::STATUS_FAILED,
                    exception: $e,
                    durationMs: $duration,
                );

                throw $e;
            }
        }

        $accumulated = '';
        $finalUsage = null;

        try {
            foreach ($provider->stream($request) as $chunk) {
                $accumulated .= $chunk->delta;

                if ($chunk->isFinal && $chunk->usage !== null) {
                    $finalUsage = $chunk->usage;
                }

                yield $chunk;
            }
        } catch (Throwable $e) {
            $duration = ((int) (microtime(true) * 1000)) - $startedAt;
            $this->circuitBreaker->recordFailure($providerName, $e);
            $this->usageTracker->recordFailure(
                request: $request,
                providerName: $providerName,
                status: \App\Models\AIUsageLog::STATUS_FAILED,
                exception: $e,
                durationMs: $duration,
            );

            throw $e;
        }

        $duration = ((int) (microtime(true) * 1000)) - $startedAt;
        $this->circuitBreaker->recordSuccess($providerName);

        $this->usageTracker->recordStreamed(
            request: $request,
            providerName: $providerName,
            accumulatedContent: $accumulated,
            usage: $finalUsage ?? TokenUsage::zero(),
            durationMs: $duration,
        );
    }

    /**
     * Substitute the request model with one the provider actually supports.
     *
     * When the fallback chain hops from OpenAI to Gemini (or vice versa),
     * the original `model` field holds a name the new provider's API will
     * reject. This method swaps it for the provider's first known model
     * so the fallback delivers a real response instead of a second failure.
     *
     * NullProvider declares `null-stub` which is always returned. Returning
     * an unchanged request when the model is already supported keeps the
     * common path zero-cost.
     */
    private function ensureModelCompatibility(CompletionRequest $request, AIProvider $provider): CompletionRequest
    {
        $supportedModels = $provider->availableModels();

        if ($supportedModels === []) {
            return $request;
        }

        if (in_array($request->model, $supportedModels, true)) {
            return $request;
        }

        $substitute = $supportedModels[0];

        return new CompletionRequest(
            model: $substitute,
            systemPrompt: $request->systemPrompt,
            userPrompt: $request->userPrompt,
            temperature: $request->temperature,
            maxTokens: $request->maxTokens,
            topP: $request->topP,
            stopSequences: $request->stopSequences,
            metadata: array_merge($request->metadata, [
                'model_substituted_from' => $request->model,
                'model_substituted_to' => $substitute,
                'substituted_by_provider' => $provider->name(),
            ]),
            promptTemplateKey: $request->promptTemplateKey,
            featureKey: $request->featureKey,
            userId: $request->userId,
        );
    }

    /**
     * @return list<string>
     */
    private function resolveFallbackChain(?string $preferred): array
    {
        $configured = (array) $this->settings->get('ai.fallback_chain', [NullProvider::NAME]);

        $chain = array_values(array_filter(
            $configured,
            fn ($name): bool => is_string($name) && $name !== '',
        ));

        if ($preferred !== null && $preferred !== '') {
            $chain = array_values(array_unique(array_merge([$preferred], $chain)));
        }

        // Always end the chain with NullProvider so we degrade gracefully
        // instead of throwing ProviderUnavailableException in dev.
        if (! in_array(NullProvider::NAME, $chain, true)) {
            $chain[] = NullProvider::NAME;
        }

        return $chain;
    }

    private function assertWithinCostCeiling(CompletionRequest $request): void
    {
        if ($request->userId === null) {
            return;
        }

        $monthly = $this->usageTracker->monthlyCostForUser($request->userId);
        $ceiling = (float) $this->settings->get('ai.user_monthly_cost_ceiling_usd', 25.0);

        if ($ceiling > 0.0 && $monthly >= $ceiling) {
            throw new QuotaExceededException(
                message: "Monthly AI cost ceiling of \${$ceiling} reached.",
                quotaType: 'user_monthly_cost',
                usedAmount: $monthly,
                ceilingAmount: $ceiling,
            );
        }

        $platformCeiling = (float) $this->settings->get('ai.platform_monthly_cost_ceiling_usd', 1000.0);

        if ($platformCeiling > 0.0) {
            $platformUsed = $this->usageTracker->monthlyPlatformCost();

            if ($platformUsed >= $platformCeiling) {
                throw new QuotaExceededException(
                    message: "Platform monthly AI cost ceiling of \${$platformCeiling} reached.",
                    quotaType: 'platform_monthly_cost',
                    usedAmount: $platformUsed,
                    ceilingAmount: $platformCeiling,
                );
            }
        }
    }

    private function container(): Container
    {
        return $this->container ?? Container::getInstance();
    }
}
