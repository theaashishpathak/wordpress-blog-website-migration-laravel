<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if (($locale = app(\App\Support\LocaleResolver::class)->current()) && $locale->isRtl()) dir="rtl" @endif
    class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4f46e5">

    {{-- Apply persisted theme before paint to avoid light/dark flash --}}
    <script>
        (function() {
            const theme = localStorage.getItem('crm-theme') || 'dark';
            const shouldUseDark = theme === 'dark' || (theme === 'system' && window.matchMedia(
                '(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', shouldUseDark);
        }());
    </script>

    @php
        $settings = app(\App\Services\SettingService::class);
        $siteName = (string) ($settings->get('site.name') ?? 'Revision');
        $pageTitle = $title ?? $siteName;
        $metaDescription =
            $metaDescription ?? (string) ($settings->get('site.description') ?? 'Latest news and articles');
    @endphp

    <title>{{ $pageTitle }}{{ $pageTitle === $siteName ? '' : ' — ' . $siteName }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="robots" content="{{ $robots ?? 'index, follow' }}">

    {{-- OG + Twitter --}}
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $ogTitle ?? $pageTitle }}">
    <meta property="og:description" content="{{ $ogDescription ?? $metaDescription }}">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:url" content="{{ $canonicalUrl ?? url()->current() }}">
    @isset($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta name="twitter:image" content="{{ $twitterImage ?? $ogImage }}">
    @endisset
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $twitterTitle ?? ($ogTitle ?? $pageTitle) }}">
    <meta name="twitter:description" content="{{ $twitterDescription ?? ($ogDescription ?? $metaDescription) }}">

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-png">

    {{-- Canonical + alternate locales --}}
    <link rel="canonical" href="{{ $canonicalUrl ?? url()->current() }}">
    @isset($alternateLocales)
        @foreach ($alternateLocales as $altLocale => $altUrl)
            <link rel="alternate" hreflang="{{ $altLocale }}" href="{{ $altUrl }}">
        @endforeach
    @endisset

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800&family=playfair-display:700,900"
        rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')

    {{-- Optional JSON-LD --}}
    @isset($jsonLd)
        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endisset
</head>

<body class="bg-[#f8f9fa] font-sans text-slate-900 antialiased dark:bg-[#0d0e12] dark:text-neutral-100 transition-colors duration-200 selection:bg-neutral-800 selection:text-white">
    <a href="#main"
        class="sr-only focus:not-sr-only focus:fixed focus:left-2 focus:top-2 focus:z-50 focus:rounded-lg focus:bg-indigo-600 focus:px-3 focus:py-1.5 focus:text-xs focus:font-semibold focus:text-white">
        Skip to content
    </a>

    <x-frontend.header />

    <main id="main" class="min-h-[60vh]">
        {{ $slot ?? '' }}
    </main>

    <x-frontend.footer />

    @livewireScripts

    <script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.js" defer></script>
    <script>
        (function() {
            const render = (root) => {
                if (!window.lucide) return;
                window.lucide.createIcons(root ? {
                    nameAttr: 'data-lucide',
                    root
                } : {});
            };

            let queued = false;
            const rendered = new WeakSet();
            const queue = (root) => {
                if (rendered.has(root || document)) return;
                if (queued) return;
                queued = true;
                requestAnimationFrame(() => {
                    queued = false;
                    render(root);
                    rendered.add(root || document);
                    setTimeout(() => rendered.delete(root || document), 250);
                });
            };

            document.addEventListener('DOMContentLoaded', () => queue());
            document.addEventListener('livewire:navigated', () => queue());

            if (window.Livewire) {
                window.Livewire.hook('morph.updated', ({
                    el
                }) => queue(el));
            }
        })();
    </script>

    @stack('scripts')
</body>

</html>
