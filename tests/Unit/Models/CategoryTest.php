<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Language;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    Language::factory()->bangla()->create();
    app(LocaleResolver::class)->flush();
});

test('factory seeds a default language translation automatically', function (): void {
    $category = Category::factory()->create();

    expect($category->translations()->count())->toBe(1);
    expect($category->translate('name'))->not->toBeNull();
});

test('root scope returns only top-level categories', function (): void {
    $tech = Category::factory()->create();
    Category::factory()->child($tech->id)->create();
    Category::factory()->create();

    $roots = Category::query()->root()->get();

    expect($roots)->toHaveCount(2);
    expect($roots->pluck('parent_id')->unique()->all())->toBe([null]);
});

test('featured and onHomepage scopes filter correctly', function (): void {
    Category::factory()->featured()->create();
    Category::factory()->create();
    Category::factory()->onHomepage()->create();

    expect(Category::query()->featured()->count())->toBe(1);
    expect(Category::query()->onHomepage()->count())->toBe(2);  // featured() also sets onHomepage
});

test('inMenu scope excludes hidden categories', function (): void {
    Category::factory()->inMenu(false)->create();
    Category::factory()->inMenu(true)->create();
    Category::factory()->create();   // default true

    expect(Category::query()->inMenu()->count())->toBe(2);
});

test('ordered scope sorts ascending by sort_order then id', function (): void {
    $third = Category::factory()->state(['sort_order' => 30])->create();
    $first = Category::factory()->state(['sort_order' => 10])->create();
    $second = Category::factory()->state(['sort_order' => 20])->create();

    $ids = Category::query()->ordered()->pluck('id')->all();

    expect($ids)->toBe([$first->id, $second->id, $third->id]);
});

test('children relation orders by sort_order', function (): void {
    $parent = Category::factory()->create();
    $b = Category::factory()->child($parent->id)->state(['sort_order' => 20])->create();
    $a = Category::factory()->child($parent->id)->state(['sort_order' => 10])->create();
    $c = Category::factory()->child($parent->id)->state(['sort_order' => 30])->create();

    expect($parent->children()->pluck('id')->all())->toBe([$a->id, $b->id, $c->id]);
});

test('translate falls back to default language when locale missing', function (): void {
    $category = Category::factory()->create();

    $bangla = Language::query()->where('code', 'bn')->firstOrFail();
    $category->translations()->create([
        'language_id' => $bangla->id,
        'name' => 'প্রযুক্তি',
        'slug' => 'projukti',
    ]);

    // Locale missing → fallback to default (en).
    expect($category->translate('name', 'fr'))->not->toBe('প্রযুক্তি');
    expect($category->translate('name', 'fr'))->toBeString()->not->toBeEmpty();

    // Locale present → uses that translation.
    expect($category->translate('name', 'bn'))->toBe('প্রযুক্তি');
});

test('hasTranslationFor returns true only for present languages', function (): void {
    $category = Category::factory()->create();
    $bangla = Language::query()->where('code', 'bn')->firstOrFail();

    expect($category->hasTranslationFor('en'))->toBeTrue();
    expect($category->hasTranslationFor('bn'))->toBeFalse();

    $category->translations()->create([
        'language_id' => $bangla->id,
        'name' => 'প্রযুক্তি',
        'slug' => 'projukti',
    ]);

    expect($category->fresh()->hasTranslationFor('bn'))->toBeTrue();
});

test('urlFor builds slug-aware paths with optional locale prefix', function (): void {
    $category = Category::factory()->create();
    $category->translations()->first()->update(['slug' => 'technology']);

    expect($category->urlFor())->toBe('/technology');
    expect($category->urlFor('en'))->toBe('/en/technology');
});

test('isRoot returns true only for top-level categories', function (): void {
    $root = Category::factory()->create();
    $child = Category::factory()->child($root->id)->create();

    expect($root->isRoot())->toBeTrue();
    expect($child->isRoot())->toBeFalse();
});

test('translationsByLocale keys results by language code', function (): void {
    $category = Category::factory()->create();
    $bangla = Language::query()->where('code', 'bn')->firstOrFail();
    $category->translations()->create([
        'language_id' => $bangla->id,
        'name' => 'প্রযুক্তি',
        'slug' => 'projukti',
    ]);

    $byLocale = $category->fresh()->translationsByLocale();

    expect($byLocale)->toHaveKey('en');
    expect($byLocale)->toHaveKey('bn');
});
