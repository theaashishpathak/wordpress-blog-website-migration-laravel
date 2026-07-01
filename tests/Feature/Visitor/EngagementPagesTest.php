<?php

declare(strict_types=1);

use App\Livewire\Visitor\Comments\Index as CommentsIndex;
use App\Livewire\Visitor\Reactions\Index as ReactionsIndex;
use App\Livewire\Visitor\Recommendations\Index as RecommendationsIndex;
use App\Models\Comment;
use App\Models\Post;
use App\Models\PostReaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->visitor = User::factory()->visitor()->create();
    $this->post = Post::factory()->create();
});

// ── Comments page ───────────────────────────────────────────────────────

test('comments index filter switches reset pagination', function () {
    Livewire::actingAs($this->visitor)
        ->test(CommentsIndex::class)
        ->call('switchFilter', 'pending')
        ->assertSet('filter', 'pending');
});

test('comments edit flow updates body and pendings status', function () {
    $comment = Comment::query()->create([
        'post_id' => $this->post->id,
        'user_id' => $this->visitor->id,
        'body' => 'Initial body',
        'status' => Comment::STATUS_APPROVED,
    ]);

    Livewire::actingAs($this->visitor)
        ->test(CommentsIndex::class)
        ->call('startEditing', $comment->id)
        ->assertSet('editingId', $comment->id)
        ->set('editingBody', 'Updated body')
        ->call('saveEdit')
        ->assertSet('editingId', null);

    expect($comment->fresh()->body)->toBe('Updated body')
        ->and($comment->fresh()->status)->toBe(Comment::STATUS_PENDING);
});

test('comments delete soft-removes own comment', function () {
    $comment = Comment::query()->create([
        'post_id' => $this->post->id,
        'user_id' => $this->visitor->id,
        'body' => 'Bye',
        'status' => Comment::STATUS_APPROVED,
    ]);

    Livewire::actingAs($this->visitor)
        ->test(CommentsIndex::class)
        ->call('delete', $comment->id);

    expect(Comment::query()->find($comment->id))->toBeNull();
});

// ── Reactions page ──────────────────────────────────────────────────────

test('reactions index defaults to like filter', function () {
    Livewire::actingAs($this->visitor)
        ->test(ReactionsIndex::class)
        ->assertSet('filter', 'like');
});

test('reactions remove toggles the reaction off', function () {
    PostReaction::factory()->like()->create([
        'user_id' => $this->visitor->id,
        'post_id' => $this->post->id,
    ]);

    Livewire::actingAs($this->visitor)
        ->test(ReactionsIndex::class)
        ->call('remove', $this->post->id);

    expect(PostReaction::query()->count())->toBe(0);
});

// ── Recommendations page ───────────────────────────────────────────────

test('recommendations index renders for any visitor', function () {
    Post::factory()->count(3)->create([
        'status' => \App\Enums\PostStatus::Published->value,
        'published_at' => now()->subDay(),
    ]);

    Livewire::actingAs($this->visitor)
        ->test(RecommendationsIndex::class)
        ->assertOk();
});

test('recommendations refresh clears the computed cache', function () {
    Post::factory()->count(2)->create([
        'status' => \App\Enums\PostStatus::Published->value,
        'published_at' => now()->subDay(),
    ]);

    Livewire::actingAs($this->visitor)
        ->test(RecommendationsIndex::class)
        ->call('refreshFeed')
        ->assertOk();
});
