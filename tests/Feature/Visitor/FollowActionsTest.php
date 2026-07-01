<?php

declare(strict_types=1);

use App\Actions\Visitor\Follow\ToggleAuthorFollowAction;
use App\Actions\Visitor\Follow\ToggleTopicFollowAction;
use App\Actions\Visitor\Follow\ToggleUserFollowAction;
use App\Models\AuthorFollow;
use App\Models\Category;
use App\Models\Tag;
use App\Models\TopicFollow;
use App\Models\User;
use App\Models\UserFollow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->visitor = User::factory()->visitor()->create();
});

// ── Topic follows ──────────────────────────────────────────────────────

test('ToggleTopicFollowAction creates a Tag follow', function () {
    $tag = Tag::factory()->create();

    expect(app(ToggleTopicFollowAction::class)->handle($this->visitor, $tag))->toBeTrue()
        ->and(TopicFollow::query()->count())->toBe(1);
});

test('ToggleTopicFollowAction removes a follow on second call', function () {
    $tag = Tag::factory()->create();
    $action = app(ToggleTopicFollowAction::class);

    $action->handle($this->visitor, $tag);
    expect($action->handle($this->visitor, $tag))->toBeFalse()
        ->and(TopicFollow::query()->count())->toBe(0);
});

test('ToggleTopicFollowAction works with Categories too', function () {
    $category = Category::factory()->create();

    expect(app(ToggleTopicFollowAction::class)->handle($this->visitor, $category))->toBeTrue()
        ->and(TopicFollow::query()->where('followable_type', Category::class)->count())->toBe(1);
});

test('ToggleTopicFollowAction rejects unsupported models', function () {
    $randomUser = User::factory()->create();

    app(ToggleTopicFollowAction::class)->handle($this->visitor, $randomUser);
})->throws(ValidationException::class);

// ── Author follows ─────────────────────────────────────────────────────

test('ToggleAuthorFollowAction creates follow', function () {
    $author = User::factory()->author()->create();

    expect(app(ToggleAuthorFollowAction::class)->handle($this->visitor, $author))->toBeTrue()
        ->and(AuthorFollow::query()->count())->toBe(1);
});

test('ToggleAuthorFollowAction blocks self-follow', function () {
    app(ToggleAuthorFollowAction::class)->handle($this->visitor, $this->visitor);
})->throws(ValidationException::class);

// ── User follows ───────────────────────────────────────────────────────

test('ToggleUserFollowAction creates and removes social follows', function () {
    $other = User::factory()->visitor()->create();
    $action = app(ToggleUserFollowAction::class);

    expect($action->handle($this->visitor, $other))->toBeTrue()
        ->and(UserFollow::query()->count())->toBe(1);

    expect($action->handle($this->visitor, $other))->toBeFalse()
        ->and(UserFollow::query()->count())->toBe(0);
});

test('ToggleUserFollowAction blocks self-follow', function () {
    app(ToggleUserFollowAction::class)->handle($this->visitor, $this->visitor);
})->throws(ValidationException::class);
