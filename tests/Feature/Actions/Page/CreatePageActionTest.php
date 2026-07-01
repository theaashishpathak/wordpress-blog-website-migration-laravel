<?php

declare(strict_types=1);

use App\Actions\Page\CreatePageAction;
use App\Enums\PageStatus;
use App\Models\Language;
use App\Models\Page;
use App\Models\User;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    $this->bangla = Language::factory()->bangla()->create();
    $this->user = User::factory()->create();
    app(LocaleResolver::class)->flush();
});

test('creates a page with a single default-language translation', function (): void {
    $page = app(CreatePageAction::class)->handle([
        'created_by' => $this->user->id,
        'translations' => [
            ['language_id' => $this->english->id, 'title' => 'About Us'],
        ],
    ]);

    expect($page)->toBeInstanceOf(Page::class);
    expect($page->status)->toBe(PageStatus::Draft);
    expect($page->translate('title', 'en'))->toBe('About Us');
    expect($page->translate('slug', 'en'))->toBe('about-us');
});

test('creates with multiple translations and per-locale is_published flags', function (): void {
    $page = app(CreatePageAction::class)->handle([
        'created_by' => $this->user->id,
        'status' => PageStatus::Published,
        'translations' => [
            ['language_id' => $this->english->id, 'title' => 'About Us', 'is_published' => true],
            ['language_id' => $this->bangla->id, 'title' => 'আমাদের সম্পর্কে', 'slug' => 'amader-somporke', 'is_published' => false],
        ],
    ]);

    expect($page->status)->toBe(PageStatus::Published);
    expect($page->isPublishedIn('en'))->toBeTrue();
    expect($page->isPublishedIn('bn'))->toBeFalse();
});

test('rejects empty translations array', function (): void {
    app(CreatePageAction::class)->handle([
        'created_by' => $this->user->id,
        'translations' => [],
    ]);
})->throws(ValidationException::class, 'At least one translation is required.');

test('rejects translation without title', function (): void {
    app(CreatePageAction::class)->handle([
        'created_by' => $this->user->id,
        'translations' => [
            ['language_id' => $this->english->id, 'title' => '  '],
        ],
    ]);
})->throws(ValidationException::class);

test('rejects missing default-language translation', function (): void {
    app(CreatePageAction::class)->handle([
        'created_by' => $this->user->id,
        'translations' => [
            ['language_id' => $this->bangla->id, 'title' => 'বাংলা'],
        ],
    ]);
})->throws(ValidationException::class, 'A translation in the default language');

test('rejects duplicate slug within same language', function (): void {
    app(CreatePageAction::class)->handle([
        'created_by' => $this->user->id,
        'translations' => [
            ['language_id' => $this->english->id, 'title' => 'A', 'slug' => 'same'],
            ['language_id' => $this->english->id, 'title' => 'B', 'slug' => 'same'],
        ],
    ]);
})->throws(ValidationException::class, 'Duplicate slug');

test('honours custom template + sort + show_in_menu', function (): void {
    $page = app(CreatePageAction::class)->handle([
        'created_by' => $this->user->id,
        'template' => Page::TEMPLATE_LANDING,
        'show_in_menu' => true,
        'sort_order' => 5,
        'translations' => [
            ['language_id' => $this->english->id, 'title' => 'Landing'],
        ],
    ]);

    expect($page->template)->toBe(Page::TEMPLATE_LANDING);
    expect($page->show_in_menu)->toBeTrue();
    expect($page->sort_order)->toBe(5);
});
