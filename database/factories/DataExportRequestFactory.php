<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DataExportRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DataExportRequest>
 */
class DataExportRequestFactory extends Factory
{
    protected $model = DataExportRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => DataExportRequest::STATUS_PENDING,
            'file_path' => null,
            'file_size_bytes' => null,
            'completed_at' => null,
            'expires_at' => null,
            'error' => null,
        ];
    }

    public function ready(): static
    {
        return $this->state(fn (): array => [
            'status' => DataExportRequest::STATUS_READY,
            'file_path' => 'exports/'.fake()->uuid().'.zip',
            'file_size_bytes' => fake()->numberBetween(1_000, 1_000_000),
            'completed_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);
    }
}
