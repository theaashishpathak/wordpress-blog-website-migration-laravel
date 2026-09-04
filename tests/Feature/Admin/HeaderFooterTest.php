<?php

declare(strict_types=1);

use App\Livewire\Admin\HeaderFooter\Index as HeaderFooterIndex;
use App\Models\Language;
use App\Models\User;
use App\Services\SettingService;
use App\Support\LocaleResolver;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->english = Language::factory()->english()->default()->create();
    app(LocaleResolver::class)->flush();
    app(PermissionSeeder::class)->run();
});

function headerFooterAdmin(): User
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $role = Role::query()->where('name', 'Admin')->where('guard_name', 'web')->firstOrFail();
    $user->assignRole($role);

    return $user->fresh();
}

test('header-footer component mounts with settings and can switch tabs', function (): void {
    $admin = headerFooterAdmin();

    Livewire::actingAs($admin)
        ->test(HeaderFooterIndex::class)
        ->assertOk()
        ->assertSet('activeTab', 'header')
        ->call('setTab', 'footer')
        ->assertSet('activeTab', 'footer')
        ->call('setTab', 'ticker')
        ->assertSet('activeTab', 'ticker');
});

test('header-footer component saves custom branding and controls', function (): void {
    $admin = headerFooterAdmin();

    Livewire::actingAs($admin)
        ->test(HeaderFooterIndex::class)
        ->set('header_brand_text', 'CUSTOM BLOG')
        ->set('header_action_auth_text', 'My Account')
        ->set('header_action_auth_url', '/my-account')
        ->set('footer_brand_text', 'CUSTOM FOOTER BRAND')
        ->set('footer_social_twitter', 'https://x.com/customblog')
        ->call('save')
        ->assertDispatched('toast.success');

    $settings = app(SettingService::class);
    expect($settings->get('header.brand_text'))->toBe('CUSTOM BLOG');
    expect($settings->get('header.action_auth_text'))->toBe('My Account');
    expect($settings->get('footer.brand_text'))->toBe('CUSTOM FOOTER BRAND');
    expect($settings->get('footer.social_twitter'))->toBe('https://x.com/customblog');
});

test('frontend header renders custom brand name and active moon in dark mode', function (): void {
    $settings = app(SettingService::class);
    $settings->set('header.brand_text', 'NEWS EXPLORER');
    $settings->set('header.action_guest_text', 'Get Started');

    $view = (string) $this->view('components.frontend.header');

    expect($view)->toContain('NEWS EXPLORER');
    expect($view)->toContain('Get Started');
    // Verify that the knob is positioned at translate-x-0 for Moon when isDark is true
    expect($view)->toContain(":class=\"isDark ? 'translate-x-0' : 'translate-x-7'\"");
    expect($view)->toContain(":class=\"isDark ? 'text-indigo-400 dark:text-white font-bold' : 'text-slate-400 dark:text-neutral-500'\"");
});

test('frontend footer renders custom brand and social links', function (): void {
    $settings = app(SettingService::class);
    $settings->set('footer.brand_text', 'NEWS EXPLORER FOOTER');
    $settings->set('footer.social_github', 'https://github.com/newspilot');

    $view = (string) $this->view('components.frontend.footer');

    expect($view)->toContain('NEWS EXPLORER FOOTER');
    expect($view)->toContain('https://github.com/newspilot');
});
