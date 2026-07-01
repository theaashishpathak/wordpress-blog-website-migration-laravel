<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Language;
use App\Models\Page;
use App\Models\Post;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
});

// -------------------------------------------------------------------------
// robots.txt
// -------------------------------------------------------------------------

test('robots.txt returns 200 with sitemap reference + admin disallow', function (): void {
    $response = $this->get('/robots.txt');

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=utf-8');

    $body = $response->getContent();

    expect($body)->toContain('User-agent: *');
    expect($body)->toContain('Disallow: /admin');
    expect($body)->toContain('Sitemap:');
});

// -------------------------------------------------------------------------
// sitemap.xml
// -------------------------------------------------------------------------

test('sitemap.xml returns valid XML with the homepage URL', function (): void {
    $response = $this->get('/sitemap.xml');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=utf-8');

    $body = $response->getContent();

    expect($body)->toStartWith('<?xml version="1.0"');
    expect($body)->toContain('<urlset');
    expect($body)->toContain(url('/'));
});

test('sitemap.xml lists published posts with their slug', function (): void {
    $post = Post::factory()->published()->create();
    $post->translations()->first()->update(['slug' => 'sitemap-test-slug']);

    $body = $this->get('/sitemap.xml')->getContent();

    expect($body)->toContain('sitemap-test-slug');
});

test('sitemap.xml excludes unpublished posts', function (): void {
    $draft = Post::factory()->draft()->create();
    $draft->translations()->first()->update(['slug' => 'should-not-appear-draft']);

    $body = $this->get('/sitemap.xml')->getContent();

    expect($body)->not->toContain('should-not-appear-draft');
});

test('sitemap.xml lists categories with their slug', function (): void {
    $cat = Category::factory()->withoutTranslations()->create();
    $cat->translations()->create([
        'language_id' => $this->english->id,
        'name' => 'Tech',
        'slug' => 'tech-sitemap-test',
    ]);

    $body = $this->get('/sitemap.xml')->getContent();

    expect($body)->toContain('tech-sitemap-test');
});

test('sitemap.xml lists published pages', function (): void {
    $page = Page::factory()->withoutTranslations()->create(['status' => \App\Enums\PageStatus::Published->value]);
    $page->translations()->create([
        'language_id' => $this->english->id,
        'title' => 'About',
        'slug' => 'about-sitemap-test',
        'is_published' => true,
    ]);

    $body = $this->get('/sitemap.xml')->getContent();

    expect($body)->toContain('about-sitemap-test');
});

// -------------------------------------------------------------------------
// RSS feed (global)
// -------------------------------------------------------------------------

test('global rss feed returns valid XML with channel + at least one item', function (): void {
    $post = Post::factory()->published()->create();
    $post->translations()->first()->update(['title' => 'AI News Update', 'slug' => 'ai-news-update']);

    $response = $this->get('/feed.xml');

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/rss+xml; charset=utf-8');

    $body = $response->getContent();

    expect($body)->toStartWith('<?xml version="1.0"');
    expect($body)->toContain('<rss version="2.0"');
    expect($body)->toContain('<channel>');
    expect($body)->toContain('AI News Update');
    expect($body)->toContain('ai-news-update');
});

test('global rss feed limits to 30 most recent posts', function (): void {
    Post::factory()->count(35)->published()->create();

    $body = $this->get('/feed.xml')->getContent();

    // Count <item> tags
    $itemCount = substr_count($body, '<item>');
    expect($itemCount)->toBe(30);
});

test('global rss feed excludes draft posts', function (): void {
    $draft = Post::factory()->draft()->create();
    $draft->translations()->first()->update(['title' => 'Draft never seen', 'slug' => 'draft-never']);

    $body = $this->get('/feed.xml')->getContent();

    expect($body)->not->toContain('Draft never seen');
});

// -------------------------------------------------------------------------
// RSS feed (category)
// -------------------------------------------------------------------------

test('category rss feed returns posts in that category only', function (): void {
    $cat = Category::factory()->withoutTranslations()->create();
    $cat->translations()->create([
        'language_id' => $this->english->id,
        'name' => 'Tech',
        'slug' => 'tech-rss-test',
    ]);

    $inside = Post::factory()->published()->state(['category_id' => $cat->id])->create();
    $inside->translations()->first()->update(['title' => 'Inside tech category', 'slug' => 'inside-tech']);

    $outside = Post::factory()->published()->create();
    $outside->translations()->first()->update(['title' => 'Outside category', 'slug' => 'outside']);

    $body = $this->get('/category/tech-rss-test.rss')->getContent();

    expect($body)->toContain('Inside tech category');
    expect($body)->not->toContain('Outside category');
});

test('category rss feed 404s for an unknown slug', function (): void {
    $this->get('/category/this-slug-does-not-exist.rss')->assertNotFound();
});
