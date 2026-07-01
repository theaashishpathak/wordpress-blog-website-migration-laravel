<?php

declare(strict_types=1);

namespace App\Actions\Visitor\ReadingList;

use App\Models\Post;
use App\Models\ReadingListItem;
use App\Models\User;

/**
 * Toggle a Reading List ("read later") entry.
 *
 * On first call: creates an active row.
 * If already active: dismisses it (soft remove — keeps the row so history
 *   of what was queued is preserved for analytics).
 * If already dismissed: re-activates by clearing dismissed_at.
 *
 * Returns true when the item is in the active queue after the call.
 */
class ToggleReadingListAction
{
    public function handle(User $user, Post $post): bool
    {
        $existing = ReadingListItem::query()
            ->where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->first();

        if ($existing === null) {
            ReadingListItem::query()->create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'added_at' => now(),
                'dismissed_at' => null,
            ]);

            return true;
        }

        if ($existing->dismissed_at === null) {
            // Currently active → dismiss it
            $existing->update(['dismissed_at' => now()]);

            return false;
        }

        // Previously dismissed → re-activate
        $existing->update([
            'dismissed_at' => null,
            'added_at' => now(),
        ]);

        return true;
    }
}
