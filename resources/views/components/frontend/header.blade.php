@php
    $settings = app(\App\Services\SettingService::class);
    $siteName = (string) ($settings->get('site.name') ?? 'RUPANTRIX');
    $localeResolver = app(\App\Support\LocaleResolver::class);
    $currentLocale = $localeResolver->current();
    
    // Fetch real top categories
    $menuCategories = \App\Models\Category::query()
        ->withCount(['posts' => fn($q) => $q->where('status', \App\Enums\PostStatus::Published->value)])
        ->having('posts_count', '>', 0)
        ->orderByDesc('posts_count')
        ->limit(8)
        ->get();

    // Fetch real published pages with short clean titles
    $menuPages = \App\Models\Page::query()
        ->visibleIn($currentLocale?->id ?? 0)
        ->inMenu()
        ->ordered()
        ->limit(6)
        ->get()
        ->filter(function($p) use ($currentLocale) {
            $t = $p->translation($currentLocale?->code) ?? $p->translation();
            return $t && mb_strlen($t->title) <= 20;
        })
        ->take(3);
@endphp

<header x-data="{
        mobileOpen: false,
        searchOpen: false,
        isDark: document.documentElement.classList.contains('dark'),
        toggleTheme() {
            this.isDark = !this.isDark;
            document.documentElement.classList.toggle('dark', this.isDark);
            localStorage.setItem('crm-theme', this.isDark ? 'dark' : 'light');
        }
    }"
    class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur-md dark:border-[#1d1f27] dark:bg-[#111217]/95 transition-colors duration-200">
    <div class="mx-auto flex max-w-[1360px] items-center justify-between px-4 py-3.5 sm:px-6 lg:px-8">
        
        {{-- Brand / Logo (Left) --}}
        <div class="flex items-center gap-6 shrink-0">
            <a href="{{ route('frontend.home') }}" class="flex items-center gap-2.5 group">
                <span class="grid h-8 w-8 place-items-center rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-950 shadow-xs group-hover:scale-105 transition duration-200">
                    <i data-lucide="sparkles" class="h-4 w-4"></i>
                </span>
                <span class="text-lg font-black uppercase tracking-widest text-slate-900 dark:text-white" style="font-family: 'Playfair Display', serif;">
                    {{ $siteName }}
                </span>
            </a>
        </div>

        {{-- Center Navigation Pill (Desktop) --}}
        <nav class="hidden md:flex items-center gap-1 rounded-full border border-slate-200 bg-slate-100/90 px-4 py-1.5 text-xs font-bold text-slate-700 dark:border-[#262832] dark:bg-[#181920] dark:text-neutral-300 shadow-2xs">
            <a href="{{ route('frontend.home') }}"
                class="rounded-full px-3 py-1 transition hover:text-slate-900 dark:hover:text-white {{ request()->routeIs('frontend.home') ? 'bg-white text-slate-900 shadow-2xs dark:bg-[#252732] dark:text-white' : '' }}">
                Home
            </a>

            {{-- Real Categories Dropdown --}}
            <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                <button type="button" x-on:click="open = !open"
                    class="inline-flex items-center gap-1 rounded-full px-3 py-1 transition hover:text-slate-900 dark:hover:text-white">
                    <span>Categories</span>
                    <i data-lucide="chevron-down" class="h-3 w-3 transition-transform" x-bind:class="open && 'rotate-180'"></i>
                </button>
                <div x-show="open" x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="absolute left-0 mt-2.5 w-60 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl dark:border-[#262832] dark:bg-[#181920]">
                    @foreach ($menuCategories as $cat)
                        @php($catTitle = html_entity_decode((string) ($cat->translate('name') ?? ('#' . $cat->id)), ENT_QUOTES | ENT_HTML5, 'UTF-8'))
                        <a href="{{ route('frontend.category', ['slug' => $cat->translate('slug')]) }}"
                           class="flex items-center justify-between rounded-xl px-3 py-2 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-[#22242e]">
                            <span class="flex items-center gap-2">
                                @if ($cat->icon)
                                    <i data-lucide="{{ $cat->icon }}" class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400"></i>
                                @endif
                                <span>{{ $catTitle }}</span>
                            </span>
                            <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-500 dark:bg-[#22242e] dark:text-neutral-400">
                                {{ $cat->posts_count }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- Clean Real Pages Menu --}}
            @if ($menuPages->isNotEmpty())
                @foreach ($menuPages as $page)
                    @php($trans = $page->translation($currentLocale?->code) ?? $page->translation())
                    @if ($trans)
                        @php($pageTitle = html_entity_decode((string) $trans->title, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
                        <a href="{{ route('frontend.page', ['slug' => $trans->slug]) }}"
                           class="rounded-full px-3 py-1 transition hover:text-slate-900 dark:hover:text-white">
                            {{ $pageTitle }}
                        </a>
                    @endif
                @endforeach
            @else
                <a href="{{ route('frontend.page', ['slug' => 'about-us']) }}" class="rounded-full px-3 py-1 transition hover:text-slate-900 dark:hover:text-white">
                    About
                </a>
                <a href="{{ route('frontend.page', ['slug' => 'contact-us']) }}" class="rounded-full px-3 py-1 transition hover:text-slate-900 dark:hover:text-white">
                    Contacts
                </a>
            @endif
        </nav>

        {{-- Right Controls (Search, Theme Pill, Action CTA) --}}
        <div class="flex items-center gap-2.5 sm:gap-3 shrink-0">
            {{-- Search Button --}}
            <button type="button" x-on:click="searchOpen = true"
                class="grid h-8 w-8 place-items-center rounded-full text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:text-neutral-400 dark:hover:bg-[#1e2028] dark:hover:text-white"
                title="Search" aria-label="Search">
                <i data-lucide="search" class="h-4 w-4"></i>
            </button>

            {{-- Sleek Theme Switcher Toggle Pill --}}
            <button type="button" x-on:click="toggleTheme()"
                class="relative inline-flex h-8 w-14 items-center rounded-full border border-slate-200 bg-slate-100 px-1 transition-colors duration-200 dark:border-[#2c2e3a] dark:bg-[#181920]"
                title="Toggle Theme" aria-label="Toggle Theme">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white shadow-xs transition-transform duration-200 dark:translate-x-6 dark:bg-[#262832]">
                    <span x-show="!isDark"><i data-lucide="moon" class="h-3.5 w-3.5 text-slate-700"></i></span>
                    <span x-show="isDark" x-cloak><i data-lucide="sun" class="h-3.5 w-3.5 text-amber-400"></i></span>
                </span>
            </button>

            {{-- Primary Action CTA --}}
            @auth
                <a href="{{ route('dashboard') }}"
                    class="inline-flex items-center gap-1.5 rounded-full bg-slate-900 px-4 py-1.5 text-xs font-bold text-white shadow-xs transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-100">
                    <i data-lucide="layout-dashboard" class="h-3.5 w-3.5"></i>
                    <span>Dashboard</span>
                </a>
            @else
                <a href="{{ route('login') }}"
                    class="inline-flex items-center gap-1.5 rounded-full bg-slate-900 px-4 py-1.5 text-xs font-bold text-white shadow-xs transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-100">
                    <span>Sign in</span>
                </a>
            @endauth

            {{-- Mobile Drawer Trigger --}}
            <button type="button" x-on:click="mobileOpen = true"
                class="grid h-8 w-8 place-items-center rounded-lg text-slate-600 hover:bg-slate-100 md:hidden dark:text-neutral-400 dark:hover:bg-[#1e2028]"
                aria-label="Open menu">
                <i data-lucide="menu" class="h-5 w-5"></i>
            </button>
        </div>
    </div>

    {{-- ───── Mobile Drawer ───── --}}
    <div x-show="mobileOpen" x-cloak class="fixed inset-0 z-50 md:hidden"
        x-on:keydown.escape.window="mobileOpen = false">
        <div x-show="mobileOpen" x-transition.opacity x-on:click="mobileOpen = false"
            class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>

        <aside x-show="mobileOpen"
            x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="absolute inset-y-0 left-0 flex h-full w-[85%] max-w-sm flex-col bg-white shadow-2xl dark:bg-[#111217]">
            
            <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3.5 dark:border-[#1d1f27]">
                <span class="text-base font-black uppercase tracking-widest text-slate-900 dark:text-white" style="font-family: 'Playfair Display', serif;">
                    {{ $siteName }}
                </span>
                <button type="button" x-on:click="mobileOpen = false" class="p-1 text-slate-500 hover:text-slate-900 dark:text-neutral-400 dark:hover:text-white">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-4 py-4 text-sm font-semibold">
                <a href="{{ route('frontend.home') }}" class="flex items-center gap-2 rounded-xl px-3 py-2.5 hover:bg-slate-100 dark:hover:bg-[#181920]">
                    <i data-lucide="home" class="h-4 w-4"></i>
                    <span>Home</span>
                </a>
                
                <p class="pt-2 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-neutral-500">Categories</p>
                @foreach ($menuCategories as $cat)
                    @php($catTitle = html_entity_decode((string) ($cat->translate('name') ?? ('#' . $cat->id)), ENT_QUOTES | ENT_HTML5, 'UTF-8'))
                    <a href="{{ route('frontend.category', ['slug' => $cat->translate('slug')]) }}"
                       class="flex items-center justify-between rounded-xl px-3 py-2 hover:bg-slate-100 dark:hover:bg-[#181920]">
                        <span class="flex items-center gap-2">
                            @if ($cat->icon)
                                <i data-lucide="{{ $cat->icon }}" class="h-4 w-4"></i>
                            @endif
                            <span>{{ $catTitle }}</span>
                        </span>
                        <span class="text-xs text-slate-400">{{ $cat->posts_count }}</span>
                    </a>
                @endforeach

                <p class="pt-2 px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-neutral-500">Pages</p>
                @foreach ($menuPages as $page)
                    @php($trans = $page->translation($currentLocale?->code) ?? $page->translation())
                    @if ($trans)
                        @php($pageTitle = html_entity_decode((string) $trans->title, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
                        <a href="{{ route('frontend.page', ['slug' => $trans->slug]) }}"
                           class="flex items-center gap-2 rounded-xl px-3 py-2 hover:bg-slate-100 dark:hover:bg-[#181920]">
                            <i data-lucide="file-text" class="h-4 w-4"></i>
                            <span>{{ $pageTitle }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>

            <div class="border-t border-slate-200 p-4 dark:border-[#1d1f27] space-y-2">
                <button type="button" x-on:click="toggleTheme()"
                    class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 py-2.5 text-xs font-bold text-slate-700 dark:border-[#262832] dark:text-neutral-300">
                    <span x-show="!isDark">🌙 Switch to Dark Mode</span>
                    <span x-show="isDark" x-cloak>☀️ Switch to Light Mode</span>
                </button>
                @auth
                    <a href="{{ route('dashboard') }}" class="flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 py-2.5 text-sm font-bold text-white dark:bg-white dark:text-slate-900">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 py-2.5 text-sm font-bold text-white dark:bg-white dark:text-slate-900">
                        Sign in
                    </a>
                @endauth
            </div>
        </aside>
    </div>

    {{-- ───── Search Modal ───── --}}
    <div x-show="searchOpen" x-cloak class="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4"
        x-on:keydown.escape.window="searchOpen = false">
        <div x-show="searchOpen" x-transition.opacity x-on:click="searchOpen = false"
            class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
        <div x-show="searchOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="relative w-full max-w-lg rounded-3xl border border-slate-200 bg-white p-4 shadow-2xl dark:border-[#262832] dark:bg-[#181920]">
            <form action="{{ route('frontend.search') }}" method="GET">
                <div class="relative">
                    <i data-lucide="search" class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="q" autofocus placeholder="Type to search articles, topics, authors…"
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-10 text-sm outline-none focus:border-emerald-500 focus:bg-white dark:border-[#262832] dark:bg-[#111217] dark:focus:bg-[#0d0e12] dark:text-white">
                    <button type="button" x-on:click="searchOpen = false" class="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</header>
