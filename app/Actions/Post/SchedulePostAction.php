<?php

declare(strict_types=1);

namespace App\Actions\Post;

use App\Enums\PostStatus;
use App\Models\Post;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Schedule a Post for future publication.
 *
 * Allowed source states: Approved, Draft (with permission elsewhere).
 * Target state: Scheduled.
 *
 * The provided $scheduledAt MUST be in the future. The `posts:publish-scheduled`
 * console command (Phase 2E-3) will sweep this status hourly / per-minute
 * and call PublishPostAction when the time arrives.
 */
class SchedulePostAction
{
    public function handle(Post $post, DateTimeInterface $scheduledAt): Post
    {
        if ($scheduledAt <= now()) {
            throw ValidationException::withMessages([
                'scheduled_at' => 'Scheduled time must be in the future.',
            ]);
        }

        if (! $post->status->canTransitionTo(PostStatus::Scheduled)) {
            throw new InvalidArgumentException(
                "Cannot schedule a post in status [{$post->status->value}]."
            );
        }

        return DB::transaction(function () use ($post, $scheduledAt): Post {
            $post->forceFill([
                'status' => PostStatus::Scheduled->value,
                'scheduled_at' => $scheduledAt,
            ])->save();

            return $post->fresh();
        });
    }
}
