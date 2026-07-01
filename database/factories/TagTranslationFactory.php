<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Language;
use App\Models\Tag;
use App\Models\TagTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TagTranslation>
 */
class TagTranslationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(asText: true);

        return [
            'tag_id' => Tag::factory()->withoutTranslations(),
            'language_id' => Language::query()->default()->first()?->id
                ?? Language::factory()->english()->default()->create()->id,
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => null,
            'meta_title' => null,
            'meta_description' => null,
        ];
    }

    public function forLanguage(Language $language): static
    {
        return $this->state(fn (array $a): array => [
            'language_id' => $language->id,
        ]);
    }

    public function forTag(Tag $tag): static
    {
        return $this->state(fn (array $a): array => [
            'tag_id' => $tag->id,
        ]);
    }
}
