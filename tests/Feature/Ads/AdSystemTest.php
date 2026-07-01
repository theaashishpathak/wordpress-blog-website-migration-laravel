<?php

declare(strict_types=1);

use App\Actions\Ad\CreateAdCreativeAction;
use App\Actions\Ad\CreateAdZoneAction;
use App\Actions\Ad\DeleteAdCreativeAction;
use App\Actions\Ad\RecordClickAction;
use App\Actions\Ad\RecordImpressionAction;
use App\Actions\Ad\UpdateAdCreativeAction;
use App\Actions\Ad\UpdateAdZoneAction;
use App\Livewire\Admin\Ads\Index as AdsIndex;
use App\Livewire\Frontend\PostShow;
use App\Models\AdCreative;
use App\Models\AdZone;
use App\Models\Language;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();
});

function adsAdmin(string $role = 'Admin'): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $r = Role::query()->where('name', $role)->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($r);

    return $user->fresh();
}

// -------------------------------------------------------------------------
// Zone Actions
// -------------------------------------------------------------------------

test('CreateAdZoneAction creates a zone with a slugified key', function (): void {
    $zone = app(CreateAdZoneAction::class)->handle([
        'name' => 'Homepage Top Banner',
        'position' => AdZone::POSITION_TOP,
    ]);

    expect($zone->name)->toBe('Homepage Top Banner');
    expect($zone->key)->toBe('homepage_top_banner');
    expect($zone->is_active)->toBeTrue();
});

test('CreateAdZoneAction rejects duplicate keys', function (): void {
    app(CreateAdZoneAction::class)->handle(['name' => 'Slot A', 'key' => 'slot_a']);

    expect(fn () => app(CreateAdZoneAction::class)->handle(['name' => 'Slot A again', 'key' => 'slot_a']))
        ->toThrow(ValidationException::class);
});

test('UpdateAdZoneAction does not allow key changes', function (): void {
    $zone = AdZone::factory()->create(['key' => 'original_key']);
    app(UpdateAdZoneAction::class)->handle($zone, ['key' => 'new_key', 'name' => 'Renamed']);

    expect($zone->fresh()->key)->toBe('original_key');
    expect($zone->fresh()->name)->toBe('Renamed');
});

// -------------------------------------------------------------------------
// Creative Actions
// -------------------------------------------------------------------------

test('CreateAdCreativeAction creates an image creative', function (): void {
    $zone = AdZone::factory()->create();
    $media = Media::factory()->create(['mime_type' => 'image/jpeg']);

    $creative = app(CreateAdCreativeAction::class)->handle([
        'zone_id' => $zone->id,
        'name' => 'Sample image ad',
        'type' => AdCreative::TYPE_IMAGE,
        'media_id' => $media->id,
        'target_url' => 'https://example.com',
    ]);

    expect($creative->zone_id)->toBe($zone->id);
    expect($creative->type)->toBe(AdCreative::TYPE_IMAGE);
    expect($creative->status)->toBe(AdCreative::STATUS_DRAFT);
});

test('CreateAdCreativeAction refuses image type without a media row', function (): void {
    $zone = AdZone::factory()->create();

    expect(fn () => app(CreateAdCreativeAction::class)->handle([
        'zone_id' => $zone->id,
        'name' => 'Missing media',
        'type' => AdCreative::TYPE_IMAGE,
    ]))->toThrow(ValidationException::class);
});

test('CreateAdCreativeAction refuses html type without code', function (): void {
    $zone = AdZone::factory()->create();

    expect(fn () => app(CreateAdCreativeAction::class)->handle([
        'zone_id' => $zone->id,
        'name' => 'Missing code',
        'type' => AdCreative::TYPE_HTML,
    ]))->toThrow(ValidationException::class);
});

test('UpdateAdCreativeAction flips status from active to paused', function (): void {
    $c = AdCreative::factory()->active()->create();

    app(UpdateAdCreativeAction::class)->handle($c, ['status' => AdCreative::STATUS_PAUSED]);

    expect($c->fresh()->status)->toBe(AdCreative::STATUS_PAUSED);
});

test('DeleteAdCreativeAction soft-deletes by default', function (): void {
    $c = AdCreative::factory()->create();

    app(DeleteAdCreativeAction::class)->handle($c);

    expect(AdCreative::query()->find($c->id))->toBeNull();
    expect(AdCreative::onlyTrashed()->whereKey($c->id)->exists())->toBeTrue();
});

// -------------------------------------------------------------------------
// Impression + click tracking
// -------------------------------------------------------------------------

test('RecordImpressionAction increments the counter atomically', function (): void {
    $c = AdCreative::factory()->active()->create(['impression_count' => 5]);

    app(RecordImpressionAction::class)->handle($c);
    app(RecordImpressionAction::class)->handle($c);

    expect($c->fresh()->impression_count)->toBe(7);
});

test('RecordClickAction increments the click counter', function (): void {
    $c = AdCreative::factory()->active()->create(['click_count' => 0]);

    app(RecordClickAction::class)->handle($c);

    expect($c->fresh()->click_count)->toBe(1);
});

test('ctrPercent computes click-through rate correctly', function (): void {
    $c = AdCreative::factory()->create([
        'impression_count' => 200,
        'click_count' => 10,
    ]);

    expect($c->ctrPercent())->toEqualWithDelta(5.0, 0.01);
});

test('GET /ads/click/{creative} bumps clicks and 302-redirects to target_url', function (): void {
    $c = AdCreative::factory()->active()->create([
        'target_url' => 'https://example.com/partner',
        'click_count' => 0,
    ]);

    $this->get(route('ads.click', ['creative' => $c->id]))
        ->assertRedirect('https://example.com/partner');

    expect($c->fresh()->click_count)->toBe(1);
});

test('GET /ads/click/{creative} 404s for missing or url-less creative', function (): void {
    $c = AdCreative::factory()->create(['target_url' => null]);

    $this->get(route('ads.click', ['creative' => $c->id]))->assertNotFound();
    $this->get(route('ads.click', ['creative' => 99999]))->assertNotFound();
});

// -------------------------------------------------------------------------
// Servable scope (active + within scheduling window)
// -------------------------------------------------------------------------

test('servable scope excludes paused / draft / expired creatives', function (): void {
    AdCreative::factory()->active()->create();
    AdCreative::factory()->paused()->create();
    AdCreative::factory()->create();   // draft
    AdCreative::factory()->expired()->create();

    expect(AdCreative::query()->servable()->count())->toBe(1);
});

test('servable scope excludes creatives outside their scheduling window', function (): void {
    AdCreative::factory()->create([
        'status' => AdCreative::STATUS_ACTIVE,
        'start_at' => now()->addDay(),
        'end_at' => null,
    ]);
    AdCreative::factory()->create([
        'status' => AdCreative::STATUS_ACTIVE,
        'start_at' => now()->subDay(),
        'end_at' => now()->subHour(),
    ]);
    $servable = AdCreative::factory()->create([
        'status' => AdCreative::STATUS_ACTIVE,
        'start_at' => now()->subDay(),
        'end_at' => now()->addDay(),
    ]);

    expect(AdCreative::query()->servable()->pluck('id')->all())->toBe([$servable->id]);
});

// -------------------------------------------------------------------------
// Premium paywall
// -------------------------------------------------------------------------

test('non-premium post renders the full content', function (): void {
    $post = Post::factory()->published()->state(['is_premium' => false])->create();
    $tr = $post->translations()->first();
    $tr->update(['content' => '<p>Full content visible to all</p>']);

    $component = Livewire::test(PostShow::class, ['post' => $post->fresh(), 'translation' => $tr->fresh()]);

    expect($component->instance()->isPaywalled)->toBeFalse();
});

test('premium post is paywalled for anonymous visitor', function (): void {
    $post = Post::factory()->published()->state(['is_premium' => true])->create();
    $tr = $post->translations()->first();

    $component = Livewire::test(PostShow::class, ['post' => $post->fresh(), 'translation' => $tr->fresh()]);

    expect($component->instance()->isPaywalled)->toBeTrue();
});

test('premium post is paywalled for logged-in user without premium.access', function (): void {
    $user = User::factory()->create();
    $post = Post::factory()->published()->state(['is_premium' => true])->create();
    $tr = $post->translations()->first();

    $component = Livewire::actingAs($user)->test(PostShow::class, [
        'post' => $post->fresh(),
        'translation' => $tr->fresh(),
    ]);

    expect($component->instance()->isPaywalled)->toBeTrue();
});

test('premium post unlocks for users with premium.access permission', function (): void {
    $user = User::factory()->create();
    $user->givePermissionTo('premium.access');

    $post = Post::factory()->published()->state(['is_premium' => true])->create();
    $tr = $post->translations()->first();

    $component = Livewire::actingAs($user->fresh())->test(PostShow::class, [
        'post' => $post->fresh(),
        'translation' => $tr->fresh(),
    ]);

    expect($component->instance()->isPaywalled)->toBeFalse();
});

test('paywallTeaser strips HTML and limits to ~80 words', function (): void {
    $post = Post::factory()->published()->state(['is_premium' => true])->create();
    $tr = $post->translations()->first();
    $tr->update([
        'content' => '<p>'.str_repeat('word ', 200).'</p>',
    ]);

    $component = Livewire::test(PostShow::class, ['post' => $post->fresh(), 'translation' => $tr->fresh()]);
    $teaser = $component->instance()->paywallTeaser;

    expect(str_word_count($teaser))->toBeLessThanOrEqual(80);
    expect($teaser)->not->toContain('<p>');
});

// -------------------------------------------------------------------------
// Admin Ads Livewire
// -------------------------------------------------------------------------

test('users without ads.view are denied admin access', function (): void {
    $u = User::factory()->create(['email_verified_at' => now()]);

    Livewire::actingAs($u)->test(AdsIndex::class)->assertForbidden();
});

test('admin can view the ad manager', function (): void {
    $admin = adsAdmin();
    AdZone::factory()->create(['name' => 'My Zone']);

    Livewire::actingAs($admin)->test(AdsIndex::class)->assertOk()->assertSee('Ad Manager');
});

test('saveZone creates a new zone via the Action', function (): void {
    $admin = adsAdmin();

    Livewire::actingAs($admin)
        ->test(AdsIndex::class)
        ->call('newZone')
        ->set('zoneName', 'Sidebar 300x250')
        ->set('zonePosition', AdZone::POSITION_SIDEBAR)
        ->set('zoneWidth', 300)
        ->set('zoneHeight', 250)
        ->call('saveZone')
        ->assertSet('showZoneForm', false);

    expect(AdZone::query()->where('name', 'Sidebar 300x250')->exists())->toBeTrue();
});

test('saveCreative creates an image creative via the Action', function (): void {
    $admin = adsAdmin();
    $zone = AdZone::factory()->create();
    $media = Media::factory()->create(['mime_type' => 'image/jpeg']);

    Livewire::actingAs($admin)
        ->test(AdsIndex::class)
        ->set('tab', 'creatives')
        ->call('newCreative')
        ->set('cZoneId', $zone->id)
        ->set('cName', 'Test image creative')
        ->set('cType', AdCreative::TYPE_IMAGE)
        ->set('cMediaId', $media->id)
        ->set('cTargetUrl', 'https://example.com/partner')
        ->set('cStatus', AdCreative::STATUS_ACTIVE)
        ->call('saveCreative')
        ->assertSet('showCreativeForm', false);

    expect(AdCreative::query()->where('name', 'Test image creative')->exists())->toBeTrue();
});

test('toggleCreativeStatus flips active to paused', function (): void {
    $admin = adsAdmin();
    $c = AdCreative::factory()->active()->create();

    Livewire::actingAs($admin)
        ->test(AdsIndex::class)
        ->call('toggleCreativeStatus', $c->id);

    expect($c->fresh()->status)->toBe(AdCreative::STATUS_PAUSED);
});
