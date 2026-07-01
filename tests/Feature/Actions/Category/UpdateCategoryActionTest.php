<?php

declare(strict_types=1);

use App\Actions\Category\UpdateCategoryAction;
use App\Models\Category;
use App\Models\Language;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    $this->bangla = Language::factory()->bangla()->create();
    app(LocaleResolver::class)->flush();
});

test('updates structural fields without touching translations', function (): void {
    $category = Category::factory()->create();
    $originalTranslationCount = $category->translations()->count();

    app(UpdateCategoryAction::class)->handle($category, [
        'icon' => 'flame',
        'color' => '#ff0000',
        'is_featured' => true,
        'sort_order' => 99,
    ]);

    $category->refresh();

    expect($category->icon)->toBe('flame');
    expect($category->color)->toBe('#ff0000');
    expect($category->is_featured)->toBeTrue();
    expect($category->sort_order)->toBe(99);
    expect($category->translations()->count())->toBe($originalTranslationCount);
});

test('adds a new translation for a previously-untranslated language', function (): void {
    $category = Category::factory()->create();

    app(UpdateCategoryAction::class)->handle($category, [
        'translations' => [
            ['language_id' => $this->bangla->id, 'name' => 'প্রযুক্তি', 'slug' => 'projukti'],
        ],
    ]);

    $category->refresh();
    expect($category->translations()->count())->toBe(2);
    expect($category->translate('name', 'bn'))->toBe('প্রযুক্তি');
    expect($category->translate('slug', 'bn'))->toBe('projukti');
});

test('updates an existing translation in place', function (): void {
    $category = Category::factory()->create();
    $original = $category->translation('en');

    app(UpdateCategoryAction::class)->handle($category, [
        'translations' => [
            ['language_id' => $this->english->id, 'name' => 'Updated Name', 'slug' => 'updated-slug'],
        ],
    ]);

    $category->refresh();
    expect($category->translations()->count())->toBe(1);
    expect($category->translate('name', 'en'))->toBe('Updated Name');
    expect($category->translate('slug', 'en'))->toBe('updated-slug');
    expect($category->translation('en')->id)->toBe($original->id);   // same row, not recreated
});

test('deletes a translation when delete=true and others remain', function (): void {
    $category = Category::factory()->create();

    // Add a Bangla translation alongside the default English one.
    $category->translations()->create([
        'language_id' => $this->bangla->id,
        'name' => 'প্রযুক্তি',
        'slug' => 'projukti',
    ]);

    expect($category->translations()->count())->toBe(2);

    app(UpdateCategoryAction::class)->handle($category, [
        'translations' => [
            ['language_id' => $this->bangla->id, 'delete' => true],
        ],
    ]);

    $category->refresh();
    expect($category->translations()->count())->toBe(1);
    expect($category->hasTranslationFor('bn'))->toBeFalse();
});

test('refuses to delete the last remaining translation', function (): void {
    $category = Category::factory()->create();

    app(UpdateCategoryAction::class)->handle($category, [
        'translations' => [
            ['language_id' => $this->english->id, 'delete' => true],
        ],
    ]);

    expect($category->fresh()->translations()->count())->toBe(1);
});

test('prevents a category from becoming its own parent', function (): void {
    $category = Category::factory()->create();

    app(UpdateCategoryAction::class)->handle($category, [
        'parent_id' => $category->id,
    ]);

    expect($category->fresh()->parent_id)->toBeNull();
});

test('preserves other fields when only one is supplied', function (): void {
    $category = Category::factory()->create([
        'icon' => 'cpu',
        'color' => '#0000ff',
        'is_featured' => true,
    ]);

    app(UpdateCategoryAction::class)->handle($category, [
        'icon' => 'flame',
    ]);

    $category->refresh();

    expect($category->icon)->toBe('flame');
    expect($category->color)->toBe('#0000ff');
    expect($category->is_featured)->toBeTrue();
});
