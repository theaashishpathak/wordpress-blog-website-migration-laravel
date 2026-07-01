<?php

declare(strict_types=1);

use App\Enums\PageStatus;
use App\Models\Language;
use App\Models\Page;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    Language::factory()->bangla()->create();
    app(LocaleResolver::class)->flush();
});

test('factory creates draft page with default-language translation', function (): void {
    $page = Page::factory()->create();

    expect($page->status)->toBe(PageStatus::Draft);
    expect($page->translations()->count())->toBe(1);
    expect($page->translate('title'))->toBeString()->not->toBeEmpty();
});

test('published state cascades to existing translations', function (): void {
    $page = Page::factory()->published()->create();

    expect($page->status)->toBe(PageStatus::Published);
    expect($page->translations()->first()->is_published)->toBeTrue();
});

test('isPublished, isDraft, isArchived match enum state', function (): void {
    $draft = Page::factory()->draft()->create();
    $published = Page::factory()->published()->create();
    $archived = Page::factory()->archived()->create();

    expect($draft->isDraft())->toBeTrue();
    expect($published->isPublished())->toBeTrue();
    expect($archived->isArchived())->toBeTrue();

    expect($draft->isPublished())->toBeFalse();
    expect($published->isArchived())->toBeFalse();
});

test('published scope returns only published pages', function (): void {
    Page::factory()->draft()->create();
    Page::factory()->published()->create();
    Page::factory()->published()->create();
    Page::factory()->archived()->create();

    expect(Page::query()->published()->count())->toBe(2);
});

test('inMenu and ordered scopes work together', function (): void {
    Page::factory()->inMenu(true, 2)->create();
    Page::factory()->inMenu(true, 1)->create();
    Page::factory()->inMenu(false)->create();

    $ordered = Page::query()->inMenu()->ordered()->get();

    expect($ordered)->toHaveCount(2);
    expect($ordered->first()->sort_order)->toBe(1);
    expect($ordered->last()->sort_order)->toBe(2);
});

test('isPublishedIn requires both page status AND per-locale flag', function (): void {
    $page = Page::factory()->published()->create();
    $bangla = Language::query()->where('code', 'bn')->firstOrFail();

    // Default-language translation is published (cascaded by published state).
    expect($page->isPublishedIn('en'))->toBeTrue();

    // Add a Bangla translation that's still in draft.
    $page->translations()->create([
        'language_id' => $bangla->id,
        'title' => 'বাংলা শিরোনাম',
        'slug' => 'bangla-shironam',
        'is_published' => false,
    ]);

    expect($page->isPublishedIn('bn'))->toBeFalse();

    // Publish the Bangla translation.
    $page->translations()->where('language_id', $bangla->id)->update(['is_published' => true]);
    expect($page->fresh()->isPublishedIn('bn'))->toBeTrue();
});

test('isPublishedIn returns false when page status is not published', function (): void {
    $page = Page::factory()->draft()->create();

    // Even if translation is_published is true...
    $page->translations()->update(['is_published' => true]);

    // ...the parent draft status gates it out.
    expect($page->fresh()->isPublishedIn('en'))->toBeFalse();
});

test('visibleIn scope returns only pages fully published in target locale', function (): void {
    $english = Language::query()->where('code', 'en')->firstOrFail();
    $bangla = Language::query()->where('code', 'bn')->firstOrFail();

    // Visible everywhere: published page + published English translation
    $a = Page::factory()->published()->create();

    // English only: published page + English translation published, Bangla draft
    $b = Page::factory()->published()->create();
    $b->translations()->create([
        'language_id' => $bangla->id,
        'title' => 'B-bn',
        'slug' => 'b-bn',
        'is_published' => false,
    ]);

    // Draft page — invisible everywhere
    Page::factory()->draft()->create();

    expect(Page::query()->visibleIn($english->id)->pluck('id')->all())
        ->toContain($a->id)
        ->toContain($b->id);

    expect(Page::query()->visibleIn($bangla->id)->pluck('id')->all())
        ->not->toContain($b->id)
        ->not->toContain($a->id);   // a has no Bangla translation
});

test('urlFor builds page-prefixed paths with optional locale', function (): void {
    $page = Page::factory()->create();
    $page->translations()->first()->update(['slug' => 'about-us']);

    expect($page->urlFor())->toBe('/page/about-us');
    expect($page->urlFor('en'))->toBe('/en/page/about-us');
});
