<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Support\Facades\Cache;

/**
 * Side-effect plumbing for the Post lifecycle.
 *
 * Responsibilities:
 *   - Backfill published_at when status transitions to Published with no
 *     explicit timestamp set by the caller.
 *   - Stamp updated_by with the authenticated user on every save.
 *   - Invalidate cached "popular posts" / "homepage sections" lists when
 *     status or published_at changes (Phase 3 will register the keys
 *     these caches live under).
 */
class PostObserver
{
    public function saving(Post $post): void
    {
        $this->autoFillPublishedAt($post);
        $this->autoStampUpdatedBy($post);
    }

    public function saved(Post $post): void
    {
        if ($post->wasChanged(['status', 'published_at', 'is_breaking', 'is_featured', 'is_trending'])) {
            $this->invalidateRelatedCaches($post);
        }
    }

    public function deleted(Post $post): void
    {
        $this->invalidateRelatedCaches($post);
    }

    private function autoFillPublishedAt(Post $post): void
    {
        // Only act on transition into Published.
        $isBecomingPublished = $post->isDirty('status')
            && (
                ($post->status instanceof PostStatus && $post->status === PostStatus::Published)
                || $post->status === PostStatus::Published->value
            );

        if (! $isBecomingPublished) {
            return;
        }

        if ($post->published_at === null) {
            $post->published_at = now();
        }
    }

    private function autoStampUpdatedBy(Post $post): void
    {
        if (! $post->isDirty()) {
            return;
        }

        $userId = auth()->id();

        // Skip on console (queues, schedulers, seeders).
        if ($userId === null) {
            return;
        }

        // Don't overwrite if the caller already set it explicitly.
        if ($post->isDirty('updated_by')) {
            return;
        }

        $post->updated_by = $userId;
    }

    /**
     * Forget cache keys that include published posts. Cache key naming is
     * locked in Phase 3 alongside the homepage builder + sitemap features
     * — until then this is a no-op safety net.
     */
    private function invalidateRelatedCaches(Post $post): void
    {
        $keys = [
            'newspilot.posts.popular',
            'newspilot.posts.trending',
            'newspilot.posts.breaking',
            'newspilot.posts.latest',
            "newspilot.posts.by-category.{$post->category_id}",
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }
}
