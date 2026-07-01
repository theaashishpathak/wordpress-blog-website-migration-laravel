<?php

declare(strict_types=1);

namespace App\Actions\Visitor\Follow;

use App\Models\Category;
use App\Models\Tag;
use App\Models\TopicFollow;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Toggle a Topic follow. Followable is any model that can be subscribed
 * to as a "topic" — currently Tag and Category. Returns true if the user
 * is following the topic after the call, false if they unfollowed.
 */
class ToggleTopicFollowAction
{
    /**
     * @param  Tag|Category  $followable
     */
    public function handle(User $user, Model $followable): bool
    {
        if (! ($followable instanceof Tag) && ! ($followable instanceof Category)) {
            throw ValidationException::withMessages([
                'followable' => 'Only tags and categories can be followed as topics.',
            ]);
        }

        $existing = TopicFollow::query()
            ->where('user_id', $user->id)
            ->where('followable_type', $followable->getMorphClass())
            ->where('followable_id', $followable->id)
            ->first();

        if ($existing) {
            $existing->delete();

            return false;
        }

        TopicFollow::query()->create([
            'user_id' => $user->id,
            'followable_type' => $followable->getMorphClass(),
            'followable_id' => $followable->id,
            'notify_on_post' => true,
        ]);

        return true;
    }
}
