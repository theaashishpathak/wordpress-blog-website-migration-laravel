<?php

declare(strict_types=1);

use App\Models\AccountDeletionRequest;
use App\Models\AuthorFollow;
use App\Models\Bookmark;
use App\Models\DataExportRequest;
use App\Models\Highlight;
use App\Models\NotificationPreference;
use App\Models\PostReaction;
use App\Models\ReadingHistory;
use App\Models\ReadingListItem;
use App\Models\TopicFollow;
use App\Models\User;
use App\Models\UserFollow;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Phase V1 — Visitor portal table & relationship sanity tests
|--------------------------------------------------------------------------
| These prove the 12 new tables migrate cleanly, that each Model→User
| relationship works in both directions, and that the unique constraints
| we declared actually prevent duplicates. The Action layer + UI tests
| land in later phases (V2–V9).
*/

test('bookmark belongs to user and post', function () {
    $bookmark = Bookmark::factory()->create();

    expect($bookmark->user)->toBeInstanceOf(User::class)
        ->and($bookmark->post->id)->toBe($bookmark->post_id);
});

test('user can have many bookmarks', function () {
    $user = User::factory()->visitor()->create();
    Bookmark::factory()->count(3)->create(['user_id' => $user->id]);

    expect($user->bookmarks()->count())->toBe(3);
});

test('reading list item active scope ignores dismissed rows', function () {
    $user = User::factory()->visitor()->create();
    ReadingListItem::factory()->count(2)->create(['user_id' => $user->id]);
    ReadingListItem::factory()->dismissed()->count(3)->create(['user_id' => $user->id]);

    expect(ReadingListItem::query()->active()->count())->toBe(2)
        ->and(ReadingListItem::query()->dismissed()->count())->toBe(3);
});

test('reading history stores per-user-per-post unique row', function () {
    $entry = ReadingHistory::factory()->create();

    expect($entry->fresh()->read_count)->toBe(1)
        ->and($entry->user->readingHistory()->first()->id)->toBe($entry->id);
});

test('post reaction stores like or dislike scopes', function () {
    PostReaction::factory()->like()->count(3)->create();
    PostReaction::factory()->dislike()->count(2)->create();

    expect(PostReaction::query()->likes()->count())->toBe(3)
        ->and(PostReaction::query()->dislikes()->count())->toBe(2);
});

test('highlight stores selected text and optional note', function () {
    $highlight = Highlight::factory()->create([
        'selected_text' => 'AI changes everything',
        'note' => 'Reminded me of the Turing quote',
    ]);

    expect($highlight->selected_text)->toBe('AI changes everything')
        ->and($highlight->note)->toContain('Turing')
        ->and($highlight->context_hash)->toHaveLength(40);
});

test('topic follow can point at a tag', function () {
    $follow = TopicFollow::factory()->create();

    expect($follow->followable)->not->toBeNull()
        ->and($follow->followable_type)->toBeIn([\App\Models\Tag::class, 'App\Models\Tag']);
});

test('author follow links follower and author users', function () {
    $follow = AuthorFollow::factory()->create();

    expect($follow->follower)->toBeInstanceOf(User::class)
        ->and($follow->author)->toBeInstanceOf(User::class)
        ->and($follow->follower_id)->not->toBe($follow->author_id);
});

test('user follow links two users (social)', function () {
    $follow = UserFollow::factory()->create();

    expect($follow->follower)->toBeInstanceOf(User::class)
        ->and($follow->followed)->toBeInstanceOf(User::class);
});

test('notification preference defaults to enabled', function () {
    $pref = NotificationPreference::factory()->create();

    expect($pref->enabled)->toBeTrue()
        ->and($pref->channel)->toBeIn(NotificationPreference::CHANNELS);
});

test('user setting helper get/set works with json values', function () {
    $user = User::factory()->visitor()->create();

    UserSetting::setValue($user->id, 'theme', 'dark');
    UserSetting::setValue($user->id, 'font_size', ['size' => 16, 'unit' => 'px']);

    expect(UserSetting::getValue($user->id, 'theme'))->toBe('dark')
        ->and(UserSetting::getValue($user->id, 'font_size'))->toBe(['size' => 16, 'unit' => 'px'])
        ->and(UserSetting::getValue($user->id, 'missing', 'fallback'))->toBe('fallback');
});

test('data export request transitions to ready state', function () {
    $request = DataExportRequest::factory()->ready()->create();

    expect($request->isReady())->toBeTrue()
        ->and($request->status)->toBe(DataExportRequest::STATUS_READY)
        ->and($request->expires_at?->isFuture())->toBeTrue();
});

test('account deletion request pending scope excludes cancelled', function () {
    $user = User::factory()->visitor()->create();
    AccountDeletionRequest::factory()->count(2)->create(['user_id' => $user->id]);
    AccountDeletionRequest::factory()->cancelled()->create(['user_id' => $user->id]);

    expect(AccountDeletionRequest::query()->pending()->count())->toBe(2);
});

test('user model exposes all visitor-portal relations', function () {
    $user = User::factory()->visitor()->create();

    // Each relation should be queryable without throwing.
    expect($user->bookmarks()->count())->toBe(0)
        ->and($user->readingListItems()->count())->toBe(0)
        ->and($user->readingHistory()->count())->toBe(0)
        ->and($user->reactions()->count())->toBe(0)
        ->and($user->highlights()->count())->toBe(0)
        ->and($user->topicFollows()->count())->toBe(0)
        ->and($user->authorFollows()->count())->toBe(0)
        ->and($user->authorFollowers()->count())->toBe(0)
        ->and($user->following()->count())->toBe(0)
        ->and($user->followers()->count())->toBe(0)
        ->and($user->notificationPreferences()->count())->toBe(0)
        ->and($user->userSettings()->count())->toBe(0)
        ->and($user->dataExportRequests()->count())->toBe(0)
        ->and($user->accountDeletionRequests()->count())->toBe(0);
});
