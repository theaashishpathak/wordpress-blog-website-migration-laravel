<?php

use App\Models\Setting;
use App\Models\User;
use App\Services\SettingService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

function settingsUser(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::findOrCreate('Admin', 'web');

    foreach (['settings.view', 'settings.update'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $role->givePermissionTo(['settings.view', 'settings.update']);
    $user->assignRole($role);

    return $user;
}

test('admin settings pages render configured groups', function () {
    $user = settingsUser();

    $this->actingAs($user)
        ->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSee('General Settings')
        ->assertSee('Company Settings')
        ->assertSee('Security Settings');

    $this->actingAs($user)
        ->get(route('admin.settings.group', ['group' => 'branding-settings']))
        ->assertOk()
        ->assertSee('Branding Settings')
        ->assertSee('Primary Logo')
        ->assertSee('Favicon');
});

test('admin settings save route persists typed values and image uploads', function () {
    Storage::fake('public');

    $user = settingsUser();
    actingAs($user);

    $response = $this->post(route('admin.settings.save'), [
        'group' => 'branding-settings',
        'values' => [
            'branding__primary_color' => '#111827',
            'branding__secondary_color' => '#0f172a',
            'branding__custom_css' => json_encode(['scheme' => 'dark']),
        ],
        'uploads' => [
            'branding__logo' => UploadedFile::fake()->image('logo.png'),
            'branding__favicon' => UploadedFile::fake()->image('favicon.png'),
        ],
    ]);

    $response->assertRedirect(route('admin.settings.group', ['group' => 'branding-settings']));

    $settings = app(SettingService::class);

    expect($settings->get('branding.primary_color'))->toBe('#111827');
    expect($settings->get('branding.secondary_color'))->toBe('#0f172a');
    expect($settings->get('branding.custom_css'))->toBe(['scheme' => 'dark']);

    $logoPath = $settings->get('branding.logo');
    $faviconPath = $settings->get('branding.favicon');

    expect($logoPath)->toContain('settings/');
    expect($faviconPath)->toContain('settings/');

    Storage::disk('public')->assertExists($logoPath);
    Storage::disk('public')->assertExists($faviconPath);
});

test('settings seeder seeds every configured key', function () {
    $this->seed(SettingsSeeder::class);

    $expectedCount = collect(config('settings.groups', []))
        ->sum(fn (array $group): int => count($group['fields'] ?? []));

    expect(Setting::query()->count())->toBe($expectedCount);
});
