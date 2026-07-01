<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\Post;
use App\Models\SeoMeta;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
});

test('seoable morphs to its owner', function (): void {
    $post = Post::factory()->create();
    $meta = SeoMeta::factory()->forSeoable($post)->create();

    $loaded = SeoMeta::query()->find($meta->id);
    expect($loaded->seoable)->toBeInstanceOf(Post::class);
    expect($loaded->seoable->id)->toBe($post->id);
});

test('schema_data is cast to and from array', function (): void {
    $meta = SeoMeta::factory()->faqPage([
        ['@type' => 'Question', 'name' => 'What is NewsPilot?'],
        ['@type' => 'Question', 'name' => 'How does AI write articles?'],
    ])->create();

    $loaded = SeoMeta::query()->find($meta->id);
    expect($loaded->schema_data)->toBeArray();
    expect($loaded->schema_data)->toHaveKey('mainEntity');
    expect($loaded->schema_data['mainEntity'])->toHaveCount(2);
});

test('forLocale scope filters by language', function (): void {
    $english = Language::query()->where('code', 'en')->firstOrFail();
    $bangla = Language::factory()->bangla()->create();
    $post = Post::factory()->create();

    SeoMeta::factory()->forSeoable($post)->state(['language_id' => $english->id])->create();
    SeoMeta::factory()->forSeoable($post)->state(['language_id' => $bangla->id])->create();
    SeoMeta::factory()->forSeoable($post)->state(['language_id' => null])->create();

    expect(SeoMeta::query()->forLocale($english->id)->count())->toBe(1);
    expect(SeoMeta::query()->forLocale($bangla->id)->count())->toBe(1);
    expect(SeoMeta::query()->forLocale(null)->count())->toBe(1);
});

test('ofSchemaType scope works with constants', function (): void {
    $post = Post::factory()->create();
    SeoMeta::factory()->forSeoable($post)->state([
        'schema_type' => SeoMeta::SCHEMA_NEWS_ARTICLE,
    ])->create();
    SeoMeta::factory()->forSeoable($post)->state([
        'language_id' => null,
        'schema_type' => SeoMeta::SCHEMA_ARTICLE,
    ])->create();

    expect(SeoMeta::query()->ofSchemaType(SeoMeta::SCHEMA_NEWS_ARTICLE)->count())->toBe(1);
});

test('unique constraint prevents duplicate seo_metas per (seoable, locale)', function (): void {
    $post = Post::factory()->create();
    $english = Language::query()->where('code', 'en')->firstOrFail();

    SeoMeta::query()->create([
        'seoable_type' => Post::class,
        'seoable_id' => $post->id,
        'language_id' => $english->id,
        'meta_title' => 'First',
    ]);

    $this->expectException(\Illuminate\Database\QueryException::class);

    SeoMeta::query()->create([
        'seoable_type' => Post::class,
        'seoable_id' => $post->id,
        'language_id' => $english->id,
        'meta_title' => 'Duplicate',
    ]);
});
