<?php

declare(strict_types=1);

use App\Actions\Seo\UpdateSeoMetaAction;
use App\Models\Language;
use App\Models\Post;
use App\Models\SeoMeta;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    $this->action = new UpdateSeoMetaAction;
});

test('creates a new seo_metas row when none exists for the locale', function (): void {
    $post = Post::factory()->draft()->create();

    $seo = $this->action->handle($post, $this->english->id, [
        'meta_title' => 'Custom Override',
        'meta_description' => 'A bespoke description for the SERP.',
        'robots' => 'index,follow',
        'schema_type' => SeoMeta::SCHEMA_ARTICLE,
    ]);

    expect($seo)->not->toBeNull();
    expect($seo->seoable_id)->toBe($post->id);
    expect($seo->seoable_type)->toBe($post->getMorphClass());
    expect($seo->language_id)->toBe($this->english->id);
    expect($seo->meta_title)->toBe('Custom Override');
    expect($seo->schema_type)->toBe(SeoMeta::SCHEMA_ARTICLE);
});

test('updates the existing row instead of duplicating', function (): void {
    $post = Post::factory()->draft()->create();

    $this->action->handle($post, $this->english->id, ['robots' => 'index,follow']);
    $this->action->handle($post, $this->english->id, ['robots' => 'noindex,nofollow', 'schema_type' => SeoMeta::SCHEMA_NEWS_ARTICLE]);

    $rows = SeoMeta::query()
        ->where('seoable_id', $post->id)
        ->where('language_id', $this->english->id)
        ->get();

    expect($rows)->toHaveCount(1);
    expect($rows->first()->robots)->toBe('noindex,nofollow');
    expect($rows->first()->schema_type)->toBe(SeoMeta::SCHEMA_NEWS_ARTICLE);
});

test('returns null and writes nothing when all fields are empty', function (): void {
    $post = Post::factory()->draft()->create();

    $result = $this->action->handle($post, $this->english->id, [
        'meta_title' => '',
        'meta_description' => null,
        'robots' => '',
    ]);

    expect($result)->toBeNull();
    expect(SeoMeta::query()->where('seoable_id', $post->id)->exists())->toBeFalse();
});

test('deletes the row when caller clears every override on an existing record', function (): void {
    $post = Post::factory()->draft()->create();

    // Seed an override.
    $this->action->handle($post, $this->english->id, ['robots' => 'noindex,follow']);
    expect(SeoMeta::query()->where('seoable_id', $post->id)->exists())->toBeTrue();

    // Clear it.
    $this->action->handle($post, $this->english->id, [
        'robots' => '',
        'schema_type' => '',
    ]);

    expect(SeoMeta::query()->where('seoable_id', $post->id)->exists())->toBeFalse();
});

test('ignores unmanaged keys', function (): void {
    $post = Post::factory()->draft()->create();

    $seo = $this->action->handle($post, $this->english->id, [
        'robots' => 'index,follow',
        'unknown_field' => 'should be discarded',
        'arbitrary_garbage' => ['a', 'b'],
    ]);

    expect($seo)->not->toBeNull();
    expect($seo->getAttributes())->not->toHaveKey('unknown_field');
});

test('respects per-locale isolation', function (): void {
    $bangla = Language::factory()->state(['code' => 'bn', 'name' => 'Bangla'])->create();
    $post = Post::factory()->draft()->create();

    $this->action->handle($post, $this->english->id, ['robots' => 'index,follow']);
    $this->action->handle($post, $bangla->id, ['robots' => 'noindex,nofollow']);

    $rows = SeoMeta::query()->where('seoable_id', $post->id)->orderBy('language_id')->get();

    expect($rows)->toHaveCount(2);
    expect($rows->where('language_id', $this->english->id)->first()->robots)->toBe('index,follow');
    expect($rows->where('language_id', $bangla->id)->first()->robots)->toBe('noindex,nofollow');
});
