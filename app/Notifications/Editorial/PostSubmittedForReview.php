<?php

declare(strict_types=1);

namespace App\Notifications\Editorial;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to editors/admins when an author submits a post for review.
 *
 * Database channel only — UI badge picks it up via the existing
 * NotificationsIndex Livewire + Notifications icon in the navbar.
 */
class PostSubmittedForReview extends Notification
{
    use Queueable;

    public function __construct(public Post $post, public User $author) {}

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
        return [
            'type' => 'post.submitted',
            'post_id' => $this->post->id,
            'post_title' => (string) ($this->post->translation()?->title ?? '#'.$this->post->id),
            'author_id' => $this->author->id,
            'author_name' => $this->author->name,
            'icon' => 'send',
            'color' => 'indigo',
            'title' => 'Post submitted for review',
            'message' => "{$this->author->name} submitted \"".(string) ($this->post->translation()?->title ?? '#'.$this->post->id).'" for review.',
            'url' => route('admin.posts.edit', $this->post),
        ];
    }
}
