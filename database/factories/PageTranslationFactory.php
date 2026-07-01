<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PageTranslation>
 */
class PageTranslationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'page_id' => Page::factory()->withoutTranslations(),
            'language_id' => Language::query()->default()->first()?->id
                ?? Language::factory()->english()->default()->create()->id,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
            'content' => fake()->paragraphs(3, asText: true),
            'meta_title' => null,
            'meta_description' => null,
            'og_image' => null,
            'is_published' => false,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $a): array => [
            'is_published' => true,
        ]);
    }

    public function forLanguage(Language $language): static
    {
        return $this->state(fn (array $a): array => [
            'language_id' => $language->id,
        ]);
    }

    public function forPage(Page $page): static
    {
        return $this->state(fn (array $a): array => [
            'page_id' => $page->id,
        ]);
    }
}
