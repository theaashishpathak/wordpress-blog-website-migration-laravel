<?php

declare(strict_types=1);

namespace App\Notifications\Reader;

use App\Models\Comment;
use App\Models\NotificationPreference;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a commenter when a moderator approves their pending comment.
 * Skipped when the commenter is themselves the moderator (i.e., admin
 * approving their own comment).
 */
class CommentApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(public Comment $comment) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = NotificationPreference::resolveChannels($notifiable->id, 'comment_approved');

        return array_values(array_unique([...$channels, 'database']));
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->toArray($notifiable);

        return (new MailMessage)
            ->subject('Your comment is live')
            ->greeting('Hi '.$notifiable->name.',')
            ->line($data['title'])
            ->line('"'.$data['message'].'"')
            ->action('See it on the article', $data['url'] ?? url('/'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $post = $this->comment->post;
        $translation = $post?->translation();
        $excerpt = \Illuminate\Support\Str::limit(strip_tags($this->comment->body), 120);

        return [
            'type' => 'comment_approved',
            'title' => 'Your comment is live',
            'message' => $excerpt,
            'icon' => 'check-circle-2',
            'color' => 'emerald',
            'post_id' => $post?->id,
            'post_title' => $translation?->title,
            'comment_id' => $this->comment->id,
            'url' => $translation
                ? url('/'.($translation->language?->code ?? 'en').'/'.$translation->slug.'#comment-'.$this->comment->id)
                : null,
        ];
    }
}
