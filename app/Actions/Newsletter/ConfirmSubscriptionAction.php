<?php

declare(strict_types=1);

namespace App\Actions\Newsletter;

use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Notifications\Newsletter\NewSubscriberNotification;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;

/**
 * Complete the double-opt-in flow.
 *
 * Looks up a subscriber by their confirmation token. If found and not
 * already confirmed, flips status to `confirmed` and stamps
 * confirmed_at. Idempotent — re-clicking the link on an already
 * confirmed subscriber returns the same subscriber without throwing.
 *
 * The confirmation token is NOT rotated after use; the same row's
 * unsubscribe_token is the long-lived identifier the user holds via
 * the campaign emails.
 */
class ConfirmSubscriptionAction
{
    public function handle(string $token): NewsletterSubscriber
    {
        $subscriber = NewsletterSubscriber::query()
            ->where('confirmation_token', $token)
            ->first();

        if ($subscriber === null) {
            throw new InvalidArgumentException('Invalid or expired confirmation token.');
        }

        $justConfirmed = false;

        if (! $subscriber->isConfirmed()) {
            $subscriber->fill([
                'status' => NewsletterSubscriber::STATUS_CONFIRMED,
                'confirmed_at' => now(),
            ])->save();
            $justConfirmed = true;
        }

        // Notify every user with `newsletter.view` permission once on the
        // first confirmation — re-clicks of the same link stay silent.
        if ($justConfirmed) {
            $this->notifyNewsletterAdmins($subscriber);
        }

        return $subscriber->fresh();
    }

    /**
     * Fan-out a NewSubscriberNotification to staff with newsletter.view.
     * Wrapped in a try/catch so a notification failure can never block
     * the actual subscription confirmation.
     */
    private function notifyNewsletterAdmins(NewsletterSubscriber $subscriber): void
    {
        try {
            $admins = User::query()
                ->whereHas('roles.permissions', fn ($q) => $q->where('name', 'newsletter.view'))
                ->orWhereHas('permissions', fn ($q) => $q->where('name', 'newsletter.view'))
                ->orWhereHas('roles', fn ($q) => $q->whereIn('name', ['Super Admin', 'Admin']))
                ->get();

            if ($admins->isNotEmpty()) {
                Notification::send($admins, new NewSubscriberNotification($subscriber));
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
