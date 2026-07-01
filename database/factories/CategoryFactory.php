<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'image_id' => null,
            // Valid Lucide icon names — `football` was on this list but
            // does not ship with lucide@latest, leaving demo categories
            // with an empty chip. Replaced with a wider set of icons that
            // all exist in the CDN bundle the layout pulls.
            'icon' => fake()->randomElement([
                'newspaper', 'globe', 'flame', 'briefcase', 'cpu',
                'trophy', 'palette', 'landmark', 'rocket', 'music',
                'film', 'book-open', 'graduation-cap', 'plane', 'utensils',
            ]),
            'color' => fake()->hexColor(),
            'show_in_menu' => true,
            'show_on_homepage' => false,
            'is_featured' => false,
            'sort_order' => 0,
            'layout' => Category::LAYOUT_GRID,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Category $category): void {
            // Always seed at least the default-language translation so that
            // ->translate('name') never returns null in tests.
            if ($category->translations()->exists()) {
                return;
            }

            $defaultLang = Language::query()->default()->first()
                ?? Language::factory()->english()->default()->create();

            $name = fake()->unique()->words(asText: true);

            $category->translations()->create([
                'language_id' => $defaultLang->id,
                'name' => $name,
                'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
                'description' => fake()->sentence(),
            ]);
        });
    }

    public function root(): static
    {
        return $this->state(fn (array $a): array => [
            'parent_id' => null,
        ]);
    }

    public function child(int $parentId): static
    {
        return $this->state(fn (array $a): array => [
            'parent_id' => $parentId,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $a): array => [
            'is_featured' => true,
            'show_on_homepage' => true,
        ]);
    }

    public function inMenu(bool $value = true): static
    {
        return $this->state(fn (array $a): array => [
            'show_in_menu' => $value,
        ]);
    }

    public function onHomepage(bool $value = true): static
    {
        return $this->state(fn (array $a): array => [
            'show_on_homepage' => $value,
        ]);
    }

    public function layout(string $layout): static
    {
        return $this->state(fn (array $a): array => [
            'layout' => $layout,
        ]);
    }

    /**
     * Skip the automatic default-language translation (use when the
     * test wants to assert "no translations exist" or supplies its own).
     *
     * The configure() callback always seeds a default translation; this
     * state queues an additional after-create hook that deletes it, so
     * the resulting category has no translation rows. Tests that supply
     * their own translation rows then no longer collide on the
     * (category_id, language_id) UNIQUE constraint.
     */
    public function withoutTranslations(): static
    {
        return $this->afterCreating(function (Category $category): void {
            $category->translations()->delete();
        });
    }
}
