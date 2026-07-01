<?php

use App\Models\Setting;
use App\Services\SettingService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('NewsPilot setting groups are registered in config', function (): void {
    $groupSlugs = array_keys((array) config('settings.groups', []));

    $expected = [
        // Pre-existing groups (must not be removed)
        'company-settings',
        'financial-settings',
        'email-smtp-settings',
        'notification-settings',
        'branding-settings',
        'file-storage-settings',
        'app-preferences',
        'security-settings',
        // New NewsPilot domain groups
        'site-settings',
        'seo-defaults',
        'ai-providers',
        'content-settings',
        'monetization-settings',
        'newsletter-settings',
        'social-settings',
    ];

    foreach ($expected as $slug) {
        expect($groupSlugs, "Missing settings group [{$slug}].")->toContain($slug);
    }
});

test('AI provider group declares all 4 encrypted API key fields', function (): void {
    $fields = collect(config('settings.groups.ai-providers.fields', []))
        ->keyBy('key')
        ->all();

    foreach (['ai.openai_api_key', 'ai.gemini_api_key', 'ai.claude_api_key', 'ai.openrouter_api_key'] as $key) {
        expect($fields, "Missing AI key [{$key}].")->toHaveKey($key);
        expect($fields[$key]['type'])->toBe(Setting::TYPE_ENCRYPTED);
    }
});

test('seeder writes default values via setValue so getValue can read them back', function (): void {
    app(SettingsSeeder::class)->run();

    $settings = app(SettingService::class);

    expect($settings->get('site.name'))->toBe('NewsPilot AI');
    expect($settings->get('site.posts_per_page'))->toBe(12);
    expect($settings->get('site.show_reading_time'))->toBeTrue();
    expect($settings->get('seo.enable_sitemap'))->toBeTrue();
    expect($settings->get('seo.robots_default'))->toBe('index,follow');
    expect($settings->get('ai.default_provider'))->toBe('openai');
    expect($settings->get('ai.default_model'))->toBe('gpt-4o-mini');
    expect($settings->get('ai.fallback_chain'))->toBe(['openai', 'gemini']);
});

test('AI quota_per_role default contains expected role mapping', function (): void {
    app(SettingsSeeder::class)->run();

    $quota = app(SettingService::class)->get('ai.quota_per_role');

    expect($quota)->toBeArray();
    expect($quota)->toHaveKey('Super Admin');
    expect($quota['Super Admin'])->toBeNull();   // unlimited
    expect($quota['Admin'])->toBe(10000);
    expect($quota['Editor'])->toBe(500);
    expect($quota['Author'])->toBe(100);
    expect($quota['Contributor'])->toBe(20);
});

test('encrypted setting keys are seeded but with null value (admin fills them later)', function (): void {
    app(SettingsSeeder::class)->run();

    foreach (['ai.openai_api_key', 'ai.gemini_api_key', 'ai.claude_api_key', 'ai.openrouter_api_key'] as $key) {
        $row = Setting::query()->where('key', $key)->first();

        expect($row)->not->toBeNull("Encrypted setting [{$key}] missing.");
        expect($row->type)->toBe(Setting::TYPE_ENCRYPTED);
        expect($row->value)->toBeNull("Encrypted setting [{$key}] should default to null, not a plaintext default.");
    }
});

test('seeder preserves existing user-edited values on re-seed', function (): void {
    app(SettingsSeeder::class)->run();

    // Simulate admin updating the site name through the UI.
    $service = app(SettingService::class);
    $service->set('site.name', 'My Custom News Portal', 'site-settings', Setting::TYPE_TEXT);

    // Re-run the seeder; user value should NOT be overwritten by config default.
    app(SettingsSeeder::class)->run();
    $service->reloadCache();

    expect(app(SettingService::class)->get('site.name'))->toBe('My Custom News Portal');
});

test('seeder corrects type drift when config type changes for an existing key', function (): void {
    // Pretend an older seeder created the row with the wrong type.
    Setting::query()->create([
        'key' => 'ai.openai_api_key',
        'group' => 'ai-providers',
        'type' => Setting::TYPE_TEXT,
        'value' => null,
    ]);

    app(SettingsSeeder::class)->run();

    expect(Setting::query()->where('key', 'ai.openai_api_key')->value('type'))
        ->toBe(Setting::TYPE_ENCRYPTED);
});

test('encrypted setting set via SettingService is encrypted at rest', function (): void {
    app(SettingsSeeder::class)->run();

    $secret = 'sk-supersecret-abcdef1234567890';

    app(SettingService::class)->set('ai.openai_api_key', $secret, 'ai-providers', Setting::TYPE_ENCRYPTED);

    $raw = Setting::query()->where('key', 'ai.openai_api_key')->value('value');

    expect($raw)->not->toBeNull();
    expect($raw)->not->toContain('supersecret');
    expect($raw)->not->toContain('sk-supersecret-abcdef');

    // SettingService caches values — clear so we hit DB on next read.
    app(SettingService::class)->reloadCache();

    expect(app(SettingService::class)->get('ai.openai_api_key'))->toBe($secret);
});
