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
 * Concrete AIProvider for Google's Gemini API.
 *
 * Uses the v1beta generateContent endpoint:
 *   POST https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent?key={api_key}
 *
 * Wired up by App\Providers\AIServiceProvider with the API key resolved
 * from the encrypted `ai.gemini_api_key` setting.
 */
class GeminiProvider implements AIProvider
{
    public const NAME = 'gemini';

    /**
     * Per-1-million-token USD rates (input + output). Update when
     * Google adjusts pricing.
     *
     * The order matters: the first entry is used as the default
     * substitute when `AIManager::ensureModelCompatibility()` needs to
     * swap a non-Gemini model name from the fallback chain. Keep a
     * cheap, fast, broadly-available model in the first slot.
     *
     * Note: `gemini-1.5-*` models were retired in v1beta — do not add
     * them back without verifying the endpoint still responds.
     *
     * @var array<string, array{in: float, out: float}>
     */
    private const PRICING = [
        'gemini-2.0-flash' => ['in' => 0.10, 'out' => 0.40],
        'gemini-2.0-flash-001' => ['in' => 0.10, 'out' => 0.40],
        'gemini-2.0-flash-lite' => ['in' => 0.075, 'out' => 0.30],
        'gemini-2.5-flash' => ['in' => 0.30, 'out' => 2.50],
        'gemini-2.5-pro' => ['in' => 1.25, 'out' => 10.00],
        'gemini-flash-latest' => ['in' => 0.30, 'out' => 2.50],
        'gemini-flash-lite-latest' => ['in' => 0.075, 'out' => 0.30],
        'gemini-pro-latest' => ['in' => 1.25, 'out' => 10.00],
    ];

    private const HEALTH_CACHE_KEY = 'ai.gemini.health';

    private const HEALTH_CACHE_TTL = 60;

    public function __construct(
        private string $apiKey,
        private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta',
        private int $timeoutSeconds = 60,
    ) {}

    public function name(): string
    {
        return self::NAME;
    }

    public function complete(CompletionRequest $request): CompletionResponse
    {
        $this->assertApiKey();

        $endpoint = "{$this->baseUrl}/models/{$request->model}:generateContent?key=".urlencode($this->apiKey);

        $response = $this->httpClient()
            ->retry(2, 1000, function (Throwable $exception): bool {
                return ! ($exception instanceof \Illuminate\Http\Client\RequestException)
                    || $exception->response->serverError();
            }, throw: false)
            ->post($endpoint, $this->buildPayload($request));

        $this->throwIfFailed($response, $request);

        $body = $response->json();

        $content = (string) data_get($body, 'candidates.0.content.parts.0.text', '');
        $finishReason = data_get($body, 'candidates.0.finishReason');
        $promptTokens = (int) data_get($body, 'usageMetadata.promptTokenCount', 0);
        $completionTokens = (int) data_get($body, 'usageMetadata.candidatesTokenCount', 0);
        $totalTokens = (int) data_get($body, 'usageMetadata.totalTokenCount', $promptTokens + $completionTokens);

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
            finishReason: is_string($finishReason) ? strtolower($finishReason) : null,
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
                $url = "{$this->baseUrl}/models?key=".urlencode($this->apiKey);

                return $this->httpClient(timeout: 5)
                    ->get($url)
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
            $rates = ['in' => 0.10, 'out' => 0.40];   // conservative default
        }

        return round(
            ($promptTokens / 1_000_000) * $rates['in']
            + ($completionTokens / 1_000_000) * $rates['out'],
            6,
        );
    }

    private function httpClient(?int $timeout = null): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->timeout($timeout ?? $this->timeoutSeconds);
    }

    /**
     * Gemini uses a different payload shape than OpenAI — system prompt
     * goes into `systemInstruction.parts[].text`, user prompt into
     * `contents[0].parts[].text`. Generation params live under
     * `generationConfig`.
     *
     * @return array<string, mixed>
     */
    private function buildPayload(CompletionRequest $request): array
    {
        $generationConfig = [
            'temperature' => $request->temperature,
            'maxOutputTokens' => $request->maxTokens,
        ];

        if ($request->topP !== null) {
            $generationConfig['topP'] = $request->topP;
        }

        if ($request->stopSequences !== null && $request->stopSequences !== []) {
            $generationConfig['stopSequences'] = $request->stopSequences;
        }

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $request->userPrompt],
                    ],
                ],
            ],
            'generationConfig' => $generationConfig,
        ];

        if ($request->systemPrompt !== '') {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $request->systemPrompt],
                ],
            ];
        }

        return $payload;
    }

    private function assertApiKey(): void
    {
        if ($this->apiKey === '') {
            throw new AIProviderException(
                message: 'Gemini API key is not configured. Set it in Settings → AI Providers.',
                providerName: self::NAME,
            );
        }
    }

    private function throwIfFailed(Response $response, CompletionRequest $request): void
    {
        if ($response->successful()) {
            return;
        }

        $errorMessage = (string) data_get($response->json(), 'error.message', 'Gemini API error.');

        if ($response->status() === 429) {
            throw new RateLimitException(
                message: $errorMessage,
                providerName: self::NAME,
                model: $request->model,
            );
        }

        throw new AIProviderException(
            message: "Gemini [{$response->status()}]: {$errorMessage}",
            providerName: self::NAME,
            model: $request->model,
            code: $response->status(),
        );
    }
}
