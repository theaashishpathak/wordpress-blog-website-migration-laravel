@php
    $settings = app(\App\Services\SettingService::class);
    $siteName = (string) ($settings->get('header.brand_text') ?? ($settings->get('site.name') ?? 'RUPANTRIX'));
    $brandIcon = (string) ($settings->get('header.brand_icon') ?? 'sparkles');
    $logoType = (string) ($settings->get('header.logo_type') ?? 'text');
    $logoUrl = (string) ($settings->get('header.logo_url') ?? '');
    $logoHeight = (int) ($settings->get('header.logo_height') ?? 32);

    $showSearch = (bool) ($settings->get('header.show_search') ?? true);
    $showThemeToggle = (bool) ($settings->get('header.show_theme_toggle') ?? true);
    $isSticky = (bool) ($settings->get('header.sticky') ?? true);

    $showActionButton = (bool) ($settings->get('header.show_action_button') ?? true);
    $actionAuthText = (string) ($settings->get('header.action_auth_text') ?? 'Dashboard');
    $actionAuthUrl = (string) ($settings->get('header.action_auth_url') ?? route('dashboard'));
    $actionGuestText = (string) ($settings->get('header.action_guest_text') ?? 'Sign in');
    $actionGuestUrl = (string) ($settings->get('header.action_guest_url') ?? route('login'));
    $actionIcon = (string) ($settings->get('header.action_icon') ?? 'layout-dashboard');

    $localeResolver = app(\App\Support\LocaleResolver::class);
    $currentLocale = $localeResolver->current();

    // Query dynamic navigation items (with auto-seed fallback)
    $headerNavItems = \App\Models\NavigationItem::forLocation('header')
        ->topLevel()
        ->active()
        ->with(['children' => fn($q) => $q->active()->ordered()])
        ->ordered()
        ->get();

    if ($headerNavItems->isEmpty()) {
        \App\Models\NavigationItem::seedDefaults();
        $headerNavItems = \App\Models\NavigationItem::forLocation('header')
            ->topLevel()
            ->active()
            ->with(['children' => fn($q) => $q->active()->ordered()])
            ->ordered()
            ->get();
    }
@endphp

<header x-data="{
        mobileOpen: false,
        searchOpen: false,
        isDark: document.documentElement.classList.contains('dark'),
        toggleTheme() {
            this.isDark = !this.isDark;
            document.documentElement.classList.toggle('dark', this.isDark);
            localStorage.setItem('crm-theme', this.isDark ? 'dark' : 'light');
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        }
    }"
    x-init="$nextTick(() => { if (window.lucide) window.lucide.createIcons(); })"
    class="{{ $isSticky ? 'sticky top-0 header-sticky-glass' : 'relative bg-white dark:bg-[#111217]' }} z-40 border-b border-slate-200/85 dark:border-white/10 transition-colors duration-200">
    <div class="mx-auto flex max-w-[1360px] items-center justify-between px-4 py-3.5 sm:px-6 lg:px-8">
        
        {{-- Brand / Logo (Left) --}}
        <div class="flex items-center gap-6 shrink-0">
            <a href="{{ route('frontend.home') }}" class="flex items-center gap-2.5 group">
                @if ($logoType === 'image' && !empty($logoUrl))
                    <img src="{{ $logoUrl }}" alt="{{ $siteName }}" style="height: {{ $logoHeight }}px" class="w-auto object-contain">
                @else
                    <span class="grid h-8 w-8 place-items-center rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-950 shadow-xs group-hover:scale-105 transition duration-200">
                        <i data-lucide="{{ $brandIcon }}" class="h-4 w-4"></i>
                    </span>
                    <span class="text-lg font-black uppercase tracking-widest text-slate-900 dark:text-white" style="font-family: 'Playfair Display', serif;">
                        {{ $siteName }}
                    </span>
                @endif
            </a>
        </div>

        {{-- Center Navigation (Desktop) --}}
        <nav class="hidden md:flex items-center gap-6 lg:gap-8 text-sm font-semibold text-slate-600 dark:text-neutral-300">
            @foreach ($headerNavItems as $navItem)
                @if ($navItem->children->isNotEmpty())
                    <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
                        <button type="button" x-on:click="open = !open"
                            class="inline-flex items-center gap-1.5 py-1 transition hover:text-emerald-600 dark:hover:text-emerald-400">
                            @if ($navItem->icon)
                                <i data-lucide="{{ $navItem->icon }}" class="h-4 w-4"></i>
                            @endif
                            <span>{{ $navItem->title }}</span>
                            <i data-lucide="chevron-down" class="h-3.5 w-3.5 transition-transform opacity-70" x-bind:class="open && 'rotate-180'"></i>
                        </button>
                        <div x-show="open" x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            class="absolute left-0 mt-3 w-60 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl dark:border-[#262832] dark:bg-[#181920] z-50">
                            @foreach ($navItem->children as $child)
                                <a href="{{ $child->url }}" target="{{ $child->target ?? '_self' }}"
                                   class="flex items-center justify-between rounded-xl px-3.5 py-2.5 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-[#22242e] text-slate-700 dark:text-neutral-200">
                                    <span class="flex items-center gap-2">
                                        @if ($child->icon)
                                            <i data-lucide="{{ $child->icon }}" class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400"></i>
                                        @endif
                                        <span>{{ $child->title }}</span>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @else
                    @php
                        $isActive = request()->is(ltrim($navItem->url, '/')) || ($navItem->url === '/' && request()->is('/'));
                    @endphp
                    <a href="{{ $navItem->url }}" target="{{ $navItem->target ?? '_self' }}"
                        class="relative py-1 transition hover:text-slate-900 dark:hover:text-white {{ $isActive ? 'text-emerald-600 font-bold dark:text-emerald-400' : '' }}">
                        @if ($navItem->icon)
                            <i data-lucide="{{ $navItem->icon }}" class="h-4 w-4 inline mr-1"></i>
                        @endif
                        {{ $navItem->title }}
                        @if ($isActive)
                            <span class="absolute -bottom-1 left-0 right-0 h-0.5 rounded-full bg-emerald-500"></span>
                        @endif
                    </a>
                @endif
            @endforeach
        </nav>

        {{-- Right Controls (Search, Theme Pill, Action CTA) --}}
        <div class="flex items-center gap-2.5 sm:gap-3 shrink-0">
            {{-- Search Button --}}
            @if ($showSearch)
                <button type="button" x-on:click="searchOpen = true"
                    class="grid h-8 w-8 place-items-center rounded-full text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 dark:text-neutral-400 dark:hover:bg-[#1e2028] dark:hover:text-white"
                    title="Search" aria-label="Search">
                    <i data-lucide="search" class="h-4 w-4"></i>
                </button>
            @endif

            {{-- Sleek Dual-Icon Theme Switcher Toggle Pill --}}
            @if ($showThemeToggle)
                <button type="button" x-on:click="toggleTheme()"
                    class="relative inline-flex h-8 w-[62px] items-center justify-between rounded-full border border-slate-200 bg-slate-100 p-1 transition-colors duration-200 dark:border-[#2c2e3a] dark:bg-[#181920]"
                    title="Toggle Theme" aria-label="Toggle Theme">
                    
                    {{-- Sliding Knob / Indicator (Left = Moon in dark mode; Right = Sun in light mode) --}}
                    <span class="absolute top-1 left-1 h-6 w-6 rounded-full bg-white shadow-xs transition-transform duration-200 ease-in-out dark:bg-[#262832]"
                          :class="isDark ? 'translate-x-0' : 'translate-x-7'"
                          aria-hidden="true"></span>

                    {{-- Moon Icon (Left - Active when isDark is true) --}}
                    <span class="relative z-10 flex h-6 w-6 items-center justify-center transition-colors duration-200"
                          :class="isDark ? 'text-indigo-400 dark:text-white font-bold' : 'text-slate-400 dark:text-neutral-500'">
                        <i data-lucide="moon" class="h-3.5 w-3.5"></i>
                    </span>

                    {{-- Sun Icon (Right - Active when isDark is false) --}}
                    <span class="relative z-10 flex h-6 w-6 items-center justify-center transition-colors duration-200"
                          :class="isDark ? 'text-slate-400 dark:text-neutral-500' : 'text-amber-500 font-bold'">
                        <i data-lucide="sun" class="h-3.5 w-3.5"></i>
                    </span>
                </button>
            @endif

            {{-- Primary Action CTA --}}
            @if ($showActionButton)
                @auth
                    <a href="{{ $actionAuthUrl }}"
                        class="inline-flex items-center gap-1.5 rounded-full bg-slate-900 px-4 py-1.5 text-xs font-bold text-white shadow-xs transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-100">
                        @if ($actionIcon)
                            <i data-lucide="{{ $actionIcon }}" class="h-3.5 w-3.5"></i>
                        @endif
                        <span>{{ $actionAuthText }}</span>
                    </a>
                @else
                    <a href="{{ $actionGuestUrl }}"
                        class="inline-flex items-center gap-1.5 rounded-full bg-slate-900 px-4 py-1.5 text-xs font-bold text-white shadow-xs transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-100">
                        <span>{{ $actionGuestText }}</span>
                    </a>
                @endauth
            @endif

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
                @foreach ($headerNavItems as $navItem)
                    @if ($navItem->children->isNotEmpty())
                        <div x-data="{ open: false }" class="space-y-1">
                            <button type="button" x-on:click="open = !open"
                                class="flex w-full items-center justify-between rounded-xl px-3 py-2.5 hover:bg-slate-100 dark:hover:bg-[#181920] text-slate-800 dark:text-neutral-200">
                                <span class="flex items-center gap-2">
                                    @if ($navItem->icon)
                                        <i data-lucide="{{ $navItem->icon }}" class="h-4 w-4"></i>
                                    @endif
                                    <span>{{ $navItem->title }}</span>
                                </span>
                                <i data-lucide="chevron-down" class="h-4 w-4 transition-transform" x-bind:class="open && 'rotate-180'"></i>
                            </button>
                            <div x-show="open" x-cloak class="pl-6 space-y-1 border-l-2 border-slate-100 dark:border-[#22242e] ml-4">
                                @foreach ($navItem->children as $child)
                                    <a href="{{ $child->url }}" target="{{ $child->target ?? '_self' }}"
                                        class="flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-semibold hover:bg-slate-100 dark:hover:bg-[#181920] text-slate-700 dark:text-neutral-300">
                                        @if ($child->icon)
                                            <i data-lucide="{{ $child->icon }}" class="h-3.5 w-3.5 text-emerald-600 dark:text-emerald-400"></i>
                                        @endif
                                        <span>{{ $child->title }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ $navItem->url }}" target="{{ $navItem->target ?? '_self' }}"
                            class="flex items-center gap-2 rounded-xl px-3 py-2.5 hover:bg-slate-100 dark:hover:bg-[#181920] text-slate-800 dark:text-neutral-200">
                            @if ($navItem->icon)
                                <i data-lucide="{{ $navItem->icon }}" class="h-4 w-4"></i>
                            @else
                                <i data-lucide="chevron-right" class="h-4 w-4 text-slate-400"></i>
                            @endif
                            <span>{{ $navItem->title }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>

            <div class="border-t border-slate-200 p-4 dark:border-[#1d1f27] space-y-2">
                @if ($showThemeToggle)
                    <button type="button" x-on:click="toggleTheme()"
                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 py-2.5 text-xs font-bold text-slate-700 dark:border-[#262832] dark:text-neutral-300">
                        <span x-show="!isDark">🌙 Switch to Dark Mode</span>
                        <span x-show="isDark" x-cloak>☀️ Switch to Light Mode</span>
                    </button>
                @endif

                @if ($showActionButton)
                    @auth
                        <a href="{{ $actionAuthUrl }}" class="flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 py-2.5 text-sm font-bold text-white dark:bg-white dark:text-slate-900">
                            @if ($actionIcon)
                                <i data-lucide="{{ $actionIcon }}" class="h-4 w-4"></i>
                            @endif
                            <span>{{ $actionAuthText }}</span>
                        </a>
                    @else
                        <a href="{{ $actionGuestUrl }}" class="flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 py-2.5 text-sm font-bold text-white dark:bg-white dark:text-slate-900">
                            <span>{{ $actionGuestText }}</span>
                        </a>
                    @endauth
                @endif
            </div>
        </aside>
    </div>

    {{-- ───── Search Modal ───── --}}
    @if ($showSearch)
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
    @endif
</header>
