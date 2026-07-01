<?php

declare(strict_types=1);

namespace App\Actions\Visitor\Recommendation;

use App\Models\Post;
use App\Models\PostReaction;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Build a personalised "For You" feed for a visitor.
 *
 * Heuristic scoring (no external ranking service yet):
 *   - +3 per matching category (taken from reading history + likes)
 *   - +1 per matching tag (likes only — tags from history would be too noisy)
 *   - +1 for each week newer than 8 weeks ago (recency boost)
 *
 * Excludes:
 *   - Posts the user has already read
 *   - Posts the user has disliked
 *
 * When the visitor has zero signal (brand-new account), falls back to
 * the freshest published posts so the feed is never empty.
 */
class BuildRecommendationsAction
{
    public function handle(User $user, int $limit = 20): Collection
    {
        // Categories with weight 3 — from reading history + likes
        $readPostIds = ReadingHistory::query()
            ->where('user_id', $user->id)
            ->pluck('post_id');

        $likedPostIds = PostReaction::query()
            ->where('user_id', $user->id)
            ->where('type', PostReaction::TYPE_LIKE)
            ->pluck('post_id');

        $dislikedPostIds = PostReaction::query()
            ->where('user_id', $user->id)
            ->where('type', PostReaction::TYPE_DISLIKE)
            ->pluck('post_id');

        $excludeIds = $readPostIds->merge($dislikedPostIds)->unique()->all();

        $signalCategoryIds = Post::query()
            ->whereIn('id', $readPostIds->merge($likedPostIds)->unique()->all())
            ->whereNotNull('category_id')
            ->pluck('category_id')
            ->unique()
            ->all();

        // Tag signal from liked posts only
        $signalTagIds = \DB::table('post_tag')
            ->whereIn('post_id', $likedPostIds->all())
            ->pluck('tag_id')
            ->unique()
            ->all();

        // No signal yet → return latest published
        if (empty($signalCategoryIds) && empty($signalTagIds)) {
            /** @var Collection<int, Post> $latest */
            $latest = Post::query()
                ->with(['translations', 'featuredImage:id,disk,path,mime_type,alt_text', 'author:id,name', 'category.translations'])
                ->where('status', \App\Enums\PostStatus::Published->value)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->whereNotIn('id', $excludeIds)
                ->orderByDesc('published_at')
                ->limit($limit)
                ->get();

            return $latest;
        }

        // Pull candidate pool — wider than $limit so we can re-rank
        /** @var Collection<int, Post> $candidates */
        $candidates = Post::query()
            ->with(['translations', 'featuredImage:id,disk,path,mime_type,alt_text', 'author:id,name', 'category.translations', 'tags:id'])
            ->where('status', \App\Enums\PostStatus::Published->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereNotIn('id', $excludeIds)
            ->where(function ($q) use ($signalCategoryIds, $signalTagIds): void {
                if (! empty($signalCategoryIds)) {
                    $q->whereIn('category_id', $signalCategoryIds);
                }
                if (! empty($signalTagIds)) {
                    $q->orWhereHas('tags', fn ($t) => $t->whereIn('tags.id', $signalTagIds));
                }
            })
            ->orderByDesc('published_at')
            ->limit($limit * 3)
            ->get();

        // Re-rank candidates
        $now = now();
        $scored = $candidates->map(function (Post $post) use ($signalCategoryIds, $signalTagIds, $now): Post {
            $score = 0;

            if ($post->category_id && in_array($post->category_id, $signalCategoryIds, true)) {
                $score += 3;
            }

            foreach ($post->tags as $tag) {
                if (in_array($tag->id, $signalTagIds, true)) {
                    $score += 1;
                }
            }

            if ($post->published_at) {
                $weeksOld = $post->published_at->diffInWeeks($now);
                if ($weeksOld < 8) {
                    $score += max(0, 8 - $weeksOld);
                }
            }

            $post->setAttribute('recommendation_score', $score);

            return $post;
        });

        return $scored
            ->sortByDesc('recommendation_score')
            ->take($limit)
            ->values();
    }
}
