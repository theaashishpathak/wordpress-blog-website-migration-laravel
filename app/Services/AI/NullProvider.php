<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\AI\Contracts\AIProvider;
use App\Services\AI\Contracts\SupportsStreaming;
use App\Services\AI\DataTransferObjects\CompletionRequest;
use App\Services\AI\DataTransferObjects\CompletionResponse;
use App\Services\AI\DataTransferObjects\StreamChunk;
use App\Services\AI\DataTransferObjects\TokenUsage;
use Generator;

/**
 * Deterministic, zero-cost provider used in two scenarios:
 *
 *   1. Pest tests — let test code exercise AIManager / CircuitBreaker /
 *      AIUsageTracker without burning real API calls or rate-limiting CI.
 *   2. Safe fallback — admin has not yet configured any real provider
 *      (fresh install). AIManager falls through the chain and lands on
 *      NullProvider so the UI does not crash.
 *
 * Returns a clearly marked placeholder so authors instantly see that no
 * real provider is wired up.
 */
class NullProvider implements AIProvider, SupportsStreaming
{
    public const NAME = 'null';

    public const PLACEHOLDER_PREFIX = '[NullProvider] No real AI provider is configured. Configured response for prompt: ';

    public function name(): string
    {
        return self::NAME;
    }

    public function complete(CompletionRequest $request): CompletionResponse
    {
        $content = $this->placeholderFor($request);

        $promptTokens = $this->estimateTokens($request->systemPrompt . $request->userPrompt);
        $completionTokens = $this->estimateTokens($content);

        return new CompletionResponse(
            content: $content,
            usage: new TokenUsage(
                promptTokens: $promptTokens,
                completionTokens: $completionTokens,
                totalTokens: $promptTokens + $completionTokens,
                estimatedCostUsd: 0.0,
            ),
            providerName: self::NAME,
            model: $request->model,
            finishReason: 'stop',
            raw: ['provider' => 'null', 'deterministic' => true],
        );
    }

    /**
     * @return Generator<int, StreamChunk>
     */
    public function stream(CompletionRequest $request): Generator
    {
        $content = $this->placeholderFor($request);

        // Emit each word as a chunk so streaming consumers see realistic shape.
        $words = preg_split('/(\s+)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];

        foreach ($words as $word) {
            if ($word === '') {
                continue;
            }
            yield new StreamChunk(delta: $word, isFinal: false);
        }

        $promptTokens = $this->estimateTokens($request->systemPrompt . $request->userPrompt);
        $completionTokens = $this->estimateTokens($content);

        yield new StreamChunk(
            delta: '',
            isFinal: true,
            usage: new TokenUsage(
                promptTokens: $promptTokens,
                completionTokens: $completionTokens,
                totalTokens: $promptTokens + $completionTokens,
                estimatedCostUsd: 0.0,
            ),
        );
    }

    public function availableModels(): array
    {
        return ['null-stub'];
    }

    public function isHealthy(): bool
    {
        return true;
    }

    public function estimateCost(string $model, int $promptTokens, int $completionTokens): float
    {
        return 0.0;
    }

    private function placeholderFor(CompletionRequest $request): string
    {
        return self::PLACEHOLDER_PREFIX . mb_substr($request->userPrompt, 0, 200);
    }

    /**
     * Cheap heuristic — roughly 4 chars per token. Good enough for the stub.
     */
    private function estimateTokens(string $text): int
    {
        $length = mb_strlen($text);

        return max(1, (int) ceil($length / 4));
    }
}
