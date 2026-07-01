<?php

declare(strict_types=1);

namespace App\Actions\Post;

use App\Models\Post;
use App\Models\PostRevision;

/**
 * Capture a JSON snapshot of a Post + its translations + tag IDs at the
 * current moment, store it as an append-only PostRevision row.
 *
 * Called by UpdatePostAction at the START of its transaction so the
 * pre-edit state is preserved and editors can diff "what changed" during
 * review.
 *
 * Snapshot shape:
 *   {
 *     "post":         {...post attributes excluding timestamps/id...},
 *     "translations": [{...full row per locale...}],
 *     "tag_ids":      [1, 5, 11]
 *   }
 *
 * If the Post has no prior revision, this insert creates revision #1.
 */
class CreatePostRevisionAction
{
    public function handle(Post $post, ?int $authorId = null, ?string $summary = null): PostRevision
    {
        $post->loadMissing(['translations', 'tags']);

        $nextNumber = (int) PostRevision::query()
            ->forPost($post->id)
            ->max('revision_number') + 1;

        return PostRevision::query()->create([
            'post_id' => $post->id,
            'revision_number' => $nextNumber,
            'author_id' => $authorId ?? auth()->id(),
            'snapshot' => $this->buildSnapshot($post),
            'summary' => $summary,
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSnapshot(Post $post): array
    {
        return [
            'post' => collect($post->getAttributes())
                ->except(['id', 'created_at', 'updated_at', 'deleted_at'])
                ->all(),
            'translations' => $post->translations
                ->map(fn ($translation) => collect($translation->getAttributes())
                    ->except(['id', 'created_at', 'updated_at'])
                    ->all())
                ->all(),
            'tag_ids' => $post->tags->pluck('id')->all(),
        ];
    }
}
