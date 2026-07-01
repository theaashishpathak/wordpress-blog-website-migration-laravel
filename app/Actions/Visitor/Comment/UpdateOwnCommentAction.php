<?php

declare(strict_types=1);

namespace App\Actions\Visitor\Comment;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

/**
 * Allow a visitor to edit their own comment body. Editing resets the
 * status back to pending — moderators re-review after edits so the
 * window for sneaking content past approval stays narrow.
 */
class UpdateOwnCommentAction
{
    public function handle(User $user, Comment $comment, string $body): Comment
    {
        if ($comment->user_id !== $user->id) {
            throw new AuthorizationException('You can only edit your own comments.');
        }

        $body = trim($body);

        if ($body === '') {
            throw ValidationException::withMessages([
                'body' => 'Comment cannot be empty.',
            ]);
        }

        if (mb_strlen($body) > 4000) {
            throw ValidationException::withMessages([
                'body' => 'Comment is too long (4000 character limit).',
            ]);
        }

        $comment->update([
            'body' => $body,
            'status' => Comment::STATUS_PENDING,
            'approved_at' => null,
            'moderated_by' => null,
        ]);

        return $comment->fresh() ?? $comment;
    }
}
