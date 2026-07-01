@php
    $localeResolver = app(\App\Support\LocaleResolver::class);
    $currentLocale = $localeResolver->current();
@endphp

<header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur-md supports-[backdrop-filter]:bg-white/80 sm:px-6 dark:border-slate-800 dark:bg-slate-950/90 dark:supports-[backdrop-filter]:bg-slate-950/80">
    {{-- Left: mobile sidebar toggle + page context --}}
    <div class="flex min-w-0 items-center gap-3">
        <button type="button" data-visitor-sidebar-toggle
                class="grid h-10 w-10 place-items-center rounded-lg text-slate-600 transition hover:bg-slate-100 lg:hidden dark:text-slate-400 dark:hover:bg-slate-800"
                aria-label="Open menu">
            <i data-lucide="menu" class="h-5 w-5"></i>
        </button>

        <div class="hidden min-w-0 flex-col leading-tight sm:flex">
            <span class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">Reader Portal</span>
            <span class="truncate text-sm font-semibold text-slate-800 dark:text-slate-200">{{ $title ?? 'Dashboard' }}</span>
        </div>
    </div>

    {{-- Right: actions --}}
    <div class="flex items-center gap-1.5">
        <a href="{{ route('frontend.home', ['locale' => $currentLocale?->code]) }}"
           class="hidden h-10 items-center gap-2 rounded-lg px-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-900 sm:inline-flex dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100"
           title="Back to site">
            <i data-lucide="globe" class="h-4 w-4"></i>
            <span class="hidden md:inline">Visit site</span>
        </a>

        <button type="button"
                x-data
                x-on:click="
                    const root = document.documentElement;
                    const isDark = root.classList.toggle('dark');
                    localStorage.setItem('crm-theme', isDark ? 'dark' : 'light');
                "
                class="grid h-10 w-10 place-items-center rounded-lg text-slate-600 transition hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800"
                title="Toggle theme"
                aria-label="Toggle theme">
            <i data-lucide="moon" class="h-4 w-4 dark:hidden"></i>
            <i data-lucide="sun" class="hidden h-4 w-4 dark:block"></i>
        </button>

        {{-- Notification bell with unread badge + dropdown preview --}}
        @auth
            <livewire:visitor.notification-bell />
        @endauth

        @auth
            <a href="{{ route('frontend.author', ['locale' => $currentLocale?->code, 'user' => auth()->id()]) }}"
               class="flex items-center gap-2 rounded-lg px-2 py-1.5 transition hover:bg-slate-100 dark:hover:bg-slate-800">
                <span class="grid h-7 w-7 place-items-center rounded-full bg-slate-900 text-xs font-black uppercase text-white dark:bg-slate-100 dark:text-slate-900">
                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                </span>
                <span class="hidden text-sm font-semibold text-slate-700 sm:inline dark:text-slate-200">{{ \Illuminate\Support\Str::limit(auth()->user()->name, 12) }}</span>
            </a>
        @endauth
    </div>
</header>
