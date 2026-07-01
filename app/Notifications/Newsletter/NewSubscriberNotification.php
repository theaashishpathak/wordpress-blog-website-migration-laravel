<?php

declare(strict_types=1);

namespace App\Notifications\Newsletter;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to every user with `newsletter.view` permission when a new
 * subscriber confirms their email (NOT on initial signup — we wait
 * for double opt-in so admins aren't notified about uncommitted
 * subscribes).
 */
class NewSubscriberNotification extends Notification
{
    use Queueable;

    public function __construct(public NewsletterSubscriber $subscriber) {}

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
        $name = $this->subscriber->name ?? $this->subscriber->email;

        return [
            'type' => 'newsletter.subscriber.confirmed',
            'subscriber_id' => $this->subscriber->id,
            'subscriber_email' => $this->subscriber->email,
            'subscriber_name' => $this->subscriber->name,
            'source' => $this->subscriber->source,
            'icon' => 'mail-check',
            'color' => 'pink',
            'title' => 'New newsletter subscriber',
            'message' => "{$name} confirmed their subscription".($this->subscriber->source ? " via {$this->subscriber->source}" : '').'.',
            'url' => route('admin.newsletter.subscribers'),
        ];
    }
}
