<?php

use App\Models\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('active scope filters out inactive languages', function (): void {
    Language::factory()->english()->create();
    Language::factory()->bangla()->create();
    Language::factory()->english()->inactive()->state(['code' => 'es', 'name' => 'Spanish'])->create();

    $codes = Language::query()->active()->pluck('code')->all();

    expect($codes)->toContain('en')->toContain('bn');
    expect($codes)->not->toContain('es');
});

test('default scope returns only the default language', function (): void {
    Language::factory()->english()->default()->create();
    Language::factory()->bangla()->create();
    Language::factory()->arabicRtl()->create();

    $defaults = Language::query()->default()->get();

    expect($defaults)->toHaveCount(1);
    expect($defaults->first()->code)->toBe('en');
});

test('admin locale scope returns only languages marked as admin', function (): void {
    Language::factory()->english()->adminLocale()->create();
    Language::factory()->bangla()->adminLocale()->create();
    Language::factory()->arabicRtl()->create();          // not admin
    Language::factory()->state([
        'code' => 'es', 'name' => 'Spanish', 'is_admin_locale' => false,
    ])->create();

    $adminCodes = Language::query()->adminLocale()->pluck('code')->all();

    expect($adminCodes)->toContain('en')->toContain('bn');
    expect($adminCodes)->not->toContain('ar')->not->toContain('es');
});

test('ordered scope sorts by sort_order ascending', function (): void {
    Language::factory()->english()->state(['sort_order' => 3])->create();
    Language::factory()->bangla()->state(['sort_order' => 1])->create();
    Language::factory()->arabicRtl()->state(['sort_order' => 2])->create();

    $codes = Language::query()->ordered()->pluck('code')->all();

    expect($codes)->toBe(['bn', 'ar', 'en']);
});

test('isRtl returns true only for RTL direction', function (): void {
    $arabic = Language::factory()->arabicRtl()->create();
    $english = Language::factory()->english()->create();

    expect($arabic->isRtl())->toBeTrue();
    expect($arabic->isLtr())->toBeFalse();
    expect($english->isRtl())->toBeFalse();
    expect($english->isLtr())->toBeTrue();
});

test('getFlagUrl returns null when flag icon is empty', function (): void {
    $lang = Language::factory()->english()->create();

    expect($lang->getFlagUrl())->toBeNull();
});

test('getFlagUrl returns absolute url when flag icon is already a URL', function (): void {
    $lang = Language::factory()->state([
        'code' => 'tt',
        'flag_icon' => 'https://cdn.example.com/flags/en.png',
    ])->create();

    expect($lang->getFlagUrl())->toBe('https://cdn.example.com/flags/en.png');
});

test('casts boolean and json fields correctly', function (): void {
    $lang = Language::factory()->english()->state([
        'date_format' => ['short' => 'd/m/Y', 'long' => 'd F Y'],
        'is_admin_locale' => 1,
    ])->create();

    expect($lang->is_admin_locale)->toBeTrue();
    expect($lang->date_format)->toBe(['short' => 'd/m/Y', 'long' => 'd F Y']);
});
