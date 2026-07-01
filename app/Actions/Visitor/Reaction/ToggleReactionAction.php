<?php

declare(strict_types=1);

namespace App\Actions\Visitor\Reaction;

use App\Models\Post;
use App\Models\PostReaction;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Toggle a Like / Dislike reaction for a (user, post) pair.
 *
 * Semantics:
 *   - No existing reaction → create with the requested type            ('created')
 *   - Same type already exists → delete (toggle off)                   ('removed')
 *   - Opposite type exists → flip to the requested type                ('switched')
 *
 * Returns an array describing the resulting state so the caller can
 * update UI counts/styles without a refetch.
 *
 * @phpstan-return array{action: 'created'|'removed'|'switched', type: ?string}
 */
class ToggleReactionAction
{
    public function handle(User $user, Post $post, string $type): array
    {
        if (! in_array($type, PostReaction::TYPES, true)) {
            throw ValidationException::withMessages([
                'type' => 'Reaction type must be one of: '.implode(', ', PostReaction::TYPES),
            ]);
        }

        $existing = PostReaction::query()
            ->where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->first();

        if ($existing === null) {
            PostReaction::query()->create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'type' => $type,
            ]);

            return ['action' => 'created', 'type' => $type];
        }

        if ($existing->type === $type) {
            $existing->delete();

            return ['action' => 'removed', 'type' => null];
        }

        $existing->update(['type' => $type]);

        return ['action' => 'switched', 'type' => $type];
    }
}
