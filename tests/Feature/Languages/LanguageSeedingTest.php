<?php

use App\Models\Language;
use Database\Seeders\LanguageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('seeder seeds the six starter languages', function (): void {
    app(LanguageSeeder::class)->run();

    expect(Language::query()->count())->toBe(6);

    foreach (['en', 'bn', 'ar', 'es', 'fr', 'hi'] as $code) {
        expect(Language::query()->where('code', $code)->exists())
            ->toBeTrue("Language [{$code}] missing.");
    }
});

test('english is the only default language and is also an admin locale', function (): void {
    app(LanguageSeeder::class)->run();

    $defaults = Language::query()->where('is_default', true)->get();

    expect($defaults)->toHaveCount(1);
    expect($defaults->first()->code)->toBe('en');
    expect($defaults->first()->is_admin_locale)->toBeTrue();
});

test('arabic is seeded as RTL', function (): void {
    app(LanguageSeeder::class)->run();

    $arabic = Language::query()->where('code', 'ar')->first();

    expect($arabic)->not->toBeNull();
    expect($arabic->direction)->toBe(Language::DIRECTION_RTL);
    expect($arabic->isRtl())->toBeTrue();
    expect($arabic->isLtr())->toBeFalse();
});

test('bangla is seeded with native script name and admin locale flag', function (): void {
    app(LanguageSeeder::class)->run();

    $bangla = Language::query()->where('code', 'bn')->first();

    expect($bangla)->not->toBeNull();
    expect($bangla->native_name)->toBe('বাংলা');
    expect($bangla->is_admin_locale)->toBeTrue();
    expect($bangla->locale_php)->toBe('bn_BD');
});

test('reseeding is idempotent and does not duplicate rows', function (): void {
    app(LanguageSeeder::class)->run();
    app(LanguageSeeder::class)->run();
    app(LanguageSeeder::class)->run();

    expect(Language::query()->count())->toBe(6);
    expect(Language::query()->where('code', 'en')->count())->toBe(1);
});

test('reseeding updates changed fields in place', function (): void {
    app(LanguageSeeder::class)->run();

    // Simulate admin renaming the language manually.
    Language::query()->where('code', 'en')->update(['name' => 'EN-Renamed']);

    // Reseeding should restore the canonical name.
    app(LanguageSeeder::class)->run();

    expect(Language::query()->where('code', 'en')->value('name'))->toBe('English');
});
