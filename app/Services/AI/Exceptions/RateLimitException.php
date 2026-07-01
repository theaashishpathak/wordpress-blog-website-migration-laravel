<?php

declare(strict_types=1);

namespace App\Services\AI\Exceptions;

/**
 * Provider returned HTTP 429 or equivalent rate-limit response.
 *
 * AIManager will increment the CircuitBreaker failure counter for this
 * provider and try the next in the fallback chain.
 */
class RateLimitException extends AIProviderException
{
    public function __construct(
        string $message = 'AI provider rate limit reached.',
        ?string $providerName = null,
        ?string $model = null,
        public readonly ?int $retryAfterSeconds = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            providerName: $providerName,
            model: $model,
            code: 429,
            previous: $previous,
        );
    }
}
