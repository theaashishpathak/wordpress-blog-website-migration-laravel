<?php

declare(strict_types=1);

use App\Actions\Tag\CreateTagAction;
use App\Models\Language;
use App\Models\Tag;
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

test('creates tag with a single default-language translation', function (): void {
    $tag = app(CreateTagAction::class)->handle([
        'created_by' => $this->user->id,
        'translations' => [
            ['language_id' => $this->english->id, 'name' => 'Technology'],
        ],
    ]);

    expect($tag)->toBeInstanceOf(Tag::class);
    expect($tag->translations)->toHaveCount(1);
    expect($tag->translate('name', 'en'))->toBe('Technology');
    expect($tag->name)->toBe('Technology');         // legacy column synced
    expect($tag->slug)->toBe('technology');         // legacy column synced
    expect($tag->code)->toMatch('/^\d{4}$/');       // auto-generated 4 digits
});

test('creates tag with multiple translations', function (): void {
    $tag = app(CreateTagAction::class)->handle([
        'created_by' => $this->user->id,
        'translations' => [
            ['language_id' => $this->english->id, 'name' => 'Sports'],
            ['language_id' => $this->bangla->id, 'name' => 'খেলা', 'slug' => 'khela'],
        ],
    ]);

    expect($tag->translations)->toHaveCount(2);
    expect($tag->translate('name', 'bn'))->toBe('খেলা');
    expect($tag->translate('slug', 'bn'))->toBe('khela');
});

test('honours an explicit code when provided', function (): void {
    $tag = app(CreateTagAction::class)->handle([
        'code' => 'BREAKING',
        'created_by' => $this->user->id,
        'translations' => [
            ['language_id' => $this->english->id, 'name' => 'Breaking'],
        ],
    ]);

    expect($tag->code)->toBe('BREAKING');
});

test('rejects empty translations array', function (): void {
    app(CreateTagAction::class)->handle([
        'created_by' => $this->user->id,
        'translations' => [],
    ]);
})->throws(ValidationException::class, 'At least one translation is required.');

test('rejects translation without default language', function (): void {
    app(CreateTagAction::class)->handle([
        'created_by' => $this->user->id,
        'translations' => [
            ['language_id' => $this->bangla->id, 'name' => 'খেলা'],
        ],
    ]);
})->throws(ValidationException::class);

test('rejects duplicate slug within same language in one request', function (): void {
    app(CreateTagAction::class)->handle([
        'created_by' => $this->user->id,
        'translations' => [
            ['language_id' => $this->english->id, 'name' => 'A', 'slug' => 'tech'],
            ['language_id' => $this->english->id, 'name' => 'B', 'slug' => 'tech'],
        ],
    ]);
})->throws(ValidationException::class, 'Duplicate slug');

test('auto-slugs from name when slug omitted', function (): void {
    $tag = app(CreateTagAction::class)->handle([
        'created_by' => $this->user->id,
        'translations' => [
            ['language_id' => $this->english->id, 'name' => 'Breaking News'],
        ],
    ]);

    expect($tag->translate('slug', 'en'))->toBe('breaking-news');
});
