@php
    $settings = app(\App\Services\SettingService::class);
    $siteName = (string) ($settings->get('site.name') ?? config('app.name', 'Aashish Pathak'));
    $tagline = (string) ($settings->get('site.tagline') ?? 'Customisation By Aashish Pathak');
    $localeResolver = app(\App\Support\LocaleResolver::class);
    $currentLocale = $localeResolver->current();
    $activeLanguages = $localeResolver->activeLanguages();

    $menuCategories = \App\Models\Category::query()->inMenu()->ordered()->limit(10)->get();
@endphp

<header x-data="{ mobileOpen: false, searchOpen: false }"
    class="sticky top-0 z-40 border-b border-slate-200 bg-white/90 backdrop-blur-md supports-[backdrop-filter]:bg-white/80 dark:border-slate-800 dark:bg-slate-950/90 dark:supports-[backdrop-filter]:bg-slate-950/80">

    {{-- Top utility strip — desktop only (lg+). On mobile/tablet, these
    controls live inside the drawer to keep the header uncluttered. --}}
    <div
        class="hidden border-b border-slate-100 px-4 py-1.5 text-[11px] font-medium text-slate-500 dark:border-slate-800/60 lg:block">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4">
            <span class="inline-flex items-center gap-1.5">
                <i data-lucide="calendar" class="h-3 w-3"></i>
                {{ now()->translatedFormat('l, F j, Y') }}
            </span>

            <div class="flex items-center gap-1">
                @if (count($activeLanguages) > 1)
                    <div x-data="{ open: false }" class="relative">
                        <button type="button" x-on:click="open = !open"
                            class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 font-semibold transition hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800 dark:hover:text-slate-100">
                            <span>{{ $currentLocale?->flag_emoji ?? '🌐' }}</span>
                            <span>{{ $currentLocale?->name ?? 'Language' }}</span>
                            <i data-lucide="chevron-down" class="h-3 w-3 transition"
                                x-bind:class="open && 'rotate-180'"></i>
                        </button>
                        <div x-show="open" x-on:click.outside="open = false" x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="absolute right-0 z-50 mt-1.5 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl ring-1 ring-black/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-white/5">
                            @foreach ($activeLanguages as $lang)
                                <a href="{{ route('frontend.home', ['locale' => $lang->code]) }}"
                                {{-- <a href="{{ route('frontend.home') }}" --}}


                                    class="flex items-center gap-2 px-3 py-2 text-xs transition hover:bg-slate-50 dark:hover:bg-slate-800 {{ $currentLocale?->id === $lang->id ? 'bg-slate-100 font-bold text-slate-900 dark:bg-slate-800 dark:text-slate-100' : '' }}">
                                    <span class="text-base leading-none">{{ $lang->flag_emoji ?? '🌐' }}</span>
                                    <span class="flex-1">{{ $lang->name }}</span>
                                    <span
                                        class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[9px] uppercase text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ $lang->code }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <span class="mx-1 text-slate-300 dark:text-slate-600">·</span>
                @endif

                @auth
                    <a href="{{ route('dashboard') }}"
                        class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 font-semibold transition hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800">
                        <i data-lucide="layout-dashboard" class="h-3 w-3"></i>
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 font-semibold transition hover:bg-slate-100 hover:text-emerald-700 dark:hover:bg-slate-800 dark:hover:text-emerald-300">
                        <i data-lucide="log-in" class="h-3 w-3"></i>
                        Sign in
                    </a>
                @endauth
            </div>
        </div>
    </div>

    {{-- Main brand row --}}
    <div class="mx-auto flex max-w-7xl items-center gap-3 px-3 py-3 sm:gap-4 sm:px-4 sm:py-4">
        {{-- Mobile menu trigger --}}
        <button type="button" x-on:click="mobileOpen = true"
            class="grid h-10 w-10 shrink-0 place-items-center rounded-lg text-slate-600 transition hover:bg-slate-100 lg:hidden dark:text-slate-400 dark:hover:bg-slate-800"
            aria-label="Open menu">
            <i data-lucide="menu" class="h-5 w-5"></i>
        </button>

        {{-- Brand — editorial monogram + serif wordmark. Truncates on
        narrow screens to avoid pushing the right cluster off. --}}
        <a href="{{ route('frontend.home', ['locale' => $currentLocale?->code]) }}"
            class="flex min-w-0 flex-1 items-center gap-2.5 transition hover:opacity-90 sm:gap-3">
            <span
                class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-slate-900 text-white sm:h-10 sm:w-10 dark:bg-slate-100 dark:text-slate-900">
                <span class="text-base font-black sm:text-lg"
                    style="font-family: 'Playfair Display', serif;">{{ mb_substr($siteName, 0, 1) }}</span>
            </span>
            <span class="flex min-w-0 flex-col leading-tight">
                <span class="truncate text-base font-black tracking-tight text-slate-900 sm:text-xl dark:text-slate-100"
                    style="font-family: 'Playfair Display', serif;">
                    {{ $siteName }}
                </span>
                <span
                    class="hidden truncate text-[10px] font-bold uppercase tracking-[0.22em] text-slate-500 lg:block dark:text-slate-400">
                    {{ $tagline }}
                </span>
            </span>
        </a>

        {{-- Desktop search --}}
        <form action="{{ route('frontend.search', ['locale' => $currentLocale?->code]) }}" method="GET"
            class="hidden flex-1 max-w-md lg:block">
            <div class="group relative">
                <i data-lucide="search"
                    class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 transition group-focus-within:text-emerald-600"></i>
                <input type="text" name="q" placeholder="Search articles, topics, authors…" value="{{ request('q') }}"
                    class="w-full rounded-full border border-slate-200 bg-slate-50 py-2.5 pl-11 pr-4 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-1 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-900 dark:focus:bg-slate-950">
            </div>
        </form>

        {{-- Right cluster: mobile search, theme, subscribe CTA --}}
        <div class="flex shrink-0 items-center gap-1 sm:gap-2">
            <button type="button" x-on:click="searchOpen = true"
                class="grid h-10 w-10 place-items-center rounded-lg text-slate-600 transition hover:bg-slate-100 lg:hidden dark:text-slate-400 dark:hover:bg-slate-800"
                aria-label="Search">
                <i data-lucide="search" class="h-4 w-4"></i>
            </button>

            <button type="button" x-data x-on:click="
                        const root = document.documentElement;
                        const isDark = root.classList.toggle('dark');
                        localStorage.setItem('crm-theme', isDark ? 'dark' : 'light');
                    "
                class="hidden h-10 w-10 place-items-center rounded-lg text-slate-600 transition hover:bg-slate-100 sm:grid dark:text-slate-400 dark:hover:bg-slate-800"
                title="Toggle theme" aria-label="Toggle theme">
                <i data-lucide="moon" class="h-4 w-4 dark:hidden"></i>
                <i data-lucide="sun" class="hidden h-4 w-4 dark:block"></i>
            </button>

            {{-- Subscribe — full button from sm up, icon-only on phones --}}
            <a href="#newsletter"
                class="grid h-10 w-10 place-items-center rounded-lg bg-emerald-600 text-white shadow-sm sm:hidden"
                aria-label="Subscribe">
                <i data-lucide="mail" class="h-4 w-4"></i>
            </a>
            <a href="#newsletter"
                class="hidden h-10 items-center gap-2 rounded-lg bg-emerald-600 px-4 text-sm font-bold text-white shadow-sm sm:inline-flex">
                <i data-lucide="mail" class="h-4 w-4"></i>
                <span class="hidden md:inline">Subscribe</span>
            </a>
        </div>
    </div>

    {{-- Category nav (desktop only) --}}
    @if ($menuCategories->isNotEmpty())
        <nav class="hidden border-t border-slate-100 dark:border-slate-800/60 lg:block">
            <div class="mx-auto flex max-w-7xl items-center gap-1 overflow-x-auto px-4 py-2 text-sm">
                <a href="{{ route('frontend.home', ['locale' => $currentLocale?->code]) }}"
                    class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-md px-3 py-1.5 font-semibold transition hover:bg-slate-100 hover:text-slate-900 dark:hover:bg-slate-800 dark:hover:text-slate-100 {{ request()->routeIs('frontend.home') ? 'bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-slate-100' : 'text-slate-600 dark:text-slate-400' }}">
                    <i data-lucide="home" class="h-3.5 w-3.5"></i>
                    Home
                </a>
                @foreach ($menuCategories as $cat)
                    <a href="{{ route('frontend.category', ['locale' => $currentLocale?->code, 'slug' => $cat->translate('slug')]) }}"
                        class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-md px-3 py-1.5 font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100">
                        @if ($cat->icon)
                            <i data-lucide="{{ $cat->icon }}" class="h-3.5 w-3.5"></i>
                        @endif
                        {{ $cat->translate('name') ?? '#' . $cat->id }}
                    </a>
                @endforeach
            </div>
        </nav>
    @endif

    {{-- ───── Mobile drawer ─────
    Rendered with explicit solid bg + min-h-screen so it always
    covers the underlying page even if `inset-y-0` runs into
    mobile-viewport quirks. --}}
    <div x-show="mobileOpen" x-cloak class="fixed inset-0 z-50 lg:hidden"
        x-on:keydown.escape.window="mobileOpen = false">
        <div x-show="mobileOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" x-on:click="mobileOpen = false"
            class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

        <aside x-show="mobileOpen" x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="absolute inset-y-0 left-0 flex h-full min-h-screen w-[85%] max-w-sm flex-col bg-white shadow-2xl dark:bg-slate-950">

            {{-- Drawer header --}}
            <div
                class="flex shrink-0 items-center justify-between border-b border-slate-200 px-4 py-3.5 dark:border-slate-800">
                <span class="flex items-center gap-2.5">
                    <span
                        class="grid h-9 w-9 place-items-center rounded-lg bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900">
                        <span class="text-base font-black"
                            style="font-family: 'Playfair Display', serif;">{{ mb_substr($siteName, 0, 1) }}</span>
                    </span>
                    <span class="text-base font-black tracking-tight"
                        style="font-family: 'Playfair Display', serif;">{{ $siteName }}</span>
                </span>
                <button type="button" x-on:click="mobileOpen = false"
                    class="grid h-9 w-9 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"
                    aria-label="Close menu">
                    <i data-lucide="x" class="h-4 w-4"></i>
                    <span class="sr-only">Close</span>
                </button>
            </div>

            {{-- Drawer nav — Home + categories.
            Icons fall back to plain bullets if Lucide hasn't
            hydrated yet, so the list is never visually empty. --}}
            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4 text-sm">
                <a href="{{ route('frontend.home', ['locale' => $currentLocale?->code]) }}"
                    class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 font-semibold text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-white">
                    <i data-lucide="home" class="h-4 w-4 shrink-0"></i>
                    <span>Home</span>
                </a>
                @forelse ($menuCategories as $cat)
                    <a href="{{ route('frontend.category', ['locale' => $currentLocale?->code, 'slug' => $cat->translate('slug')]) }}"
                        class="flex items-center gap-2.5 rounded-lg px-3 py-2.5 font-semibold text-slate-700 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-white">
                        <i data-lucide="{{ $cat->icon ?: 'folder' }}" class="h-4 w-4 shrink-0"></i>
                        <span>{{ $cat->translate('name') ?? '#' . $cat->id }}</span>
                    </a>
                @empty
                    <p class="px-3 py-2 text-xs text-slate-400 dark:text-slate-500">No categories yet.</p>
                @endforelse

                {{-- Language switcher (mobile) --}}
                @if (count($activeLanguages) > 1)
                    <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-800">
                        <p
                            class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">
                            Language</p>
                        @foreach ($activeLanguages as $lang)
                            <a href="{{ route('frontend.home', ['locale' => $lang->code]) }}"
                                class="flex items-center gap-2.5 rounded-lg px-3 py-2 text-sm transition hover:bg-slate-100 dark:hover:bg-slate-800 {{ $currentLocale?->id === $lang->id ? 'bg-slate-100 font-bold text-slate-900 dark:bg-slate-800 dark:text-slate-100' : 'text-slate-700 dark:text-slate-300' }}">
                                <span class="text-base leading-none">{{ $lang->flag_emoji ?? '🌐' }}</span>
                                <span class="flex-1">{{ $lang->name }}</span>
                                <span
                                    class="rounded bg-slate-200/70 px-1.5 py-0.5 font-mono text-[9px] uppercase text-slate-500 dark:bg-slate-800 dark:text-slate-400">{{ $lang->code }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- Theme toggle (mobile) --}}
                <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-800">
                    <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-800">
                        <button type="button" x-on:click="
                                const root = document.documentElement;
                                const isDark = root.classList.toggle('dark');
                                localStorage.setItem('crm-theme', isDark ? 'dark' : 'light');
                            "
                            class="flex w-full items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800">
                            <i data-lucide="moon" class="h-4 w-4 dark:hidden"></i>
                            <i data-lucide="sun" class="hidden h-4 w-4 dark:block"></i>
                            <span class="dark:hidden">Dark mode</span>
                            <span class="hidden dark:inline">Light mode</span>
                        </button>
                    </div>
            </nav>

            {{-- Drawer footer — auth actions --}}
            <div class="shrink-0 border-t border-slate-200 p-4 dark:border-slate-800">
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-bold text-white dark:bg-slate-100 dark:text-slate-900">
                        <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                        Dashboard
                    </a>
                @else
                    <div class="flex gap-2">
                        <a href="{{ route('login') }}"
                            class="flex flex-1 items-center justify-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">
                            <i data-lucide="log-in" class="h-4 w-4"></i>
                            Sign in
                        </a>
                        <a href="#newsletter" x-on:click="mobileOpen = false"
                            class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm">
                            <i data-lucide="mail" class="h-4 w-4"></i>
                            Subscribe
                        </a>
                    </div>
                @endauth
            </div>
        </aside>
    </div>

    {{-- ───── Mobile search overlay ───── --}}
    <div x-show="searchOpen" x-cloak class="fixed inset-0 z-50 lg:hidden"
        x-on:keydown.escape.window="searchOpen = false">
        <div x-show="searchOpen" x-on:click="searchOpen = false" x-transition.opacity
            class="absolute inset-0 bg-slate-900/60"></div>
        <div x-show="searchOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
            class="absolute inset-x-4 top-4 rounded-2xl bg-white p-3 shadow-2xl dark:bg-slate-900">
            <form action="{{ route('frontend.search', ['locale' => $currentLocale?->code]) }}" method="GET">
                <div class="relative">
                    <i data-lucide="search"
                        class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="q" autofocus placeholder="Search…"
                        class="w-full rounded-lg border border-slate-200 bg-slate-50 py-3 pl-11 pr-12 text-sm outline-none transition focus:border-emerald-500 focus:bg-white focus:ring-1 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950">
                    <button type="button" x-on:click="searchOpen = false"
                        class="absolute right-2 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</header>
