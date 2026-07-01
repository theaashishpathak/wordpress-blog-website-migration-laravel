<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Post;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingHistory>
 */
class ReadingHistoryFactory extends Factory
{
    protected $model = ReadingHistory::class;

    public function definition(): array
    {
        // First-read distributed over the last 60 days, weighted toward
        // the recent end so the "Today / Yesterday" buckets on the
        // reading-history page have plenty to show.
        $first = fake()->dateTimeBetween('-60 days', '-1 hour');

        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
            'first_read_at' => $first,
            // Default: first_read_at == last_read_at and read_count == 1
            // (matches the "single fresh open" the unique-row test relies
            // on). The seeder uses `->reread()` when it wants re-reads.
            'last_read_at' => $first,
            'read_count' => 1,
            'read_duration_seconds' => fake()->numberBetween(20, 600),
            // 65% completion rate matches a realistic reader analytics curve.
            'completed' => fake()->boolean(65),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => ['completed' => true]);
    }

    /**
     * Multi-read state — bumps read_count and pushes last_read_at forward
     * by 0–7 days. Use in seeders to simulate returning readers.
     */
    public function reread(): static
    {
        return $this->state(function (array $attributes): array {
            $first = $attributes['first_read_at'] instanceof \DateTimeInterface
                ? $attributes['first_read_at']
                : new \DateTime((string) $attributes['first_read_at']);

            return [
                'read_count' => fake()->randomElement([2, 2, 3, 3, 4]),
                'last_read_at' => (clone $first)->modify('+'.fake()->numberBetween(60, 60 * 24 * 7).' minutes'),
            ];
        });
    }

    /** Read today — useful for ensuring the "Today" date bucket has rows. */
    public function readToday(): static
    {
        $when = now()->subHours(fake()->numberBetween(0, 12));

        return $this->state(fn (): array => [
            'first_read_at' => $when,
            'last_read_at' => $when,
        ]);
    }
}
