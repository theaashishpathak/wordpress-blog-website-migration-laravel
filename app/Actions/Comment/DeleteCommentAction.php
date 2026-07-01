<?php

declare(strict_types=1);

namespace App\Actions\Comment;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Soft-delete a comment. Replies attached to the deleted comment are
 * left in place (they fall back to being orphan top-level comments
 * once the parent is gone — handled by the frontend rendering).
 *
 * Pass `$force = true` to permanently delete instead of soft-delete.
 */
class DeleteCommentAction
{
    public function handle(Comment $comment, ?User $moderator = null, bool $force = false): void
    {
        DB::transaction(function () use ($comment, $moderator, $force): void {
            if ($moderator !== null) {
                $comment->moderated_by = $moderator->id;
                $comment->status = Comment::STATUS_TRASH;
                $comment->save();
            }

            $force ? $comment->forceDelete() : $comment->delete();
        });
    }
}
