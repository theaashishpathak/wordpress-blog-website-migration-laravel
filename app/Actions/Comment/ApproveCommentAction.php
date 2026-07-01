<?php

declare(strict_types=1);

namespace App\Actions\Comment;

use App\Models\Comment;
use App\Models\User;
use App\Notifications\Reader\CommentApprovedNotification;

class ApproveCommentAction
{
    public function handle(Comment $comment, User $moderator): Comment
    {
        $wasPending = $comment->status !== Comment::STATUS_APPROVED;

        $comment->fill([
            'status' => Comment::STATUS_APPROVED,
            'approved_at' => $comment->approved_at ?? now(),
            'moderated_by' => $moderator->id,
        ])->save();

        $fresh = $comment->fresh();

        // Notify the original commenter that their comment is live — only
        // on the actual pending→approved transition, and not when the
        // commenter is the moderator themselves.
        if ($wasPending && $fresh && $fresh->user_id && $fresh->user_id !== $moderator->id) {
            $commenter = $fresh->author;
            if ($commenter) {
                $commenter->notify(new CommentApprovedNotification($fresh));
            }
        }

        return $fresh;
    }
}
