<?php

declare(strict_types=1);

namespace App\Actions\Newsletter;

use App\Mail\NewsletterConfirmationMail;
use App\Models\NewsletterSubscriber;
use App\Support\LocaleResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Capture a new newsletter subscriber via the public signup form.
 *
 * Behaviour:
 *   - Email is normalised + de-duplicated. If the same email already
 *     has a `confirmed` or `pending` record, no new row is created
 *     and the existing token is re-sent. This makes the endpoint safe
 *     against accidental double-submits.
 *   - An `unsubscribed` row for the same email is reactivated to
 *     `pending` (gives the user a clean re-opt-in path).
 *   - A confirmation email is dispatched via Mail::queue so signup
 *     latency stays low.
 *
 * Caller is responsible for any rate-limit / captcha — the Action
 * trusts inputs to be already filtered.
 */
class SubscribeToNewsletterAction
{
    public function __construct(private LocaleResolver $localeResolver) {}

    /**
     * @param  array<string, mixed>  $context  optional source / ip / user_agent / name
     */
    public function handle(string $email, array $context = []): NewsletterSubscriber
    {
        $email = mb_strtolower(trim($email));

        return DB::transaction(function () use ($email, $context): NewsletterSubscriber {
            $subscriber = NewsletterSubscriber::query()
                ->where('email', $email)
                ->whereIn('status', [
                    NewsletterSubscriber::STATUS_PENDING,
                    NewsletterSubscriber::STATUS_CONFIRMED,
                    NewsletterSubscriber::STATUS_UNSUBSCRIBED,
                ])
                ->latest('id')
                ->first();

            if ($subscriber === null) {
                $subscriber = NewsletterSubscriber::query()->create([
                    'email' => $email,
                    'name' => $context['name'] ?? null,
                    'status' => NewsletterSubscriber::STATUS_PENDING,
                    'confirmation_token' => Str::random(48),
                    'unsubscribe_token' => Str::random(48),
                    'source' => $context['source'] ?? null,
                    'language_id' => $this->localeResolver->current()?->id,
                    'ip_address' => $context['ip'] ?? null,
                    'user_agent' => isset($context['user_agent']) ? mb_substr((string) $context['user_agent'], 0, 500) : null,
                ]);
            } elseif ($subscriber->status === NewsletterSubscriber::STATUS_UNSUBSCRIBED) {
                // Re-opt-in: reset to pending and rotate tokens.
                $subscriber->fill([
                    'status' => NewsletterSubscriber::STATUS_PENDING,
                    'confirmation_token' => Str::random(48),
                    'unsubscribe_token' => Str::random(48),
                    'unsubscribed_at' => null,
                ])->save();
            } else {
                // Already pending or confirmed — rotate the confirmation
                // token so a fresh confirm URL works (useful when the
                // earlier email was lost). Idempotent for confirmed
                // subscribers — we just resend the welcome notice.
                if ($subscriber->status === NewsletterSubscriber::STATUS_PENDING) {
                    $subscriber->fill(['confirmation_token' => Str::random(48)])->save();
                }
            }

            // Send the confirmation email unless the user is already
            // confirmed (don't spam them).
            if ($subscriber->status === NewsletterSubscriber::STATUS_PENDING) {
                Mail::to($email)->queue(new NewsletterConfirmationMail($subscriber));
            }

            return $subscriber->fresh();
        });
    }
}
