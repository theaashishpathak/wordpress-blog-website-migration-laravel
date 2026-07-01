<?php

declare(strict_types=1);

namespace App\Notifications\Reader;

use App\Models\Comment;
use App\Models\NotificationPreference;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the author of a comment when someone replies to them.
 * Database channel only for now — V6 wires the email channel through
 * NotificationPreference matrix lookup.
 */
class CommentReplyNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Comment $reply,
        public Comment $parent,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = NotificationPreference::resolveChannels($notifiable->id, 'comment_reply');

        // Database is always retained so the in-app bell stays functional even
        // when the user disabled in_app explicitly — design choice to avoid
        // accidental silent muting that confuses users.
        return array_values(array_unique([...$channels, 'database']));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject('Someone replied to your comment')
            ->greeting('Hi '.$notifiable->name.',')
            ->line($data['title'])
            ->line('"'.$data['message'].'"')
            ->action('Read the reply', $data['url'] ?? url('/'))
            ->line('You can mute replies from your notification preferences.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        /** @var Post|null $post */
        $post = $this->reply->post;
        $replyAuthor = $this->reply->authorName();
        $excerpt = \Illuminate\Support\Str::limit(strip_tags($this->reply->body), 120);

        return [
            'type' => 'comment_reply',
            'title' => "{$replyAuthor} replied to your comment",
            'message' => $excerpt,
            'icon' => 'message-square-reply',
            'color' => 'indigo',
            'post_id' => $post?->id,
            'post_title' => $post?->translation()?->title,
            'comment_id' => $this->reply->id,
            'parent_comment_id' => $this->parent->id,
            'reply_author' => $replyAuthor,
            'url' => $post && $post->translation()
                ? url('/'.($post->translation()->language?->code ?? 'en').'/'.$post->translation()->slug.'#comment-'.$this->reply->id)
                : null,
        ];
    }
}
