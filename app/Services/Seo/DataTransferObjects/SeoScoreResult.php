<?php

declare(strict_types=1);

namespace App\Services\Seo\DataTransferObjects;

/**
 * Aggregate SEO score for a post / page / category.
 *
 * Sum of weighted SeoCheckResult scores, clamped to 0–100. The UI uses
 * `overall` for the gauge and `checks` for the per-rule checklist.
 */
final readonly class SeoScoreResult
{
    /**
     * @param  list<SeoCheckResult>  $checks
     */
    public function __construct(
        public int $overall,
        public array $checks,
    ) {}

    public function level(): string
    {
        return match (true) {
            $this->overall >= 80 => 'good',
            $this->overall >= 50 => 'warning',
            default => 'bad',
        };
    }

    /**
     * Group checks by level for the checklist UI.
     *
     * @return array{good: list<SeoCheckResult>, warning: list<SeoCheckResult>, bad: list<SeoCheckResult>}
     */
    public function grouped(): array
    {
        $groups = ['good' => [], 'warning' => [], 'bad' => []];

        foreach ($this->checks as $check) {
            $bucket = $check->level === 'good' ? 'good' : ($check->level === 'warning' ? 'warning' : 'bad');
            $groups[$bucket][] = $check;
        }

        return $groups;
    }
}
