<?php

use App\Http\Middleware\SetLocale;
use App\Models\Language;
use App\Support\LocaleResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Seed only what these tests need — avoids running the full DatabaseSeeder.
    Language::factory()->english()->default()->create();
    Language::factory()->bangla()->create();
    Language::factory()->arabicRtl()->create();

    // Active language cache is per-request; flush so each test sees fresh state.
    app(LocaleResolver::class)->flush();

    // Register a couple of throw-away routes that go through the middleware.
    Route::middleware(['web', SetLocale::class])->group(function (): void {
        Route::get('/_test/locale-probe/{locale?}', function () {
            return response()->json([
                'app_locale' => app()->getLocale(),
                'current' => app(LocaleResolver::class)->current()?->code,
            ]);
        });
    });
});

test('valid locale URL segment sets the application locale', function (): void {
    $response = $this->get('/_test/locale-probe/bn');

    $response->assertOk();
    $response->assertJson([
        'app_locale' => 'bn',
        'current' => 'bn',
    ]);
});

test('rtl locale resolves correctly', function (): void {
    $response = $this->get('/_test/locale-probe/ar');

    $response->assertOk();
    $response->assertJson([
        'app_locale' => 'ar',
        'current' => 'ar',
    ]);
});

test('unknown locale falls back to the default language', function (): void {
    $response = $this->get('/_test/locale-probe/zz');

    $response->assertOk();
    $response->assertJson([
        'app_locale' => 'en',
        'current' => 'en',
    ]);
});

test('inactive language code falls back to default', function (): void {
    Language::factory()->state([
        'code' => 'pt',
        'name' => 'Portuguese',
        'native_name' => 'Português',
        'is_active' => false,
    ])->create();

    app(LocaleResolver::class)->flush();

    $response = $this->get('/_test/locale-probe/pt');

    $response->assertOk();
    $response->assertJson([
        'app_locale' => 'en',
        'current' => 'en',
    ]);
});

test('missing locale segment falls back to default language', function (): void {
    $response = $this->get('/_test/locale-probe');

    $response->assertOk();
    $response->assertJson([
        'app_locale' => 'en',
        'current' => 'en',
    ]);
});

test('session locale persists between requests when URL has no segment', function (): void {
    $this->withSession(['locale' => 'bn'])
        ->get('/_test/locale-probe')
        ->assertOk()
        ->assertJson(['app_locale' => 'bn']);
});

test('url segment overrides session locale', function (): void {
    $this->withSession(['locale' => 'bn'])
        ->get('/_test/locale-probe/ar')
        ->assertOk()
        ->assertJson(['app_locale' => 'ar']);
});

test('accept language header is honoured when no other source provides a locale', function (): void {
    $response = $this->withHeader('Accept-Language', 'bn,en;q=0.8')
        ->get('/_test/locale-probe');

    $response->assertOk();
    $response->assertJson(['app_locale' => 'bn']);
});

test('locale resolver caches the active language map', function (): void {
    $resolver = app(LocaleResolver::class);

    $first = $resolver->activeMap();
    $codesFirst = array_keys($first);

    // Add a new active language directly via DB, BYPASSING the resolver's cache.
    Language::query()->create([
        'code' => 'de',
        'name' => 'German',
        'native_name' => 'Deutsch',
        'direction' => Language::DIRECTION_LTR,
        'is_active' => true,
    ]);

    // Cache should still reflect the original set until flushed.
    $cached = $resolver->activeMap();
    expect(array_keys($cached))->toBe($codesFirst);

    // After flush, new language should appear.
    $resolver->flush();
    expect(array_keys($resolver->activeMap()))->toContain('de');
});

test('App::setLocale is set even when languages table is empty', function (): void {
    // Wipe seeded data first
    Language::query()->delete();
    app(LocaleResolver::class)->flush();

    $response = $this->get('/_test/locale-probe/bn');

    // Middleware must not crash; falls back to config('app.locale').
    $response->assertOk();
    expect(App::getLocale())->not->toBeEmpty();
});
