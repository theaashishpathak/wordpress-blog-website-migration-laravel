<?php

declare(strict_types=1);

namespace App\Services\AI\Exceptions;

use RuntimeException;

/**
 * Base exception for the AI subsystem.
 *
 * AIManager catches this type (and subclasses) to attempt fallback to
 * the next provider in the configured chain. Other RuntimeExceptions
 * are NOT swallowed.
 */
class AIProviderException extends RuntimeException
{
    public function __construct(
        string $message = '',
        public readonly ?string $providerName = null,
        public readonly ?string $model = null,
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
