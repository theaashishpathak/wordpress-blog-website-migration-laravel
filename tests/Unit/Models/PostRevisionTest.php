<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\Post;
use App\Models\PostRevision;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
});

test('forPost scope filters revisions by post id', function (): void {
    $a = Post::factory()->create();
    $b = Post::factory()->create();

    PostRevision::factory()->forPost($a)->create();
    PostRevision::factory()->forPost($a, 2)->create();
    PostRevision::factory()->forPost($b)->create();

    expect(PostRevision::query()->forPost($a->id)->count())->toBe(2);
    expect(PostRevision::query()->forPost($b->id)->count())->toBe(1);
});

test('latestRevision and oldestRevision scopes order correctly', function (): void {
    $post = Post::factory()->create();

    PostRevision::factory()->forPost($post, 1)->create();
    PostRevision::factory()->forPost($post, 3)->create();
    PostRevision::factory()->forPost($post, 2)->create();

    expect(PostRevision::query()->forPost($post->id)->latestRevision()->first()->revision_number)
        ->toBe(3);
    expect(PostRevision::query()->forPost($post->id)->oldestRevision()->first()->revision_number)
        ->toBe(1);
});

test('snapshot is stored and retrieved as array', function (): void {
    $rev = PostRevision::factory()->withSnapshot([
        'post' => ['status' => 'draft', 'is_featured' => true],
        'translations' => [['title' => 'Old Title']],
        'tag_ids' => [1, 2, 3],
    ])->create();

    $loaded = PostRevision::query()->find($rev->id);
    expect($loaded->snapshot)->toBeArray();
    expect($loaded->snapshot)->toHaveKey('post');
    expect($loaded->snapshot['post']['status'])->toBe('draft');
    expect($loaded->snapshot['tag_ids'])->toBe([1, 2, 3]);
});

test('snapshotValue helper reads nested keys via dot notation', function (): void {
    $rev = PostRevision::factory()->withSnapshot([
        'post' => ['status' => 'published'],
        'translations' => [['title' => 'Hello']],
    ])->create();

    expect($rev->snapshotValue('post.status'))->toBe('published');
    expect($rev->snapshotValue('translations.0.title'))->toBe('Hello');
    expect($rev->snapshotValue('post.missing', 'fallback'))->toBe('fallback');
});

test('unique constraint prevents duplicate (post_id, revision_number) rows', function (): void {
    $post = Post::factory()->create();

    PostRevision::factory()->forPost($post, 1)->create();

    $this->expectException(\Illuminate\Database\QueryException::class);

    PostRevision::factory()->forPost($post, 1)->create();
});

test('revisions are immutable — no updated_at column', function (): void {
    $rev = PostRevision::factory()->create();

    expect($rev->timestamps)->toBeFalse();
});
