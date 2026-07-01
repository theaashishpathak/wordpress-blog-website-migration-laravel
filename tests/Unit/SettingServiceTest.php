<?php

use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

test('settings service reads values and refreshes cache when updated', function () {
    Cache::flush();

    $setting = Setting::query()->create([
        'group' => 'company-settings',
        'key' => 'company.name',
        'value' => json_encode('Acme CRM'),
        'type' => Setting::TYPE_TEXT,
    ]);

    $service = app(SettingService::class);

    expect($service->get('company.name'))->toBe('Acme CRM');

    $setting->forceFill([
        'value' => json_encode('Updated CRM'),
    ])->save();

    expect($service->get('company.name'))->toBe('Acme CRM');

    $service->reloadCache();

    expect($service->get('company.name'))->toBe('Updated CRM');
});
