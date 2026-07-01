<?php

declare(strict_types=1);

namespace App\Notifications\Editorial;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to the post author when someone (else) leaves a comment.
 *
 * Triggered from CreateCommentAction. The dispatch helper there
 * silently skips self-comments to avoid notification noise.
 */
class NewCommentOnPost extends Notification
{
    use Queueable;

    public function __construct(public Comment $comment) {}

    /**
     * @return array<int, string>
     */
    public function via(): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $post = $this->comment->post;
        $title = (string) ($post?->translation()?->title ?? '#'.$this->comment->post_id);

        return [
            'type' => 'comment.new',
            'comment_id' => $this->comment->id,
            'post_id' => $this->comment->post_id,
            'post_title' => $title,
            'commenter_name' => $this->comment->authorName(),
            'status' => $this->comment->status,
            'icon' => 'message-circle',
            'color' => 'sky',
            'title' => 'New comment',
            'message' => "{$this->comment->authorName()} commented on \"{$title}\": ".\Illuminate\Support\Str::limit(strip_tags((string) $this->comment->body), 120),
            'url' => $post ? route('admin.posts.edit', $post) : '#',
        ];
    }
}
