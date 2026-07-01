<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Tag;
use App\Models\TopicFollow;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TopicFollow>
 */
class TopicFollowFactory extends Factory
{
    protected $model = TopicFollow::class;

    public function definition(): array
    {
        $tag = Tag::factory()->create();

        return [
            'user_id' => User::factory(),
            'followable_type' => $tag->getMorphClass(),
            'followable_id' => $tag->id,
            'notify_on_post' => true,
        ];
    }
}
