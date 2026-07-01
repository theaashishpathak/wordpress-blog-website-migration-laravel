<?php

declare(strict_types=1);

/**
 * Multi-language switch — verifies the /{locale?} prefix kicks in and
 * the SetLocale middleware swaps the default language so the same post
 * renders its Bangla translation under /bn/{slug}.
 */

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Language;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\User;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    $this->bangla = Language::factory()->state([
        'code' => 'bn',
        'name' => 'Bangla',
        'is_active' => true,
        'is_default' => false,
    ])->create();

    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();

    $cat = Category::factory()->withoutTranslations()->create();
    CategoryTranslation::query()->create([
        'category_id' => $cat->id,
        'language_id' => $this->english->id,
        'name' => 'Tech',
        'slug' => 'tech',
    ]);
    CategoryTranslation::query()->create([
        'category_id' => $cat->id,
        'language_id' => $this->bangla->id,
        'name' => 'প্রযুক্তি',
        'slug' => 'projukti',
    ]);

    $author = User::factory()->create(['email_verified_at' => now()]);
    $this->post = Post::factory()->published()->withAuthor($author->id)
        ->state(['category_id' => $cat->id])->create();

    // English translation already created by factory; just update.
    PostTranslation::query()->where('post_id', $this->post->id)
        ->where('language_id', $this->english->id)
        ->update(['title' => 'Hello World', 'slug' => 'hello-world']);

    PostTranslation::query()->create([
        'post_id' => $this->post->id,
        'language_id' => $this->bangla->id,
        'title' => 'হ্যালো ওয়ার্ল্ড',
        'slug' => 'hyalo-warld',
    ]);
});

test('default locale renders English content under /', function (): void {
    visit('/hello-world')
        ->assertOk()
        ->assertSee('Hello World');
});

test('bangla prefix renders the Bangla translation', function (): void {
    visit('/bn/hyalo-warld')
        ->assertOk()
        ->assertSee('হ্যালো ওয়ার্ল্ড');
});

test('locale switcher in the header navigates to the prefixed URL', function (): void {
    visit('/hello-world')
        ->assertOk()
        ->click('a[href*="/bn"]')
        ->assertPathContains('/bn');
});

test('unsupported locale prefix 404s', function (): void {
    visit('/zz/hello-world')
        ->assertStatus(404);
});
