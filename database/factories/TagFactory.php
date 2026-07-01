<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Language;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * Legacy `tags.name` / `tags.slug` columns kept populated so the
     * existing TagFormModal UI keeps working. TagObserver then mirrors
     * the values into a default-language tag_translations row — but in
     * tests we'd rather not depend on the observer firing, so we also
     * create the translation explicitly via afterCreating().
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(asText: true);

        // tags table has FK constraints on created_by / updated_by. In
        // tests, no user is guaranteed to exist yet — auto-create one
        // (or reuse the first available user) so the factory is
        // self-sufficient under RefreshDatabase.
        $userId = $this->resolveDefaultUserId();

        return [
            'code' => fake()->unique()->numerify('####'),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'color' => fake()->randomElement(['red', 'blue', 'green', 'yellow', 'purple', 'pink', 'orange']),
            'type' => Tag::TYPE_GENERAL,
            'status' => Tag::STATUS_PUBLISHED,
            'created_by' => $userId,
            'updated_by' => $userId,
        ];
    }

    /**
     * Reuse an existing user if one exists, otherwise create a system user.
     * Keeps factory call count low during large test runs.
     */
    private function resolveDefaultUserId(): int
    {
        $existingId = User::query()->value('id');

        if ($existingId !== null) {
            return (int) $existingId;
        }

        return (int) User::factory()->create()->id;
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Tag $tag): void {
            // The TagObserver may have already created this translation
            // — guard with exists() so we don't trip the unique constraint.
            if ($tag->translations()->exists()) {
                return;
            }

            $defaultLang = Language::query()->default()->first()
                ?? Language::factory()->english()->default()->create();

            $tag->translations()->create([
                'language_id' => $defaultLang->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ]);
        });
    }

    /**
     * Indicate that the tag is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Tag::STATUS_PUBLISHED,
        ]);
    }

    /**
     * Indicate that the tag is unpublished.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Tag::STATUS_UNPUBLISHED,
        ]);
    }

    /**
     * Skip the automatic default-language translation (negative tests).
     */
    public function withoutTranslations(): static
    {
        return $this->afterCreating(function (Tag $tag): void {
            $tag->translations()->delete();
        });
    }
}
