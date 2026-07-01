<?php

declare(strict_types=1);

use App\Actions\Post\CreatePostRevisionAction;
use App\Actions\Post\UpdatePostAction;
use App\Models\Language;
use App\Models\Post;
use App\Models\PostRevision;
use App\Models\Tag;
use App\Models\User;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    Language::factory()->bangla()->create();
    $this->user = User::factory()->create();
    app(LocaleResolver::class)->flush();
});

test('creates revision #1 for a post with no prior history', function (): void {
    $post = Post::factory()->create();
    $tag = Tag::factory()->create();
    $post->tags()->attach($tag->id, ['created_at' => now()]);

    $revision = app(CreatePostRevisionAction::class)->handle($post, $this->user->id, 'first edit');

    expect($revision)->toBeInstanceOf(PostRevision::class);
    expect($revision->revision_number)->toBe(1);
    expect($revision->author_id)->toBe($this->user->id);
    expect($revision->summary)->toBe('first edit');

    expect($revision->snapshot)->toHaveKey('post');
    expect($revision->snapshot)->toHaveKey('translations');
    expect($revision->snapshot)->toHaveKey('tag_ids');
    expect($revision->snapshot['tag_ids'])->toContain($tag->id);
});

test('increments revision_number on subsequent snapshots', function (): void {
    $post = Post::factory()->create();

    app(CreatePostRevisionAction::class)->handle($post);
    app(CreatePostRevisionAction::class)->handle($post);
    app(CreatePostRevisionAction::class)->handle($post);

    expect($post->revisions()->count())->toBe(3);
    expect($post->revisions()->orderByDesc('revision_number')->first()->revision_number)->toBe(3);
});

test('snapshot captures translation fields', function (): void {
    $post = Post::factory()->create();
    $post->translations()->first()->update(['title' => 'Snapshot Title']);

    $revision = app(CreatePostRevisionAction::class)->handle($post->fresh());

    expect($revision->snapshot['translations'])->toBeArray();
    expect($revision->snapshot['translations'][0]['title'])->toBe('Snapshot Title');
});

test('UpdatePostAction auto-creates a revision before mutating', function (): void {
    $post = Post::factory()->create();
    expect($post->revisions()->count())->toBe(0);

    app(UpdatePostAction::class)->handle($post, [
        'is_featured' => true,
        'revision_summary' => 'marked as featured',
    ]);

    $post->refresh();
    expect($post->revisions()->count())->toBe(1);
    expect($post->revisions()->first()->summary)->toBe('marked as featured');
    expect($post->is_featured)->toBeTrue();
});

test('UpdatePostAction with skip_revision flag does NOT create a revision', function (): void {
    $post = Post::factory()->create();

    app(UpdatePostAction::class)->handle($post, [
        'is_featured' => true,
        'skip_revision' => true,
    ]);

    expect($post->fresh()->revisions()->count())->toBe(0);
});

test('snapshot preserves the pre-edit state, not the post-edit state', function (): void {
    $post = Post::factory()->create();
    $post->translations()->first()->update(['title' => 'Before Edit']);
    expect($post->fresh()->translate('title'))->toBe('Before Edit');

    app(UpdatePostAction::class)->handle($post->fresh(), [
        'translations' => [
            ['language_id' => $this->english->id, 'title' => 'After Edit', 'slug' => 'after-edit'],
        ],
    ]);

    $latestRevision = $post->fresh()->revisions()->first();
    expect($latestRevision->snapshot['translations'][0]['title'])->toBe('Before Edit');
    expect($post->fresh()->translate('title'))->toBe('After Edit');
});
