<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostReaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostReaction>
 */
class PostReactionFactory extends Factory
{
    protected $model = PostReaction::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
            'type' => fake()->randomElement(PostReaction::TYPES),
        ];
    }

    public function like(): static
    {
        return $this->state(fn (): array => ['type' => PostReaction::TYPE_LIKE]);
    }

    public function dislike(): static
    {
        return $this->state(fn (): array => ['type' => PostReaction::TYPE_DISLIKE]);
    }
}
