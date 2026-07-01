<?php

namespace Database\Factories;

use App\Models\ProfileActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfileActivityLog>
 */
class ProfileActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event' => 'profile_information_updated',
            'description' => 'Updated profile information.',
            'meta' => [
                'changed_fields' => ['name', 'phone'],
            ],
        ];
    }
}
