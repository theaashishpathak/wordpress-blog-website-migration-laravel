@php
    $settings = app(\App\Services\SettingService::class);
    $logo = $settings->get('site.logo');
    $siteName = (string) ($settings->get('site.name') ?? config('app.name', 'Aashish Pathak'));
    $tagline = (string) ($settings->get('site.tagline') ?? 'Customisation By Aashish Pathak');
    $localeResolver = app(\App\Support\LocaleResolver::class);
    $currentLocale = $localeResolver->current();
    $activeLanguages = $localeResolver->activeLanguages();

    $menuCategories = \App\Models\Category::query()->inMenu()->ordered()->limit(10)->get();
@endphp

<header x-data="{
    mobileOpen: false,
    searchOpen: false,
    scrolled: false,
    userMenuOpen: false
}"
    x-init="window.addEventListener('scroll', () => { scrolled = window.scrollY > 20 })"
    class="sticky top-0 z-40 transition-all duration-300"
    :class="scrolled ? 'border-b border-slate-200/80 bg-white/95 shadow-sm backdrop-blur-xl supports-[backdrop-filter]:bg-white/90 dark:border-slate-800/80 dark:bg-slate-950/95 dark:supports-[backdrop-filter]:bg-slate-950/90' : 'border-b border-transparent bg-white/80 backdrop-blur-md supports-[backdrop-filter]:bg-white/70 dark:bg-slate-950/80'">

    {{-- Top utility strip — refined with better spacing and visual hierarchy --}}
    <div class="hidden border-b border-slate-100/80 px-4 py-2 text-xs font-medium text-slate-500 dark:border-slate-800/60 lg:block">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <span class="inline-flex items-center gap-2">
                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-400/80 shadow-sm shadow-emerald-400/30"></span>
                    {{ now()->translatedFormat('l, F j, Y') }}
                </span>
                <span class="text-slate-300 dark:text-slate-700">|</span>
                <span class="inline-flex items-center gap-1.5 text-slate-400 dark:text-slate-500">
                    <span class="h-1.5 w-1.5 rounded-full bg-slate-300 dark:bg-slate-700"></span>
                    Latest stories
                </span>
            </div>

            <div class="flex items-center gap-2">
                @if (count($activeLanguages) > 1)
                    <div x-data="{ open: false }" class="relative" x-on:mouseenter="open = true" x-on:mouseleave="open = false">
                        <button type="button"
                            class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-medium transition-all duration-200 hover:bg-slate-100/80 hover:text-slate-900 dark:hover:bg-slate-800/80 dark:hover:text-slate-100">
                            <span class="text-base leading-none">{{ $currentLocale?->flag_emoji ?? '🌐' }}</span>
                            <span>{{ $currentLocale?->name ?? 'Language' }}</span>
                            <svg class="h-3 w-3 transition-transform duration-200" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div x-show="open" x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-2xl border border-slate-200/80 bg-white/95 shadow-xl shadow-slate-200/20 backdrop-blur-xl dark:border-slate-700/80 dark:bg-slate-900/95 dark:shadow-black/20">
                            @foreach ($activeLanguages as $lang)
                                <a href="{{ route('frontend.home', ['locale' => $lang->code]) }}"
                                    class="flex items-center gap-3 px-4 py-2.5 text-sm transition-all duration-150 hover:bg-slate-50/80 hover:pl-5 dark:hover:bg-slate-800/80 {{ $currentLocale?->id === $lang->id ? 'bg-slate-100/80 font-semibold text-slate-900 dark:bg-slate-800/80 dark:text-slate-100' : 'text-slate-600 dark:text-slate-400' }}">
                                    <span class="text-lg leading-none">{{ $lang->flag_emoji ?? '🌐' }}</span>
                                    <span class="flex-1">{{ $lang->name }}</span>
                                    @if($currentLocale?->id === $lang->id)
                                        <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <span class="text-slate-300 dark:text-slate-700">·</span>
                @endif

                @auth
                    <div x-data="{ open: false }" class="relative" x-on:click.away="open = false">
                        <button type="button" x-on:click="open = !open"
                            class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-medium transition-all duration-200 hover:bg-slate-100/80 hover:text-slate-900 dark:hover:bg-slate-800/80 dark:hover:text-slate-100">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <span>{{ auth()->user()->name ?? 'Account' }}</span>
                        </button>
                        <div x-show="open" x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            class="absolute right-0 z-50 mt-2 w-48 overflow-hidden rounded-2xl border border-slate-200/80 bg-white/95 shadow-xl shadow-slate-200/20 backdrop-blur-xl dark:border-slate-700/80 dark:bg-slate-900/95 dark:shadow-black/20">
                            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 text-sm transition-all duration-150 hover:bg-slate-50/80 hover:pl-5 dark:hover:bg-slate-800/80">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                                </svg>
                                Dashboard
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="block">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-3 px-4 py-2.5 text-sm text-red-600 transition-all duration-150 hover:bg-red-50/80 hover:pl-5 dark:text-red-400 dark:hover:bg-red-900/30">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                    </svg>
                                    Sign out
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-medium transition-all duration-200 hover:bg-slate-100/80 hover:text-emerald-600 dark:hover:bg-slate-800/80 dark:hover:text-emerald-400">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                        </svg>
                        Sign in
                    </a>

                    <a href="{{ route('register') }}"
                        class="inline-flex items-center gap-2 rounded-full bg-emerald-600 px-4 py-1.5 text-xs font-medium text-white transition-all duration-200 hover:bg-emerald-700 hover:shadow-lg hover:shadow-emerald-600/25 active:scale-95">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                        Sign up
                    </a>
                @endauth
            </div>
        </div>
    </div>

    {{-- Main brand row — more refined spacing and typography --}}
    <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 sm:px-6 sm:py-4">
        {{-- Mobile menu trigger --}}
        <button type="button" x-on:click="mobileOpen = true"
            class="group grid h-10 w-10 shrink-0 place-items-center rounded-xl text-slate-600 transition-all duration-200 hover:bg-slate-100/80 hover:text-slate-900 lg:hidden dark:text-slate-400 dark:hover:bg-slate-800/80 dark:hover:text-slate-100"
            aria-label="Open menu">
            <svg class="h-5 w-5 transition-transform duration-200 group-hover:scale-105" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        {{-- Brand — with refined visual hierarchy --}}
        <a href="{{ route('frontend.home', ['locale' => $currentLocale?->code]) }}"
            class="group flex min-w-0 flex-1 items-center gap-3 transition-all duration-200 hover:opacity-90 sm:gap-4">
            <div class="relative grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-slate-900 to-slate-700 text-white shadow-md shadow-slate-900/20 transition-all duration-300 group-hover:shadow-lg group-hover:shadow-slate-900/30 group-hover:scale-105 sm:h-12 sm:w-12 dark:from-slate-100 dark:to-slate-300 dark:text-slate-900">
                <span class="text-lg font-black tracking-tight sm:text-xl" style="font-family: 'Playfair Display', serif;">
                    {{ mb_substr($siteName, 0, 1) }}
                </span>
                <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-emerald-400 ring-2 ring-white dark:ring-slate-950"></span>
            </div>
            <span class="flex min-w-0 flex-col leading-tight">
                <span class="truncate text-lg font-black tracking-tight text-slate-900 transition-colors group-hover:text-emerald-600 sm:text-2xl dark:text-slate-100 dark:group-hover:text-emerald-400"
                    style="font-family: 'Playfair Display', serif;">
                    {{ $siteName }}
                </span>
                <span class="hidden truncate text-[11px] font-semibold uppercase tracking-[0.25em] text-slate-400 transition-colors group-hover:text-slate-600 lg:block dark:text-slate-500 dark:group-hover:text-slate-400">
                    {{ $tagline }}
                </span>
            </span>
        </a>

        {{-- Desktop search --}}
        <form action="{{ route('frontend.search', ['locale' => $currentLocale?->code]) }}" method="GET"
            class="hidden flex-1 max-w-md lg:block">
            <div class="group relative">
                <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 transition-colors duration-200 group-focus-within:text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="q" placeholder="Search articles, topics, authors…" value="{{ request('q') }}"
                    class="w-full rounded-full border-0 bg-slate-100/80 py-2.5 pl-11 pr-4 text-sm outline-none ring-1 ring-slate-200/60 transition-all duration-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-500/60 dark:bg-slate-800/60 dark:ring-slate-700/60 dark:focus:bg-slate-900 dark:focus:ring-emerald-500/60">
            </div>
        </form>

        {{-- Right cluster — refined actions --}}
        <div class="flex shrink-0 items-center gap-1.5 sm:gap-2">
            <button type="button" x-on:click="searchOpen = true"
                class="group grid h-10 w-10 place-items-center rounded-xl text-slate-600 transition-all duration-200 hover:bg-slate-100/80 hover:text-slate-900 lg:hidden dark:text-slate-400 dark:hover:bg-slate-800/80 dark:hover:text-slate-100"
                aria-label="Search">
                <svg class="h-4 w-4 transition-transform duration-200 group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </button>

            <button type="button" x-data x-on:click="
                        const root = document.documentElement;
                        const isDark = root.classList.toggle('dark');
                        localStorage.setItem('crm-theme', isDark ? 'dark' : 'light');
                    "
                class="group grid h-10 w-10 place-items-center rounded-xl text-slate-600 transition-all duration-200 hover:bg-slate-100/80 hover:text-slate-900 sm:grid dark:text-slate-400 dark:hover:bg-slate-800/80 dark:hover:text-slate-100"
                title="Toggle theme" aria-label="Toggle theme">
                <svg class="h-4 w-4 transition-transform duration-200 group-hover:scale-110 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                <svg class="hidden h-4 w-4 transition-transform duration-200 group-hover:scale-110 dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </button>

            {{-- Subscribe — refined button with gradient --}}
            <a href="#newsletter"
                class="group grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-white shadow-md shadow-emerald-500/25 transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/35 hover:scale-105 active:scale-95 sm:hidden"
                aria-label="Subscribe">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </a>
            <a href="#newsletter"
                class="hidden h-10 items-center gap-2 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 px-5 text-sm font-semibold text-white shadow-md shadow-emerald-500/25 transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/35 hover:scale-105 active:scale-95 sm:inline-flex">
                <svg class="h-4 w-4 transition-transform duration-200 group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span class="hidden md:inline">Subscribe</span>
            </a>
        </div>
    </div>

    {{-- Category nav — refined with subtle indicators --}}
    @if ($menuCategories->isNotEmpty())
        <nav class="hidden border-t border-slate-100/80 dark:border-slate-800/60 lg:block">
            <div class="mx-auto flex max-w-7xl items-center gap-0.5 overflow-x-auto px-4 py-2 text-sm">
                <a href="{{ route('frontend.home', ['locale' => $currentLocale?->code]) }}"
                    class="relative inline-flex items-center gap-2 whitespace-nowrap rounded-xl px-4 py-2 font-medium transition-all duration-200 hover:bg-slate-100/80 hover:text-slate-900 dark:hover:bg-slate-800/80 dark:hover:text-slate-100 {{ request()->routeIs('frontend.home') ? 'bg-slate-100/80 text-slate-900 dark:bg-slate-800/80 dark:text-slate-100' : 'text-slate-600 dark:text-slate-400' }}">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Home
                    @if(request()->routeIs('frontend.home'))
                        <span class="absolute bottom-0 left-1/2 h-0.5 w-6 -translate-x-1/2 rounded-full bg-emerald-500"></span>
                    @endif
                </a>
                @foreach ($menuCategories as $cat)
                    <a href="{{ route('frontend.category', ['locale' => $currentLocale?->code, 'slug' => $cat->translate('slug')]) }}"
                        class="relative inline-flex items-center gap-2 whitespace-nowrap rounded-xl px-4 py-2 font-medium text-slate-600 transition-all duration-200 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/80 dark:hover:text-slate-100">
                        @if ($cat->icon)
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                        @endif
                        {{ $cat->translate('name') ?? '#' . $cat->id }}
                    </a>
                @endforeach
            </div>
        </nav>
    @endif

    {{-- ───── Mobile drawer ───── refined with glassmorphism --}}
    <div x-show="mobileOpen" x-cloak class="fixed inset-0 z-50 lg:hidden"
        x-on:keydown.escape.window="mobileOpen = false">
        <div x-show="mobileOpen" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" x-on:click="mobileOpen = false"
            class="absolute inset-0 bg-slate-900/50 backdrop-blur-md"></div>

        <aside x-show="mobileOpen" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="absolute inset-y-0 left-0 flex h-full min-h-screen w-[85%] max-w-sm flex-col bg-white/95 backdrop-blur-xl shadow-2xl shadow-slate-900/20 dark:bg-slate-950/95 dark:shadow-black/40">

            {{-- Drawer header --}}
            <div class="flex shrink-0 items-center justify-between border-b border-slate-200/80 px-5 py-4 dark:border-slate-800/80">
                <span class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-slate-900 to-slate-700 text-white shadow-md dark:from-slate-100 dark:to-slate-300 dark:text-slate-900">
                        <span class="text-lg font-black" style="font-family: 'Playfair Display', serif;">{{ mb_substr($siteName, 0, 1) }}</span>
                    </span>
                    <span class="text-lg font-black tracking-tight" style="font-family: 'Playfair Display', serif;">{{ $siteName }}</span>
                </span>
                <button type="button" x-on:click="mobileOpen = false"
                    class="group grid h-9 w-9 place-items-center rounded-xl text-slate-500 transition-all duration-200 hover:bg-slate-100/80 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800/80 dark:hover:text-slate-100"
                    aria-label="Close menu">
                    <svg class="h-4 w-4 transition-transform duration-200 group-hover:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Drawer nav --}}
            <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-5 text-sm">
                <a href="{{ route('frontend.home', ['locale' => $currentLocale?->code]) }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-3 font-semibold text-slate-700 transition-all duration-200 hover:bg-slate-100/80 hover:pl-5 hover:text-slate-900 dark:text-slate-200 dark:hover:bg-slate-800/80 dark:hover:text-white">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Home</span>
                </a>
                @forelse ($menuCategories as $cat)
                    <a href="{{ route('frontend.category', ['locale' => $currentLocale?->code, 'slug' => $cat->translate('slug')]) }}"
                        class="flex items-center gap-3 rounded-xl px-4 py-3 font-semibold text-slate-700 transition-all duration-200 hover:bg-slate-100/80 hover:pl-5 hover:text-slate-900 dark:text-slate-200 dark:hover:bg-slate-800/80 dark:hover:text-white">
                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                        </svg>
                        <span>{{ $cat->translate('name') ?? '#' . $cat->id }}</span>
                    </a>
                @empty
                    <p class="px-4 py-3 text-xs text-slate-400 dark:text-slate-500">No categories yet.</p>
                @endforelse

                {{-- Language switcher (mobile) --}}
                @if (count($activeLanguages) > 1)
                    <div class="mt-6 border-t border-slate-200/80 pt-4 dark:border-slate-800/80">
                        <p class="px-4 pb-3 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500">
                            Language</p>
                        @foreach ($activeLanguages as $lang)
                            <a href="{{ route('frontend.home', ['locale' => $lang->code]) }}"
                                class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm transition-all duration-200 hover:bg-slate-100/80 hover:pl-5 dark:hover:bg-slate-800/80 {{ $currentLocale?->id === $lang->id ? 'bg-slate-100/80 font-semibold text-slate-900 dark:bg-slate-800/80 dark:text-slate-100' : 'text-slate-700 dark:text-slate-300' }}">
                                <span class="text-lg leading-none">{{ $lang->flag_emoji ?? '🌐' }}</span>
                                <span class="flex-1">{{ $lang->name }}</span>
                                @if($currentLocale?->id === $lang->id)
                                    <svg class="h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- Theme toggle (mobile) --}}
                <div class="mt-6 border-t border-slate-200/80 pt-4 dark:border-slate-800/80">
                    <button type="button" x-on:click="
                            const root = document.documentElement;
                            const isDark = root.classList.toggle('dark');
                            localStorage.setItem('crm-theme', isDark ? 'dark' : 'light');
                        "
                        class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 transition-all duration-200 hover:bg-slate-100/80 hover:pl-5 dark:text-slate-200 dark:hover:bg-slate-800/80">
                        <svg class="h-4 w-4 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                        <svg class="hidden h-4 w-4 dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <span class="dark:hidden">Dark mode</span>
                        <span class="hidden dark:inline">Light mode</span>
                    </button>
                </div>
            </nav>

            {{-- Drawer footer — auth actions refined --}}
            <div class="shrink-0 border-t border-slate-200/80 p-5 dark:border-slate-800/80">
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center justify-center gap-2 rounded-xl bg-gradient-to-br from-slate-900 to-slate-700 px-4 py-3 text-sm font-semibold text-white shadow-md shadow-slate-900/20 transition-all duration-200 hover:shadow-lg hover:shadow-slate-900/30 hover:scale-[1.02] active:scale-95 dark:from-slate-100 dark:to-slate-300 dark:text-slate-900">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                        </svg>
                        Dashboard
                    </a>
                @else
                    <div class="flex gap-3">
                        <a href="{{ route('login') }}"
                            class="flex flex-1 items-center justify-center gap-2 rounded-xl border border-slate-200/80 px-4 py-3 text-sm font-semibold text-slate-700 transition-all duration-200 hover:bg-slate-100/80 hover:border-slate-300 dark:border-slate-700/80 dark:text-slate-200 dark:hover:bg-slate-800/80">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                            </svg>
                            Sign in
                        </a>
                        <a href="#newsletter" x-on:click="mobileOpen = false"
                            class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-md shadow-emerald-500/25 transition-all duration-200 hover:shadow-lg hover:shadow-emerald-500/35 hover:scale-[1.02] active:scale-95">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            Subscribe
                        </a>
                    </div>
                @endauth
            </div>
        </aside>
    </div>

    {{-- ───── Mobile search overlay ───── refined with glass --}}
    <div x-show="searchOpen" x-cloak class="fixed inset-0 z-50 lg:hidden"
        x-on:keydown.escape.window="searchOpen = false">
        <div x-show="searchOpen" x-on:click="searchOpen = false" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="absolute inset-0 bg-slate-900/50 backdrop-blur-md"></div>
        <div x-show="searchOpen" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-8 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            class="absolute inset-x-4 top-6 rounded-2xl bg-white/95 p-4 shadow-2xl shadow-slate-900/20 backdrop-blur-xl dark:bg-slate-950/95 dark:shadow-black/40">
            <form action="{{ route('frontend.search', ['locale' => $currentLocale?->code]) }}" method="GET">
                <div class="relative">
                    <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="q" autofocus placeholder="Search articles, topics, authors…"
                        class="w-full rounded-xl border-0 bg-slate-100/80 py-3.5 pl-11 pr-14 text-sm outline-none ring-1 ring-slate-200/60 transition-all duration-200 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-emerald-500/60 dark:bg-slate-800/60 dark:ring-slate-700/60 dark:focus:bg-slate-900 dark:focus:ring-emerald-500/60">
                    <button type="button" x-on:click="searchOpen = false"
                        class="absolute right-2 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-lg text-slate-500 transition-all duration-200 hover:bg-slate-100/80 hover:text-slate-900 dark:hover:bg-slate-800/80 dark:hover:text-slate-100">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</header>
