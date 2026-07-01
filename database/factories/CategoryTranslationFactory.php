<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CategoryTranslation>
 */
class CategoryTranslationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(asText: true);

        return [
            'category_id' => Category::factory()->withoutTranslations(),
            'language_id' => Language::query()->default()->first()?->id
                ?? Language::factory()->english()->default()->create()->id,
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'description' => fake()->sentence(),
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

    public function forCategory(Category $category): static
    {
        return $this->state(fn (array $a): array => [
            'category_id' => $category->id,
        ]);
    }
}
