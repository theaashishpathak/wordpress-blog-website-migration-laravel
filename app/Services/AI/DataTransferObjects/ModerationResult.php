<?php

declare(strict_types=1);

namespace App\Services\AI\DataTransferObjects;

/**
 * Outcome of a moderation pass over generated content.
 */
final readonly class ModerationResult
{
    /**
     * @param  array<string, float>  $scores  category → confidence score (0..1)
     * @param  list<string>  $flaggedCategories  categories that exceeded provider thresholds
     */
    public function __construct(
        public bool $flagged,
        public array $scores,
        public array $flaggedCategories,
        public string $providerName,
    ) {}
}
