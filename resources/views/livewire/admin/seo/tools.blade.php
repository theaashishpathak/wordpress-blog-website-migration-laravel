<div class="space-y-5">
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">SEO Tools</span>
    </nav>

    <div class="flex items-center gap-3">
        <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-500 text-white shadow-sm">
            <i data-lucide="search" class="h-5 w-5"></i>
        </span>
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">SEO Tools</h1>
            <p class="text-xs text-slate-500">Site-wide SEO health, sitemap status, and redirect rules in one place.</p>
        </div>
    </div>

    @php($a = $this->auditCounts)
    {{-- Audit tiles --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['label' => 'Total published', 'value' => $a['total_published'], 'icon' => 'newspaper', 'color' => 'from-indigo-500 to-violet-500'],
            ['label' => 'Missing meta title', 'value' => $a['missing_meta_title'], 'icon' => 'triangle-alert', 'color' => 'from-rose-500 to-orange-500'],
            ['label' => 'Missing meta description', 'value' => $a['missing_meta_description'], 'icon' => 'align-left', 'color' => 'from-amber-500 to-orange-500'],
            ['label' => 'Missing focus keyword', 'value' => $a['missing_focus_keyword'], 'icon' => 'target', 'color' => 'from-pink-500 to-rose-500'],
            ['label' => 'Short titles (< 30 ch)', 'value' => $a['short_titles'], 'icon' => 'minimize-2', 'color' => 'from-yellow-500 to-amber-500'],
            ['label' => 'Pages marked noindex', 'value' => $a['noindex_pages'], 'icon' => 'eye-off', 'color' => 'from-slate-500 to-slate-700'],
        ] as $tile)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $tile['label'] }}</span>
                    <span class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br {{ $tile['color'] }} text-white">
                        <i data-lucide="{{ $tile['icon'] }}" class="h-4 w-4"></i>
                    </span>
                </div>
                <p class="mt-2 text-3xl font-black tabular-nums text-slate-900 dark:text-slate-100">{{ number_format($tile['value']) }}</p>
            </div>
        @endforeach
    </div>

    {{-- Sitemap + Redirects panels --}}
    <div class="grid gap-4 lg:grid-cols-2">
        @php($s = $this->sitemapStatus)
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="flex items-center gap-2 text-sm font-bold text-slate-800 dark:text-slate-100">
                    <i data-lucide="map" class="h-4 w-4 text-emerald-500"></i> Sitemap
                </h3>
                @can('seo.sitemap')
                    <button type="button" wire:click="rebuildSitemap"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700">
                        <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i> Rebuild
                    </button>
                @endcan
            </header>
            <div class="space-y-2 p-5 text-sm">
                @if ($s['exists'])
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">URL count</span>
                        <span class="font-mono font-bold tabular-nums">{{ number_format($s['url_count']) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">File size</span>
                        <span class="font-mono">{{ number_format($s['size_kb']) }} KB</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-500">Last generated</span>
                        <span>{{ $s['generated_at']?->diffForHumans() }}</span>
                    </div>
                    <a href="{{ url('/sitemap.xml') }}" target="_blank" rel="noopener"
                       class="mt-2 inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                        <i data-lucide="external-link" class="h-3.5 w-3.5"></i> Open /sitemap.xml
                    </a>
                @else
                    <p class="text-sm text-slate-500">
                        Sitemap is served dynamically via the SitemapController on every request — no on-disk file is cached.
                        <a href="{{ url('/sitemap.xml') }}" target="_blank" rel="noopener" class="font-semibold text-emerald-600 hover:underline">
                            Open the live sitemap ↗
                        </a>
                    </p>
                @endif
            </div>
        </div>

        @php($r = $this->redirectsSummary)
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="flex items-center gap-2 text-sm font-bold text-slate-800 dark:text-slate-100">
                    <i data-lucide="corner-down-right" class="h-4 w-4 text-sky-500"></i> Redirects
                </h3>
                @can('seo.redirects')
                    <a href="{{ route('admin.seo.redirects') }}" wire:navigate
                       class="inline-flex items-center gap-1.5 rounded-lg bg-sky-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-sky-700">
                        <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i> Manage
                    </a>
                @endcan
            </header>
            <div class="space-y-2 p-5 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Total rules</span>
                    <span class="font-mono font-bold tabular-nums">{{ number_format($r['total']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Active</span>
                    <span class="font-mono font-bold tabular-nums text-emerald-700 dark:text-emerald-300">{{ number_format($r['active']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Hits (last 30 days)</span>
                    <span class="font-mono tabular-nums">{{ number_format($r['hits_30d']) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Action shortcuts --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @can('seo.redirects')
            <a href="{{ route('admin.seo.redirects') }}" wire:navigate
               class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-sky-300 hover:bg-sky-50/40 dark:border-slate-800 dark:bg-slate-900 dark:hover:bg-sky-500/5">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-sky-500 to-indigo-500 text-white">
                    <i data-lucide="corner-down-right" class="h-4 w-4"></i>
                </span>
                <p class="mt-3 text-sm font-bold text-slate-800 dark:text-slate-100">Manage Redirects</p>
                <p class="mt-1 text-xs text-slate-500">301/302 rules for old URLs.</p>
            </a>
        @endcan

        @can('seo.audit')
            <a href="{{ route('admin.posts.index', ['type' => 'post']) }}" wire:navigate
               class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-rose-300 hover:bg-rose-50/40 dark:border-slate-800 dark:bg-slate-900 dark:hover:bg-rose-500/5">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-rose-500 to-orange-500 text-white">
                    <i data-lucide="triangle-alert" class="h-4 w-4"></i>
                </span>
                <p class="mt-3 text-sm font-bold text-slate-800 dark:text-slate-100">Posts missing SEO</p>
                <p class="mt-1 text-xs text-slate-500">Open the post list and filter by missing meta.</p>
            </a>
        @endcan

        @can('seo.sitemap')
            <a href="{{ url('/sitemap.xml') }}" target="_blank" rel="noopener"
               class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-50/40 dark:border-slate-800 dark:bg-slate-900 dark:hover:bg-emerald-500/5">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white">
                    <i data-lucide="map" class="h-4 w-4"></i>
                </span>
                <p class="mt-3 text-sm font-bold text-slate-800 dark:text-slate-100">Open sitemap.xml</p>
                <p class="mt-1 text-xs text-slate-500">Live XML served by the SitemapController.</p>
            </a>
        @endcan
    </div>
</div>
