<header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur dark:border-slate-800 dark:bg-slate-900/95">
    <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
        <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" data-sidebar-toggle aria-label="Toggle sidebar">
            <i data-lucide="menu" class="h-5 w-5"></i>
        </button>

        <livewire:admin.global-search />

        @canany([
            'posts.create', 'pages.create', 'categories.create', 'tags.create', 'media.upload',
            'ai.use_writer', 'ai.use_seo', 'ai.templates',
            'rss.create', 'ads.create', 'seo.redirects',
            'staff.create', 'departments.create',
        ])
            <button
                type="button"
                class="group inline-flex h-10 items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:from-indigo-700 hover:to-violet-700"
                data-open-modal="quick-create-modal"
                aria-label="Quick Create"
                title="Ctrl+Shift+C"
            >
                <i data-lucide="plus" class="h-4 w-4"></i>
                <span class="hidden sm:inline">Quick Create</span>
                <kbd class="hidden rounded border border-white/30 bg-white/15 px-1.5 py-0.5 font-sans text-[10px] font-semibold lg:inline-block">Ctrl+Shift+C</kbd>
            </button>
        @endcanany

        <livewire:notification-bell />

        <div class="relative">
            <button type="button" class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800" data-dropdown-toggle="user-dropdown">
                <img src="{{ auth()->user()?->avatarUrl() }}" alt="User" class="h-8 w-8 rounded-full object-cover">
                <div class="hidden text-left sm:block">
                    <div class="text-sm font-semibold">{{ auth()->user()?->name ?? 'Admin' }}</div>
                    <div class="text-xs text-slate-500">{{ auth()->user()?->email ?? 'admin@newspilot.ai' }}</div>
                </div>
                <i data-lucide="chevron-down" class="h-4 w-4 text-slate-500"></i>
            </button>

            <div data-dropdown="user-dropdown" class="absolute right-0 top-12 hidden w-56 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900">
                <a href="{{ route('profile') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-800"><i data-lucide="user-round" class="h-4 w-4"></i> Profile</a>
                <a href="{{ route('settings') }}" class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-800"><i data-lucide="settings-2" class="h-4 w-4"></i> Settings</a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2 rounded-xl px-3 py-2 text-sm text-left text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-500/10">
                        <i data-lucide="log-out" class="h-4 w-4"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
