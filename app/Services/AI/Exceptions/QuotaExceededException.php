<?php

declare(strict_types=1);

namespace App\Services\AI\Exceptions;

/**
 * Application-side quota guard tripped — per-user monthly cost ceiling,
 * platform-wide cost ceiling, or per-role generation quota.
 *
 * Unlike RateLimitException this is NOT a transient provider failure and
 * does NOT trigger fallback. The user must wait for the next billing
 * period or the admin must raise the ceiling.
 */
class QuotaExceededException extends AIProviderException
{
    public function __construct(
        string $message = 'AI quota exceeded.',
        public readonly ?string $quotaType = null,
        public readonly ?float $usedAmount = null,
        public readonly ?float $ceilingAmount = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            code: 402,
            previous: $previous,
        );
    }
}
