<?php

declare(strict_types=1);

namespace App\Actions\Visitor\Comment;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Soft-delete the visitor's own comment. Admins can still restore it via
 * the moderation queue if needed — the comments table uses SoftDeletes.
 */
class DeleteOwnCommentAction
{
    public function handle(User $user, Comment $comment): bool
    {
        if ($comment->user_id !== $user->id) {
            throw new AuthorizationException('You can only delete your own comments.');
        }

        return (bool) $comment->delete();
    }
}
