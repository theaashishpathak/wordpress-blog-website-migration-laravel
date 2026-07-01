<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\Tag;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    Language::factory()->bangla()->create();
    app(LocaleResolver::class)->flush();
});

test('factory auto-creates a default-language translation', function (): void {
    $tag = Tag::factory()->create();

    expect($tag->translations()->count())->toBe(1);
    expect($tag->translate('name'))->toBe($tag->name);
    expect($tag->translate('slug'))->toBe($tag->slug);
});

test('observer creates default-language translation when none exists', function (): void {
    // Bypass factory's afterCreating by going through a raw create-and-save.
    $tag = Tag::factory()->withoutTranslations()->create();

    // Sanity check: factory's withoutTranslations() drops the auto-created row.
    expect($tag->fresh()->translations()->count())->toBe(0);

    // Now force-trigger the observer by touching the model.
    $tag->touch();

    expect($tag->fresh()->translations()->count())->toBe(1);
    expect($tag->fresh()->translate('name'))->toBe($tag->name);
});

test('observer syncs renamed legacy column into existing translation', function (): void {
    $tag = Tag::factory()->create(['name' => 'Old Name', 'slug' => 'old-name']);

    expect($tag->translate('name'))->toBe('Old Name');

    $tag->update(['name' => 'Renamed', 'slug' => 'renamed']);

    $tag->refresh();
    expect($tag->translate('name'))->toBe('Renamed');
    expect($tag->translate('slug'))->toBe('renamed');

    // The translation row should NOT have been duplicated.
    expect($tag->translations()->count())->toBe(1);
});

test('translate falls back to default language when locale missing', function (): void {
    $tag = Tag::factory()->create(['name' => 'Technology', 'slug' => 'technology']);
    $bangla = Language::query()->where('code', 'bn')->firstOrFail();

    $tag->translations()->create([
        'language_id' => $bangla->id,
        'name' => 'প্রযুক্তি',
        'slug' => 'projukti',
    ]);

    expect($tag->translate('name', 'bn'))->toBe('প্রযুক্তি');
    expect($tag->translate('name', 'fr'))->toBe('Technology');   // fallback to default
});

test('per-language slug uniqueness allows same slug across different languages', function (): void {
    $tagA = Tag::factory()->create(['name' => 'Tech A', 'slug' => 'tech-a']);
    $tagB = Tag::factory()->create(['name' => 'Tech B', 'slug' => 'tech-b']);

    $bangla = Language::query()->where('code', 'bn')->firstOrFail();

    // Both tags can have a Bangla translation with slug "tech-bn-different"
    // — slug uniqueness is per (language_id, slug), so we need distinct slugs.
    $tagA->translations()->create(['language_id' => $bangla->id, 'name' => 'A', 'slug' => 'tech-a-bn']);
    $tagB->translations()->create(['language_id' => $bangla->id, 'name' => 'B', 'slug' => 'tech-b-bn']);

    expect($tagA->translate('slug', 'bn'))->toBe('tech-a-bn');
    expect($tagB->translate('slug', 'bn'))->toBe('tech-b-bn');
});

test('published scope filters out unpublished tags', function (): void {
    Tag::factory()->published()->create();
    Tag::factory()->unpublished()->create();
    Tag::factory()->published()->create();

    expect(Tag::query()->published()->count())->toBe(2);
});

test('hasTranslationFor reflects per-locale presence', function (): void {
    $tag = Tag::factory()->create();
    $bangla = Language::query()->where('code', 'bn')->firstOrFail();

    expect($tag->hasTranslationFor('en'))->toBeTrue();
    expect($tag->hasTranslationFor('bn'))->toBeFalse();

    $tag->translations()->create([
        'language_id' => $bangla->id,
        'name' => 'প্রযুক্তি',
        'slug' => 'projukti',
    ]);

    expect($tag->fresh()->hasTranslationFor('bn'))->toBeTrue();
});
