<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EditorialNote;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EditorialNote>
 */
class EditorialNoteFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'author_id' => User::factory(),
            'type' => EditorialNote::TYPE_INTERNAL_COMMENT,
            'body' => fake()->sentence(15),
            'mention_user_ids' => null,
            'is_internal' => true,
        ];
    }

    public function approve(): static
    {
        return $this->state(fn (array $a): array => [
            'type' => EditorialNote::TYPE_APPROVE,
            'is_internal' => false,
        ]);
    }

    public function reject(): static
    {
        return $this->state(fn (array $a): array => [
            'type' => EditorialNote::TYPE_REJECT,
            'is_internal' => false,
        ]);
    }

    public function requestChanges(): static
    {
        return $this->state(fn (array $a): array => [
            'type' => EditorialNote::TYPE_REQUEST_CHANGES,
            'is_internal' => false,
        ]);
    }

    public function internal(): static
    {
        return $this->state(fn (array $a): array => [
            'type' => EditorialNote::TYPE_INTERNAL_COMMENT,
            'is_internal' => true,
        ]);
    }

    public function aiSuggestion(): static
    {
        return $this->state(fn (array $a): array => [
            'type' => EditorialNote::TYPE_AI_SUGGESTION,
            'is_internal' => true,
        ]);
    }

    public function forPost(Post $post): static
    {
        return $this->state(fn (array $a): array => [
            'post_id' => $post->id,
        ]);
    }
}
