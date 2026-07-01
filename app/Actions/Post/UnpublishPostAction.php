<?php

declare(strict_types=1);

namespace App\Actions\Post;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Take a Published post off the frontend without archiving it.
 *
 * Translations keep their is_published flags so the post can be re-published
 * later with one click. published_at is preserved as the original go-live
 * timestamp (useful for analytics + sitemap).
 */
class UnpublishPostAction
{
    public function handle(Post $post): Post
    {
        if (! $post->status->canTransitionTo(PostStatus::Unpublished)) {
            throw new InvalidArgumentException(
                "Cannot unpublish a post in status [{$post->status->value}]."
            );
        }

        DB::transaction(function () use ($post): void {
            $post->forceFill(['status' => PostStatus::Unpublished->value])->save();
        });

        return $post->fresh();
    }
}
