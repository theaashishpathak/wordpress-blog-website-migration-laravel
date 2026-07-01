<?php

declare(strict_types=1);

namespace App\Actions\Comment;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Notifications\Editorial\NewCommentOnPost;
use App\Notifications\Reader\CommentReplyNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Post a new comment on a published post.
 *
 * Moderation decision matrix:
 *   - Authenticated user      → auto-approve
 *   - Guest with prior approved comment from same email → auto-approve
 *   - Anyone else (first-time guest, suspicious patterns) → status = pending
 *
 * Refuses to comment on a post whose `allow_comments` flag is false
 * or that isn't currently published.
 *
 * Replies are validated to belong to the same post and to not exceed
 * one level of nesting (parent must itself be top-level).
 */
class CreateCommentAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Post $post, ?User $user, array $data): Comment
    {
        $this->assertCommentingAllowed($post);

        $parent = $this->resolveParent($post, $data['parent_id'] ?? null);

        $body = trim((string) ($data['body'] ?? ''));
        if (mb_strlen($body) < 2) {
            throw ValidationException::withMessages(['body' => 'Comment is too short.']);
        }

        if ($user === null) {
            $guestName = trim((string) ($data['guest_name'] ?? ''));
            $guestEmail = mb_strtolower(trim((string) ($data['guest_email'] ?? '')));

            if ($guestName === '' || $guestEmail === '') {
                throw ValidationException::withMessages([
                    'guest_name' => 'Name and email are required for guest comments.',
                ]);
            }
        }

        $comment = DB::transaction(function () use ($post, $user, $parent, $body, $data): Comment {
            $guestEmail = $user === null
                ? mb_strtolower(trim((string) ($data['guest_email'] ?? '')))
                : null;

            $status = $this->decideStatus($user, $guestEmail);

            $payload = [
                'post_id' => $post->id,
                'parent_id' => $parent?->id,
                'user_id' => $user?->id,
                'guest_name' => $user === null ? trim((string) ($data['guest_name'] ?? '')) : null,
                'guest_email' => $guestEmail,
                'guest_website' => $user === null && ! empty($data['guest_website'])
                    ? trim((string) $data['guest_website'])
                    : null,
                'body' => $body,
                'status' => $status,
                'approved_at' => $status === Comment::STATUS_APPROVED ? now() : null,
                'ip_address' => $data['ip'] ?? null,
                'user_agent' => isset($data['user_agent'])
                    ? mb_substr((string) $data['user_agent'], 0, 500)
                    : null,
            ];

            return Comment::query()->create($payload);
        });

        $this->notifyPostAuthor($comment, $post, $user);
        $this->notifyParentCommenter($comment, $parent, $user);

        return $comment;
    }

    /**
     * Notify the parent commenter when somebody replies to them. Skipped
     * when the replier is the same user, or when the parent commenter
     * is a guest with no account.
     */
    private function notifyParentCommenter(Comment $reply, ?Comment $parent, ?User $replier): void
    {
        if ($parent === null || $parent->user_id === null) {
            return;
        }

        if ($replier !== null && $parent->user_id === $replier->id) {
            return;
        }

        $parentAuthor = $parent->author;
        if ($parentAuthor) {
            $parentAuthor->notify(new CommentReplyNotification($reply, $parent));
        }
    }

    /**
     * Notify the post author about the new comment, skipping cases
     * where the commenter is themselves the author (no point pinging
     * yourself).
     */
    private function notifyPostAuthor(Comment $comment, Post $post, ?User $commenter): void
    {
        $author = $post->author;

        if ($author === null) {
            return;
        }

        if ($commenter !== null && $commenter->id === $author->id) {
            return;
        }

        $author->notify(new NewCommentOnPost($comment));
    }

    private function assertCommentingAllowed(Post $post): void
    {
        if ($post->status !== \App\Enums\PostStatus::Published) {
            throw ValidationException::withMessages([
                'post' => 'Comments are only allowed on published posts.',
            ]);
        }

        if (! (bool) $post->allow_comments) {
            throw ValidationException::withMessages([
                'post' => 'Comments are disabled for this post.',
            ]);
        }
    }

    private function resolveParent(Post $post, mixed $parentId): ?Comment
    {
        if ($parentId === null || $parentId === '') {
            return null;
        }

        $parent = Comment::query()->find((int) $parentId);

        if ($parent === null) {
            throw ValidationException::withMessages(['parent_id' => 'Reply target does not exist.']);
        }

        if ($parent->post_id !== $post->id) {
            throw ValidationException::withMessages(['parent_id' => 'Reply target belongs to a different post.']);
        }

        // Cap nesting at 1 level — replies to replies attach to the
        // top-level ancestor so the UI stays readable.
        if ($parent->parent_id !== null) {
            $parent = $parent->parent;
        }

        return $parent;
    }

    private function decideStatus(?User $user, ?string $guestEmail): string
    {
        // Auth'd users skip the queue.
        if ($user !== null) {
            return Comment::STATUS_APPROVED;
        }

        // Returning guests with at least one prior approved comment
        // bypass moderation — replicates the classic WordPress flow.
        if ($guestEmail !== null && $guestEmail !== '') {
            $hasPriorApproved = Comment::query()
                ->where('guest_email', $guestEmail)
                ->where('status', Comment::STATUS_APPROVED)
                ->exists();

            if ($hasPriorApproved) {
                return Comment::STATUS_APPROVED;
            }
        }

        return Comment::STATUS_PENDING;
    }
}
