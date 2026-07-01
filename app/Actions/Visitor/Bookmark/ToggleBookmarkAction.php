<?php

declare(strict_types=1);

namespace App\Actions\Visitor\Bookmark;

use App\Models\Bookmark;
use App\Models\Post;
use App\Models\User;

/**
 * Toggle a bookmark for a (user, post) pair.
 *
 * Returns true if the bookmark now exists (created), false if removed.
 * Idempotent — calling twice with the same args returns to the starting state.
 */
class ToggleBookmarkAction
{
    public function handle(User $user, Post $post): bool
    {
        $existing = Bookmark::query()
            ->where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        Bookmark::query()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
        ]);

        return true;
    }
}
