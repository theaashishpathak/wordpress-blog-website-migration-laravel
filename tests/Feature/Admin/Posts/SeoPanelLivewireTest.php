<?php

declare(strict_types=1);

use App\Livewire\Admin\Posts\Create;
use App\Livewire\Admin\Posts\Edit;
use App\Models\Category;
use App\Models\Language;
use App\Models\Post;
use App\Models\SeoMeta;
use App\Models\User;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();
});

function seoUser(string $roleName): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

test('Edit hydrates SEO state from existing translation + seo_metas row', function (): void {
    $admin = seoUser('Admin');
    $post = Post::factory()->draft()->create();

    $post->translations()->first()->update([
        'meta_title' => 'Existing Meta Title',
        'meta_description' => 'Existing meta description for SERP.',
        'focus_keyword' => 'ai marketing',
        'canonical_url' => 'https://example.com/canonical',
    ]);

    SeoMeta::query()->create([
        'seoable_type' => $post->getMorphClass(),
        'seoable_id' => $post->id,
        'language_id' => $this->english->id,
        'robots' => 'noindex,nofollow',
        'schema_type' => SeoMeta::SCHEMA_NEWS_ARTICLE,
        'meta_keywords' => 'ai, marketing, 2026',
    ]);

    Livewire::actingAs($admin)
        ->test(Edit::class, ['post' => $post->fresh()])
        ->assertSet('seoMetaTitle', 'Existing Meta Title')
        ->assertSet('seoMetaDescription', 'Existing meta description for SERP.')
        ->assertSet('seoFocusKeyword', 'ai marketing')
        ->assertSet('seoCanonicalUrl', 'https://example.com/canonical')
        ->assertSet('seoRobots', 'noindex,nofollow')
        ->assertSet('seoSchemaType', SeoMeta::SCHEMA_NEWS_ARTICLE)
        ->assertSet('seoMetaKeywords', 'ai, marketing, 2026');
});

test('Edit save persists basic SEO fields into post_translations and advanced into seo_metas', function (): void {
    $admin = seoUser('Admin');
    $post = Post::factory()->draft()->create();

    Livewire::actingAs($admin)
        ->test(Edit::class, ['post' => $post])
        ->set('seoMetaTitle', 'Brand New SEO Title')
        ->set('seoMetaDescription', 'Brand new description that should be persisted.')
        ->set('seoFocusKeyword', 'ai marketing')
        ->set('seoRobots', 'index,follow')
        ->set('seoSchemaType', SeoMeta::SCHEMA_BLOG_POSTING)
        ->call('save');

    $translation = $post->fresh()->translations()->first();
    expect($translation->meta_title)->toBe('Brand New SEO Title');
    expect($translation->meta_description)->toBe('Brand new description that should be persisted.');
    expect($translation->focus_keyword)->toBe('ai marketing');

    $seo = SeoMeta::query()
        ->where('seoable_id', $post->id)
        ->where('language_id', $this->english->id)
        ->first();
    expect($seo)->not->toBeNull();
    expect($seo->robots)->toBe('index,follow');
    expect($seo->schema_type)->toBe(SeoMeta::SCHEMA_BLOG_POSTING);
});

test('seoScore computed property returns score result reflecting form state', function (): void {
    $admin = seoUser('Admin');
    $post = Post::factory()->draft()->create();

    $component = Livewire::actingAs($admin)->test(Edit::class, ['post' => $post])
        ->set('title', 'AI Marketing in 2026')
        ->set('slug', 'ai-marketing-2026')
        ->set('seoMetaTitle', str_repeat('a', 55))
        ->set('seoMetaDescription', str_repeat('a', 140))
        ->set('seoFocusKeyword', 'ai marketing')
        ->set('content', str_repeat('AI marketing helps brands grow. ', 60));

    $score = $component->instance()->seoScore;

    expect($score->overall)->toBeGreaterThan(50);
    expect($score->checks)->toHaveCount(9);
});

test('Create save persists SEO state for a brand new post', function (): void {
    $admin = seoUser('Admin');
    $category = Category::factory()->create();

    Livewire::actingAs($admin)
        ->test(Create::class)
        ->set('title', 'AI Marketing in 2026')
        ->set('categoryId', $category->id)
        ->set('content', str_repeat('AI marketing helps brands grow. ', 50))
        ->set('seoMetaTitle', 'AI Marketing Strategy for 2026 — Complete Guide')
        ->set('seoMetaDescription', 'A practical guide to AI-powered marketing that drives measurable roi for brands of every size in 2026.')
        ->set('seoFocusKeyword', 'ai marketing')
        ->set('seoRobots', 'index,follow')
        ->call('saveDraft');

    $post = Post::query()->latest('id')->first();
    expect($post)->not->toBeNull();

    $translation = $post->translations()->first();
    expect($translation->meta_title)->toBe('AI Marketing Strategy for 2026 — Complete Guide');
    expect($translation->focus_keyword)->toBe('ai marketing');

    $seo = SeoMeta::query()->where('seoable_id', $post->id)->first();
    expect($seo)->not->toBeNull();
    expect($seo->robots)->toBe('index,follow');
});

test('ai.seo-generated event populates SEO panel fields', function (): void {
    $admin = seoUser('Admin');
    $post = Post::factory()->draft()->create();

    Livewire::actingAs($admin)
        ->test(Edit::class, ['post' => $post])
        ->dispatch('ai.seo-generated', payload: [
            'meta_title' => 'AI-generated meta title',
            'meta_description' => 'AI-generated meta description for the SERP.',
            'focus_keyword' => 'ai marketing',
            'meta_keywords' => ['ai', 'marketing', '2026'],
            'slug' => 'ai-generated-slug',
        ])
        ->assertSet('seoMetaTitle', 'AI-generated meta title')
        ->assertSet('seoMetaDescription', 'AI-generated meta description for the SERP.')
        ->assertSet('seoFocusKeyword', 'ai marketing')
        ->assertSet('seoMetaKeywords', 'ai, marketing, 2026')
        ->assertSet('slug', 'ai-generated-slug');
});

test('ai.seo-generated does not overwrite manually set fields when payload omits them', function (): void {
    $admin = seoUser('Admin');
    $post = Post::factory()->draft()->create();

    Livewire::actingAs($admin)
        ->test(Edit::class, ['post' => $post])
        ->set('seoMetaTitle', 'Manually curated title')
        ->dispatch('ai.seo-generated', payload: [
            'meta_description' => 'AI description only.',
        ])
        ->assertSet('seoMetaTitle', 'Manually curated title')
        ->assertSet('seoMetaDescription', 'AI description only.');
});
