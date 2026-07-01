<?php

declare(strict_types=1);

use App\Livewire\Frontend\CategoryShow;
use App\Livewire\Frontend\PageShow;
use App\Livewire\Frontend\TagShow;
use App\Models\Category;
use App\Models\Language;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
});

// -------------------------------------------------------------------------
// CategoryShow
// -------------------------------------------------------------------------

test('category-show lists published posts in the category', function (): void {
    $cat = Category::factory()->withoutTranslations()->create();
    $cat->translations()->create([
        'language_id' => $this->english->id,
        'name' => 'Tech',
        'slug' => 'tech',
    ]);

    $inside = Post::factory()->published()->state(['category_id' => $cat->id])->create();
    $outside = Post::factory()->published()->create();   // different category

    $component = Livewire::test(CategoryShow::class, ['category' => $cat]);

    expect($component->instance()->posts->pluck('id'))->toContain($inside->id);
    expect($component->instance()->posts->pluck('id'))->not->toContain($outside->id);
});

test('category-show excludes draft posts', function (): void {
    $cat = Category::factory()->create();
    $draft = Post::factory()->draft()->state(['category_id' => $cat->id])->create();

    expect(Livewire::test(CategoryShow::class, ['category' => $cat])
        ->instance()->posts->pluck('id'))
        ->not->toContain($draft->id);
});

test('category-show renders the category name in the header', function (): void {
    $cat = Category::factory()->withoutTranslations()->create();
    $cat->translations()->create([
        'language_id' => $this->english->id,
        'name' => 'Politics',
        'slug' => 'politics',
    ]);

    Livewire::test(CategoryShow::class, ['category' => $cat->fresh()])
        ->assertOk()
        ->assertSee('Politics');
});

// -------------------------------------------------------------------------
// TagShow
// -------------------------------------------------------------------------

test('tag-show lists published posts attached to the tag', function (): void {
    $tag = Tag::factory()->create(['name' => 'AI', 'slug' => 'ai']);

    $post = Post::factory()->published()->create();
    $post->tags()->attach($tag->id, ['created_at' => now()]);

    $component = Livewire::test(TagShow::class, ['tag' => $tag]);

    expect($component->instance()->posts->pluck('id'))->toContain($post->id);
});

test('tag-show excludes posts not tagged', function (): void {
    $tag = Tag::factory()->create();
    $other = Post::factory()->published()->create();

    expect(Livewire::test(TagShow::class, ['tag' => $tag])
        ->instance()->posts->pluck('id'))
        ->not->toContain($other->id);
});

test('tag-show excludes draft posts even if tagged', function (): void {
    $tag = Tag::factory()->create();
    $draft = Post::factory()->draft()->create();
    $draft->tags()->attach($tag->id, ['created_at' => now()]);

    expect(Livewire::test(TagShow::class, ['tag' => $tag])
        ->instance()->posts->pluck('id'))
        ->not->toContain($draft->id);
});

// -------------------------------------------------------------------------
// PageShow
// -------------------------------------------------------------------------

test('page-show renders the page content + title', function (): void {
    $page = Page::factory()->withoutTranslations()->create(['status' => \App\Enums\PageStatus::Published->value]);
    $tr = $page->translations()->create([
        'language_id' => $this->english->id,
        'title' => 'About Us',
        'slug' => 'about-us',
        'content' => '<p>Our story begins here.</p>',
        'is_published' => true,
    ]);

    Livewire::test(PageShow::class, ['page' => $page->fresh(), 'translation' => $tr->fresh()])
        ->assertOk()
        ->assertSee('About Us')
        ->assertSee('Our story begins here', escape: false);
});
