<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Language;
use App\Models\Post;
use App\Models\SeoMeta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeoMeta>
 */
class SeoMetaFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $post = Post::factory()->withoutTranslations()->create();

        return [
            'seoable_type' => Post::class,
            'seoable_id' => $post->id,
            'language_id' => Language::query()->default()->first()?->id
                ?? Language::factory()->english()->default()->create()->id,
            'meta_title' => fake()->sentence(8),
            'meta_description' => fake()->sentence(20),
            'meta_keywords' => implode(', ', fake()->words(5)),
            'focus_keyword' => fake()->word(),
            'canonical_url' => null,
            'robots' => 'index,follow',
            'og_title' => null,
            'og_description' => null,
            'og_image' => null,
            'twitter_title' => null,
            'twitter_description' => null,
            'twitter_image' => null,
            'schema_type' => SeoMeta::SCHEMA_ARTICLE,
            'schema_data' => null,
            'seo_score' => fake()->numberBetween(60, 100),
        ];
    }

    public function forSeoable(\Illuminate\Database\Eloquent\Model $seoable): static
    {
        return $this->state(fn (array $a): array => [
            'seoable_type' => $seoable::class,
            'seoable_id' => $seoable->id,
        ]);
    }

    public function newsArticle(): static
    {
        return $this->state(fn (array $a): array => [
            'schema_type' => SeoMeta::SCHEMA_NEWS_ARTICLE,
        ]);
    }

    public function faqPage(array $faqs = []): static
    {
        return $this->state(fn (array $a): array => [
            'schema_type' => SeoMeta::SCHEMA_FAQ_PAGE,
            'schema_data' => $faqs !== [] ? ['mainEntity' => $faqs] : null,
        ]);
    }

    public function noindex(): static
    {
        return $this->state(fn (array $a): array => [
            'robots' => 'noindex,nofollow',
        ]);
    }
}
