<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <script>
            (function () {
                const theme = localStorage.getItem('crm-theme') || 'system';
                const shouldUseDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', shouldUseDark);
            }());
        </script>

        <title>{{ ($title ?? 'Login') . ' - ' . ($settings->get('company.name') ?: config('app.name', 'NewsPilot AI')) }}</title>

        @include('partials.branding')
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.css">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-100 text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
        {{-- Top bar — gives guest auth pages (login / forgot / reset)
             a "Back to site" escape hatch so visitors who landed here
             by mistake (or finished signing in elsewhere) can still
             reach the public news home. --}}
        @php($brandName = $settings->get('site.name') ?: ($settings->get('company.name') ?: config('app.name', 'NewsPilot AI')))
        <header class="absolute inset-x-0 top-0 z-10 px-4 py-4 sm:px-6">
            <div class="mx-auto flex max-w-6xl items-center justify-between">
                <a href="{{ route('frontend.home') }}"
                   class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-indigo-600 dark:text-slate-300 dark:hover:text-indigo-300">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    <span>Back to <span class="font-bold">{{ $brandName }}</span></span>
                </a>
                <a href="{{ route('frontend.home') }}"
                   class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 transition hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100">
                    <i data-lucide="home" class="h-3.5 w-3.5"></i>
                    <span>Home</span>
                </a>
            </div>
        </header>

        <main class="flex min-h-screen items-center justify-center p-6 pt-20">
            @yield('content')
        </main>
        <script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/build/toastr.min.js"></script>
        <script>
            (function () {
                if (window.lucide) window.lucide.createIcons();
            })();
        </script>
        @stack('scripts')
    </body>
</html>

