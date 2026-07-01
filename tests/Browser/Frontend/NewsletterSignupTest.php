<?php

declare(strict_types=1);

/**
 * Newsletter signup + double opt-in flow:
 *
 *   1. Visitor fills the footer signup widget.
 *   2. A confirmation email is queued (faked).
 *   3. Visitor clicks the confirm link — status flips to confirmed.
 */

use App\Models\Language;
use App\Models\NewsletterSubscriber;
use App\Notifications\NewsletterConfirmation;
use App\Support\LocaleResolver;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    Notification::fake();
});

test('visitor signs up via the footer widget and receives a confirmation notification', function (): void {
    visit('/')
        ->assertOk()
        ->fill('input[type="email"][wire\\:model="email"]', 'subscriber@example.com')
        ->press('Subscribe')
        ->assertSee('check your inbox');

    $sub = NewsletterSubscriber::query()->where('email', 'subscriber@example.com')->first();
    expect($sub)->not->toBeNull();
    expect($sub->status)->toBe(NewsletterSubscriber::STATUS_PENDING);

    Notification::assertSentTo($sub, NewsletterConfirmation::class);
});

test('clicking the confirm link flips the subscriber to confirmed', function (): void {
    $sub = NewsletterSubscriber::factory()->create();

    visit('/newsletter/confirm/'.$sub->confirmation_token)
        ->assertOk()
        ->assertSee("You're confirmed");

    expect($sub->fresh()->status)->toBe(NewsletterSubscriber::STATUS_CONFIRMED);
});

test('clicking the unsubscribe link sets the status to unsubscribed', function (): void {
    $sub = NewsletterSubscriber::factory()->confirmed()->create();

    visit('/newsletter/unsubscribe/'.$sub->unsubscribe_token)
        ->assertOk()
        ->assertSee("You've unsubscribed");

    expect($sub->fresh()->status)->toBe(NewsletterSubscriber::STATUS_UNSUBSCRIBED);
});

test('honeypot field traps spam submissions silently', function (): void {
    visit('/')
        ->assertOk()
        ->fill('input[type="email"][wire\\:model="email"]', 'spammer@example.com')
        ->fill('input[name="website"]', 'http://spam.example')   // honeypot
        ->press('Subscribe');

    // Spam should NOT create a subscriber record.
    expect(NewsletterSubscriber::query()->where('email', 'spammer@example.com')->exists())->toBeFalse();
});
