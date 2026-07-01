<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostRevision;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostRevision>
 */
class PostRevisionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'revision_number' => 1,
            'author_id' => User::factory(),
            'snapshot' => [
                'post' => ['status' => 'draft', 'type' => 'post'],
                'translations' => [],
                'tag_ids' => [],
            ],
            'summary' => fake()->sentence(),
            'created_at' => now(),
        ];
    }

    public function forPost(Post $post, int $revisionNumber = 1): static
    {
        return $this->state(fn (array $a): array => [
            'post_id' => $post->id,
            'revision_number' => $revisionNumber,
        ]);
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function withSnapshot(array $snapshot): static
    {
        return $this->state(fn (array $a): array => [
            'snapshot' => $snapshot,
        ]);
    }
}
