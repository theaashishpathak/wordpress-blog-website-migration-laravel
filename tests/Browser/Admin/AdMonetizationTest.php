<?php

declare(strict_types=1);

/**
 * Ad monetization journey:
 *
 *   Admin creates a zone + creative → visitor sees the ad on a post →
 *   clicking the ad bumps `click_count` and redirects to target_url.
 */

use App\Models\AdCreative;
use App\Models\AdZone;
use App\Models\Language;
use App\Models\Post;
use App\Models\PostTranslation;
use App\Models\User;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();
});

function adAdmin(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole(Role::query()->where('name', 'Admin')->firstOrFail());

    return $user->fresh();
}

test('admin can land on the Ad Manager page and see the zone list', function (): void {
    $admin = adAdmin();

    visit('/admin/ads', as: $admin)
        ->assertOk()
        ->assertSee('Ad Manager');
});

test('frontend renders a creative wired to a zone on the post page', function (): void {
    $zone = AdZone::factory()->create([
        'placement' => 'post_inline',
        'is_active' => true,
    ]);
    $creative = AdCreative::factory()->active()->create([
        'zone_id' => $zone->id,
        'target_url' => 'https://example.com/partner',
        'alt' => 'Sponsored: Acme Co.',
    ]);

    $author = User::factory()->create(['email_verified_at' => now()]);
    $post = Post::factory()->published()->withAuthor($author->id)->create();
    PostTranslation::query()->where('post_id', $post->id)
        ->update(['title' => 'Ad Test Post', 'slug' => 'ad-test-post']);

    visit('/ad-test-post')
        ->assertOk()
        ->assertSee('Sponsored: Acme Co.');

    // Impression should be recorded by the AdSlot blade component.
    expect($creative->fresh()->impression_count)->toBeGreaterThan(0);
});

test('GET /ads/click/{creative} increments click_count and redirects to the target', function (): void {
    $creative = AdCreative::factory()->active()->create([
        'target_url' => 'https://example.com/partner',
        'click_count' => 0,
    ]);

    visit('/ads/click/'.$creative->id)
        ->assertPathIs('https://example.com/partner');

    expect($creative->fresh()->click_count)->toBe(1);
});
