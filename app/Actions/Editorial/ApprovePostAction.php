<?php

declare(strict_types=1);

namespace App\Actions\Editorial;

use App\Enums\PostStatus;
use App\Models\EditorialNote;
use App\Models\Post;
use App\Models\User;
use App\Notifications\Editorial\PostApproved;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Editor approves a submitted post.
 *
 * Allowed source states: PendingReview, InReview.
 * Target state: Approved.
 *
 * Note is optional. Approval is visible to the author.
 */
class ApprovePostAction
{
    public function handle(Post $post, User $editor, ?string $note = null): Post
    {
        if (! $post->status->canTransitionTo(PostStatus::Approved)) {
            throw new InvalidArgumentException(
                "Cannot approve a post in status [{$post->status->value}]."
            );
        }

        $post = DB::transaction(function () use ($post, $editor, $note): Post {
            $post->forceFill(['status' => PostStatus::Approved->value])->save();

            EditorialNote::query()->create([
                'post_id' => $post->id,
                'author_id' => $editor->id,
                'type' => EditorialNote::TYPE_APPROVE,
                'body' => trim((string) $note) !== '' ? trim((string) $note) : 'Approved.',
                'is_internal' => false,
            ]);

            return $post->fresh(['editorialNotes']);
        });

        $author = $post->author;
        if ($author !== null && $author->id !== $editor->id) {
            $author->notify(new PostApproved($post, $editor, $note));
        }

        return $post;
    }
}
