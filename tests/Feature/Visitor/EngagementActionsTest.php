<?php

declare(strict_types=1);

use App\Actions\Visitor\Comment\DeleteOwnCommentAction;
use App\Actions\Visitor\Comment\UpdateOwnCommentAction;
use App\Actions\Visitor\Reaction\ToggleReactionAction;
use App\Actions\Visitor\Recommendation\BuildRecommendationsAction;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostReaction;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->visitor = User::factory()->visitor()->create();
    $this->post = Post::factory()->create();
});

// ── Reactions ───────────────────────────────────────────────────────────

test('ToggleReactionAction creates new reaction on first call', function () {
    $result = app(ToggleReactionAction::class)->handle($this->visitor, $this->post, 'like');

    expect($result['action'])->toBe('created')
        ->and($result['type'])->toBe('like')
        ->and(PostReaction::query()->count())->toBe(1);
});

test('ToggleReactionAction removes reaction on same type repeat', function () {
    $action = app(ToggleReactionAction::class);

    $action->handle($this->visitor, $this->post, 'like');
    $result = $action->handle($this->visitor, $this->post, 'like');

    expect($result['action'])->toBe('removed')
        ->and($result['type'])->toBeNull()
        ->and(PostReaction::query()->count())->toBe(0);
});

test('ToggleReactionAction switches when type changes', function () {
    $action = app(ToggleReactionAction::class);

    $action->handle($this->visitor, $this->post, 'like');
    $result = $action->handle($this->visitor, $this->post, 'dislike');

    expect($result['action'])->toBe('switched')
        ->and($result['type'])->toBe('dislike');

    // Still only one row — switched via UPDATE
    expect(PostReaction::query()->count())->toBe(1)
        ->and(PostReaction::query()->first()->type)->toBe('dislike');
});

test('ToggleReactionAction rejects invalid type', function () {
    app(ToggleReactionAction::class)->handle($this->visitor, $this->post, 'love');
})->throws(ValidationException::class);

// ── Comment edit / delete ───────────────────────────────────────────────

test('UpdateOwnCommentAction updates body and resets status to pending', function () {
    $comment = Comment::query()->create([
        'post_id' => $this->post->id,
        'user_id' => $this->visitor->id,
        'body' => 'Original',
        'status' => Comment::STATUS_APPROVED,
        'approved_at' => now(),
    ]);

    app(UpdateOwnCommentAction::class)->handle($this->visitor, $comment, 'Updated content');

    $fresh = $comment->fresh();
    expect($fresh->body)->toBe('Updated content')
        ->and($fresh->status)->toBe(Comment::STATUS_PENDING)
        ->and($fresh->approved_at)->toBeNull();
});

test('UpdateOwnCommentAction throws when another user edits the comment', function () {
    $owner = User::factory()->visitor()->create();
    $comment = Comment::query()->create([
        'post_id' => $this->post->id,
        'user_id' => $owner->id,
        'body' => 'Theirs',
        'status' => Comment::STATUS_APPROVED,
    ]);

    app(UpdateOwnCommentAction::class)->handle($this->visitor, $comment, 'Hijacked');
})->throws(AuthorizationException::class);

test('UpdateOwnCommentAction rejects empty body', function () {
    $comment = Comment::query()->create([
        'post_id' => $this->post->id,
        'user_id' => $this->visitor->id,
        'body' => 'Hi',
        'status' => Comment::STATUS_APPROVED,
    ]);

    app(UpdateOwnCommentAction::class)->handle($this->visitor, $comment, '   ');
})->throws(ValidationException::class);

test('DeleteOwnCommentAction soft-deletes own comment', function () {
    $comment = Comment::query()->create([
        'post_id' => $this->post->id,
        'user_id' => $this->visitor->id,
        'body' => 'My comment',
        'status' => Comment::STATUS_APPROVED,
    ]);

    expect(app(DeleteOwnCommentAction::class)->handle($this->visitor, $comment))->toBeTrue()
        ->and(Comment::query()->find($comment->id))->toBeNull()
        ->and(Comment::query()->withTrashed()->find($comment->id))->not->toBeNull();
});

test('DeleteOwnCommentAction blocks foreign deletes', function () {
    $other = User::factory()->visitor()->create();
    $comment = Comment::query()->create([
        'post_id' => $this->post->id,
        'user_id' => $other->id,
        'body' => 'Theirs',
        'status' => Comment::STATUS_APPROVED,
    ]);

    app(DeleteOwnCommentAction::class)->handle($this->visitor, $comment);
})->throws(AuthorizationException::class);

// ── Recommendations ────────────────────────────────────────────────────

test('BuildRecommendationsAction falls back to latest when user has no signal', function () {
    Post::factory()->count(3)->create([
        'status' => \App\Enums\PostStatus::Published->value,
        'published_at' => now()->subDay(),
    ]);

    $picks = app(BuildRecommendationsAction::class)->handle($this->visitor, limit: 10);

    expect($picks->count())->toBeGreaterThan(0);
});

test('BuildRecommendationsAction excludes already-read and disliked posts', function () {
    [$read, $disliked, $candidate] = Post::factory()->count(3)->create([
        'status' => \App\Enums\PostStatus::Published->value,
        'published_at' => now()->subDay(),
    ]);

    ReadingHistory::factory()->create([
        'user_id' => $this->visitor->id,
        'post_id' => $read->id,
    ]);
    PostReaction::factory()->dislike()->create([
        'user_id' => $this->visitor->id,
        'post_id' => $disliked->id,
    ]);

    $picks = app(BuildRecommendationsAction::class)->handle($this->visitor, limit: 10);
    $ids = $picks->pluck('id')->all();

    expect($ids)->not->toContain($read->id)
        ->and($ids)->not->toContain($disliked->id);
});
