<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use App\Models\UserFollow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserFollow>
 */
class UserFollowFactory extends Factory
{
    protected $model = UserFollow::class;

    public function definition(): array
    {
        return [
            'follower_id' => User::factory()->visitor(),
            'followed_id' => User::factory()->visitor(),
        ];
    }
}
