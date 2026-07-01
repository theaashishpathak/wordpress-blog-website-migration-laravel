<div class="space-y-6">
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">SEO Analytics</span>
    </nav>

    <div class="flex items-center gap-3">
        <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-emerald-500 via-teal-500 to-cyan-500 text-white shadow-sm">
            <i data-lucide="search" class="h-5 w-5"></i>
        </span>
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">SEO Analytics</h1>
            <p class="text-xs text-slate-500">On-page SEO health across all published content.</p>
        </div>
    </div>

    @php($a = $this->audit)
    {{-- Health audit tiles --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-teal-50 p-5 shadow-sm dark:border-emerald-500/30 dark:from-emerald-500/10 dark:to-teal-500/10">
            <div class="flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white">
                    <i data-lucide="target" class="h-4 w-4"></i>
                </span>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Avg SEO score</p>
                    <p class="text-3xl font-black tabular-nums text-emerald-900 dark:text-emerald-200">
                        {{ $a['avg_seo_score'] ?: '—' }}<span class="text-base">/100</span>
                    </p>
                </div>
            </div>
        </div>
        @foreach ([
            ['label' => 'Total published', 'value' => $a['total_published'], 'icon' => 'newspaper', 'color' => 'from-indigo-500 to-violet-500'],
            ['label' => 'Missing meta title', 'value' => $a['missing_meta_title'], 'icon' => 'triangle-alert', 'color' => 'from-rose-500 to-orange-500'],
            ['label' => 'Missing meta description', 'value' => $a['missing_meta_description'], 'icon' => 'align-left', 'color' => 'from-amber-500 to-orange-500'],
            ['label' => 'Missing focus keyword', 'value' => $a['missing_focus_keyword'], 'icon' => 'crosshair', 'color' => 'from-pink-500 to-rose-500'],
            ['label' => 'Short titles (< 30 ch)', 'value' => $a['short_titles'], 'icon' => 'minimize-2', 'color' => 'from-yellow-500 to-amber-500'],
            ['label' => 'Over-long descriptions (> 170)', 'value' => $a['over_long_descriptions'], 'icon' => 'maximize-2', 'color' => 'from-orange-500 to-rose-500'],
            ['label' => 'Pages set to noindex', 'value' => $a['noindex'], 'icon' => 'eye-off', 'color' => 'from-slate-500 to-slate-700'],
        ] as $tile)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ $tile['label'] }}</span>
                    <span class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br {{ $tile['color'] }} text-white">
                        <i data-lucide="{{ $tile['icon'] }}" class="h-4 w-4"></i>
                    </span>
                </div>
                <p class="mt-2 text-3xl font-black tabular-nums text-slate-900 dark:text-slate-100">{{ number_format($tile['value']) }}</p>
            </div>
        @endforeach
    </div>

    {{-- Score distribution chart --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h3 class="mb-3 text-sm font-bold text-slate-800 dark:text-slate-100">SEO score distribution</h3>
        <div class="relative h-64">
            <canvas id="seo-score-chart"></canvas>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        {{-- Worst scoring posts --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Lowest-scoring posts</h3>
                <p class="text-[11px] text-slate-500">Open each post and rerun the AI SEO Generator to lift the score.</p>
            </header>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($this->worstScoringPosts as $p)
                    @php($scoreColor = $p->score < 40 ? 'text-rose-700 bg-rose-100 dark:bg-rose-500/20 dark:text-rose-300' : ($p->score < 70 ? 'text-amber-700 bg-amber-100 dark:bg-amber-500/20 dark:text-amber-300' : 'text-emerald-700 bg-emerald-100 dark:bg-emerald-500/20 dark:text-emerald-300'))
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="rounded-md px-2 py-0.5 text-xs font-bold {{ $scoreColor }}">{{ $p->score }}</span>
                        <a href="{{ route('admin.posts.edit', $p->post_id) }}" wire:navigate
                           class="min-w-0 flex-1 truncate text-sm font-semibold text-slate-800 hover:text-indigo-600 dark:text-slate-100">
                            {{ $p->title }}
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-xs text-slate-500">No SEO scores recorded yet — save any post to capture one.</li>
                @endforelse
            </ul>
        </div>

        {{-- Redirects summary --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Redirect performance</h3>
                @can('seo.redirects')
                    <a href="{{ route('admin.seo.redirects') }}" wire:navigate class="text-xs font-semibold text-indigo-600 hover:underline">Manage →</a>
                @endcan
            </header>
            @php($r = $this->redirectsSummary)
            <div class="grid gap-3 border-b border-slate-100 px-5 py-3 text-center dark:border-slate-800 sm:grid-cols-4">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total</p>
                    <p class="text-xl font-black tabular-nums">{{ number_format($r['total']) }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Active</p>
                    <p class="text-xl font-black tabular-nums text-emerald-700 dark:text-emerald-300">{{ number_format($r['active']) }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Hits · all-time</p>
                    <p class="text-xl font-black tabular-nums">{{ number_format($r['hits_total']) }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Hits · 30d</p>
                    <p class="text-xl font-black tabular-nums">{{ number_format($r['hits_30d']) }}</p>
                </div>
            </div>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($this->topRedirects as $rd)
                    <li class="flex items-center gap-3 px-5 py-2">
                        <code class="truncate text-xs text-slate-700 dark:text-slate-200">{{ $rd->from_path }}</code>
                        <i data-lucide="arrow-right" class="h-3.5 w-3.5 text-slate-400"></i>
                        <code class="min-w-0 flex-1 truncate text-xs text-slate-500">{{ $rd->to_url }}</code>
                        <span class="font-mono text-xs tabular-nums text-slate-500">{{ number_format($rd->hit_count) }} hits</span>
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-xs text-slate-500">No redirect hits yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>

@script
<script>
    const renderSeoScoreChart = () => {
        const el = document.getElementById('seo-score-chart');
        if (! el || typeof Chart === 'undefined') return;
        if (window._seoScoreChart) window._seoScoreChart.destroy();

        const labels = @json($this->scoreDistribution['labels']);
        const data = @json($this->scoreDistribution['counts']);

        window._seoScoreChart = new Chart(el, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Posts',
                    data,
                    backgroundColor: ['#f43f5e', '#f97316', '#facc15', '#84cc16', '#10b981'],
                    borderRadius: 6,
                }],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
            },
        });
    };
    renderSeoScoreChart();
    Livewire.hook('morph.updated', () => renderSeoScoreChart());
</script>
@endscript
