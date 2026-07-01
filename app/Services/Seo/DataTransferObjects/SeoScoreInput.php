<?php

declare(strict_types=1);

namespace App\Services\Seo\DataTransferObjects;

/**
 * Input shape for SeoScoreService.
 *
 * Built from the current Livewire form state (not the persisted model)
 * so the score updates live as the user types. Empty strings are
 * tolerated — the scorer reports each missing field as a bad check.
 */
final readonly class SeoScoreInput
{
    public function __construct(
        public string $title,
        public string $slug,
        public string $excerpt,
        public string $content,
        public string $metaTitle,
        public string $metaDescription,
        public string $focusKeyword,
    ) {}
}
