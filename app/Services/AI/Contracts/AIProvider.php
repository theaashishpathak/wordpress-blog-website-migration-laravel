<?php

declare(strict_types=1);

namespace App\Services\AI\Contracts;

use App\Services\AI\DataTransferObjects\CompletionRequest;
use App\Services\AI\DataTransferObjects\CompletionResponse;
use App\Services\AI\Exceptions\AIProviderException;

/**
 * Main AI provider contract.
 *
 * Every provider class (OpenAI, Gemini, Claude, OpenRouter, Null) must
 * implement this interface. Application code MUST go through AIManager —
 * never instantiate a concrete provider directly. The arch test enforces
 * this rule (see tests/Arch.php).
 *
 * Authoritative spec: docs/AI Provider Contract.txt Section 3.
 */
interface AIProvider
{
    /**
     * Provider canonical name (e.g., "openai", "gemini", "claude", "null").
     * Used for logging, usage attribution, and circuit-breaker keys.
     */
    public function name(): string;

    /**
     * Synchronous completion. Throws on transport / API failure.
     *
     * @throws AIProviderException
     */
    public function complete(CompletionRequest $request): CompletionResponse;

    /**
     * List supported model identifiers for this provider.
     *
     * @return list<string>
     */
    public function availableModels(): array;

    /**
     * Fast health probe used by CircuitBreaker. Should not block longer
     * than a couple of seconds; cache results internally when sensible.
     */
    public function isHealthy(): bool;

    /**
     * Estimated USD cost for the given token usage on the given model.
     */
    public function estimateCost(string $model, int $promptTokens, int $completionTokens): float;
}
