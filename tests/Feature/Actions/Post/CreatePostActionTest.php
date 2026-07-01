<?php

declare(strict_types=1);

use App\Actions\Post\CreatePostAction;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Category;
use App\Models\Language;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    $this->bangla = Language::factory()->bangla()->create();
    $this->author = User::factory()->create();
    app(LocaleResolver::class)->flush();
});

test('creates a draft post with a single default-language translation', function (): void {
    $post = app(CreatePostAction::class)->handle([
        'author_id' => $this->author->id,
        'translations' => [
            ['language_id' => $this->english->id, 'title' => 'AI Tools 2026', 'content' => 'Body here.'],
        ],
    ]);

    expect($post)->toBeInstanceOf(Post::class);
    expect($post->status)->toBe(PostStatus::Draft);
    expect($post->type)->toBe(PostType::Post);
    expect($post->author_id)->toBe($this->author->id);
    expect($post->default_language_id)->toBe($this->english->id);
    expect($post->translate('title', 'en'))->toBe('AI Tools 2026');
});

test('creates with category + multiple translations + tags in one transaction', function (): void {
    $category = Category::factory()->create();
    $tag1 = Tag::factory()->create();
    $tag2 = Tag::factory()->create();

    $post = app(CreatePostAction::class)->handle([
        'author_id' => $this->author->id,
        'type' => PostType::News,
        'category_id' => $category->id,
        'translations' => [
            ['language_id' => $this->english->id, 'title' => 'Breaking story', 'content' => 'EN body'],
            ['language_id' => $this->bangla->id, 'title' => 'খবর', 'content' => 'BN body', 'slug' => 'khobor-test'],
        ],
        'tag_ids' => [$tag1->id, $tag2->id],
    ]);

    expect($post->type)->toBe(PostType::News);
    expect($post->category_id)->toBe($category->id);
    expect($post->translations()->count())->toBe(2);
    expect($post->tags()->pluck('tags.id')->all())->toContain($tag1->id, $tag2->id);
    expect($post->translate('title', 'bn'))->toBe('খবর');
});

test('rejects empty translations', function (): void {
    app(CreatePostAction::class)->handle([
        'author_id' => $this->author->id,
        'translations' => [],
    ]);
})->throws(ValidationException::class, 'At least one translation is required.');

test('rejects translation without title', function (): void {
    app(CreatePostAction::class)->handle([
        'author_id' => $this->author->id,
        'translations' => [
            ['language_id' => $this->english->id, 'title' => '   '],
        ],
    ]);
})->throws(ValidationException::class);

test('rejects missing default-language translation', function (): void {
    app(CreatePostAction::class)->handle([
        'author_id' => $this->author->id,
        'translations' => [
            ['language_id' => $this->bangla->id, 'title' => 'খবর'],
        ],
    ]);
})->throws(ValidationException::class, "default language");

test('rejects duplicate slug within request', function (): void {
    app(CreatePostAction::class)->handle([
        'author_id' => $this->author->id,
        'translations' => [
            ['language_id' => $this->english->id, 'title' => 'A', 'slug' => 'same'],
            ['language_id' => $this->english->id, 'title' => 'B', 'slug' => 'same'],
        ],
    ]);
})->throws(ValidationException::class, 'Duplicate slug');

test('requires author_id', function (): void {
    app(CreatePostAction::class)->handle([
        'translations' => [
            ['language_id' => $this->english->id, 'title' => 'A'],
        ],
    ]);
})->throws(ValidationException::class, 'author_id is required');

test('honours flag inputs (featured, breaking, etc.)', function (): void {
    $post = app(CreatePostAction::class)->handle([
        'author_id' => $this->author->id,
        'is_featured' => true,
        'is_breaking' => true,
        'is_trending' => true,
        'is_editors_pick' => true,
        'is_premium' => true,
        'allow_comments' => false,
        'translations' => [
            ['language_id' => $this->english->id, 'title' => 'Hot'],
        ],
    ]);

    expect($post->is_featured)->toBeTrue();
    expect($post->is_breaking)->toBeTrue();
    expect($post->is_trending)->toBeTrue();
    expect($post->is_editors_pick)->toBeTrue();
    expect($post->is_premium)->toBeTrue();
    expect($post->allow_comments)->toBeFalse();
});
