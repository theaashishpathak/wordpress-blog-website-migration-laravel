<?php

declare(strict_types=1);

use App\Actions\Page\UpdatePageAction;
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

test('updates structural fields independently from translations', function (): void {
    $page = Page::factory()->create();

    app(UpdatePageAction::class)->handle($page, [
        'template' => Page::TEMPLATE_LANDING,
        'show_in_menu' => true,
        'sort_order' => 42,
    ]);

    $page->refresh();
    expect($page->template)->toBe(Page::TEMPLATE_LANDING);
    expect($page->show_in_menu)->toBeTrue();
    expect($page->sort_order)->toBe(42);
});

test('status string is normalised to enum value', function (): void {
    $page = Page::factory()->draft()->create();

    app(UpdatePageAction::class)->handle($page, ['status' => 'published']);

    expect($page->fresh()->status)->toBe(PageStatus::Published);
});

test('status enum instance is accepted', function (): void {
    $page = Page::factory()->draft()->create();

    app(UpdatePageAction::class)->handle($page, ['status' => PageStatus::Archived]);

    expect($page->fresh()->status)->toBe(PageStatus::Archived);
});

test('adds a new translation in a different language', function (): void {
    $page = Page::factory()->create();

    app(UpdatePageAction::class)->handle($page, [
        'translations' => [
            ['language_id' => $this->bangla->id, 'title' => 'বাংলা', 'slug' => 'bangla'],
        ],
    ]);

    $page->refresh();
    expect($page->translations()->count())->toBe(2);
    expect($page->translate('title', 'bn'))->toBe('বাংলা');
});

test('updates existing translation in place', function (): void {
    $page = Page::factory()->create();
    $originalId = $page->translation('en')->id;

    app(UpdatePageAction::class)->handle($page, [
        'translations' => [
            ['language_id' => $this->english->id, 'title' => 'Updated', 'slug' => 'updated'],
        ],
    ]);

    expect($page->translation('en')->id)->toBe($originalId);
    expect($page->fresh()->translate('title', 'en'))->toBe('Updated');
});

test('toggles per-locale is_published independently', function (): void {
    $page = Page::factory()->published()->create();

    // After published() factory state, English translation should be published.
    expect($page->fresh()->isPublishedIn('en'))->toBeTrue();

    // Flip the English translation back to draft.
    app(UpdatePageAction::class)->handle($page, [
        'translations' => [
            ['language_id' => $this->english->id, 'is_published' => false],
        ],
    ]);

    expect($page->fresh()->isPublishedIn('en'))->toBeFalse();
    // But the parent page-level status should still be published.
    expect($page->fresh()->isPublished())->toBeTrue();
});

test('refuses to delete the last translation', function (): void {
    $page = Page::factory()->create();

    app(UpdatePageAction::class)->handle($page, [
        'translations' => [
            ['language_id' => $this->english->id, 'delete' => true],
        ],
    ]);

    expect($page->fresh()->translations()->count())->toBe(1);
});
