<?php

declare(strict_types=1);

namespace App\Services\AI\Exceptions;

/**
 * Thrown by AIManager when every provider in the fallback chain is either
 * circuit-broken or has just failed. The end-user sees a friendly degraded
 * state — admin should check provider health and API key validity.
 */
class ProviderUnavailableException extends AIProviderException
{
    public function __construct(
        string $message = 'All configured AI providers are currently unavailable.',
        /** @var list<string> */
        public readonly array $attemptedProviders = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            code: 503,
            previous: $previous,
        );
    }
}
