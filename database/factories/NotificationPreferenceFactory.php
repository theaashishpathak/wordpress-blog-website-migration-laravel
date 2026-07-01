<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationPreference>
 */
class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'key' => fake()->randomElement([
                'comment_reply', 'comment_approved', 'author_published',
                'weekly_digest', 'newsletter_daily', 'follower_new',
            ]),
            'channel' => fake()->randomElement(NotificationPreference::CHANNELS),
            'enabled' => true,
        ];
    }
}
