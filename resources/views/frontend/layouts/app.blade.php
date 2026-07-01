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
            const theme = localStorage.getItem('crm-theme') || 'system';
            const shouldUseDark = theme === 'dark' || (theme === 'system' && window.matchMedia(
                '(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', shouldUseDark);
        }());
    </script>

    @php
        $settings = app(\App\Services\SettingService::class);
        $siteName = (string) ($settings->get('site.name') ?? config('app.name', 'Aashish Pathak'));
        $pageTitle = $title ?? $siteName;
        $metaDescription =
            $metaDescription ?? (string) ($settings->get('site.description') ?? 'Latest news and articles');
    @endphp

    <title>{{ $pageTitle }}{{ $pageTitle === $siteName ? '' : ' — ' . $siteName }}</title>
    <meta name="description" content="{{ $metaDescription }}">

    {{-- OG + Twitter --}}
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @isset($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endisset
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    {{-- Canonical + alternate locales --}}
    @isset($canonicalUrl)
        <link rel="canonical" href="{{ $canonicalUrl }}">
    @endisset
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

<body class="bg-white font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
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

    {{--
            Lucide icons — pinned version (not @latest) so the CDN gives us
            an immutable response that the browser can cache for a year.

            The createIcons() call walks every <i data-lucide="..."> in the
            DOM, so calling it on every Livewire morph used to scan the
            entire page on every keystroke. We now:
              1. Run it once on first paint and on full SPA navigation.
              2. On morph.updated, scope the scan to ONLY the element that
                 actually changed (`{ root: el }`) — Livewire 4 passes the
                 element as the first hook argument.
              3. Debounce overlapping morphs so a burst of updates only
                 triggers a single rescan in the next animation frame.
        --}}
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
                    // Allow the same root to be re-rendered later if it
                    // gets new icons — drop after a tick.
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
