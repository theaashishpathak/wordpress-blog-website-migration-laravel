<?php

declare(strict_types=1);

namespace App\Notifications\Editorial;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to the author when their post goes live.
 *
 * Triggered from PublishPostAction (and also from the scheduled
 * `posts:publish-scheduled` command when a scheduled post auto-flips
 * to published). The author skips their own notification when they
 * are also the publisher.
 */
class PostPublishedNotification extends Notification
{
    use Queueable;

    public function __construct(public Post $post, public ?User $publisher = null) {}

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
        $title = (string) ($this->post->translation()?->title ?? '#'.$this->post->id);
        $publishedAt = $this->post->published_at?->diffForHumans() ?? 'just now';
        $by = $this->publisher?->name ?? 'The system';

        return [
            'type' => 'post.published',
            'post_id' => $this->post->id,
            'post_title' => $title,
            'publisher_id' => $this->publisher?->id,
            'publisher_name' => $this->publisher?->name,
            'published_at' => $this->post->published_at?->toIso8601String(),
            'icon' => 'sparkles',
            'color' => 'emerald',
            'title' => 'Post published',
            'message' => "\"{$title}\" went live {$publishedAt} — published by {$by}.",
            'url' => route('admin.posts.edit', $this->post),
        ];
    }
}
