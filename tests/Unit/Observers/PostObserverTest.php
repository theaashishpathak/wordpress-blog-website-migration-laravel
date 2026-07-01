<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Models\Language;
use App\Models\Post;
use App\Models\User;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
});

test('published_at is auto-backfilled when status transitions to Published', function (): void {
    $post = Post::factory()->draft()->state(['published_at' => null])->create();
    expect($post->published_at)->toBeNull();

    $post->forceFill(['status' => PostStatus::Published->value])->save();

    expect($post->fresh()->published_at)->not->toBeNull();
});

test('published_at is NOT overwritten when already set', function (): void {
    $original = now()->subMonth();
    $post = Post::factory()->state([
        'status' => PostStatus::Approved,
        'published_at' => $original,
    ])->create();

    $post->forceFill(['status' => PostStatus::Published->value])->save();

    // Compare at second precision — DB datetime columns strip microseconds.
    expect($post->fresh()->published_at->format('Y-m-d H:i:s'))
        ->toBe($original->format('Y-m-d H:i:s'));
});

test('updated_by is stamped from authenticated user on save', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $post = Post::factory()->draft()->create();
    $post->is_featured = true;
    $post->save();

    expect($post->fresh()->updated_by)->toBe($user->id);
});

test('updated_by is not stamped when running unauthenticated (console / queue)', function (): void {
    auth()->logout();

    $post = Post::factory()->state(['updated_by' => null])->create();

    $post->is_trending = true;
    $post->save();

    expect($post->fresh()->updated_by)->toBeNull();
});

test('explicit updated_by setting is not overwritten by the observer', function (): void {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $this->actingAs($a);

    $post = Post::factory()->create();
    $post->forceFill(['updated_by' => $b->id, 'is_featured' => true])->save();

    expect($post->fresh()->updated_by)->toBe($b->id);
});
