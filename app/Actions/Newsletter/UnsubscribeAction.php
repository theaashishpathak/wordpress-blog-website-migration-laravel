<?php

declare(strict_types=1);

namespace App\Actions\Newsletter;

use App\Models\NewsletterSubscriber;
use InvalidArgumentException;

/**
 * One-click unsubscribe handler.
 *
 * Looks up by the unsubscribe_token (carried in every campaign email
 * footer) and flips the row to `unsubscribed`. Idempotent.
 */
class UnsubscribeAction
{
    public function handle(string $token): NewsletterSubscriber
    {
        $subscriber = NewsletterSubscriber::query()
            ->where('unsubscribe_token', $token)
            ->first();

        if ($subscriber === null) {
            throw new InvalidArgumentException('Invalid or expired unsubscribe token.');
        }

        if (! $subscriber->isUnsubscribed()) {
            $subscriber->fill([
                'status' => NewsletterSubscriber::STATUS_UNSUBSCRIBED,
                'unsubscribed_at' => now(),
            ])->save();
        }

        return $subscriber->fresh();
    }
}
