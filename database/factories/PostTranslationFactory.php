<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Language;
use App\Models\Post;
use App\Models\PostTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PostTranslation>
 */
class PostTranslationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(6);
        $minutes = fake()->numberBetween(3, 12);

        return [
            'post_id' => Post::factory()->withoutTranslations(),
            'language_id' => Language::query()->default()->first()?->id
                ?? Language::factory()->english()->default()->create()->id,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('####'),
            'excerpt' => fake()->sentence(20),
            'content' => fake()->paragraphs(5, asText: true),
            'reading_time' => "{$minutes} min read",
            'meta_title' => null,
            'meta_description' => null,
            'focus_keyword' => null,
            'canonical_url' => null,
            'og_image' => null,
            'seo_score' => null,
            'translation_status' => PostTranslation::TRANSLATION_STATUS_MANUAL,
            'is_published' => false,
            'translated_at' => null,
            'translated_by' => null,
            'ai_translation_provider' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $a): array => [
            'is_published' => true,
            'translation_status' => PostTranslation::TRANSLATION_STATUS_PUBLISHED,
        ]);
    }

    public function aiGenerated(string $provider = 'openai'): static
    {
        return $this->state(fn (array $a): array => [
            'translation_status' => PostTranslation::TRANSLATION_STATUS_AI_GENERATED,
            'ai_translation_provider' => $provider,
            'translated_at' => now(),
        ]);
    }

    public function forLanguage(Language $language): static
    {
        return $this->state(fn (array $a): array => [
            'language_id' => $language->id,
        ]);
    }

    public function forPost(Post $post): static
    {
        return $this->state(fn (array $a): array => [
            'post_id' => $post->id,
        ]);
    }
}
