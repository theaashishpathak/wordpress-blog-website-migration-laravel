<?php

declare(strict_types=1);

namespace App\Notifications\Reader;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to a visitor when another reader starts following them (social).
 */
class NewFollowerNotification extends Notification
{
    use Queueable;

    public function __construct(public User $follower) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = NotificationPreference::resolveChannels($notifiable->id, 'new_follower');

        return array_values(array_unique([...$channels, 'database']));
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->follower->name.' started following you')
            ->greeting('Hi '.$notifiable->name.',')
            ->line($this->follower->name.' just started following you on NewsPilot.')
            ->action('View your followers', route('visitor.following.users', ['tab' => 'followers']));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_follower',
            'title' => "{$this->follower->name} started following you",
            'message' => 'Open your followers list to learn more.',
            'icon' => 'user-plus',
            'color' => 'emerald',
            'follower_id' => $this->follower->id,
            'follower_name' => $this->follower->name,
            'url' => route('visitor.following.users', ['tab' => 'followers']),
        ];
    }
}
