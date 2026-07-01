<?php

declare(strict_types=1);

namespace App\Actions\Visitor\Follow;

use App\Models\User;
use App\Models\UserFollow;
use App\Notifications\Reader\NewFollowerNotification;
use Illuminate\Validation\ValidationException;

/**
 * Toggle visitor↔visitor social follows. Separate from author_follows
 * so we can apply different visibility rules. Self-follow blocked.
 */
class ToggleUserFollowAction
{
    public function handle(User $follower, User $followed): bool
    {
        if ($follower->id === $followed->id) {
            throw ValidationException::withMessages([
                'followed' => "You can't follow yourself.",
            ]);
        }

        $existing = UserFollow::query()
            ->where('follower_id', $follower->id)
            ->where('followed_id', $followed->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        UserFollow::query()->create([
            'follower_id' => $follower->id,
            'followed_id' => $followed->id,
        ]);

        // Ping the followed user so they see the new follower in their
        // notifications hub. Database channel only — V6 wires email.
        $followed->notify(new NewFollowerNotification($follower));

        return true;
    }
}
