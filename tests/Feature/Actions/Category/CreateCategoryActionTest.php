<?php

declare(strict_types=1);

use App\Actions\Category\CreateCategoryAction;
use App\Models\Category;
use App\Models\Language;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    $this->bangla = Language::factory()->bangla()->create();
    $this->arabic = Language::factory()->arabicRtl()->create();
    app(LocaleResolver::class)->flush();
});

test('creates a category with a single default-language translation', function (): void {
    $action = app(CreateCategoryAction::class);

    $category = $action->handle([
        'icon' => 'cpu',
        'color' => '#4f46e5',
        'translations' => [
            ['language_id' => $this->english->id, 'name' => 'Technology'],
        ],
    ]);

    expect($category)->toBeInstanceOf(Category::class);
    expect($category->translations)->toHaveCount(1);
    expect($category->translate('name', 'en'))->toBe('Technology');
    expect($category->translate('slug', 'en'))->toBe('technology');
});

test('creates a category with multiple translations in one call', function (): void {
    $action = app(CreateCategoryAction::class);

    $category = $action->handle([
        'translations' => [
            ['language_id' => $this->english->id, 'name' => 'Sports'],
            ['language_id' => $this->bangla->id, 'name' => 'খেলা', 'slug' => 'khela'],
            ['language_id' => $this->arabic->id, 'name' => 'رياضة', 'slug' => 'riada'],
        ],
    ]);

    expect($category->translations)->toHaveCount(3);
    expect($category->translate('name', 'bn'))->toBe('খেলা');
    expect($category->translate('slug', 'bn'))->toBe('khela');
    expect($category->translate('slug', 'ar'))->toBe('riada');
});

test('auto-slugs from name when slug omitted', function (): void {
    $action = app(CreateCategoryAction::class);

    $category = $action->handle([
        'translations' => [
            ['language_id' => $this->english->id, 'name' => 'Breaking News'],
        ],
    ]);

    expect($category->translate('slug', 'en'))->toBe('breaking-news');
});

test('rejects empty translations array', function (): void {
    $action = app(CreateCategoryAction::class);

    $action->handle(['translations' => []]);
})->throws(ValidationException::class, 'At least one translation is required.');

test('rejects translation without a name', function (): void {
    $action = app(CreateCategoryAction::class);

    $action->handle([
        'translations' => [
            ['language_id' => $this->english->id, 'name' => '   '],
        ],
    ]);
})->throws(ValidationException::class);

test('rejects missing default-language translation', function (): void {
    $action = app(CreateCategoryAction::class);

    $action->handle([
        'translations' => [
            ['language_id' => $this->bangla->id, 'name' => 'খেলা'],
        ],
    ]);
})->throws(ValidationException::class, 'A translation in the default language');

test('rejects duplicate slug within the same language in one request', function (): void {
    $action = app(CreateCategoryAction::class);

    $action->handle([
        'translations' => [
            ['language_id' => $this->english->id, 'name' => 'Tech A', 'slug' => 'tech'],
            ['language_id' => $this->english->id, 'name' => 'Tech B', 'slug' => 'tech'],
        ],
    ]);
})->throws(ValidationException::class, 'Duplicate slug');

test('rolls back the whole transaction when a translation fails', function (): void {
    $action = app(CreateCategoryAction::class);

    $existing = Category::factory()->create();
    $existing->translations()->first()->update(['slug' => 'taken-slug']);

    try {
        $action->handle([
            'translations' => [
                // duplicate slug at DB level (unique index across language_id+slug)
                ['language_id' => $this->english->id, 'name' => 'Clash', 'slug' => 'taken-slug'],
            ],
        ]);
        $this->fail('Expected unique-constraint failure.');
    } catch (\Throwable) {
        // Database transaction must have rolled back — only the pre-existing
        // category row should remain.
        expect(Category::query()->count())->toBe(1);
    }
});

test('persists structural fields verbatim', function (): void {
    $action = app(CreateCategoryAction::class);

    $category = $action->handle([
        'icon' => 'flame',
        'color' => '#ff0000',
        'show_in_menu' => false,
        'show_on_homepage' => true,
        'is_featured' => true,
        'sort_order' => 42,
        'layout' => Category::LAYOUT_MAGAZINE,
        'translations' => [
            ['language_id' => $this->english->id, 'name' => 'Breaking'],
        ],
    ]);

    expect($category->icon)->toBe('flame');
    expect($category->color)->toBe('#ff0000');
    expect($category->show_in_menu)->toBeFalse();
    expect($category->show_on_homepage)->toBeTrue();
    expect($category->is_featured)->toBeTrue();
    expect($category->sort_order)->toBe(42);
    expect($category->layout)->toBe(Category::LAYOUT_MAGAZINE);
});
