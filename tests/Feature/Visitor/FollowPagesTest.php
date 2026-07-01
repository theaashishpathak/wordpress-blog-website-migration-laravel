<?php

declare(strict_types=1);

use App\Livewire\Frontend\FollowButton;
use App\Livewire\Visitor\Following\Authors as AuthorsPage;
use App\Livewire\Visitor\Following\Topics as TopicsPage;
use App\Livewire\Visitor\Following\Users as UsersPage;
use App\Models\AuthorFollow;
use App\Models\Tag;
use App\Models\TopicFollow;
use App\Models\User;
use App\Models\UserFollow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->visitor = User::factory()->visitor()->create();
});

// ── FollowButton component ─────────────────────────────────────────────

test('FollowButton toggles a Tag follow for a visitor', function () {
    $tag = Tag::factory()->create();

    Livewire::actingAs($this->visitor)
        ->test(FollowButton::class, ['targetType' => 'tag', 'targetId' => $tag->id])
        ->assertSet('isFollowing', false)
        ->call('toggle')
        ->assertSet('isFollowing', true)
        ->call('toggle')
        ->assertSet('isFollowing', false);
});

test('FollowButton shows count to guests but cannot toggle', function () {
    $author = User::factory()->author()->create();
    AuthorFollow::factory()->count(3)->create(['author_id' => $author->id]);

    Livewire::test(FollowButton::class, ['targetType' => 'author', 'targetId' => $author->id])
        ->assertSet('isFollowing', false)
        ->assertSet('followerCount', 3);
});

// ── Topics page ────────────────────────────────────────────────────────

test('topics page unfollow removes the follow row', function () {
    $tag = Tag::factory()->create();
    TopicFollow::query()->create([
        'user_id' => $this->visitor->id,
        'followable_type' => Tag::class,
        'followable_id' => $tag->id,
    ]);

    Livewire::actingAs($this->visitor)
        ->test(TopicsPage::class)
        ->call('unfollow', TopicFollow::query()->first()->id);

    expect(TopicFollow::query()->count())->toBe(0);
});

test('topics page toggle notify flips notify_on_post', function () {
    $tag = Tag::factory()->create();
    $follow = TopicFollow::query()->create([
        'user_id' => $this->visitor->id,
        'followable_type' => Tag::class,
        'followable_id' => $tag->id,
        'notify_on_post' => true,
    ]);

    Livewire::actingAs($this->visitor)
        ->test(TopicsPage::class)
        ->call('toggleNotify', $follow->id);

    expect($follow->fresh()->notify_on_post)->toBeFalse();
});

// ── Authors page ───────────────────────────────────────────────────────

test('authors page unfollow removes the row', function () {
    $author = User::factory()->author()->create();
    AuthorFollow::query()->create([
        'follower_id' => $this->visitor->id,
        'author_id' => $author->id,
    ]);

    Livewire::actingAs($this->visitor)
        ->test(AuthorsPage::class)
        ->call('unfollow', $author->id);

    expect(AuthorFollow::query()->count())->toBe(0);
});

// ── Users (Readers) page ───────────────────────────────────────────────

test('users page can switch between following and followers tabs', function () {
    Livewire::actingAs($this->visitor)
        ->test(UsersPage::class)
        ->assertSet('direction', 'following')
        ->call('switchDirection', 'followers')
        ->assertSet('direction', 'followers');
});

test('users page unfollow removes the social follow', function () {
    $other = User::factory()->visitor()->create();
    UserFollow::query()->create([
        'follower_id' => $this->visitor->id,
        'followed_id' => $other->id,
    ]);

    Livewire::actingAs($this->visitor)
        ->test(UsersPage::class)
        ->call('unfollow', $other->id);

    expect(UserFollow::query()->count())->toBe(0);
});
