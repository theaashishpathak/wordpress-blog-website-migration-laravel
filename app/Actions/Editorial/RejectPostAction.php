<?php

declare(strict_types=1);

namespace App\Actions\Editorial;

use App\Enums\PostStatus;
use App\Models\EditorialNote;
use App\Models\Post;
use App\Models\User;
use App\Notifications\Editorial\PostRejected;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Editor rejects a submitted post.
 *
 * Allowed source states: PendingReview, InReview.
 * Target state: Rejected.
 *
 * Note (rejection reason) is REQUIRED — never reject without explanation,
 * the author needs to know why.
 */
class RejectPostAction
{
    public function handle(Post $post, User $editor, string $reason): Post
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A rejection reason is required.',
            ]);
        }

        if (! $post->status->canTransitionTo(PostStatus::Rejected)) {
            throw new InvalidArgumentException(
                "Cannot reject a post in status [{$post->status->value}]."
            );
        }

        $post = DB::transaction(function () use ($post, $editor, $reason): Post {
            $post->forceFill(['status' => PostStatus::Rejected->value])->save();

            EditorialNote::query()->create([
                'post_id' => $post->id,
                'author_id' => $editor->id,
                'type' => EditorialNote::TYPE_REJECT,
                'body' => $reason,
                'is_internal' => false,
            ]);

            return $post->fresh(['editorialNotes']);
        });

        $author = $post->author;
        if ($author !== null && $author->id !== $editor->id) {
            $author->notify(new PostRejected($post, $editor, $reason));
        }

        return $post;
    }
}
