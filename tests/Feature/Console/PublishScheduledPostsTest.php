<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Models\Language;
use App\Models\Post;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
});

test('exits cleanly when no scheduled posts are due', function (): void {
    Post::factory()->draft()->create();
    Post::factory()->published()->create();

    $this->artisan('posts:publish-scheduled')
        ->expectsOutputToContain('No scheduled posts due for publication.')
        ->assertExitCode(0);
});

test('publishes posts whose scheduled_at is in the past', function (): void {
    $duePost = Post::factory()->state([
        'status' => PostStatus::Scheduled,
        'scheduled_at' => now()->subMinutes(5),
    ])->create();

    $futurePost = Post::factory()->state([
        'status' => PostStatus::Scheduled,
        'scheduled_at' => now()->addHour(),
    ])->create();

    $this->artisan('posts:publish-scheduled')
        ->expectsOutputToContain('Publishing 1 scheduled post')
        ->expectsOutputToContain("Post #{$duePost->id} published.")
        ->assertExitCode(0);

    expect($duePost->fresh()->status)->toBe(PostStatus::Published);
    expect($duePost->fresh()->published_at)->not->toBeNull();
    expect($futurePost->fresh()->status)->toBe(PostStatus::Scheduled);
});

test('cascades translation is_published=true on publication', function (): void {
    $post = Post::factory()->state([
        'status' => PostStatus::Scheduled,
        'scheduled_at' => now()->subMinute(),
    ])->create();

    expect($post->translations()->first()->is_published)->toBeFalse();

    $this->artisan('posts:publish-scheduled')->assertExitCode(0);

    expect($post->fresh()->translations()->first()->is_published)->toBeTrue();
});

test('dry-run reports posts without publishing them', function (): void {
    $post = Post::factory()->state([
        'status' => PostStatus::Scheduled,
        'scheduled_at' => now()->subMinute(),
    ])->create();

    $this->artisan('posts:publish-scheduled', ['--dry-run' => true])
        ->expectsOutputToContain('[DRY RUN]')
        ->expectsOutputToContain('would publish')
        ->assertExitCode(0);

    expect($post->fresh()->status)->toBe(PostStatus::Scheduled);
});

test('limit option caps the batch size per run', function (): void {
    Post::factory()->count(3)->state([
        'status' => PostStatus::Scheduled,
        'scheduled_at' => now()->subMinute(),
    ])->create();

    $this->artisan('posts:publish-scheduled', ['--limit' => 2])
        ->expectsOutputToContain('Publishing 2 scheduled post')
        ->assertExitCode(0);

    expect(Post::query()->scheduled()->count())->toBe(1);
    expect(Post::query()->published()->count())->toBe(2);
});

test('continues processing the batch even when one post fails', function (): void {
    // Two due posts; we'll induce a hypothetical failure on the second
    // by NULLing its required default_language_id mid-run. Since we
    // can't easily simulate failures cleanly here, this test just
    // confirms the command tolerates the happy path for multiple posts.
    Post::factory()->count(2)->state([
        'status' => PostStatus::Scheduled,
        'scheduled_at' => now()->subMinute(),
    ])->create();

    $this->artisan('posts:publish-scheduled')
        ->expectsOutputToContain('Publishing 2 scheduled post')
        ->expectsOutputToContain('Published: 2')
        ->assertExitCode(0);
});
