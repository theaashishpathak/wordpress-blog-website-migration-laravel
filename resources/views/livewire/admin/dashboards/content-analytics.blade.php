<div class="space-y-6">
    {{-- Header --}}
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">Content Analytics</span>
    </nav>

    <div class="flex items-center gap-3">
        <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-500 text-white shadow-sm">
            <i data-lucide="bar-chart-3" class="h-5 w-5"></i>
        </span>
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">Content Analytics</h1>
            <p class="text-xs text-slate-500">Catalogue shape, throughput, and per-post performance.</p>
        </div>
    </div>

    @php($s = $this->statusCounts)
    {{-- Status mix --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
        @foreach ([
            ['label' => 'Total posts', 'value' => $s['total'], 'color' => 'from-indigo-500 to-violet-500', 'icon' => 'newspaper'],
            ['label' => 'Published', 'value' => $s['published'], 'color' => 'from-emerald-500 to-teal-500', 'icon' => 'check-circle'],
            ['label' => 'Draft', 'value' => $s['draft'], 'color' => 'from-slate-400 to-slate-600', 'icon' => 'file'],
            ['label' => 'Pending review', 'value' => $s['pending'], 'color' => 'from-amber-500 to-orange-500', 'icon' => 'hourglass'],
            ['label' => 'Scheduled', 'value' => $s['scheduled'], 'color' => 'from-violet-500 to-fuchsia-500', 'icon' => 'calendar-clock'],
            ['label' => 'Archived', 'value' => $s['archived'], 'color' => 'from-zinc-400 to-zinc-600', 'icon' => 'archive'],
        ] as $tile)
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ $tile['label'] }}</span>
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-gradient-to-br {{ $tile['color'] }} text-white">
                        <i data-lucide="{{ $tile['icon'] }}" class="h-3.5 w-3.5"></i>
                    </span>
                </div>
                <p class="mt-1 text-2xl font-black tabular-nums text-slate-900 dark:text-slate-100">{{ number_format($tile['value']) }}</p>
            </div>
        @endforeach
    </div>

    {{-- Publishing chart --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h3 class="mb-3 text-sm font-bold text-slate-800 dark:text-slate-100">Publishing throughput — last 30 days</h3>
        <div class="relative h-64">
            <canvas id="content-throughput-chart"></canvas>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        {{-- Top by views --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Top posts by views</h3>
            </header>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($this->topByViews as $i => $p)
                        <tr>
                            <td class="w-8 px-3 py-2 text-right font-mono text-xs text-slate-400">{{ $i + 1 }}</td>
                            <td class="px-3 py-2">
                                <a href="{{ route('admin.posts.edit', $p) }}" wire:navigate class="line-clamp-1 text-sm font-semibold text-slate-800 hover:text-indigo-600 dark:text-slate-100">
                                    {{ $p->translation()?->title ?? '#'.$p->id }}
                                </a>
                                <p class="text-[11px] text-slate-500">{{ $p->category?->translation()?->name ?? '—' }}</p>
                            </td>
                            <td class="w-24 px-3 py-2 text-right">
                                <span class="font-mono font-bold tabular-nums text-pink-700 dark:text-pink-300">{{ number_format((int) $p->view_count) }}</span>
                                <p class="text-[10px] uppercase tracking-wider text-slate-400">views</p>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-8 text-center text-xs text-slate-500">No published posts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Top by comments --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Top posts by comments</h3>
            </header>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($this->topByComments as $i => $p)
                        <tr>
                            <td class="w-8 px-3 py-2 text-right font-mono text-xs text-slate-400">{{ $i + 1 }}</td>
                            <td class="px-3 py-2">
                                <a href="{{ route('admin.posts.edit', $p) }}" wire:navigate class="line-clamp-1 text-sm font-semibold text-slate-800 hover:text-indigo-600 dark:text-slate-100">
                                    {{ $p->translation()?->title ?? '#'.$p->id }}
                                </a>
                                <p class="text-[11px] text-slate-500">{{ $p->category?->translation()?->name ?? '—' }}</p>
                            </td>
                            <td class="w-24 px-3 py-2 text-right">
                                <span class="font-mono font-bold tabular-nums text-sky-700 dark:text-sky-300">{{ number_format((int) $p->comment_count) }}</span>
                                <p class="text-[10px] uppercase tracking-wider text-slate-400">comments</p>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-5 py-8 text-center text-xs text-slate-500">No commented posts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        {{-- Author leaderboard --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Authors · last 30 days</h3>
            </header>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($this->authorLeaderboard as $i => $row)
                    <li class="flex items-center gap-3 px-5 py-2">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 text-[10px] font-black text-white">
                            #{{ $i + 1 }}
                        </span>
                        <span class="min-w-0 flex-1 truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $row->name }}</span>
                        <span class="font-mono text-xs tabular-nums text-slate-500">{{ $row->c }} posts</span>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-xs text-slate-500">No authors active in the last 30 days.</li>
                @endforelse
            </ul>
        </div>

        {{-- Category breakdown --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Categories</h3>
            </header>
            <ul class="space-y-2 px-5 py-4">
                @php($max = $this->categoryBreakdown->max('c') ?: 1)
                @forelse ($this->categoryBreakdown as $row)
                    <li>
                        <div class="mb-0.5 flex items-center justify-between text-xs">
                            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $row->label }}</span>
                            <span class="font-mono text-slate-500">{{ $row->c }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-violet-500"
                                 style="width: {{ (int) (($row->c / $max) * 100) }}%"></div>
                        </div>
                    </li>
                @empty
                    <li class="py-4 text-center text-xs text-slate-500">No categorised posts yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Translation coverage --}}
    @if (count($this->translationCoverage) > 0)
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Translation coverage · published posts</h3>
            </header>
            <div class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->translationCoverage as $row)
                    <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">
                                {{ $row['language']->flag_emoji ?? '🌐' }} {{ $row['language']->name }}
                            </span>
                            <span class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-300">{{ $row['percent'] }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-violet-500"
                                 style="width: {{ $row['percent'] }}%"></div>
                        </div>
                        <p class="mt-2 text-[10px] uppercase tracking-wider text-slate-400">{{ $row['count'] }} translation rows</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Tag cloud --}}
    @php($ts = $this->tagStats)
    @if ($ts['most_used']->isNotEmpty())
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Most-used tags</h3>
                <span class="text-xs text-slate-500">{{ $ts['total'] }} total tags · avg {{ $ts['posts_per_tag_avg'] }} posts/tag</span>
            </div>
            <div class="flex flex-wrap gap-2">
                @php($max = $ts['most_used']->max('c') ?: 1)
                @foreach ($ts['most_used'] as $row)
                    @php($scale = max(0.85, min(1.6, $row->c / $max * 1.5)))
                    <span class="rounded-full bg-violet-50 px-3 py-1 font-semibold text-violet-700 dark:bg-violet-500/10 dark:text-violet-300"
                          style="font-size: {{ $scale }}rem;">
                        {{ $row->name }} <span class="ml-1 font-mono text-[10px] opacity-70">{{ $row->c }}</span>
                    </span>
                @endforeach
            </div>
        </div>
    @endif
</div>

@script
<script>
    const renderContentThroughput = () => {
        const el = document.getElementById('content-throughput-chart');
        if (! el || typeof Chart === 'undefined') return;
        if (window._contentThroughputChart) window._contentThroughputChart.destroy();

        const labels = @json($this->publishingChart['labels']);
        const data = @json($this->publishingChart['counts']);

        window._contentThroughputChart = new Chart(el, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    data,
                    backgroundColor: 'rgba(99, 102, 241, 0.6)',
                    borderColor: 'rgb(99, 102, 241)',
                    borderRadius: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                },
            },
        });
    };
    renderContentThroughput();
    Livewire.hook('morph.updated', () => renderContentThroughput());
</script>
@endscript
