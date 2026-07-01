<?php

declare(strict_types=1);

use App\Actions\Page\ArchivePageAction;
use App\Actions\Page\DeletePageAction;
use App\Actions\Page\PublishPageAction;
use App\Enums\PageStatus;
use App\Models\Language;
use App\Models\Page;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    $this->bangla = Language::factory()->bangla()->create();
    app(LocaleResolver::class)->flush();
});

test('publish flips status to published but leaves translations untouched by default', function (): void {
    $page = Page::factory()->draft()->create();
    expect($page->fresh()->translation('en')->is_published)->toBeFalse();

    app(PublishPageAction::class)->handle($page);

    expect($page->fresh()->status)->toBe(PageStatus::Published);
    expect($page->fresh()->translation('en')->is_published)->toBeFalse();
});

test('publish with cascadeTranslations=true also flips every translation row', function (): void {
    $page = Page::factory()->draft()->create();

    // Add a Bangla translation (also draft) so we can verify cascade hits both.
    $page->translations()->create([
        'language_id' => $this->bangla->id,
        'title' => 'বাংলা',
        'slug' => 'bangla',
        'is_published' => false,
    ]);

    app(PublishPageAction::class)->handle($page, cascadeTranslations: true);

    expect($page->fresh()->status)->toBe(PageStatus::Published);
    expect($page->fresh()->isPublishedIn('en'))->toBeTrue();
    expect($page->fresh()->isPublishedIn('bn'))->toBeTrue();
});

test('archive sets status to archived without touching translations', function (): void {
    $page = Page::factory()->published()->create();
    $translationCountBefore = $page->translations()->count();

    app(ArchivePageAction::class)->handle($page);

    expect($page->fresh()->status)->toBe(PageStatus::Archived);
    expect($page->fresh()->translations()->count())->toBe($translationCountBefore);
});

test('delete soft-deletes the page and translations remain accessible via withTrashed', function (): void {
    $page = Page::factory()->published()->create();

    app(DeletePageAction::class)->handle($page);

    expect(Page::query()->find($page->id))->toBeNull();
    expect(Page::withTrashed()->find($page->id))->not->toBeNull();
    // Translation rows are NOT cascade-deleted on soft-delete (only on
    // hard-delete via the FK constraint), so they remain queryable.
    expect(Page::withTrashed()->find($page->id)->translations()->count())->toBeGreaterThan(0);
});
