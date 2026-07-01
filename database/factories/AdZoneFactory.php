<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AdZone;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AdZone>
 */
class AdZoneFactory extends Factory
{
    protected $model = AdZone::class;

    public function definition(): array
    {
        $position = fake()->randomElement(AdZone::POSITIONS);
        $name = ucwords(str_replace('_', ' ', $position)).' '.fake()->word();

        return [
            'key' => Str::slug($name.'-'.fake()->unique()->randomNumber(4), '_'),
            'name' => $name,
            'description' => fake()->optional()->sentence(),
            'width' => fake()->randomElement([300, 320, 728, 970, null]),
            'height' => fake()->randomElement([100, 250, 90, 600, null]),
            'position' => $position,
            'is_active' => true,
            'max_creatives' => fake()->randomElement([1, 1, 1, 3]),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $a): array => ['is_active' => false]);
    }
}
