<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#4f46e5">

        {{-- Apply persisted theme before paint to avoid light/dark flash --}}
        <script>
            (function () {
                const theme = localStorage.getItem('crm-theme') || 'system';
                const shouldUseDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', shouldUseDark);
            }());
        </script>

        <title>{{ ($title ?? 'My Dashboard') . ' - ' . config('app.name', 'NewsPilot AI') }}</title>

        @include('partials.branding')
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800&family=playfair-display:700,900" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
        @stack('styles')
    </head>
    <body class="bg-slate-50 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100" data-app-shell>
        <div class="flex min-h-screen flex-col lg:flex-row">
            <x-visitor.sidebar />

            <div class="flex min-h-screen flex-1 flex-col">
                <x-visitor.navbar />

                <main class="flex-1 p-4 sm:p-6 lg:p-8">
                    @if (session('success'))
                        <div hidden data-toast-message="{{ session('success') }}" data-toast-type="success"></div>
                    @endif

                    @if (session('error'))
                        <div hidden data-toast-message="{{ session('error') }}" data-toast-type="error"></div>
                    @endif

                    {{ $slot ?? '' }}
                    @yield('content')
                </main>

                <x-visitor.footer />
            </div>
        </div>

        @livewireScripts

        {{-- Lucide icons — pinned version (cacheable). createIcons() is
             scoped to the morphed element on Livewire updates and
             debounced into the next animation frame so a burst of morphs
             only triggers one rescan. --}}
        <script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.js" defer></script>
        <script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.js" defer></script>
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

                document.addEventListener('DOMContentLoaded', () => queue());
                document.addEventListener('livewire:navigated', () => queue());
                if (window.Livewire) {
                    window.Livewire.hook('morph.updated', ({ el }) => queue(el));
                }

                // Toast flash from session
                document.querySelectorAll('[data-toast-message]').forEach((el) => {
                    if (window.toastr) {
                        const type = el.dataset.toastType || 'success';
                        window.toastr[type](el.dataset.toastMessage);
                    }
                });

                // Mobile sidebar toggle
                const sidebar = document.querySelector('[data-visitor-sidebar]');
                const overlay = document.querySelector('[data-visitor-overlay]');
                const openBtns = document.querySelectorAll('[data-visitor-sidebar-toggle]');
                const closeBtns = document.querySelectorAll('[data-visitor-sidebar-close]');
                const open = () => { sidebar?.classList.remove('-translate-x-full'); overlay?.classList.remove('hidden'); };
                const close = () => { sidebar?.classList.add('-translate-x-full'); overlay?.classList.add('hidden'); };
                openBtns.forEach(b => b.addEventListener('click', open));
                closeBtns.forEach(b => b.addEventListener('click', close));
                overlay?.addEventListener('click', close);
            })();
        </script>

        @stack('scripts')
    </body>
</html>
