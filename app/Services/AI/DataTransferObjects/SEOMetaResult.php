<?php

declare(strict_types=1);

namespace App\Services\AI\DataTransferObjects;

/**
 * Structured output of GenerateSEOMetaAction.
 *
 * Field-length conventions (modern SERP standards, 2025):
 *   meta_title:       <= 60 chars  — primary keyword in first 30 chars
 *   meta_description: 140-160 chars — include a CTA verb
 *   og_title:         <= 88 chars  — social cards have more room
 *   og_description:   <= 200 chars
 *   focus_keyphrase:  2-5 words    — long-tail target
 *   secondary_keywords: 3-7 related phrases for semantic SEO
 *   tags:             5-10 entries — folksonomy + topical clusters
 *   slug:             <= 60 chars  — kebab-case, no stop-words
 *   image_alt:        <= 125 chars — describes the featured image
 *
 * Optional fields are nullable / empty by default so legacy callers
 * (which only inspect meta_title / meta_description / tags / slug) keep
 * working without touching their code.
 */
final readonly class SEOMetaResult
{
    /**
     * @param  list<string>  $tags
     * @param  list<string>  $secondaryKeywords
     */
    public function __construct(
        public string $metaTitle,
        public string $metaDescription,
        public array $tags,
        public string $slug,
        public string $ogTitle = '',
        public string $ogDescription = '',
        public string $focusKeyphrase = '',
        public array $secondaryKeywords = [],
        public string $imageAlt = '',
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $tags = self::stringList($data['tags'] ?? []);
        $secondary = self::stringList($data['secondary_keywords'] ?? $data['related_keywords'] ?? []);

        $metaTitle = self::truncate((string) ($data['meta_title'] ?? ''), 60);
        $metaDesc = self::truncate((string) ($data['meta_description'] ?? ''), 160);

        return new self(
            metaTitle: $metaTitle,
            metaDescription: $metaDesc,
            tags: array_slice($tags, 0, 10),
            slug: self::truncate((string) ($data['slug'] ?? ''), 60),
            ogTitle: self::truncate((string) ($data['og_title'] ?? $metaTitle), 88),
            ogDescription: self::truncate((string) ($data['og_description'] ?? $metaDesc), 200),
            focusKeyphrase: self::truncate((string) ($data['focus_keyphrase'] ?? ''), 80),
            secondaryKeywords: array_slice($secondary, 0, 7),
            imageAlt: self::truncate((string) ($data['image_alt'] ?? ''), 125),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'tags' => $this->tags,
            'slug' => $this->slug,
            'og_title' => $this->ogTitle,
            'og_description' => $this->ogDescription,
            'focus_keyphrase' => $this->focusKeyphrase,
            'secondary_keywords' => $this->secondaryKeywords,
            'image_alt' => $this->imageAlt,
        ];
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(
            array_map(static fn ($t): string => is_string($t) ? trim($t) : '', $value),
            static fn (string $t): bool => $t !== '',
        ));
    }

    private static function truncate(string $value, int $max): string
    {
        $value = trim($value);

        if (mb_strlen($value) <= $max) {
            return $value;
        }

        return mb_substr($value, 0, $max);
    }
}
