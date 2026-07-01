<?php

declare(strict_types=1);

namespace App\Services\Seo\DataTransferObjects;

/**
 * Outcome of a single SEO heuristic (meta length, focus keyword
 * placement, readability, etc.).
 *
 * `level` drives the UI dot color in the SEO panel:
 *   - "good"     → green
 *   - "warning"  → amber
 *   - "bad"      → red
 *
 * `weight` is the score contribution if the check passes (level=good).
 * Warnings contribute 50%, bad contributes 0%. Total weights across all
 * checks should sum to 100 so the overall score is a pure 0-100 scale.
 */
final readonly class SeoCheckResult
{
    public function __construct(
        public string $key,
        public string $label,
        public string $level,
        public string $message,
        public int $weight,
    ) {}

    public function score(): float
    {
        return match ($this->level) {
            'good' => (float) $this->weight,
            'warning' => $this->weight * 0.5,
            default => 0.0,
        };
    }
}
