<div class="space-y-6">
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">AI Usage Overview</span>
    </nav>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-violet-500 via-fuchsia-500 to-pink-500 text-white shadow-sm">
                <i data-lucide="sparkles" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">AI Usage Overview</h1>
                <p class="text-xs text-slate-500">Pulse of every AI call this month.</p>
            </div>
        </div>
        <a href="{{ route('admin.ai.usage-reports') }}" wire:navigate
           class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
            <i data-lucide="bar-chart-3" class="h-3.5 w-3.5"></i> Detailed report
        </a>
    </div>

    @php($t = $this->tiles)
    @php($costDelta = $t['prev_cost'] > 0 ? round((($t['month_cost'] - $t['prev_cost']) / $t['prev_cost']) * 100, 1) : null)
    @php($callsDelta = $t['prev_calls'] > 0 ? round((($t['month_calls'] - $t['prev_calls']) / $t['prev_calls']) * 100, 1) : null)

    {{-- Hero cost tile + comparison --}}
    <div class="grid gap-4 lg:grid-cols-[2fr_1fr]">
        <div class="rounded-3xl bg-gradient-to-br from-violet-600 via-fuchsia-600 to-pink-600 p-6 text-white shadow-lg">
            <p class="text-xs font-bold uppercase tracking-widest text-white/70">Spend this month</p>
            <p class="mt-1 text-5xl font-black tabular-nums">${{ number_format($t['month_cost'], 4) }}</p>
            <p class="mt-2 text-xs text-white/80">
                Last month: ${{ number_format($t['prev_cost'], 4) }}
                @if ($costDelta !== null)
                    <span class="ml-2 inline-flex items-center gap-1 rounded-full bg-white/15 px-2 py-0.5 text-[10px] font-bold">
                        @if ($costDelta > 0)
                            <i data-lucide="trending-up" class="h-3 w-3"></i> +{{ $costDelta }}%
                        @elseif ($costDelta < 0)
                            <i data-lucide="trending-down" class="h-3 w-3"></i> {{ $costDelta }}%
                        @else
                            flat
                        @endif
                    </span>
                @endif
            </p>
            <div class="mt-5 grid grid-cols-3 gap-2 text-center">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-white/70">Calls (mo)</p>
                    <p class="text-xl font-black tabular-nums">{{ number_format($t['month_calls']) }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-white/70">Tokens (mo)</p>
                    <p class="text-xl font-black tabular-nums">{{ number_format($t['month_tokens']) }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-white/70">Success rate</p>
                    <p class="text-xl font-black tabular-nums">{{ $t['month_success_rate'] }}%</p>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Today</p>
                <p class="mt-1 text-3xl font-black tabular-nums text-slate-900 dark:text-slate-100">${{ number_format($t['today_cost'], 4) }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ number_format($t['today_calls']) }} call{{ $t['today_calls'] === 1 ? '' : 's' }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Calls month-over-month</p>
                <div class="mt-1 flex items-baseline gap-2">
                    <span class="text-2xl font-black tabular-nums text-slate-900 dark:text-slate-100">{{ number_format($t['month_calls']) }}</span>
                    @if ($callsDelta !== null)
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold
                                     {{ $callsDelta >= 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300' }}">
                            {{ $callsDelta >= 0 ? '+' : '' }}{{ $callsDelta }}%
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-xs text-slate-500">vs {{ number_format($t['prev_calls']) }} last month</p>
            </div>
        </div>
    </div>

    {{-- 30-day chart --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h3 class="mb-3 text-sm font-bold text-slate-800 dark:text-slate-100">Cost & calls · last 30 days</h3>
        <div class="relative h-64">
            <canvas id="ai-cost-chart"></canvas>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        {{-- By provider --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Spend by provider · this month</h3>
            </header>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($this->byProvider as $row)
                        <tr>
                            <td class="px-4 py-2 font-mono text-xs text-slate-700 dark:text-slate-200">{{ $row->provider }}</td>
                            <td class="px-4 py-2 text-right font-mono text-xs tabular-nums">{{ number_format($row->calls) }} calls</td>
                            <td class="px-4 py-2 text-right font-mono text-xs font-bold tabular-nums text-emerald-700 dark:text-emerald-300">${{ number_format($row->cost, 4) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-xs text-slate-500">No AI calls this month.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- By feature --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Spend by feature · this month</h3>
            </header>
            <table class="w-full text-sm">
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($this->byFeature as $row)
                        <tr>
                            <td class="px-4 py-2 font-mono text-xs text-slate-700 dark:text-slate-200">{{ $row->feature_key }}</td>
                            <td class="px-4 py-2 text-right font-mono text-xs tabular-nums">{{ number_format($row->calls) }} calls</td>
                            <td class="px-4 py-2 text-right font-mono text-xs font-bold tabular-nums text-emerald-700 dark:text-emerald-300">${{ number_format($row->cost, 4) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-8 text-center text-xs text-slate-500">No features tagged yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent errors --}}
    <div class="overflow-hidden rounded-2xl border border-rose-200 bg-rose-50/40 shadow-sm dark:border-rose-500/30 dark:bg-rose-500/5">
        <header class="flex items-center justify-between border-b border-rose-200/60 px-5 py-3 dark:border-rose-500/30">
            <h3 class="flex items-center gap-2 text-sm font-bold text-rose-700 dark:text-rose-300">
                <i data-lucide="triangle-alert" class="h-4 w-4"></i> Recent errors
            </h3>
            <a href="{{ route('admin.ai.usage-reports') }}" wire:navigate class="text-xs font-semibold text-rose-700 hover:underline dark:text-rose-300">See all →</a>
        </header>
        <div class="divide-y divide-rose-200/60 dark:divide-rose-500/30">
            @forelse ($this->recentErrors as $err)
                <div class="grid gap-2 px-5 py-3 md:grid-cols-[140px_120px_120px_1fr_120px]">
                    <span class="font-mono text-xs text-slate-500">{{ $err->created_at?->diffForHumans() }}</span>
                    <span class="font-mono text-xs text-slate-700 dark:text-slate-200">{{ $err->provider }}</span>
                    <span class="rounded-md bg-rose-100 px-2 py-0.5 text-center text-[10px] font-bold uppercase tracking-wider text-rose-700 dark:bg-rose-500/20 dark:text-rose-300">{{ $err->status }}</span>
                    <span class="truncate text-xs text-slate-700 dark:text-slate-200" title="{{ $err->error_message }}">{{ $err->error_message ?? '—' }}</span>
                    <span class="truncate font-mono text-[10px] text-slate-500">{{ $err->user?->name ?? '—' }}</span>
                </div>
            @empty
                <div class="px-5 py-6 text-center text-xs text-slate-500">No errors recently. 🎉</div>
            @endforelse
        </div>
    </div>
</div>

@script
<script>
    const renderAiChart = () => {
        const el = document.getElementById('ai-cost-chart');
        if (! el || typeof Chart === 'undefined') return;
        if (window._aiCostChart) window._aiCostChart.destroy();

        const labels = @json($this->costChart['labels']);
        const cost = @json($this->costChart['cost']);
        const calls = @json($this->costChart['calls']);

        window._aiCostChart = new Chart(el, {
            type: 'line',
            data: { labels, datasets: [
                {
                    label: 'Cost ($)',
                    data: cost,
                    borderColor: 'rgb(168, 85, 247)',
                    backgroundColor: 'rgba(168, 85, 247, 0.12)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.35,
                    yAxisID: 'y',
                },
                {
                    label: 'Calls',
                    data: calls,
                    type: 'bar',
                    backgroundColor: 'rgba(236, 72, 153, 0.4)',
                    borderRadius: 4,
                    yAxisID: 'y1',
                },
            ] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10 } } },
                scales: {
                    y: { position: 'left', beginAtZero: true, title: { display: true, text: 'USD' } },
                    y1: { position: 'right', beginAtZero: true, ticks: { stepSize: 1 }, grid: { display: false }, title: { display: true, text: 'Calls' } },
                },
            },
        });
    };
    renderAiChart();
    Livewire.hook('morph.updated', () => renderAiChart());
</script>
@endscript
