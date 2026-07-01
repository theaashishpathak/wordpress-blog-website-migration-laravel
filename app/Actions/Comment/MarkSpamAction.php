<?php

declare(strict_types=1);

namespace App\Actions\Comment;

use App\Models\Comment;
use App\Models\User;

/**
 * Flag a comment as spam — leaves the row in place so future spam
 * filters can train against it.
 */
class MarkSpamAction
{
    public function handle(Comment $comment, User $moderator): Comment
    {
        $comment->fill([
            'status' => Comment::STATUS_SPAM,
            'approved_at' => null,
            'moderated_by' => $moderator->id,
        ])->save();

        return $comment->fresh();
    }
}
