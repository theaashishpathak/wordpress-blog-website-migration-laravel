<?php

declare(strict_types=1);

use App\Actions\Post\UpdatePostAction;
use App\Enums\PostStatus;
use App\Models\Language;
use App\Models\Post;
use App\Models\Tag;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    $this->bangla = Language::factory()->bangla()->create();
    app(LocaleResolver::class)->flush();
});

test('updates structural flags without touching translations', function (): void {
    $post = Post::factory()->create();
    $translationCount = $post->translations()->count();

    app(UpdatePostAction::class)->handle($post, [
        'is_featured' => true,
        'is_trending' => true,
        'visibility' => Post::VISIBILITY_PRIVATE,
    ]);

    $post->refresh();
    expect($post->is_featured)->toBeTrue();
    expect($post->is_trending)->toBeTrue();
    expect($post->visibility)->toBe(Post::VISIBILITY_PRIVATE);
    expect($post->translations()->count())->toBe($translationCount);
});

test('upserts a new locale translation', function (): void {
    $post = Post::factory()->create();

    app(UpdatePostAction::class)->handle($post, [
        'translations' => [
            ['language_id' => $this->bangla->id, 'title' => 'বাংলা', 'content' => 'BN body', 'slug' => 'ba-body'],
        ],
    ]);

    $post->refresh();
    expect($post->translations()->count())->toBe(2);
    expect($post->translate('title', 'bn'))->toBe('বাংলা');
});

test('updates an existing translation in place without recreating the row', function (): void {
    $post = Post::factory()->create();
    $originalId = $post->translation('en')->id;

    app(UpdatePostAction::class)->handle($post, [
        'translations' => [
            ['language_id' => $this->english->id, 'title' => 'Updated', 'content' => 'New body'],
        ],
    ]);

    expect($post->translation('en')->id)->toBe($originalId);
    expect($post->fresh()->translate('title', 'en'))->toBe('Updated');
});

test('status changes via update() are silently ignored', function (): void {
    $post = Post::factory()->draft()->create();

    app(UpdatePostAction::class)->handle($post, [
        'status' => PostStatus::Published,
    ]);

    expect($post->fresh()->status)->toBe(PostStatus::Draft);
});

test('tag_ids triggers full sync', function (): void {
    $post = Post::factory()->create();
    $tag1 = Tag::factory()->create();
    $tag2 = Tag::factory()->create();
    $tag3 = Tag::factory()->create();

    $post->tags()->sync([$tag1->id]);
    expect($post->fresh()->tags->pluck('id')->all())->toBe([$tag1->id]);

    app(UpdatePostAction::class)->handle($post, [
        'tag_ids' => [$tag2->id, $tag3->id],
    ]);

    $tags = $post->fresh()->tags->pluck('id')->all();
    expect($tags)->toContain($tag2->id, $tag3->id);
    expect($tags)->not->toContain($tag1->id);
});

test('refuses to delete the only remaining translation', function (): void {
    $post = Post::factory()->create();

    app(UpdatePostAction::class)->handle($post, [
        'translations' => [
            ['language_id' => $this->english->id, 'delete' => true],
        ],
    ]);

    expect($post->fresh()->translations()->count())->toBe(1);
});
