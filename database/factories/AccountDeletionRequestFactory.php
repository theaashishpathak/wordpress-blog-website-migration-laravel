<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AccountDeletionRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccountDeletionRequest>
 */
class AccountDeletionRequestFactory extends Factory
{
    protected $model = AccountDeletionRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reason' => fake()->randomElement(['no_longer_needed', 'privacy_concerns', 'too_many_emails', 'other']),
            'note' => null,
            'scheduled_for' => now()->addDays(30),
            'cancelled_at' => null,
            'processed_at' => null,
        ];
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => ['cancelled_at' => now()]);
    }
}
