<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Actions\Newsletter\ConfirmSubscriptionAction;
use App\Actions\Newsletter\UnsubscribeAction;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;

/**
 * Public token-driven endpoints for the newsletter double-opt-in flow.
 *
 * Both routes are GET-only — the token is the sole secret carried in
 * the URL, no extra confirmation form is required. The Actions are
 * idempotent so repeat clicks are safe.
 */
class NewsletterController
{
    public function confirm(string $token, ConfirmSubscriptionAction $confirm): View
    {
        try {
            $subscriber = $confirm->handle($token);

            return view('frontend.newsletter.confirmed', [
                'subscriber' => $subscriber,
            ]);
        } catch (InvalidArgumentException) {
            return view('frontend.newsletter.invalid');
        }
    }

    public function unsubscribe(string $token, UnsubscribeAction $unsubscribe): View
    {
        try {
            $subscriber = $unsubscribe->handle($token);

            return view('frontend.newsletter.unsubscribed', [
                'subscriber' => $subscriber,
            ]);
        } catch (InvalidArgumentException) {
            return view('frontend.newsletter.invalid');
        }
    }
}
