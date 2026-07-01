<?php

declare(strict_types=1);

use App\Actions\Post\ArchivePostAction;
use App\Actions\Post\PublishPostAction;
use App\Actions\Post\SchedulePostAction;
use App\Actions\Post\UnpublishPostAction;
use App\Enums\PostStatus;
use App\Models\Language;
use App\Models\Post;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
});

test('publish moves Approved -> Published and backfills published_at', function (): void {
    $post = Post::factory()->state([
        'status' => PostStatus::Approved,
        'published_at' => null,
    ])->create();

    app(PublishPostAction::class)->handle($post);

    $post->refresh();
    expect($post->status)->toBe(PostStatus::Published);
    expect($post->published_at)->not->toBeNull();
});

test('publish preserves an existing published_at timestamp', function (): void {
    $original = now()->subWeek();
    $post = Post::factory()->state([
        'status' => PostStatus::Approved,
        'published_at' => $original,
    ])->create();

    app(PublishPostAction::class)->handle($post);

    // Compare at second precision — DB datetime columns strip microseconds.
    expect($post->fresh()->published_at->format('Y-m-d H:i:s'))
        ->toBe($original->format('Y-m-d H:i:s'));
});

test('publish refuses Draft without direct-publish flag', function (): void {
    $post = Post::factory()->draft()->create();

    app(PublishPostAction::class)->handle($post);
})->throws(InvalidArgumentException::class);

test('publish accepts Draft when allowDirectPublish=true', function (): void {
    $post = Post::factory()->draft()->create();

    app(PublishPostAction::class)->handle($post, allowDirectPublish: true);

    expect($post->fresh()->status)->toBe(PostStatus::Published);
});

test('publish with cascadeTranslations marks every translation row as published', function (): void {
    $post = Post::factory()->state(['status' => PostStatus::Approved])->create();
    expect($post->translations()->first()->is_published)->toBeFalse();

    app(PublishPostAction::class)->handle($post, cascadeTranslations: true);

    expect($post->fresh()->translations()->first()->is_published)->toBeTrue();
});

test('publish is idempotent on an already-published post', function (): void {
    $post = Post::factory()->published()->create();
    $publishedAt = $post->published_at;

    app(PublishPostAction::class)->handle($post);

    expect($post->fresh()->status)->toBe(PostStatus::Published);
    expect($post->fresh()->published_at->equalTo($publishedAt))->toBeTrue();
});

test('schedule moves Approved -> Scheduled with future timestamp', function (): void {
    $post = Post::factory()->state(['status' => PostStatus::Approved])->create();
    $when = now()->addHours(6);

    app(SchedulePostAction::class)->handle($post, $when);

    $post->refresh();
    expect($post->status)->toBe(PostStatus::Scheduled);
    expect($post->scheduled_at->format('Y-m-d H:i:s'))
        ->toBe($when->format('Y-m-d H:i:s'));
});

test('schedule rejects past timestamps', function (): void {
    $post = Post::factory()->state(['status' => PostStatus::Approved])->create();

    app(SchedulePostAction::class)->handle($post, now()->subHour());
})->throws(ValidationException::class);

test('unpublish moves Published -> Unpublished', function (): void {
    $post = Post::factory()->published()->create();

    app(UnpublishPostAction::class)->handle($post);

    expect($post->fresh()->status)->toBe(PostStatus::Unpublished);
});

test('archive moves Published -> Archived', function (): void {
    $post = Post::factory()->published()->create();

    app(ArchivePostAction::class)->handle($post);

    expect($post->fresh()->status)->toBe(PostStatus::Archived);
});

test('archive is idempotent', function (): void {
    $post = Post::factory()->state(['status' => PostStatus::Archived])->create();

    $result = app(ArchivePostAction::class)->handle($post);

    expect($result->status)->toBe(PostStatus::Archived);
});
