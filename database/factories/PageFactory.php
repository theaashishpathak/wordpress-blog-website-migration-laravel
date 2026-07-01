<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PageStatus;
use App\Models\Language;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => PageStatus::Draft,
            'template' => Page::TEMPLATE_DEFAULT,
            'show_in_menu' => false,
            'sort_order' => 0,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Page $page): void {
            if ($page->translations()->exists()) {
                return;
            }

            $defaultLang = Language::query()->default()->first()
                ?? Language::factory()->english()->default()->create();

            $title = fake()->unique()->sentence(3);
            $page->translations()->create([
                'language_id' => $defaultLang->id,
                'title' => $title,
                'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
                'content' => fake()->paragraphs(3, asText: true),
                'is_published' => false,
            ]);
        });
    }

    public function published(): static
    {
        return $this->state(fn (array $a): array => [
            'status' => PageStatus::Published,
        ])->afterCreating(function (Page $page): void {
            // When the parent page is published, mark every translation
            // it has so far as published too (default factory state).
            $page->translations()->update(['is_published' => true]);
        });
    }

    public function draft(): static
    {
        return $this->state(fn (array $a): array => [
            'status' => PageStatus::Draft,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $a): array => [
            'status' => PageStatus::Archived,
        ]);
    }

    public function inMenu(bool $value = true, int $sortOrder = 0): static
    {
        return $this->state(fn (array $a): array => [
            'show_in_menu' => $value,
            'sort_order' => $sortOrder,
        ]);
    }

    public function template(string $template): static
    {
        return $this->state(fn (array $a): array => [
            'template' => $template,
        ]);
    }

    public function withoutTranslations(): static
    {
        return $this->afterCreating(function (Page $page): void {
            $page->translations()->delete();
        });
    }
}
