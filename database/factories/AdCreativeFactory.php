<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AdCreative;
use App\Models\AdZone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdCreative>
 */
class AdCreativeFactory extends Factory
{
    protected $model = AdCreative::class;

    public function definition(): array
    {
        return [
            'zone_id' => AdZone::factory(),
            'name' => fake()->company().' — '.fake()->bs(),
            'type' => AdCreative::TYPE_IMAGE,
            'media_id' => null,
            'target_url' => fake()->url(),
            'alt_text' => fake()->optional()->sentence(),
            'html_code' => null,
            'status' => AdCreative::STATUS_DRAFT,
            'start_at' => null,
            'end_at' => null,
            'priority' => fake()->numberBetween(50, 200),
            'impression_count' => 0,
            'click_count' => 0,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $a): array => [
            'status' => AdCreative::STATUS_ACTIVE,
            'start_at' => now()->subDay(),
            'end_at' => now()->addMonth(),
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn (array $a): array => ['status' => AdCreative::STATUS_PAUSED]);
    }

    public function html(string $code = '<div>Sample Ad HTML</div>'): static
    {
        return $this->state(fn (array $a): array => [
            'type' => AdCreative::TYPE_HTML,
            'html_code' => $code,
            'media_id' => null,
            'target_url' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $a): array => [
            'status' => AdCreative::STATUS_EXPIRED,
            'end_at' => now()->subDay(),
        ]);
    }
}
