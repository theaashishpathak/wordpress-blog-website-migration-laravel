<?php

declare(strict_types=1);

namespace App\Services\AI\DataTransferObjects;

/**
 * Token-level accounting for one AI call.
 *
 * Recorded into the ai_usage_logs table by AIUsageTracker so admins can
 * monitor cost and enforce per-user / platform-wide ceilings.
 */
final readonly class TokenUsage
{
    public function __construct(
        public int $promptTokens,
        public int $completionTokens,
        public int $totalTokens,
        public float $estimatedCostUsd,
    ) {}

    public static function zero(): self
    {
        return new self(0, 0, 0, 0.0);
    }
}
