<?php

declare(strict_types=1);

namespace App\Notifications\Editorial;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PostApproved extends Notification
{
    use Queueable;

    public function __construct(public Post $post, public User $editor, public ?string $note = null) {}

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
            'type' => 'post.approved',
            'post_id' => $this->post->id,
            'post_title' => (string) ($this->post->translation()?->title ?? '#'.$this->post->id),
            'editor_id' => $this->editor->id,
            'editor_name' => $this->editor->name,
            'note' => $this->note,
            'icon' => 'check-circle',
            'color' => 'emerald',
            'title' => 'Post approved',
            'message' => "{$this->editor->name} approved \"".(string) ($this->post->translation()?->title ?? '#'.$this->post->id).'".'.($this->note ? ' Note: '.$this->note : ''),
            'url' => route('admin.posts.edit', $this->post),
        ];
    }
}
