<?php

declare(strict_types=1);

namespace App\Actions\Visitor\ReadingHistory;

use App\Models\Post;
use App\Models\ReadingHistory;
use App\Models\User;

/**
 * Record (or update) a reading history entry for a (user, post) pair.
 *
 * Idempotent within a day — calling repeatedly bumps last_read_at +
 * read_count but doesn't insert duplicates. read_duration_seconds and
 * completed are optional; pass them when you have them (e.g., from a
 * scroll-tracking JS event sent on article unload).
 */
class RecordReadAction
{
    public function handle(
        User $user,
        Post $post,
        ?int $durationSeconds = null,
        ?bool $completed = null,
    ): ReadingHistory {
        $history = ReadingHistory::query()
            ->where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->first();

        if ($history === null) {
            return ReadingHistory::query()->create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'first_read_at' => now(),
                'last_read_at' => now(),
                'read_count' => 1,
                'read_duration_seconds' => $durationSeconds,
                'completed' => (bool) $completed,
            ]);
        }

        $updates = ['last_read_at' => now()];

        // Bump count only when the previous read was on a different calendar
        // day (debounces refresh-spamming).
        if (! $history->last_read_at?->isSameDay(now())) {
            $updates['read_count'] = $history->read_count + 1;
        }

        if ($durationSeconds !== null) {
            $updates['read_duration_seconds'] = ($history->read_duration_seconds ?? 0) + $durationSeconds;
        }

        if ($completed === true) {
            $updates['completed'] = true;
        }

        $history->update($updates);

        return $history->fresh() ?? $history;
    }
}
