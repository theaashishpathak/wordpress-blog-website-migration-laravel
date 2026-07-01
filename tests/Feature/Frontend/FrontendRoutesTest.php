<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Language;
use App\Models\Page;
use App\Models\Post;
use App\Models\User;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
});

// -------------------------------------------------------------------------
// Routes
// -------------------------------------------------------------------------

test('homepage returns 200 with no locale prefix', function (): void {
    $this->get('/')->assertOk();
});

test('homepage returns 200 with locale prefix', function (): void {
    $this->get('/en')->assertOk();
});

test('single post URL returns 200 for a published post', function (): void {
    $post = Post::factory()->published()->create();
    $post->translations()->first()->update(['title' => 'Public Article', 'slug' => 'public-article-route-test']);

    $this->get('/en/public-article-route-test')->assertOk()->assertSee('Public Article');
});

test('single post URL 404s for a draft post', function (): void {
    $draft = Post::factory()->draft()->create();
    $draft->translations()->first()->update(['slug' => 'unpublished-slug-test']);

    $this->get('/en/unpublished-slug-test')->assertNotFound();
});

test('single post URL 404s for a future-scheduled post', function (): void {
    $future = Post::factory()->published()->state(['published_at' => now()->addDays(2)])->create();
    $future->translations()->first()->update(['slug' => 'future-slug-test']);

    $this->get('/en/future-slug-test')->assertNotFound();
});

test('post URL increments view count on first visit', function (): void {
    $post = Post::factory()->published()->state(['view_count' => 5])->create();
    $post->translations()->first()->update(['slug' => 'view-count-test']);

    $this->get('/en/view-count-test')->assertOk();

    expect($post->fresh()->view_count)->toBe(6);
});

test('post URL does not double-count on repeated visits in same session', function (): void {
    $post = Post::factory()->published()->state(['view_count' => 5])->create();
    $post->translations()->first()->update(['slug' => 'view-count-debounce-test']);

    $this->get('/en/view-count-debounce-test');
    $this->get('/en/view-count-debounce-test');
    $this->get('/en/view-count-debounce-test');

    expect($post->fresh()->view_count)->toBe(6);
});

test('category URL returns 200 with the category name', function (): void {
    $cat = Category::factory()->withoutTranslations()->create();
    $cat->translations()->create([
        'language_id' => $this->english->id,
        'name' => 'Tech',
        'slug' => 'tech-route-test',
    ]);

    $this->get('/en/category/tech-route-test')->assertOk()->assertSee('Tech');
});

test('category URL 404s for unknown slug', function (): void {
    $this->get('/en/category/does-not-exist-anywhere')->assertNotFound();
});

test('page URL returns 200 for published page', function (): void {
    $page = Page::factory()->withoutTranslations()->create(['status' => \App\Enums\PageStatus::Published->value]);
    $page->translations()->create([
        'language_id' => $this->english->id,
        'title' => 'About Us',
        'slug' => 'about-page-route-test',
        'content' => '<p>about content</p>',
        'is_published' => true,
    ]);

    $this->get('/en/page/about-page-route-test')->assertOk()->assertSee('About Us');
});

test('page URL 404s for draft page', function (): void {
    $page = Page::factory()->withoutTranslations()->create(['status' => \App\Enums\PageStatus::Draft->value]);
    $page->translations()->create([
        'language_id' => $this->english->id,
        'title' => 'Draft Page',
        'slug' => 'draft-page-route-test',
        'is_published' => false,
    ]);

    $this->get('/en/page/draft-page-route-test')->assertNotFound();
});

test('author URL returns 200 with author name', function (): void {
    $author = User::factory()->create(['name' => 'Sarah Reporter']);
    Post::factory()->published()->state(['author_id' => $author->id])->create();

    $this->get('/en/author/'.$author->id)->assertOk()->assertSee('Sarah Reporter');
});

test('search URL returns 200 with query', function (): void {
    $post = Post::factory()->published()->create();
    $post->translations()->first()->update(['title' => 'Search target article']);

    $this->get('/en/search?q=Search')->assertOk();
});

test('feed.xml URL is accessible', function (): void {
    $this->get('/en/feed.xml')->assertOk()->assertHeader('Content-Type', 'application/rss+xml; charset=utf-8');
});
