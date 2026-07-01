<?php

declare(strict_types=1);

namespace App\Actions\Visitor\Follow;

use App\Models\AuthorFollow;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Toggle a visitor → author follow. Authors are User rows where
 * portal_type='author'. Self-follow is blocked. Returns true when the
 * follow exists after the call, false on unfollow.
 */
class ToggleAuthorFollowAction
{
    public function handle(User $follower, User $author): bool
    {
        if ($follower->id === $author->id) {
            throw ValidationException::withMessages([
                'author' => "You can't follow yourself.",
            ]);
        }

        $existing = AuthorFollow::query()
            ->where('follower_id', $follower->id)
            ->where('author_id', $author->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        AuthorFollow::query()->create([
            'follower_id' => $follower->id,
            'author_id' => $author->id,
            'notify_on_publish' => true,
        ]);

        return true;
    }
}
