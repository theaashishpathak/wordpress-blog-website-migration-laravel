<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ImportSource;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportSource>
 */
class ImportSourceFactory extends Factory
{
    protected $model = ImportSource::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company().' RSS',
            'feed_url' => fake()->url().'/feed.xml',
            'category_id' => null,
            'default_language_id' => Language::query()->default()->value('id')
                ?? Language::factory()->english()->default()->create()->id,
            'status' => ImportSource::STATUS_ACTIVE,
            'auto_publish' => false,
            'default_post_type' => 'news',
            'fetch_interval_minutes' => 60,
            'last_fetched_at' => null,
            'item_count' => 0,
        ];
    }

    public function paused(): static
    {
        return $this->state(fn (array $a): array => ['status' => ImportSource::STATUS_PAUSED]);
    }

    public function error(string $message = 'Feed unreachable'): static
    {
        return $this->state(fn (array $a): array => [
            'status' => ImportSource::STATUS_ERROR,
            'last_error' => $message,
        ]);
    }

    public function fetchedRecently(): static
    {
        return $this->state(fn (array $a): array => [
            'last_fetched_at' => now()->subMinutes(5),
        ]);
    }

    public function autoPublish(): static
    {
        return $this->state(fn (array $a): array => ['auto_publish' => true]);
    }
}
