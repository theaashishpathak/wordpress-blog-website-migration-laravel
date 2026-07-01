<?php

declare(strict_types=1);

use App\Livewire\Visitor\Email\Preferences;
use App\Livewire\Visitor\Email\Subscriptions;
use App\Models\Comment;
use App\Models\NewsletterSubscriber;
use App\Models\NotificationPreference;
use App\Models\Post;
use App\Models\User;
use App\Notifications\Reader\CommentReplyNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->visitor = User::factory()->visitor()->create();
});

// ── Catalog + helpers ──────────────────────────────────────────────────

test('event catalog has all expected keys', function () {
    $catalog = NotificationPreference::eventCatalog();

    expect(array_keys($catalog))->toMatchArray([
        'comment_reply', 'comment_approved', 'new_follower',
        'author_published', 'daily_digest', 'weekly_digest',
    ]);
});

test('isEnabled falls back to catalog default when no row exists', function () {
    // Defaults: in_app=true, email=false for comment_reply
    expect(NotificationPreference::isEnabled($this->visitor->id, 'comment_reply', 'in_app'))->toBeTrue()
        ->and(NotificationPreference::isEnabled($this->visitor->id, 'comment_reply', 'email'))->toBeFalse();
});

test('isEnabled returns stored row value when present', function () {
    NotificationPreference::setValue($this->visitor->id, 'comment_reply', 'email', true);

    expect(NotificationPreference::isEnabled($this->visitor->id, 'comment_reply', 'email'))->toBeTrue();
});

test('resolveChannels respects opt-ins', function () {
    NotificationPreference::setValue($this->visitor->id, 'comment_reply', 'email', true);

    $channels = NotificationPreference::resolveChannels($this->visitor->id, 'comment_reply');

    expect($channels)->toContain('database')
        ->and($channels)->toContain('mail');
});

// ── Notification via() integration ─────────────────────────────────────

test('comment reply notification sends only to database by default', function () {
    Notification::fake();

    $post = Post::factory()->create();
    $parent = Comment::query()->create([
        'post_id' => $post->id,
        'user_id' => $this->visitor->id,
        'body' => 'parent',
        'status' => Comment::STATUS_APPROVED,
    ]);
    $reply = Comment::query()->create([
        'post_id' => $post->id,
        'user_id' => User::factory()->visitor()->create()->id,
        'body' => 'reply',
        'status' => Comment::STATUS_APPROVED,
        'parent_id' => $parent->id,
    ]);

    $this->visitor->notify(new CommentReplyNotification($reply, $parent));

    Notification::assertSentTo($this->visitor, CommentReplyNotification::class, function ($notification, $channels) {
        expect($channels)->toContain('database')
            ->and($channels)->not->toContain('mail');

        return true;
    });
});

test('opted-in user gets the mail channel added', function () {
    Notification::fake();
    NotificationPreference::setValue($this->visitor->id, 'comment_reply', 'email', true);

    $post = Post::factory()->create();
    $parent = Comment::query()->create([
        'post_id' => $post->id,
        'user_id' => $this->visitor->id,
        'body' => 'parent',
        'status' => Comment::STATUS_APPROVED,
    ]);
    $reply = Comment::query()->create([
        'post_id' => $post->id,
        'user_id' => User::factory()->visitor()->create()->id,
        'body' => 'reply',
        'status' => Comment::STATUS_APPROVED,
        'parent_id' => $parent->id,
    ]);

    $this->visitor->notify(new CommentReplyNotification($reply, $parent));

    Notification::assertSentTo($this->visitor, CommentReplyNotification::class, function ($notification, $channels) {
        expect($channels)->toContain('mail');

        return true;
    });
});

// ── Preferences page ──────────────────────────────────────────────────

test('preferences page toggles store the new value', function () {
    Livewire::actingAs($this->visitor)
        ->test(Preferences::class)
        ->call('toggle', 'comment_reply', 'email');

    expect(NotificationPreference::isEnabled($this->visitor->id, 'comment_reply', 'email'))->toBeTrue();
});

test('master email mute flips every event email to false', function () {
    // Pre-enable a couple to prove they get turned off
    NotificationPreference::setValue($this->visitor->id, 'comment_reply', 'email', true);
    NotificationPreference::setValue($this->visitor->id, 'new_follower', 'email', true);

    Livewire::actingAs($this->visitor)
        ->test(Preferences::class)
        ->call('toggleMasterEmailMute')
        ->assertSet('masterEmailMute', true);

    expect(NotificationPreference::isEnabled($this->visitor->id, 'comment_reply', 'email'))->toBeFalse()
        ->and(NotificationPreference::isEnabled($this->visitor->id, 'new_follower', 'email'))->toBeFalse();
});

// ── Subscriptions page ────────────────────────────────────────────────

test('subscriptions page lists rows by user email', function () {
    NewsletterSubscriber::factory()->count(2)->create(['email' => $this->visitor->email]);
    NewsletterSubscriber::factory()->create(['email' => 'someone-else@test.com']);

    Livewire::actingAs($this->visitor)
        ->test(Subscriptions::class)
        ->assertOk();

    expect(NewsletterSubscriber::query()->where('email', $this->visitor->email)->count())->toBe(2);
});

test('subscriptions unsubscribe flips status', function () {
    $sub = NewsletterSubscriber::factory()->create([
        'email' => $this->visitor->email,
        'status' => NewsletterSubscriber::STATUS_CONFIRMED,
        'confirmed_at' => now()->subDays(5),
    ]);

    Livewire::actingAs($this->visitor)
        ->test(Subscriptions::class)
        ->call('unsubscribe', $sub->id);

    expect($sub->fresh()->status)->toBe(NewsletterSubscriber::STATUS_UNSUBSCRIBED)
        ->and($sub->fresh()->unsubscribed_at)->not->toBeNull();
});

test('subscriptions resubscribe flips status back', function () {
    $sub = NewsletterSubscriber::factory()->create([
        'email' => $this->visitor->email,
        'status' => NewsletterSubscriber::STATUS_UNSUBSCRIBED,
        'unsubscribed_at' => now()->subDay(),
    ]);

    Livewire::actingAs($this->visitor)
        ->test(Subscriptions::class)
        ->call('resubscribe', $sub->id);

    expect($sub->fresh()->status)->toBe(NewsletterSubscriber::STATUS_CONFIRMED)
        ->and($sub->fresh()->unsubscribed_at)->toBeNull();
});

test('subscriptions unsubscribeAll bulk action', function () {
    NewsletterSubscriber::factory()->count(2)->create([
        'email' => $this->visitor->email,
        'status' => NewsletterSubscriber::STATUS_CONFIRMED,
    ]);

    Livewire::actingAs($this->visitor)
        ->test(Subscriptions::class)
        ->call('unsubscribeAll');

    expect(
        NewsletterSubscriber::query()
            ->where('email', $this->visitor->email)
            ->where('status', NewsletterSubscriber::STATUS_CONFIRMED)
            ->count()
    )->toBe(0);
});
