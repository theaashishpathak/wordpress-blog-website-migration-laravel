<?php

declare(strict_types=1);

use App\Actions\Tag\MergeTagsAction;
use App\Models\Language;
use App\Models\Tag;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
});

test('merge deletes source tags when no pivot table exists', function (): void {
    $target = Tag::factory()->create(['name' => 'Tech', 'slug' => 'tech']);
    $sourceA = Tag::factory()->create(['name' => 'Technology', 'slug' => 'technology']);
    $sourceB = Tag::factory()->create(['name' => 'Tech Stack', 'slug' => 'tech-stack']);

    $result = app(MergeTagsAction::class)->handle($target, [$sourceA->id, $sourceB->id]);

    expect($result->id)->toBe($target->id);
    expect(Tag::query()->find($sourceA->id))->toBeNull();
    expect(Tag::query()->find($sourceB->id))->toBeNull();
    expect(Tag::query()->find($target->id))->not->toBeNull();
});

test('merge is idempotent when target id is passed as a source', function (): void {
    $target = Tag::factory()->create();

    $result = app(MergeTagsAction::class)->handle($target, [$target->id]);

    expect($result->id)->toBe($target->id);
    expect(Tag::query()->find($target->id))->not->toBeNull();
});

test('merge with empty source list is a no-op', function (): void {
    $target = Tag::factory()->create();

    $result = app(MergeTagsAction::class)->handle($target, []);

    expect($result->id)->toBe($target->id);
    expect(Tag::query()->count())->toBe(1);
});
