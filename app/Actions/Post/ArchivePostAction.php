<?php

declare(strict_types=1);

namespace App\Actions\Post;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Move a Post to the terminal Archived state.
 *
 * Allowed from any non-Archived status. The post stays in the DB and
 * remains visible in the admin "Archived" filter, but is excluded from
 * every frontend listing and the sitemap.
 *
 * Use DeletePostAction (soft-delete) for content that should disappear
 * from admin lists too.
 */
class ArchivePostAction
{
    public function handle(Post $post): Post
    {
        if ($post->status === PostStatus::Archived) {
            return $post;   // idempotent
        }

        if (! $post->status->canTransitionTo(PostStatus::Archived)) {
            throw new InvalidArgumentException(
                "Cannot archive a post in status [{$post->status->value}]."
            );
        }

        DB::transaction(function () use ($post): void {
            $post->forceFill(['status' => PostStatus::Archived->value])->save();
        });

        return $post->fresh();
    }
}
