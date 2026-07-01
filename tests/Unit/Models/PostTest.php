<?php

declare(strict_types=1);

use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Category;
use App\Models\Language;
use App\Models\Post;
use App\Models\SeoMeta;
use App\Models\Tag;
use App\Models\User;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    Language::factory()->bangla()->create();
    app(LocaleResolver::class)->flush();
});

test('factory creates a draft post with default-language translation', function (): void {
    $post = Post::factory()->create();

    expect($post)->toBeInstanceOf(Post::class);
    expect($post->type)->toBe(PostType::Post);
    expect($post->status)->toBe(PostStatus::Draft);
    expect($post->translations()->count())->toBe(1);
    expect($post->translate('title'))->toBeString()->not->toBeEmpty();
});

test('published scope returns only Published with published_at <= now', function (): void {
    Post::factory()->draft()->create();
    Post::factory()->published()->create();
    Post::factory()->published()->state(['published_at' => now()->addDays(1)])->create();

    expect(Post::query()->published()->count())->toBe(1);
});

test('breaking scope respects expiry timestamp', function (): void {
    Post::factory()->breaking(6)->create();
    Post::factory()->published()->state([
        'is_breaking' => true,
        'breaking_expires_at' => now()->subHour(),
    ])->create();
    Post::factory()->draft()->state(['is_breaking' => true])->create();

    expect(Post::query()->breaking()->count())->toBe(1);
});

test('ofType scope filters by enum or string', function (): void {
    Post::factory()->ofType(PostType::News)->create();
    Post::factory()->ofType(PostType::Video)->create();
    Post::factory()->create();   // default: Post

    expect(Post::query()->ofType(PostType::News)->count())->toBe(1);
    expect(Post::query()->ofType('video')->count())->toBe(1);
    expect(Post::query()->ofType(PostType::Post)->count())->toBe(1);
});

test('featured, trending, editorsPick scopes filter correctly', function (): void {
    Post::factory()->featured()->create();
    Post::factory()->trending()->create();
    Post::factory()->editorsPick()->create();
    Post::factory()->create();

    expect(Post::query()->featured()->count())->toBe(1);
    expect(Post::query()->trending()->count())->toBe(1);
    expect(Post::query()->editorsPick()->count())->toBe(1);
});

test('byCategory and byAuthor scopes work', function (): void {
    $cat = Category::factory()->create();
    $author = User::factory()->create();

    Post::factory()->withCategory($cat->id)->create();
    Post::factory()->withCategory($cat->id)->create();
    Post::factory()->create();
    Post::factory()->withAuthor($author->id)->create();

    expect(Post::query()->byCategory($cat->id)->count())->toBe(2);
    expect(Post::query()->byAuthor($author->id)->count())->toBe(1);
});

test('visibleIn scope requires published post AND published translation', function (): void {
    $english = Language::query()->where('code', 'en')->firstOrFail();
    $bangla = Language::query()->where('code', 'bn')->firstOrFail();

    // Post 1: published, with default-language (en) translation published
    $a = Post::factory()->published()->create();

    // Post 2: published, with en translation published, bn translation draft
    $b = Post::factory()->published()->create();
    $b->translations()->create([
        'language_id' => $bangla->id,
        'title' => 'খবর',
        'slug' => 'khobor-'.uniqid(),
        'is_published' => false,
    ]);

    // Post 3: draft post — invisible everywhere
    Post::factory()->draft()->create();

    expect(Post::query()->visibleIn($english->id)->pluck('id')->all())
        ->toContain($a->id)
        ->toContain($b->id);

    expect(Post::query()->visibleIn($bangla->id)->pluck('id')->all())
        ->not->toContain($a->id)
        ->not->toContain($b->id);
});

test('isOwnedBy returns true when author matches user', function (): void {
    $author = User::factory()->create();
    $other = User::factory()->create();
    $post = Post::factory()->withAuthor($author->id)->create();

    expect($post->isOwnedBy($author))->toBeTrue();
    expect($post->isOwnedBy($other))->toBeFalse();
});

test('urlFor builds locale + category + slug paths', function (): void {
    $category = Category::factory()->create();
    $category->translations()->first()->update(['slug' => 'technology']);

    $post = Post::factory()->withCategory($category->id)->create();
    $post->translations()->first()->update(['slug' => 'ai-tools-2026']);

    expect($post->urlFor('en'))->toBe('/en/technology/ai-tools-2026');
});

test('seoMetas relation is polymorphic and persists schema_data array', function (): void {
    $post = Post::factory()->create();

    $post->seoMetas()->create([
        'language_id' => $post->default_language_id,
        'schema_type' => SeoMeta::SCHEMA_NEWS_ARTICLE,
        'schema_data' => ['headline' => 'Test', 'author' => 'Jubayer'],
        'robots' => 'index,follow',
    ]);

    $loaded = $post->seoMetas()->first();
    expect($loaded->schema_type)->toBe(SeoMeta::SCHEMA_NEWS_ARTICLE);
    expect($loaded->schema_data)->toBe(['headline' => 'Test', 'author' => 'Jubayer']);
});

test('tags many-to-many attaches and detaches via pivot', function (): void {
    $post = Post::factory()->create();
    $tag1 = Tag::factory()->create();
    $tag2 = Tag::factory()->create();

    $post->tags()->sync([$tag1->id, $tag2->id]);

    expect($post->fresh()->tags->pluck('id')->all())->toContain($tag1->id, $tag2->id);

    $post->tags()->detach($tag1->id);
    expect($post->fresh()->tags->pluck('id')->all())->toContain($tag2->id);
    expect($post->fresh()->tags->pluck('id')->all())->not->toContain($tag1->id);
});

test('translation fallback uses default_language_id when locale missing', function (): void {
    $bangla = Language::query()->where('code', 'bn')->firstOrFail();

    $post = Post::factory()->create();
    expect($post->translate('title', 'en'))->not->toBeNull();

    // Missing Bangla translation falls back to English (default_language_id).
    $englishTitle = $post->translate('title', 'en');
    expect($post->translate('title', 'bn'))->toBe($englishTitle);
});

test('isBreakingActive requires published + flag + non-expired', function (): void {
    $a = Post::factory()->breaking(6)->create();
    $b = Post::factory()->draft()->state(['is_breaking' => true])->create();
    $c = Post::factory()->published()->state([
        'is_breaking' => true,
        'breaking_expires_at' => now()->subMinute(),
    ])->create();

    expect($a->isBreakingActive())->toBeTrue();
    expect($b->isBreakingActive())->toBeFalse();
    expect($c->isBreakingActive())->toBeFalse();
});

test('isScheduled returns true only for Scheduled posts with future scheduled_at', function (): void {
    $futureScheduled = Post::factory()->scheduled(now()->addDay())->create();
    $pastScheduled = Post::factory()->scheduled()->state(['scheduled_at' => now()->subHour()])->create();

    expect($futureScheduled->isScheduled())->toBeTrue();
    expect($pastScheduled->isScheduled())->toBeFalse();
});
