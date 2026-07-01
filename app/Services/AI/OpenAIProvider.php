<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\AI\Contracts\AIProvider;
use App\Services\AI\DataTransferObjects\CompletionRequest;
use App\Services\AI\DataTransferObjects\CompletionResponse;
use App\Services\AI\DataTransferObjects\TokenUsage;
use App\Services\AI\Exceptions\AIProviderException;
use App\Services\AI\Exceptions\RateLimitException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Concrete AIProvider for OpenAI Chat Completions API.
 *
 * Wired up by App\Providers\AIServiceProvider with the API key resolved
 * from the encrypted `ai.openai_api_key` setting.
 *
 * Streaming + embedding + moderation are deliberately deferred to
 * Phase 3C — this class implements just the synchronous complete() path
 * + the bookkeeping (cost estimation, health probe) that AIManager needs.
 */
class OpenAIProvider implements AIProvider
{
    public const NAME = 'openai';

    /**
     * Per-1-million-token USD rates. Update when OpenAI changes pricing.
     *
     * @var array<string, array{in: float, out: float}>
     */
    private const PRICING = [
        'gpt-4o' => ['in' => 2.50, 'out' => 10.00],
        'gpt-4o-mini' => ['in' => 0.15, 'out' => 0.60],
        'gpt-4-turbo' => ['in' => 10.00, 'out' => 30.00],
        'gpt-4' => ['in' => 30.00, 'out' => 60.00],
        'gpt-3.5-turbo' => ['in' => 0.50, 'out' => 1.50],
    ];

    private const HEALTH_CACHE_KEY = 'ai.openai.health';

    private const HEALTH_CACHE_TTL = 60;

    public function __construct(
        private string $apiKey,
        private string $baseUrl = 'https://api.openai.com/v1',
        private int $timeoutSeconds = 60,
    ) {}

    public function name(): string
    {
        return self::NAME;
    }

    public function complete(CompletionRequest $request): CompletionResponse
    {
        $this->assertApiKey();

        $response = $this->httpClient()
            ->retry(2, 1000, function (Throwable $exception): bool {
                // Retry only on transient 5xx + network errors, never on 4xx.
                return ! ($exception instanceof \Illuminate\Http\Client\RequestException)
                    || $exception->response->serverError();
            }, throw: false)
            ->post("{$this->baseUrl}/chat/completions", $this->buildPayload($request));

        $this->throwIfFailed($response, $request);

        $body = $response->json();

        $content = (string) data_get($body, 'choices.0.message.content', '');
        $finishReason = data_get($body, 'choices.0.finish_reason');
        $promptTokens = (int) data_get($body, 'usage.prompt_tokens', 0);
        $completionTokens = (int) data_get($body, 'usage.completion_tokens', 0);
        $totalTokens = (int) data_get($body, 'usage.total_tokens', $promptTokens + $completionTokens);

        return new CompletionResponse(
            content: $content,
            usage: new TokenUsage(
                promptTokens: $promptTokens,
                completionTokens: $completionTokens,
                totalTokens: $totalTokens,
                estimatedCostUsd: $this->estimateCost($request->model, $promptTokens, $completionTokens),
            ),
            providerName: self::NAME,
            model: $request->model,
            finishReason: is_string($finishReason) ? $finishReason : null,
            raw: is_array($body) ? $body : [],
        );
    }

    public function availableModels(): array
    {
        return array_keys(self::PRICING);
    }

    public function isHealthy(): bool
    {
        if ($this->apiKey === '') {
            return false;
        }

        return (bool) Cache::remember(self::HEALTH_CACHE_KEY, self::HEALTH_CACHE_TTL, function (): bool {
            try {
                return $this->httpClient(timeout: 5)
                    ->get("{$this->baseUrl}/models")
                    ->successful();
            } catch (Throwable) {
                return false;
            }
        });
    }

    public function estimateCost(string $model, int $promptTokens, int $completionTokens): float
    {
        $rates = self::PRICING[$model] ?? null;

        if ($rates === null) {
            // Unknown model — conservative guess (matches gpt-3.5-turbo).
            $rates = ['in' => 0.50, 'out' => 1.50];
        }

        return round(
            ($promptTokens / 1_000_000) * $rates['in']
            + ($completionTokens / 1_000_000) * $rates['out'],
            6,
        );
    }

    private function httpClient(?int $timeout = null): PendingRequest
    {
        return Http::withToken($this->apiKey)
            ->acceptJson()
            ->asJson()
            ->timeout($timeout ?? $this->timeoutSeconds);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(CompletionRequest $request): array
    {
        $payload = [
            'model' => $request->model,
            'messages' => [
                ['role' => 'system', 'content' => $request->systemPrompt],
                ['role' => 'user', 'content' => $request->userPrompt],
            ],
            'temperature' => $request->temperature,
            'max_tokens' => $request->maxTokens,
        ];

        if ($request->topP !== null) {
            $payload['top_p'] = $request->topP;
        }

        if ($request->stopSequences !== null && $request->stopSequences !== []) {
            $payload['stop'] = $request->stopSequences;
        }

        return $payload;
    }

    private function assertApiKey(): void
    {
        if ($this->apiKey === '') {
            throw new AIProviderException(
                message: 'OpenAI API key is not configured. Set it in Settings → AI Providers.',
                providerName: self::NAME,
            );
        }
    }

    private function throwIfFailed(Response $response, CompletionRequest $request): void
    {
        if ($response->successful()) {
            return;
        }

        $errorMessage = (string) data_get($response->json(), 'error.message', 'OpenAI API error.');

        if ($response->status() === 429) {
            $retryAfter = (int) ($response->header('Retry-After') ?: 0);

            throw new RateLimitException(
                message: $errorMessage,
                providerName: self::NAME,
                model: $request->model,
                retryAfterSeconds: $retryAfter > 0 ? $retryAfter : null,
            );
        }

        throw new AIProviderException(
            message: "OpenAI [{$response->status()}]: {$errorMessage}",
            providerName: self::NAME,
            model: $request->model,
            code: $response->status(),
        );
    }
}
