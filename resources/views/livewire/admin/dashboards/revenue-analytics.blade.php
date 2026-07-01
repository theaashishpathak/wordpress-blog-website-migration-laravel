<div class="space-y-6">
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">Revenue Analytics</span>
    </nav>

    <div class="flex items-center gap-3">
        <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-emerald-500 via-lime-500 to-yellow-500 text-white shadow-sm">
            <i data-lucide="dollar-sign" class="h-5 w-5"></i>
        </span>
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">Revenue Analytics</h1>
            <p class="text-xs text-slate-500">Ad performance and newsletter growth.</p>
        </div>
    </div>

    @php($t = $this->tiles)
    {{-- Top tiles --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 to-teal-50 p-5 shadow-sm dark:border-emerald-500/30 dark:from-emerald-500/10 dark:to-teal-500/10">
            <div class="flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white">
                    <i data-lucide="eye" class="h-4 w-4"></i>
                </span>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Impressions · all-time</p>
                    <p class="text-3xl font-black tabular-nums text-emerald-900 dark:text-emerald-200">{{ number_format($t['impressions']) }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-violet-50 p-5 shadow-sm dark:border-indigo-500/30 dark:from-indigo-500/10 dark:to-violet-500/10">
            <div class="flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 text-white">
                    <i data-lucide="mouse-pointer-click" class="h-4 w-4"></i>
                </span>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-indigo-700">Clicks · all-time</p>
                    <p class="text-3xl font-black tabular-nums text-indigo-900 dark:text-indigo-200">{{ number_format($t['clicks']) }}</p>
                </div>
            </div>
        </div>
        <div class="rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50 p-5 shadow-sm dark:border-amber-500/30 dark:from-amber-500/10 dark:to-orange-500/10">
            <div class="flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 text-white">
                    <i data-lucide="percent" class="h-4 w-4"></i>
                </span>
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-amber-700">Average CTR</p>
                    <p class="text-3xl font-black tabular-nums text-amber-900 dark:text-amber-200">{{ $t['ctr'] }}%</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        @foreach ([
            ['label' => 'Active creatives', 'value' => $t['active_creatives'], 'icon' => 'image', 'color' => 'from-sky-500 to-cyan-500'],
            ['label' => 'Active zones', 'value' => $t['active_zones'], 'icon' => 'layout-grid', 'color' => 'from-violet-500 to-fuchsia-500'],
            ['label' => 'Newsletter (confirmed)', 'value' => $t['subscribers'], 'icon' => 'mail-check', 'color' => 'from-pink-500 to-rose-500'],
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

    <div class="grid gap-4 lg:grid-cols-2">
        {{-- By zone --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Performance by zone</h3>
            </header>
            <table class="w-full text-sm">
                <thead class="bg-slate-50/60 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-950/40">
                    <tr>
                        <th class="px-4 py-2 text-left">Zone</th>
                        <th class="px-4 py-2 text-right">Impressions</th>
                        <th class="px-4 py-2 text-right">Clicks</th>
                        <th class="px-4 py-2 text-right">CTR</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($this->byZone as $row)
                        <tr>
                            <td class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $row->label }}</td>
                            <td class="px-4 py-2 text-right font-mono text-xs tabular-nums">{{ number_format($row->impressions) }}</td>
                            <td class="px-4 py-2 text-right font-mono text-xs tabular-nums">{{ number_format($row->clicks) }}</td>
                            <td class="px-4 py-2 text-right font-mono text-xs font-bold tabular-nums text-emerald-700 dark:text-emerald-300">{{ $row->ctr }}%</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-xs text-slate-500">No ad zones configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Top creatives --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Top creatives by clicks</h3>
            </header>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($this->topCreatives as $i => $c)
                    <li class="flex items-start gap-3 px-5 py-3">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-500 text-[10px] font-black text-white">#{{ $i + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $c->name }}</p>
                            <p class="text-[11px] text-slate-500">{{ $c->zone ?? '—' }}</p>
                        </div>
                        <div class="text-right text-xs">
                            <p class="font-mono font-bold tabular-nums">{{ number_format($c->clicks) }} clicks</p>
                            <p class="font-mono text-[10px] tabular-nums text-emerald-700 dark:text-emerald-300">CTR {{ $c->ctr }}%</p>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-xs text-slate-500">No ad creatives yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Newsletter growth chart + mix --}}
    @php($mix = $this->subscriberMix)
    <div class="grid gap-4 lg:grid-cols-[2fr_1fr]">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Newsletter growth · last 30 days</h3>
                    <p class="text-[11px] text-slate-500">Confirmed subscribers (cumulative) + new confirmations per day.</p>
                </div>
                <span class="rounded-full bg-pink-50 px-3 py-0.5 text-xs font-bold text-pink-700 dark:bg-pink-500/15 dark:text-pink-300">
                    +{{ number_format($mix['growth_30d']) }} this month
                </span>
            </div>
            <div class="relative h-64">
                <canvas id="newsletter-growth-chart"></canvas>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h3 class="mb-3 text-sm font-bold text-slate-800 dark:text-slate-100">Subscriber mix</h3>
            <ul class="space-y-2 text-sm">
                <li class="flex items-center justify-between">
                    <span class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-emerald-500"></span> Confirmed</span>
                    <span class="font-mono font-bold tabular-nums">{{ number_format($mix['confirmed']) }}</span>
                </li>
                <li class="flex items-center justify-between">
                    <span class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-500"></span> Pending</span>
                    <span class="font-mono tabular-nums">{{ number_format($mix['pending']) }}</span>
                </li>
                <li class="flex items-center justify-between">
                    <span class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-slate-400"></span> Unsubscribed</span>
                    <span class="font-mono tabular-nums">{{ number_format($mix['unsubscribed']) }}</span>
                </li>
                <li class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 text-base font-bold dark:border-slate-800">
                    <span>Total</span>
                    <span class="font-mono tabular-nums">{{ number_format($mix['total']) }}</span>
                </li>
            </ul>
        </div>
    </div>
</div>

@script
<script>
    const renderNewsletterChart = () => {
        const el = document.getElementById('newsletter-growth-chart');
        if (! el || typeof Chart === 'undefined') return;
        if (window._newsletterChart) window._newsletterChart.destroy();

        const labels = @json($this->newsletterGrowth['labels']);
        const total = @json($this->newsletterGrowth['total']);
        const newToday = @json($this->newsletterGrowth['new_today']);

        window._newsletterChart = new Chart(el, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'Cumulative confirmed',
                        data: total,
                        borderColor: 'rgb(236, 72, 153)',
                        backgroundColor: 'rgba(236, 72, 153, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.35,
                        yAxisID: 'y',
                    },
                    {
                        label: 'New today',
                        data: newToday,
                        type: 'bar',
                        backgroundColor: 'rgba(99, 102, 241, 0.5)',
                        borderRadius: 4,
                        yAxisID: 'y1',
                    },
                ],
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10 } } },
                scales: {
                    y: { position: 'left', beginAtZero: false },
                    y1: { position: 'right', beginAtZero: true, ticks: { stepSize: 1 }, grid: { display: false } },
                },
            },
        });
    };
    renderNewsletterChart();
    Livewire.hook('morph.updated', () => renderNewsletterChart());
</script>
@endscript
