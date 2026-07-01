<?php

declare(strict_types=1);

use App\Actions\Newsletter\ConfirmSubscriptionAction;
use App\Actions\Newsletter\SubscribeToNewsletterAction;
use App\Actions\Newsletter\UnsubscribeAction;
use App\Livewire\Admin\Newsletter\Subscribers;
use App\Livewire\Frontend\NewsletterSignup;
use App\Mail\NewsletterConfirmationMail;
use App\Models\Language;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();
    Mail::fake();
});

function adminWith(string $role): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $r = Role::query()->where('name', $role)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($r);

    return $user->fresh();
}

// -------------------------------------------------------------------------
// SubscribeToNewsletterAction
// -------------------------------------------------------------------------

test('subscribe creates a pending subscriber and queues the confirmation email', function (): void {
    $action = app(SubscribeToNewsletterAction::class);

    $sub = $action->handle('hello@example.com', ['source' => 'footer_form']);

    expect($sub->email)->toBe('hello@example.com');
    expect($sub->status)->toBe(NewsletterSubscriber::STATUS_PENDING);
    expect($sub->confirmation_token)->not->toBeEmpty();
    expect($sub->unsubscribe_token)->not->toBeEmpty();
    expect($sub->source)->toBe('footer_form');

    Mail::assertQueued(NewsletterConfirmationMail::class, fn ($mail) => $mail->subscriber->email === 'hello@example.com');
});

test('subscribe is idempotent for already-pending emails (re-sends confirmation)', function (): void {
    $action = app(SubscribeToNewsletterAction::class);

    $first = $action->handle('twice@example.com');
    $firstToken = $first->confirmation_token;

    $second = $action->handle('twice@example.com');

    expect(NewsletterSubscriber::query()->count())->toBe(1);
    expect($second->id)->toBe($first->id);
    // Token is rotated for fresh-link UX
    expect($second->confirmation_token)->not->toBe($firstToken);

    Mail::assertQueuedCount(2);   // both signups send a mail
});

test('subscribe is a no-op for already-confirmed emails (no new mail)', function (): void {
    $existing = NewsletterSubscriber::factory()->confirmed()->create(['email' => 'already@example.com']);

    $result = app(SubscribeToNewsletterAction::class)->handle('already@example.com');

    expect($result->id)->toBe($existing->id);
    expect($result->status)->toBe(NewsletterSubscriber::STATUS_CONFIRMED);

    Mail::assertNothingQueued();
});

test('subscribe reactivates an unsubscribed row back to pending', function (): void {
    $existing = NewsletterSubscriber::factory()->unsubscribed()->create(['email' => 'back@example.com']);

    $result = app(SubscribeToNewsletterAction::class)->handle('back@example.com');

    expect($result->id)->toBe($existing->id);
    expect($result->status)->toBe(NewsletterSubscriber::STATUS_PENDING);
    expect($result->unsubscribed_at)->toBeNull();

    Mail::assertQueuedCount(1);
});

test('subscribe normalises email to lowercase + trim', function (): void {
    $sub = app(SubscribeToNewsletterAction::class)->handle('  MIXED@Example.COM  ');

    expect($sub->email)->toBe('mixed@example.com');
});

// -------------------------------------------------------------------------
// ConfirmSubscriptionAction
// -------------------------------------------------------------------------

test('confirm flips status to confirmed and stamps confirmed_at', function (): void {
    $sub = NewsletterSubscriber::factory()->create();

    $result = app(ConfirmSubscriptionAction::class)->handle($sub->confirmation_token);

    expect($result->status)->toBe(NewsletterSubscriber::STATUS_CONFIRMED);
    expect($result->confirmed_at)->not->toBeNull();
});

test('confirm with invalid token throws', function (): void {
    expect(fn () => app(ConfirmSubscriptionAction::class)->handle('not-a-real-token'))
        ->toThrow(InvalidArgumentException::class);
});

test('confirm is idempotent on already-confirmed subscriber', function (): void {
    $sub = NewsletterSubscriber::factory()->confirmed()->create();
    $stampBefore = $sub->confirmed_at;

    $result = app(ConfirmSubscriptionAction::class)->handle($sub->confirmation_token);

    expect($result->status)->toBe(NewsletterSubscriber::STATUS_CONFIRMED);
    expect($result->confirmed_at->equalTo($stampBefore))->toBeTrue();
});

// -------------------------------------------------------------------------
// UnsubscribeAction
// -------------------------------------------------------------------------

test('unsubscribe flips status to unsubscribed and stamps unsubscribed_at', function (): void {
    $sub = NewsletterSubscriber::factory()->confirmed()->create();

    $result = app(UnsubscribeAction::class)->handle($sub->unsubscribe_token);

    expect($result->status)->toBe(NewsletterSubscriber::STATUS_UNSUBSCRIBED);
    expect($result->unsubscribed_at)->not->toBeNull();
});

test('unsubscribe with invalid token throws', function (): void {
    expect(fn () => app(UnsubscribeAction::class)->handle('bogus'))
        ->toThrow(InvalidArgumentException::class);
});

// -------------------------------------------------------------------------
// Public confirm + unsubscribe routes
// -------------------------------------------------------------------------

test('GET /newsletter/confirm/{token} confirms the subscriber and renders success page', function (): void {
    $sub = NewsletterSubscriber::factory()->create();

    $this->get('/newsletter/confirm/'.$sub->confirmation_token)
        ->assertOk()
        ->assertSee("You're confirmed", false);

    expect($sub->fresh()->status)->toBe(NewsletterSubscriber::STATUS_CONFIRMED);
});

test('GET /newsletter/confirm/{token} with bad token renders invalid page', function (): void {
    $this->get('/newsletter/confirm/abcdefghijklmnopqrstuvwxyz12345')
        ->assertOk()
        ->assertSee('no longer valid');
});

test('GET /newsletter/unsubscribe/{token} unsubscribes and renders the unsubscribed page', function (): void {
    $sub = NewsletterSubscriber::factory()->confirmed()->create();

    $this->get('/newsletter/unsubscribe/'.$sub->unsubscribe_token)
        ->assertOk()
        ->assertSee("You've unsubscribed", false);

    expect($sub->fresh()->status)->toBe(NewsletterSubscriber::STATUS_UNSUBSCRIBED);
});

// -------------------------------------------------------------------------
// Frontend signup Livewire
// -------------------------------------------------------------------------

test('signup form persists the email and queues confirmation', function (): void {
    Livewire::test(NewsletterSignup::class)
        ->set('email', 'new-signup@example.com')
        ->call('subscribe')
        ->assertSet('submitted', true);

    expect(NewsletterSubscriber::query()->where('email', 'new-signup@example.com')->exists())->toBeTrue();
    Mail::assertQueued(NewsletterConfirmationMail::class);
});

test('signup form rejects invalid email', function (): void {
    Livewire::test(NewsletterSignup::class)
        ->set('email', 'not-an-email')
        ->call('subscribe')
        ->assertHasErrors(['email']);

    expect(NewsletterSubscriber::query()->count())->toBe(0);
});

test('signup form silently swallows bot submissions via honeypot', function (): void {
    Livewire::test(NewsletterSignup::class)
        ->set('email', 'bot@example.com')
        ->set('hp', 'something-bot-filled')
        ->call('subscribe')
        ->assertSet('submitted', true);

    expect(NewsletterSubscriber::query()->count())->toBe(0);
    Mail::assertNothingQueued();
});

// -------------------------------------------------------------------------
// Admin subscribers list
// -------------------------------------------------------------------------

test('users without newsletter.view are denied', function (): void {
    $user = User::factory()->create(['email_verified_at' => now()]);

    Livewire::actingAs($user)->test(Subscribers::class)->assertForbidden();
});

test('admin can view the subscribers list', function (): void {
    $admin = adminWith('Admin');
    NewsletterSubscriber::factory()->confirmed()->create(['email' => 'visible@example.com']);

    Livewire::actingAs($admin)
        ->test(Subscribers::class)
        ->assertOk()
        ->assertSee('visible@example.com');
});

test('status filter narrows the list', function (): void {
    $admin = adminWith('Admin');
    NewsletterSubscriber::factory()->confirmed()->count(3)->create();
    NewsletterSubscriber::factory()->count(2)->create();   // pending

    $component = Livewire::actingAs($admin)
        ->test(Subscribers::class)
        ->set('statusFilter', NewsletterSubscriber::STATUS_CONFIRMED);

    expect($component->instance()->subscribers->total())->toBe(3);
});

test('counts computed reports the totals correctly', function (): void {
    $admin = adminWith('Admin');
    NewsletterSubscriber::factory()->confirmed()->count(5)->create();
    NewsletterSubscriber::factory()->count(2)->create();
    NewsletterSubscriber::factory()->unsubscribed()->count(3)->create();

    $component = Livewire::actingAs($admin)->test(Subscribers::class);
    $counts = $component->instance()->counts;

    expect($counts['total'])->toBe(10);
    expect($counts['confirmed'])->toBe(5);
    expect($counts['pending'])->toBe(2);
    expect($counts['unsubscribed'])->toBe(3);
});

test('bulkDelete removes selected subscribers', function (): void {
    $admin = adminWith('Admin');
    $sub1 = NewsletterSubscriber::factory()->create();
    $sub2 = NewsletterSubscriber::factory()->create();
    $sub3 = NewsletterSubscriber::factory()->create();

    Livewire::actingAs($admin)
        ->test(Subscribers::class)
        ->set('selectedIds', [$sub1->id, $sub2->id])
        ->call('bulkDelete');

    expect(NewsletterSubscriber::query()->whereIn('id', [$sub1->id, $sub2->id])->count())->toBe(0);
    expect(NewsletterSubscriber::query()->whereKey($sub3->id)->exists())->toBeTrue();
});

test('markUnsubscribed flips a subscriber to unsubscribed', function (): void {
    $admin = adminWith('Admin');
    $sub = NewsletterSubscriber::factory()->confirmed()->create();

    Livewire::actingAs($admin)
        ->test(Subscribers::class)
        ->call('markUnsubscribed', $sub->id);

    expect($sub->fresh()->status)->toBe(NewsletterSubscriber::STATUS_UNSUBSCRIBED);
    expect($sub->fresh()->unsubscribed_at)->not->toBeNull();
});

test('exportCsv returns a streamed CSV with subscribers', function (): void {
    $admin = adminWith('Admin');
    NewsletterSubscriber::factory()->confirmed()->create(['email' => 'csv1@example.com']);
    NewsletterSubscriber::factory()->confirmed()->create(['email' => 'csv2@example.com']);

    // Invoke the action directly on the component instance so we can
    // inspect the returned StreamedResponse — Livewire's call() wraps
    // the response and exposes no portable helper across versions.
    $component = Livewire::actingAs($admin)->test(Subscribers::class);
    $response = $component->instance()->exportCsv();

    expect($response)->toBeInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class);
    expect($response->headers->get('Content-Type'))->toContain('text/csv');
});
