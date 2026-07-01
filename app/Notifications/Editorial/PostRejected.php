<?php

declare(strict_types=1);

namespace App\Notifications\Editorial;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PostRejected extends Notification
{
    use Queueable;

    public function __construct(public Post $post, public User $editor, public string $reason) {}

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
            'type' => 'post.rejected',
            'post_id' => $this->post->id,
            'post_title' => (string) ($this->post->translation()?->title ?? '#'.$this->post->id),
            'editor_id' => $this->editor->id,
            'editor_name' => $this->editor->name,
            'reason' => $this->reason,
            'icon' => 'x-circle',
            'color' => 'rose',
            'title' => 'Post rejected',
            'message' => "{$this->editor->name} rejected \"".(string) ($this->post->translation()?->title ?? '#'.$this->post->id).'". Reason: '.$this->reason,
            'url' => route('admin.posts.edit', $this->post),
        ];
    }
}
