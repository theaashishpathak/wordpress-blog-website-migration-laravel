<?php

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('plain text settings round-trip without encryption', function (): void {
    $setting = new Setting([
        'key' => 'site.name',
        'group' => 'site-settings',
        'type' => Setting::TYPE_TEXT,
    ]);

    $setting->setValue('NewsPilot AI');
    $setting->save();

    // Raw DB column should be a JSON-encoded plain string.
    $raw = Setting::query()->where('key', 'site.name')->value('value');
    expect($raw)->toBe('"NewsPilot AI"');

    expect($setting->fresh()->getValue())->toBe('NewsPilot AI');
});

test('encrypted settings round-trip via Crypt and are not stored as plaintext', function (): void {
    $secret = 'sk-test-1234567890abcdefghijklmnop';

    $setting = new Setting([
        'key' => 'ai.openai_api_key',
        'group' => 'ai-providers',
        'type' => Setting::TYPE_ENCRYPTED,
    ]);

    $setting->setValue($secret);
    $setting->save();

    $raw = Setting::query()->where('key', 'ai.openai_api_key')->value('value');

    expect($raw)->not->toBeNull();
    expect($raw)->not->toContain('sk-test-1234567890');

    // Decryption succeeds via getValue() with correct app key.
    expect($setting->fresh()->getValue())->toBe($secret);
});

test('encrypted setting with null or empty value stores null', function (): void {
    $setting = new Setting([
        'key' => 'ai.gemini_api_key',
        'group' => 'ai-providers',
        'type' => Setting::TYPE_ENCRYPTED,
    ]);

    $setting->setValue('');
    $setting->save();

    expect($setting->fresh()->value)->toBeNull();
    expect($setting->fresh()->getValue())->toBeNull();

    $setting->setValue(null);
    $setting->save();

    expect($setting->fresh()->value)->toBeNull();
});

test('encrypted setting falls back to default on decryption failure', function (): void {
    $setting = new Setting([
        'key' => 'ai.claude_api_key',
        'group' => 'ai-providers',
        'type' => Setting::TYPE_ENCRYPTED,
    ]);

    // Simulate corrupted / tampered ciphertext on disk.
    $setting->value = json_encode('not-a-valid-cipher-blob');
    $setting->save();

    expect($setting->fresh()->getValue('fallback-default'))->toBe('fallback-default');
});

test('encrypted setting type is registered in the TYPES list', function (): void {
    expect(Setting::TYPES)->toContain(Setting::TYPE_ENCRYPTED);
    expect(Setting::TYPE_ENCRYPTED)->toBe('encrypted');
});

test('settings round-trip works after re-fetching the model from the database', function (): void {
    $secret = 'gem-secret-key-987654321';

    $created = new Setting([
        'key' => 'ai.gemini_api_key',
        'group' => 'ai-providers',
        'type' => Setting::TYPE_ENCRYPTED,
    ]);
    $created->setValue($secret);
    $created->save();

    // Fetch a brand new instance to make sure cast/decryption isn't relying
    // on in-memory state from setValue().
    $fresh = Setting::query()->where('key', 'ai.gemini_api_key')->firstOrFail();
    expect($fresh->getValue())->toBe($secret);

    // And the raw DB column round-trips through Crypt manually.
    $rawJson = $fresh->getAttributes()['value'];
    $cipher = json_decode($rawJson, true);
    expect(Crypt::decryptString($cipher))->toBe($secret);
});
