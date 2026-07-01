<?php

declare(strict_types=1);

namespace App\Actions\Editorial;

use App\Enums\PostStatus;
use App\Models\EditorialNote;
use App\Models\Post;
use App\Models\User;
use App\Notifications\Editorial\PostSubmittedForReview;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

/**
 * Transition a Post into the review pipeline.
 *
 * Allowed source states: Draft, ChangesRequested, Rejected.
 * Target state: PendingReview.
 *
 * Author may optionally attach a note (e.g., "addressed all editor feedback").
 */
class SubmitForReviewAction
{
    public function handle(Post $post, User $author, ?string $note = null): Post
    {
        if (! $post->status->canTransitionTo(PostStatus::PendingReview)) {
            throw new InvalidArgumentException(
                "Cannot submit a post in status [{$post->status->value}] for review."
            );
        }

        $post = DB::transaction(function () use ($post, $author, $note): Post {
            $post->forceFill(['status' => PostStatus::PendingReview->value])->save();

            if ($note !== null && trim($note) !== '') {
                EditorialNote::query()->create([
                    'post_id' => $post->id,
                    'author_id' => $author->id,
                    'type' => EditorialNote::TYPE_INTERNAL_COMMENT,
                    'body' => trim($note),
                    'is_internal' => false,
                ]);
            }

            return $post->fresh(['editorialNotes']);
        });

        $this->notifyEditors($post, $author);

        return $post;
    }

    /**
     * Notify every user who can act on submitted posts. Falls back to
     * users with role Admin/Editor when the permission isn't seeded.
     */
    private function notifyEditors(Post $post, User $author): void
    {
        $editors = User::query()
            ->where('id', '!=', $author->id)
            ->where(function ($q): void {
                $q->whereHas('permissions', fn ($p) => $p->where('name', 'editorial.approve'))
                    ->orWhereHas('roles.permissions', fn ($p) => $p->where('name', 'editorial.approve'))
                    ->orWhereHas('roles', fn ($r) => $r->whereIn('name', ['Super Admin', 'Admin', 'Editor']));
            })
            ->get();

        if ($editors->isNotEmpty()) {
            Notification::send($editors, new PostSubmittedForReview($post, $author));
        }
    }
}
