<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Bookmark;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bookmark>
 */
class BookmarkFactory extends Factory
{
    protected $model = Bookmark::class;

    public function definition(): array
    {
        // Distribute saves across the last 30 days so the bookmarks
        // page doesn't show 20 items all created in the same minute.
        $createdAt = now()->subMinutes(fake()->numberBetween(5, 60 * 24 * 30));

        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }

    /** Save within the past 48 hours (powers the "Saved recently" copy). */
    public function recent(): static
    {
        $when = now()->subHours(fake()->numberBetween(1, 48));

        return $this->state(fn (array $a): array => [
            'created_at' => $when,
            'updated_at' => $when,
        ]);
    }
}
