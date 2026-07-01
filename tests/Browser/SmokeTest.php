<?php

declare(strict_types=1);

/**
 * Smoke test — hits every primary route as different actors and asserts
 * the page renders without HTTP errors or JavaScript console errors.
 *
 * This is the single biggest safety net for a CodeCanyon release: it
 * catches blade syntax mistakes, missing routes, broken Livewire mounts
 * and JS regressions across the whole product in one pass. Add a new
 * route → add a new line to the datasets below.
 *
 * Skipped if Pest's Chromium binary isn't installed locally; the test
 * also degrades to assertOk() if the headless browser is unavailable
 * (so CI without a browser still verifies HTTP correctness).
 */

use App\Models\Category;
use App\Models\CategoryTranslation;
use App\Models\Language;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    // Default language + minimal locale plumbing — all frontend routes
    // depend on a default language being resolvable.
    $this->english = Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();

    // Sample content so detail routes don't 404 under the smoke.
    $this->category = Category::factory()->withoutTranslations()->create();
    CategoryTranslation::query()->create([
        'category_id' => $this->category->id,
        'language_id' => $this->english->id,
        'name' => 'Technology',
        'slug' => 'technology',
    ]);

    $this->tag = Tag::factory()->create(['name' => 'AI', 'slug' => 'ai']);

    $this->post = Post::factory()->published()->withAuthor(
        User::factory()->create(['email_verified_at' => now()])->id
    )->state(['category_id' => $this->category->id])->create();

    $this->page = Page::factory()->withoutTranslations()->create([
        'status' => \App\Enums\PageStatus::Published->value,
    ]);
    PageTranslation::query()->create([
        'page_id' => $this->page->id,
        'language_id' => $this->english->id,
        'title' => 'About Us',
        'slug' => 'about',
        'content' => '<p>About us page.</p>',
        'is_published' => true,
    ]);
});

/**
 * Helper — assign a role to a fresh user.
 */
function smokeUserWith(string $roleName): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

// -------------------------------------------------------------------------
// Guest-accessible routes — the public website + auth pages.
// -------------------------------------------------------------------------

test('smoke: guest can load public frontend routes', function (string $path): void {
    visit($path)
        ->assertOk()
        ->assertNoJavaScriptErrors();
})->with([
    'home' => ['/'],
    'home (en prefix)' => ['/en'],
    'about page' => ['/page/about'],
    'category page' => ['/category/technology'],
    'sitemap' => ['/sitemap.xml'],
    'robots' => ['/robots.txt'],
    'rss feed' => ['/feed.xml'],
    'login' => ['/login'],
    'register' => ['/register'],
    'forgot password' => ['/forgot-password'],
]);

// -------------------------------------------------------------------------
// Super-Admin sees everything — the longest smoke fan-out.
// -------------------------------------------------------------------------

test('smoke: super admin can load every admin route', function (string $path): void {
    $admin = smokeUserWith('Super Admin');

    visit($path, as: $admin)
        ->assertOk()
        ->assertNoJavaScriptErrors();
})->with([
    'dashboard' => ['/dashboard'],
    'notifications' => ['/notifications'],
    'profile' => ['/user/profile'],
    'settings' => ['/settings'],
    'posts index' => ['/admin/posts'],
    'posts create' => ['/admin/posts/create'],
    'categories index' => ['/admin/categories'],
    'categories create' => ['/admin/categories/create'],
    'tags index' => ['/admin/tags'],
    'media index' => ['/admin/media'],
    'pages index' => ['/admin/pages'],
    'pages create' => ['/admin/pages/create'],
    'languages index' => ['/admin/languages'],
    'editorial queue' => ['/admin/editorial/queue'],
    'editorial calendar' => ['/admin/editorial/calendar'],
    'newsletter subscribers' => ['/admin/newsletter/subscribers'],
    'comments moderation' => ['/admin/comments'],
    'ads index' => ['/admin/ads'],
    'rss sources' => ['/admin/imports/sources'],
    'staff index' => ['/admin/staff'],
    'roles' => ['/admin/roles'],
    'permissions' => ['/admin/permissions'],
    'assign role' => ['/admin/assign-role'],
    'assign user permissions' => ['/admin/assign-user-permissions'],
    'login log' => ['/admin/logs/login'],
    'activity log' => ['/admin/logs/activity'],
]);

// -------------------------------------------------------------------------
// Role-scoped routes — ensure each persona sees the right slice.
// -------------------------------------------------------------------------

test('smoke: author can load author portal routes', function (string $path): void {
    $author = smokeUserWith('Author');

    visit($path, as: $author)
        ->assertOk()
        ->assertNoJavaScriptErrors();
})->with([
    'dashboard (dispatched author)' => ['/dashboard'],
    'admin posts (author scope)' => ['/admin/posts'],
    'admin posts create' => ['/admin/posts/create'],
    'author profile editor' => ['/author/profile'],
    'notifications' => ['/notifications'],
]);

test('smoke: editor can load editorial routes', function (string $path): void {
    $editor = smokeUserWith('Editor');

    visit($path, as: $editor)
        ->assertOk()
        ->assertNoJavaScriptErrors();
})->with([
    'dashboard' => ['/dashboard'],
    'admin posts' => ['/admin/posts'],
    'editorial queue' => ['/admin/editorial/queue'],
    'editorial calendar' => ['/admin/editorial/calendar'],
    'comments moderation' => ['/admin/comments'],
]);

test('smoke: ad manager can load monetization routes', function (string $path): void {
    $adManager = smokeUserWith('Ad Manager');

    visit($path, as: $adManager)
        ->assertOk()
        ->assertNoJavaScriptErrors();
})->with([
    'dashboard' => ['/dashboard'],
    'ads index' => ['/admin/ads'],
]);
