<?php

declare(strict_types=1);

namespace App\Services\AI\Exceptions;

/**
 * Provider returned content that failed safety moderation (hate, self-harm,
 * sexual minors, weapons, PII). UI should surface a friendly "try a
 * different prompt" message and offer to re-run with a refined prompt.
 */
class ContentFilteredException extends AIProviderException
{
    /**
     * @param  list<string>  $flaggedCategories
     */
    public function __construct(
        string $message = 'Generated content was blocked by AI moderation.',
        ?string $providerName = null,
        public readonly array $flaggedCategories = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            providerName: $providerName,
            code: 451,
            previous: $previous,
        );
    }
}
