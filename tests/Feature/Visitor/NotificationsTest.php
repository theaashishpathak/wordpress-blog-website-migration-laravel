<?php

declare(strict_types=1);

use App\Actions\Comment\ApproveCommentAction;
use App\Actions\Comment\CreateCommentAction;
use App\Actions\Visitor\Follow\ToggleUserFollowAction;
use App\Livewire\Visitor\NotificationBell;
use App\Livewire\Visitor\Notifications\Index as NotificationsIndex;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Notifications\Reader\CommentApprovedNotification;
use App\Notifications\Reader\CommentReplyNotification;
use App\Notifications\Reader\NewFollowerNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->visitor = User::factory()->visitor()->create();
    $this->post = Post::factory()->create([
        'status' => \App\Enums\PostStatus::Published,
        'published_at' => now()->subDay(),
        'allow_comments' => true,
    ]);
});

// ── Triggers (Actions emit notifications) ───────────────────────────────

test('replying to a comment pings the parent commenter', function () {
    Notification::fake();

    $parent = Comment::query()->create([
        'post_id' => $this->post->id,
        'user_id' => $this->visitor->id,
        'body' => 'Top-level comment',
        'status' => Comment::STATUS_APPROVED,
    ]);

    $replier = User::factory()->visitor()->create();
    app(CreateCommentAction::class)->handle($this->post, $replier, [
        'body' => 'My reply',
        'parent_id' => $parent->id,
    ]);

    Notification::assertSentTo($this->visitor, CommentReplyNotification::class);
});

test('self-reply does not notify the same user', function () {
    Notification::fake();

    $parent = Comment::query()->create([
        'post_id' => $this->post->id,
        'user_id' => $this->visitor->id,
        'body' => 'My own comment',
        'status' => Comment::STATUS_APPROVED,
    ]);

    app(CreateCommentAction::class)->handle($this->post, $this->visitor, [
        'body' => 'Replying to myself',
        'parent_id' => $parent->id,
    ]);

    Notification::assertNotSentTo($this->visitor, CommentReplyNotification::class);
});

test('approving a pending comment pings the commenter', function () {
    Notification::fake();
    $moderator = User::factory()->admin()->create();

    $comment = Comment::query()->create([
        'post_id' => $this->post->id,
        'user_id' => $this->visitor->id,
        'body' => 'Pending one',
        'status' => Comment::STATUS_PENDING,
    ]);

    app(ApproveCommentAction::class)->handle($comment, $moderator);

    Notification::assertSentTo($this->visitor, CommentApprovedNotification::class);
});

test('approving already-approved comment does not re-notify', function () {
    Notification::fake();
    $moderator = User::factory()->admin()->create();

    $comment = Comment::query()->create([
        'post_id' => $this->post->id,
        'user_id' => $this->visitor->id,
        'body' => 'Already approved',
        'status' => Comment::STATUS_APPROVED,
        'approved_at' => now(),
    ]);

    app(ApproveCommentAction::class)->handle($comment, $moderator);

    Notification::assertNotSentTo($this->visitor, CommentApprovedNotification::class);
});

test('following a user notifies them', function () {
    Notification::fake();
    $target = User::factory()->visitor()->create();

    app(ToggleUserFollowAction::class)->handle($this->visitor, $target);

    Notification::assertSentTo($target, NewFollowerNotification::class);
});

// ── Bell + Index ───────────────────────────────────────────────────────

test('bell shows unread count and marks single notification read', function () {
    $this->visitor->notify(new CommentReplyNotification(
        Comment::query()->create([
            'post_id' => $this->post->id,
            'user_id' => User::factory()->visitor()->create()->id,
            'body' => 'reply',
            'status' => Comment::STATUS_APPROVED,
        ]),
        Comment::query()->create([
            'post_id' => $this->post->id,
            'user_id' => $this->visitor->id,
            'body' => 'parent',
            'status' => Comment::STATUS_APPROVED,
        ]),
    ));

    $notification = $this->visitor->notifications()->first();

    Livewire::actingAs($this->visitor)
        ->test(NotificationBell::class)
        ->assertOk()
        ->call('markRead', $notification->id);

    expect($this->visitor->fresh()->unreadNotifications()->count())->toBe(0);
});

test('bell mark all read clears unread count', function () {
    for ($i = 0; $i < 3; $i++) {
        $this->visitor->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'App\\Test',
            'data' => ['title' => 'Test'],
        ]);
    }

    expect($this->visitor->unreadNotifications()->count())->toBe(3);

    Livewire::actingAs($this->visitor)
        ->test(NotificationBell::class)
        ->call('markAllRead');

    expect($this->visitor->fresh()->unreadNotifications()->count())->toBe(0);
});

test('notifications index can filter and delete', function () {
    $unread = $this->visitor->notifications()->create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'type' => 'App\\Test',
        'data' => ['title' => 'Unread'],
    ]);
    $read = $this->visitor->notifications()->create([
        'id' => (string) \Illuminate\Support\Str::uuid(),
        'type' => 'App\\Test',
        'data' => ['title' => 'Read'],
        'read_at' => now(),
    ]);

    Livewire::actingAs($this->visitor)
        ->test(NotificationsIndex::class)
        ->call('switchFilter', 'unread')
        ->assertSet('filter', 'unread')
        ->call('delete', $unread->id);

    expect($this->visitor->notifications()->count())->toBe(1)
        ->and($this->visitor->notifications()->first()->id)->toBe($read->id);
});

test('notifications index clearAll wipes everything', function () {
    for ($i = 0; $i < 5; $i++) {
        $this->visitor->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'App\\Test',
            'data' => ['title' => 'n'.$i],
        ]);
    }

    Livewire::actingAs($this->visitor)
        ->test(NotificationsIndex::class)
        ->call('clearAll');

    expect($this->visitor->notifications()->count())->toBe(0);
});
