<?php

declare(strict_types=1);

namespace App\Notifications\Editorial;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ChangesRequestedOnPost extends Notification
{
    use Queueable;

    public function __construct(public Post $post, public User $editor, public string $feedback) {}

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
            'type' => 'post.changes_requested',
            'post_id' => $this->post->id,
            'post_title' => (string) ($this->post->translation()?->title ?? '#'.$this->post->id),
            'editor_id' => $this->editor->id,
            'editor_name' => $this->editor->name,
            'feedback' => $this->feedback,
            'icon' => 'message-square',
            'color' => 'amber',
            'title' => 'Changes requested',
            'message' => "{$this->editor->name} requested changes on \"".(string) ($this->post->translation()?->title ?? '#'.$this->post->id).'": '.$this->feedback,
            'url' => route('admin.posts.edit', $this->post),
        ];
    }
}
