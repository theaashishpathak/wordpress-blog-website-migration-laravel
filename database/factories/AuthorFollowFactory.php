<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AuthorFollow;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuthorFollow>
 */
class AuthorFollowFactory extends Factory
{
    protected $model = AuthorFollow::class;

    public function definition(): array
    {
        return [
            'follower_id' => User::factory()->visitor(),
            'author_id' => User::factory()->author(),
            'notify_on_publish' => true,
        ];
    }
}
