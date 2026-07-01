<?php

declare(strict_types=1);

use App\Models\Language;
use App\Models\User;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();
});

function sidebarUser(string $roleName): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', $roleName)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

/*
 |--------------------------------------------------------------------------
 | Marker conventions used in these assertions
 |--------------------------------------------------------------------------
 | The sidebar uses unique `data-sidebar-menu-toggle="..."` IDs for each
 | dropdown group, plus distinct `data-lucide="..."` icon names for the
 | top-level standalone links. We assert on these structural markers
 | rather than plain words like "Content" because words can collide with
 | unrelated text in the response (CSS class names, inline scripts, etc.).
 |
 |   content-sidebar-menu   → Content dropdown button
 |   ai-sidebar-menu        → AI Studio dropdown button
 |   editorial-sidebar-menu → Editorial dropdown button
 |   data-lucide="search"   → SEO Tools link icon
 |   data-lucide="send"     → Newsletter link icon
 |   data-lucide="dollar-sign" → Monetization link icon
 |   data-lucide="globe"    → Languages link icon
 */

test('super admin sees every NewsPilot sidebar group', function (): void {
    $user = sidebarUser('Super Admin');

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('content-sidebar-menu')
        ->assertSee('ai-sidebar-menu')
        ->assertSee('editorial-sidebar-menu')
        ->assertSee('admin/posts');
});

test('editor sees Content + Editorial + AI Studio but no Monetization', function (): void {
    $user = sidebarUser('Editor');

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk()
        ->assertSee('content-sidebar-menu')
        ->assertSee('editorial-sidebar-menu')
        ->assertSee('ai-sidebar-menu')
        ->assertSee('admin/posts');

    // Editor lacks ads.* permissions — the Monetization icon block must not render.
    $response->assertDontSee('data-lucide="dollar-sign"', false);
});

test('author sees Content + AI Studio but neither Editorial dropdown nor Monetization', function (): void {
    $user = sidebarUser('Author');

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk()
        ->assertSee('content-sidebar-menu')
        ->assertSee('ai-sidebar-menu')
        ->assertSee('admin/posts');

    // Author has only editorial.notes/revisions (read-only, used INLINE on
    // post detail page). The sidebar Editorial group is gated to active
    // editorial perms (review_queue / calendar / approve) — Author lacks all three.
    $response->assertDontSee('editorial-sidebar-menu');
    $response->assertDontSee('data-lucide="dollar-sign"', false);
});

test('subscriber sees neither Content nor AI Studio nor Editorial sidebar groups', function (): void {
    $user = sidebarUser('Subscriber');

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();

    $response->assertDontSee('content-sidebar-menu');
    $response->assertDontSee('ai-sidebar-menu');
    $response->assertDontSee('editorial-sidebar-menu');
    $response->assertDontSee('admin/posts');
});

test('ad manager sees Monetization but neither Content nor AI Studio', function (): void {
    $user = sidebarUser('Ad Manager');

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk()
        ->assertSee('data-lucide="dollar-sign"', false);

    $response->assertDontSee('content-sidebar-menu');
    $response->assertDontSee('ai-sidebar-menu');
    $response->assertDontSee('editorial-sidebar-menu');
});

test('seo manager sees Content + SEO Tools link', function (): void {
    $user = sidebarUser('SEO Manager');

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk()
        ->assertSee('content-sidebar-menu')
        ->assertSee('admin/posts')
        // SEO Tools is a single top-level link with the search icon.
        ->assertSee('data-lucide="search"', false);
});
