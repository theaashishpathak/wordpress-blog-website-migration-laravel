<?php

declare(strict_types=1);

namespace App\Actions\Editorial;

use App\Enums\PostStatus;
use App\Models\EditorialNote;
use App\Models\Post;
use App\Models\User;
use App\Notifications\Editorial\ChangesRequestedOnPost;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Editor sends the post back to the author with specific change requests.
 *
 * Allowed source states: PendingReview, InReview, Approved (e.g., editor
 * spotted an issue after approving but before publishing).
 * Target state: ChangesRequested.
 *
 * Note (what to change) is REQUIRED — author needs actionable feedback.
 */
class RequestChangesAction
{
    public function handle(Post $post, User $editor, string $feedback): Post
    {
        $feedback = trim($feedback);

        if ($feedback === '') {
            throw ValidationException::withMessages([
                'feedback' => 'Change-request feedback is required.',
            ]);
        }

        if (! $post->status->canTransitionTo(PostStatus::ChangesRequested)) {
            throw new InvalidArgumentException(
                "Cannot request changes on a post in status [{$post->status->value}]."
            );
        }

        $post = DB::transaction(function () use ($post, $editor, $feedback): Post {
            $post->forceFill(['status' => PostStatus::ChangesRequested->value])->save();

            EditorialNote::query()->create([
                'post_id' => $post->id,
                'author_id' => $editor->id,
                'type' => EditorialNote::TYPE_REQUEST_CHANGES,
                'body' => $feedback,
                'is_internal' => false,
            ]);

            return $post->fresh(['editorialNotes']);
        });

        $author = $post->author;
        if ($author !== null && $author->id !== $editor->id) {
            $author->notify(new ChangesRequestedOnPost($post, $editor, $feedback));
        }

        return $post;
    }
}
