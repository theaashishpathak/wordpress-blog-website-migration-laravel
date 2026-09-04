<?php

declare(strict_types=1);

namespace App\Livewire\Admin\HeaderFooter;

use App\Services\SettingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Header & Footer Customizer')]
class Index extends Component
{
    public string $activeTab = 'header'; // 'header' | 'footer' | 'ticker'

    // ── Header Settings ───────────────────────────────────────────────────────
    public string $header_brand_text = '';
    public string $header_brand_icon = 'sparkles';
    public string $header_logo_type = 'text'; // 'text' | 'image'
    public string $header_logo_url = '';
    public int $header_logo_height = 32;

    public bool $header_show_search = true;
    public bool $header_show_theme_toggle = true;
    public bool $header_sticky = true;

    public bool $header_show_action_button = true;
    public string $header_action_auth_text = 'Dashboard';
    public string $header_action_auth_url = '/admin';
    public string $header_action_guest_text = 'Sign in';
    public string $header_action_guest_url = '/login';
    public string $header_action_icon = 'layout-dashboard';

    // ── Footer Settings ───────────────────────────────────────────────────────
    public string $footer_brand_text = '';
    public string $footer_brand_icon = 'sparkles';
    public string $footer_logo_type = 'text'; // 'text' | 'image'
    public string $footer_logo_url = '';
    public int $footer_logo_height = 32;
    public string $footer_description = '';
    public string $footer_copyright = '© {year} — {siteName}. All Rights Reserved.';

    public string $footer_social_facebook = '';
    public string $footer_social_twitter = '';
    public string $footer_social_instagram = '';
    public string $footer_social_linkedin = '';
    public string $footer_social_youtube = '';
    public string $footer_social_github = '';

    public string $footer_col1_title = 'HOMEPAGES';
    public string $footer_col2_title = 'CATEGORIES';
    public int $footer_col2_count = 5;
    public string $footer_col3_title = 'PAGES';
    public int $footer_col3_count = 6;

    // ── Bottom Ticker Settings ────────────────────────────────────────────────
    public bool $footer_ticker_enabled = true;
    public int $footer_ticker_count = 10;
    public bool $footer_show_back_to_top = true;

    public function mount(SettingService $settings): void
    {
        abort_unless(
            auth()->user()?->can('settings.view') ?? false,
            403,
            'You do not have permission to view appearance settings.',
        );

        // Header
        $this->header_brand_text = (string) ($settings->get('header.brand_text') ?? ($settings->get('site.name') ?? 'RUPANTRIX'));
        $this->header_brand_icon = (string) ($settings->get('header.brand_icon') ?? 'sparkles');
        $this->header_logo_type = (string) ($settings->get('header.logo_type') ?? 'text');
        $this->header_logo_url = (string) ($settings->get('header.logo_url') ?? '');
        $this->header_logo_height = (int) ($settings->get('header.logo_height') ?? 32);

        $this->header_show_search = (bool) ($settings->get('header.show_search') ?? true);
        $this->header_show_theme_toggle = (bool) ($settings->get('header.show_theme_toggle') ?? true);
        $this->header_sticky = (bool) ($settings->get('header.sticky') ?? true);

        $this->header_show_action_button = (bool) ($settings->get('header.show_action_button') ?? true);
        $this->header_action_auth_text = (string) ($settings->get('header.action_auth_text') ?? 'Dashboard');
        $this->header_action_auth_url = (string) ($settings->get('header.action_auth_url') ?? '/admin');
        $this->header_action_guest_text = (string) ($settings->get('header.action_guest_text') ?? 'Sign in');
        $this->header_action_guest_url = (string) ($settings->get('header.action_guest_url') ?? '/login');
        $this->header_action_icon = (string) ($settings->get('header.action_icon') ?? 'layout-dashboard');

        // Footer
        $this->footer_brand_text = (string) ($settings->get('footer.brand_text') ?? ($settings->get('site.name') ?? 'RUPANTRIX'));
        $this->footer_brand_icon = (string) ($settings->get('footer.brand_icon') ?? 'sparkles');
        $this->footer_logo_type = (string) ($settings->get('footer.logo_type') ?? 'text');
        $this->footer_logo_url = (string) ($settings->get('footer.logo_url') ?? '');
        $this->footer_logo_height = (int) ($settings->get('footer.logo_height') ?? 32);
        $this->footer_description = (string) ($settings->get('footer.description') ?? ($settings->get('site.description') ?? 'Welcome to ultimate source for fresh perspectives! Explore curated content to enlighten, entertain and engage global readers.'));
        $this->footer_copyright = (string) ($settings->get('footer.copyright') ?? '© {year} — {siteName}. All Rights Reserved.');

        $this->footer_social_facebook = (string) ($settings->get('footer.social_facebook') ?? '');
        $this->footer_social_twitter = (string) ($settings->get('footer.social_twitter') ?? '');
        $this->footer_social_instagram = (string) ($settings->get('footer.social_instagram') ?? '');
        $this->footer_social_linkedin = (string) ($settings->get('footer.social_linkedin') ?? '');
        $this->footer_social_youtube = (string) ($settings->get('footer.social_youtube') ?? '');
        $this->footer_social_github = (string) ($settings->get('footer.social_github') ?? '');

        $this->footer_col1_title = (string) ($settings->get('footer.col1_title') ?? 'HOMEPAGES');
        $this->footer_col2_title = (string) ($settings->get('footer.col2_title') ?? 'CATEGORIES');
        $this->footer_col2_count = (int) ($settings->get('footer.col2_count') ?? 5);
        $this->footer_col3_title = (string) ($settings->get('footer.col3_title') ?? 'PAGES');
        $this->footer_col3_count = (int) ($settings->get('footer.col3_count') ?? 6);

        // Ticker
        $this->footer_ticker_enabled = (bool) ($settings->get('footer.ticker_enabled') ?? true);
        $this->footer_ticker_count = (int) ($settings->get('footer.ticker_count') ?? 10);
        $this->footer_show_back_to_top = (bool) ($settings->get('footer.show_back_to_top') ?? true);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function save(SettingService $settings): void
    {
        abort_unless(
            auth()->user()?->can('settings.update') ?? false,
            403,
            'You do not have permission to update settings.',
        );

        $this->validate([
            'header_brand_text' => 'required|string|max:100',
            'header_brand_icon' => 'nullable|string|max:50',
            'header_logo_type' => 'required|in:text,image',
            'header_logo_url' => 'nullable|string|max:500',
            'header_logo_height' => 'required|integer|min:16|max:120',
            'header_action_auth_text' => 'nullable|string|max:50',
            'header_action_auth_url' => 'nullable|string|max:255',
            'header_action_guest_text' => 'nullable|string|max:50',
            'header_action_guest_url' => 'nullable|string|max:255',
            'footer_brand_text' => 'required|string|max:100',
            'footer_description' => 'nullable|string|max:1000',
            'footer_copyright' => 'nullable|string|max:255',
            'footer_col2_count' => 'required|integer|min:1|max:20',
            'footer_col3_count' => 'required|integer|min:1|max:20',
            'footer_ticker_count' => 'required|integer|min:1|max:30',
        ]);

        // Header
        $settings->set('header.brand_text', $this->header_brand_text, 'branding', 'string', false);
        $settings->set('header.brand_icon', $this->header_brand_icon, 'branding', 'string', false);
        $settings->set('header.logo_type', $this->header_logo_type, 'branding', 'string', false);
        $settings->set('header.logo_url', $this->header_logo_url, 'branding', 'string', false);
        $settings->set('header.logo_height', $this->header_logo_height, 'branding', 'integer', false);
        $settings->set('header.show_search', $this->header_show_search, 'branding', 'boolean', false);
        $settings->set('header.show_theme_toggle', $this->header_show_theme_toggle, 'branding', 'boolean', false);
        $settings->set('header.sticky', $this->header_sticky, 'branding', 'boolean', false);

        $settings->set('header.show_action_button', $this->header_show_action_button, 'branding', 'boolean', false);
        $settings->set('header.action_auth_text', $this->header_action_auth_text, 'branding', 'string', false);
        $settings->set('header.action_auth_url', $this->header_action_auth_url, 'branding', 'string', false);
        $settings->set('header.action_guest_text', $this->header_action_guest_text, 'branding', 'string', false);
        $settings->set('header.action_guest_url', $this->header_action_guest_url, 'branding', 'string', false);
        $settings->set('header.action_icon', $this->header_action_icon, 'branding', 'string', false);

        // Footer
        $settings->set('footer.brand_text', $this->footer_brand_text, 'branding', 'string', false);
        $settings->set('footer.brand_icon', $this->footer_brand_icon, 'branding', 'string', false);
        $settings->set('footer.logo_type', $this->footer_logo_type, 'branding', 'string', false);
        $settings->set('footer.logo_url', $this->footer_logo_url, 'branding', 'string', false);
        $settings->set('footer.logo_height', $this->footer_logo_height, 'branding', 'integer', false);
        $settings->set('footer.description', $this->footer_description, 'branding', 'string', false);
        $settings->set('footer.copyright', $this->footer_copyright, 'branding', 'string', false);

        $settings->set('footer.social_facebook', $this->footer_social_facebook, 'branding', 'string', false);
        $settings->set('footer.social_twitter', $this->footer_social_twitter, 'branding', 'string', false);
        $settings->set('footer.social_instagram', $this->footer_social_instagram, 'branding', 'string', false);
        $settings->set('footer.social_linkedin', $this->footer_social_linkedin, 'branding', 'string', false);
        $settings->set('footer.social_youtube', $this->footer_social_youtube, 'branding', 'string', false);
        $settings->set('footer.social_github', $this->footer_social_github, 'branding', 'string', false);

        $settings->set('footer.col1_title', $this->footer_col1_title, 'branding', 'string', false);
        $settings->set('footer.col2_title', $this->footer_col2_title, 'branding', 'string', false);
        $settings->set('footer.col2_count', $this->footer_col2_count, 'branding', 'integer', false);
        $settings->set('footer.col3_title', $this->footer_col3_title, 'branding', 'string', false);
        $settings->set('footer.col3_count', $this->footer_col3_count, 'branding', 'integer', false);

        // Ticker
        $settings->set('footer.ticker_enabled', $this->footer_ticker_enabled, 'branding', 'boolean', false);
        $settings->set('footer.ticker_count', $this->footer_ticker_count, 'branding', 'integer', false);
        $settings->set('footer.show_back_to_top', $this->footer_show_back_to_top, 'branding', 'boolean', true);

        $this->dispatch('toast.success', message: 'Header & Footer settings saved successfully.');
    }

    public function resetDefaults(SettingService $settings): void
    {
        $this->header_brand_text = 'RUPANTRIX';
        $this->header_brand_icon = 'sparkles';
        $this->header_logo_type = 'text';
        $this->header_logo_url = '';
        $this->header_logo_height = 32;
        $this->header_show_search = true;
        $this->header_show_theme_toggle = true;
        $this->header_sticky = true;
        $this->header_show_action_button = true;
        $this->header_action_auth_text = 'Dashboard';
        $this->header_action_auth_url = '/admin';
        $this->header_action_guest_text = 'Sign in';
        $this->header_action_guest_url = '/login';
        $this->header_action_icon = 'layout-dashboard';

        $this->footer_brand_text = 'RUPANTRIX';
        $this->footer_brand_icon = 'sparkles';
        $this->footer_logo_type = 'text';
        $this->footer_logo_url = '';
        $this->footer_logo_height = 32;
        $this->footer_description = 'Welcome to ultimate source for fresh perspectives! Explore curated content to enlighten, entertain and engage global readers.';
        $this->footer_copyright = '© {year} — {siteName}. All Rights Reserved.';

        $this->footer_social_facebook = '';
        $this->footer_social_twitter = '';
        $this->footer_social_instagram = '';
        $this->footer_social_linkedin = '';
        $this->footer_social_youtube = '';
        $this->footer_social_github = '';

        $this->footer_col1_title = 'HOMEPAGES';
        $this->footer_col2_title = 'CATEGORIES';
        $this->footer_col2_count = 5;
        $this->footer_col3_title = 'PAGES';
        $this->footer_col3_count = 6;

        $this->footer_ticker_enabled = true;
        $this->footer_ticker_count = 10;
        $this->footer_show_back_to_top = true;

        $this->save($settings);
        $this->dispatch('toast.success', message: 'Header & Footer reset to defaults.');
    }

    public function render(): View
    {
        return view('livewire.admin.header-footer.index');
    }
}
