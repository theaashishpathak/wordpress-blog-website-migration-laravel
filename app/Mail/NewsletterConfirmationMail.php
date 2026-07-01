<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use App\Services\SettingService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Double-opt-in confirmation email sent immediately after signup.
 *
 * Renders the public confirmation link tied to the subscriber's
 * confirmation_token. Once the user clicks, ConfirmSubscriptionAction
 * flips them to `confirmed` and they're eligible for campaigns.
 */
class NewsletterConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public NewsletterSubscriber $subscriber) {}

    public function envelope(): Envelope
    {
        $settings = app(SettingService::class);
        $siteName = (string) ($settings->get('site.name') ?? config('app.name', 'NewsPilot AI'));

        return new Envelope(
            subject: "Confirm your subscription to {$siteName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.newsletter.confirmation',
            with: [
                'subscriber' => $this->subscriber,
                'confirmUrl' => url('/newsletter/confirm/'.$this->subscriber->confirmation_token),
                'unsubscribeUrl' => url('/newsletter/unsubscribe/'.$this->subscriber->unsubscribe_token),
                'siteName' => (string) (app(SettingService::class)->get('site.name') ?? config('app.name')),
            ],
        );
    }
}
