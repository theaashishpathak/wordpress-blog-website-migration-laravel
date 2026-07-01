<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#4f46e5">
        <script>
            (function () {
                const theme = localStorage.getItem('crm-theme') || 'system';
                const shouldUseDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', shouldUseDark);
            }());
        </script>

        <title>{{ ($title ?? 'Dashboard') . ' - ' . config('app.name', 'NewsPilot AI') }}</title>

        @include('partials.branding')
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
        {{-- Bootstrap and Summernote CSS removed (unused; Bootstrap classes
             are not referenced anywhere in admin views, Summernote was
             replaced by TipTap). Saved ~250 KB / two extra HTTP round-trips. --}}
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @stack('styles')
    </head>
    <body class="bg-slate-100 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100" data-app-shell>
        @if ((bool) config('app.demo_mode'))
            <div class="sticky top-0 z-50 flex items-center justify-center gap-2 bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500 px-4 py-1.5 text-center text-xs font-semibold text-white shadow">
                <i data-lucide="info" class="h-3.5 w-3.5"></i>
                <span>Demo Mode — all data changes are disabled. Refresh to reset.</span>
            </div>
        @endif

        <div class="flex min-h-screen flex-col lg:flex-row">
            <x-admin.sidebar />

            <div class="flex min-h-screen flex-1 flex-col lg:pl-0">
                <x-admin.navbar />

                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    @if (session('success'))
                        <div hidden data-toast-message="{{ session('success') }}" data-toast-type="success"></div>
                    @endif

                    @if (session('error'))
                        <div hidden data-toast-message="{{ session('error') }}" data-toast-type="error"></div>
                    @endif

                    @if (session('danger'))
                        <div hidden data-toast-message="{{ session('danger') }}" data-toast-type="danger"></div>
                    @endif

                    {{ $slot ?? '' }}
                    @yield('content')
                </main>

                <x-admin.footer />
            </div>
        </div>

        <x-admin.quick-create-modal />

        @livewireScripts

        {{--
            CDN scripts cleanup (was 8 blocking, ~1 MB):
            - Bootstrap JS removed — no Bootstrap classes/components used.
            - Summernote removed — replaced by TipTap (Vite-bundled).
            - jQuery still loaded (Select2 + flatpickr legacy require it),
              but deferred so it no longer blocks first paint.
            - Lucide pinned to a real version (was @latest, which busted
              every CDN cache layer) and the morph-hook scoped to the
              changed element so a re-render no longer scans the whole DOM.
            - Everything except Livewire's own scripts uses `defer`.
        --}}
        <script src="https://code.jquery.com/jquery-3.7.1.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr" defer></script>
        @include('partials.datetime-pickers')
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js" defer></script>
        @include('partials.select2-init')
        @include('partials.file-uploads')
        @include('partials.phone-mask')
        @include('partials.toastr-extras')

        {{-- Lucide morph-scoped renderer. createIcons() now runs only on
             the element that just morphed (Livewire 4 passes `{ el }`),
             not the whole document. Bursty updates collapse into one
             rAF tick so even keystroke-driven Livewire polls stay cheap. --}}
        <script>
            (function () {
                const render = (root) => {
                    if (! window.lucide) return;
                    window.lucide.createIcons(root ? { nameAttr: 'data-lucide', root } : {});
                };
                let queued = false;
                const queue = (root) => {
                    if (queued) return;
                    queued = true;
                    requestAnimationFrame(() => { queued = false; render(root); });
                };

                // Wait for the deferred lucide.js to actually load.
                const boot = () => { render(); };
                if (document.readyState !== 'loading') boot();
                else document.addEventListener('DOMContentLoaded', boot);

                document.addEventListener('livewire:navigated', () => queue());
                document.addEventListener('livewire:init', () => {
                    if (window.Livewire) {
                        window.Livewire.hook('morph.updated', ({ el }) => queue(el));
                    }
                });
            })();
        </script>

        @stack('scripts')
    </body>
</html>

