<?php

declare(strict_types=1);

use App\Actions\Tag\UpdateTagAction;
use App\Models\Language;
use App\Models\Tag;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    $this->bangla = Language::factory()->bangla()->create();
    app(LocaleResolver::class)->flush();
});

test('updates structural fields without touching translations', function (): void {
    $tag = Tag::factory()->create(['color' => '#ff0000', 'status' => Tag::STATUS_PUBLISHED]);

    app(UpdateTagAction::class)->handle($tag, [
        'color' => '#0000ff',
        'status' => Tag::STATUS_UNPUBLISHED,
    ]);

    $tag->refresh();
    expect($tag->color)->toBe('#0000ff');
    expect($tag->status)->toBe(Tag::STATUS_UNPUBLISHED);
    expect($tag->translations()->count())->toBe(1);
});

test('adds a new translation for a previously untranslated language', function (): void {
    $tag = Tag::factory()->create();

    app(UpdateTagAction::class)->handle($tag, [
        'translations' => [
            ['language_id' => $this->bangla->id, 'name' => 'প্রযুক্তি', 'slug' => 'projukti'],
        ],
    ]);

    $tag->refresh();
    expect($tag->translations()->count())->toBe(2);
    expect($tag->translate('name', 'bn'))->toBe('প্রযুক্তি');
});

test('updates an existing translation in place', function (): void {
    $tag = Tag::factory()->create(['name' => 'Old', 'slug' => 'old']);
    $originalId = $tag->translation('en')->id;

    app(UpdateTagAction::class)->handle($tag, [
        'translations' => [
            ['language_id' => $this->english->id, 'name' => 'New', 'slug' => 'new'],
        ],
    ]);

    $tag->refresh();
    expect($tag->translation('en')->id)->toBe($originalId);
    expect($tag->translate('name', 'en'))->toBe('New');
    expect($tag->translate('slug', 'en'))->toBe('new');
    expect($tag->name)->toBe('New');         // legacy column synced
    expect($tag->slug)->toBe('new');
});

test('refuses to delete the only remaining translation', function (): void {
    $tag = Tag::factory()->create();

    app(UpdateTagAction::class)->handle($tag, [
        'translations' => [
            ['language_id' => $this->english->id, 'delete' => true],
        ],
    ]);

    expect($tag->fresh()->translations()->count())->toBe(1);
});

test('deletes a translation when at least one remains', function (): void {
    $tag = Tag::factory()->create();
    $tag->translations()->create([
        'language_id' => $this->bangla->id,
        'name' => 'প্রযুক্তি',
        'slug' => 'projukti',
    ]);

    expect($tag->translations()->count())->toBe(2);

    app(UpdateTagAction::class)->handle($tag, [
        'translations' => [
            ['language_id' => $this->bangla->id, 'delete' => true],
        ],
    ]);

    expect($tag->fresh()->translations()->count())->toBe(1);
    expect($tag->fresh()->hasTranslationFor('bn'))->toBeFalse();
});
