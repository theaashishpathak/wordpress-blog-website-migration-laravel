@php
    /**
     * Renders branding overrides into <head>:
     *   - Favicon from settings (with fallback)
     *   - CSS variables for primary/secondary colors
     *   - Tailwind class overrides so existing bg-indigo-* / text-indigo-* etc. follow the chosen primary color
     *   - Custom CSS injected from settings
     *
     * Requires global $settings (App\Services\SettingService) — shared via AppServiceProvider.
     */
    $primary = $settings->get('branding.primary_color', '#4f46e5');
    $secondary = $settings->get('branding.secondary_color', '#06b6d4');

    $favicon = $settings->get('branding.favicon');
    $faviconUrl = $favicon
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($favicon)
        : '/favicon.ico';

    // Custom CSS may be stored as JSON {"css": "..."} or a raw string.
    $customCssRaw = $settings->get('branding.custom_css');
    $customCss = '';
    if (is_array($customCssRaw)) {
        $customCss = (string) ($customCssRaw['css'] ?? '');
    } elseif (is_string($customCssRaw)) {
        $customCss = $customCssRaw;
    }

    /**
     * Lighten/darken a hex color by a percent (-100 to 100).
     * Used to compute hover variants automatically.
     */
    $shade = function (string $hex, int $percent): string {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return '#'.$hex;
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        $adjust = function (int $c) use ($percent): int {
            $delta = (int) round(($percent / 100) * 255);
            return max(0, min(255, $c + $delta));
        };
        return sprintf('#%02x%02x%02x', $adjust($r), $adjust($g), $adjust($b));
    };

    $primaryDark = $shade($primary, -10);     // hover:bg-indigo-700 equivalent
    $primaryDarker = $shade($primary, -20);   // active state
    $primaryLight = $shade($primary, 35);     // text on dark bg
@endphp

{{-- Favicon override --}}
<link rel="icon" href="{{ $faviconUrl }}" sizes="any">

<style>
    :root {
        --crm-primary: {{ $primary }};
        --crm-primary-dark: {{ $primaryDark }};
        --crm-primary-darker: {{ $primaryDarker }};
        --crm-primary-light: {{ $primaryLight }};
        --crm-secondary: {{ $secondary }};
    }

    /*
     * Tailwind class overrides — re-paints existing utility classes
     * with the user-chosen primary color. !important required because
     * Tailwind compiled CSS loads after this style block.
     */
    .bg-indigo-500 { background-color: var(--crm-primary) !important; }
    .bg-indigo-600 { background-color: var(--crm-primary) !important; }
    .bg-indigo-700 { background-color: var(--crm-primary-dark) !important; }
    .hover\:bg-indigo-600:hover { background-color: var(--crm-primary) !important; }
    .hover\:bg-indigo-700:hover { background-color: var(--crm-primary-dark) !important; }

    .text-indigo-500,
    .text-indigo-600 { color: var(--crm-primary) !important; }
    .text-indigo-700 { color: var(--crm-primary-dark) !important; }
    .dark\:text-indigo-200,
    .dark\:text-indigo-300,
    .dark\:text-indigo-400 { color: var(--crm-primary-light) !important; }
    .hover\:text-indigo-600:hover { color: var(--crm-primary) !important; }

    .border-indigo-500,
    .border-indigo-600 { border-color: var(--crm-primary) !important; }
    .focus\:border-indigo-500:focus { border-color: var(--crm-primary) !important; }
    .hover\:border-indigo-300:hover { border-color: var(--crm-primary) !important; }

    .ring-indigo-500,
    .ring-indigo-600 { --tw-ring-color: var(--crm-primary) !important; }
    .focus\:ring-indigo-500:focus { --tw-ring-color: var(--crm-primary) !important; }

    .from-indigo-500 { --tw-gradient-from: var(--crm-primary) !important; }
    .to-indigo-500,
    .to-indigo-600 { --tw-gradient-to: var(--crm-primary) !important; }
    .via-indigo-500,
    .via-indigo-600 { --tw-gradient-via: var(--crm-primary) !important; }

    /* Secondary color usage (cyan-* etc. used in some accent spots) */
    .bg-cyan-500 { background-color: var(--crm-secondary) !important; }
    .text-cyan-500 { color: var(--crm-secondary) !important; }

    /* Browser color picker preview swatch */
    input[type="color"] {
        -webkit-appearance: none;
        appearance: none;
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        cursor: pointer;
        height: 38px;
        padding: 2px;
    }
    .dark input[type="color"] { border-color: #334155; }
    input[type="color"]::-webkit-color-swatch-wrapper { padding: 0; }
    input[type="color"]::-webkit-color-swatch { border: none; border-radius: 0.35rem; }
</style>

@if ($customCss !== '')
    <style>
        /* Custom CSS from Branding Settings */
        {!! $customCss !!}
    </style>
@endif
