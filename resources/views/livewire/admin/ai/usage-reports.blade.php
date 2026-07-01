<div class="space-y-5">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">AI · Usage Reports</span>
    </nav>

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-500 text-white shadow-sm">
                <i data-lucide="bar-chart-3" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">AI Usage Reports</h1>
                <p class="text-xs text-slate-500">Calls, tokens, cost, and recent errors across every AI provider.</p>
            </div>
        </div>
    </div>

    {{-- Date range presets --}}
    <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        @foreach ([
            'today' => 'Today',
            '7d' => 'Last 7 days',
            '30d' => 'Last 30 days',
            'this_month' => 'This month',
            'last_month' => 'Last month',
            'ytd' => 'YTD',
        ] as $key => $label)
            <button type="button" wire:click="setRange('{{ $key }}')"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                {{ $label }}
            </button>
        @endforeach

        <div class="ml-auto flex items-center gap-2">
            <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">From</label>
            <input type="date" wire:model.live="from"
                   class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-950">
            <label class="text-[11px] font-bold uppercase tracking-wider text-slate-500">To</label>
            <input type="date" wire:model.live="to"
                   class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-950">

            <select wire:model.live="providerFilter"
                    class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-950">
                <option value="">All providers</option>
                @foreach ($this->providers as $p)
                    <option value="{{ $p }}">{{ $p }}</option>
                @endforeach
            </select>
            <select wire:model.live="featureFilter"
                    class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-950">
                <option value="">All features</option>
                @foreach ($this->features as $f)
                    <option value="{{ $f }}">{{ $f }}</option>
                @endforeach
            </select>
            <button type="button" wire:click="clearFilters"
                    class="grid h-8 w-8 place-items-center rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900">
                <i data-lucide="filter-x" class="h-3.5 w-3.5"></i>
            </button>
        </div>
    </div>

    {{-- Summary tiles --}}
    @php($s = $this->summary)
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['label' => 'Total calls', 'value' => number_format($s['calls']), 'icon' => 'activity', 'color' => 'from-indigo-500 to-violet-500'],
            ['label' => 'Successful', 'value' => number_format($s['successful']), 'icon' => 'check-circle', 'color' => 'from-emerald-500 to-teal-500'],
            ['label' => 'Failed / filtered', 'value' => number_format($s['failed']), 'icon' => 'triangle-alert', 'color' => 'from-rose-500 to-orange-500'],
            ['label' => 'Total tokens', 'value' => number_format($s['tokens']), 'icon' => 'hash', 'color' => 'from-amber-500 to-yellow-500'],
        ] as $tile)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $tile['label'] }}</span>
                    <span class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br {{ $tile['color'] }} text-white">
                        <i data-lucide="{{ $tile['icon'] }}" class="h-4 w-4"></i>
                    </span>
                </div>
                <p class="mt-2 text-3xl font-black tabular-nums text-slate-900 dark:text-slate-100">{{ $tile['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        {{-- Cost + latency --}}
        <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-emerald-50 to-teal-50 p-5 shadow-sm dark:border-emerald-500/30 dark:from-emerald-500/10 dark:to-teal-500/10">
            <div class="flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white">
                    <i data-lucide="dollar-sign" class="h-4 w-4"></i>
                </span>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Estimated spend</p>
                    <p class="text-3xl font-black tabular-nums text-emerald-900 dark:text-emerald-200">${{ number_format($s['cost'], 4) }}</p>
                </div>
            </div>
            <p class="mt-3 text-xs text-emerald-800/80 dark:text-emerald-200/80">
                Based on per-provider pricing tables. Treat as a low-precision estimate — final billing comes from your provider invoice.
            </p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-gradient-to-br from-sky-50 to-indigo-50 p-5 shadow-sm dark:border-sky-500/30 dark:from-sky-500/10 dark:to-indigo-500/10">
            <div class="flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-sky-500 to-indigo-500 text-white">
                    <i data-lucide="zap" class="h-4 w-4"></i>
                </span>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Average latency</p>
                    <p class="text-3xl font-black tabular-nums text-sky-900 dark:text-sky-200">{{ number_format($s['avg_latency_ms']) }} <span class="text-base font-bold">ms</span></p>
                </div>
            </div>
            <p class="mt-3 text-xs text-sky-800/80 dark:text-sky-200/80">
                Average wall-clock for successful generations in the selected window.
            </p>
        </div>
    </div>

    {{-- Breakdown tables --}}
    <div class="grid gap-4 lg:grid-cols-2">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">By provider</h3>
            </header>
            <table class="w-full text-sm">
                <thead class="bg-slate-50/60 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-950/40">
                    <tr><th class="px-4 py-2 text-left">Provider</th><th class="px-4 py-2 text-right">Calls</th><th class="px-4 py-2 text-right">Tokens</th><th class="px-4 py-2 text-right">Cost</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($this->byProvider as $row)
                        <tr>
                            <td class="px-4 py-2 font-mono text-xs text-slate-700 dark:text-slate-200">{{ $row->provider }}</td>
                            <td class="px-4 py-2 text-right font-mono tabular-nums">{{ number_format((int) $row->calls) }}</td>
                            <td class="px-4 py-2 text-right font-mono tabular-nums">{{ number_format((int) $row->tokens) }}</td>
                            <td class="px-4 py-2 text-right font-mono tabular-nums text-emerald-700 dark:text-emerald-300">${{ number_format((float) $row->cost, 4) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-xs text-slate-500">No usage in this window.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">By feature</h3>
            </header>
            <table class="w-full text-sm">
                <thead class="bg-slate-50/60 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-950/40">
                    <tr><th class="px-4 py-2 text-left">Feature key</th><th class="px-4 py-2 text-right">Calls</th><th class="px-4 py-2 text-right">Tokens</th><th class="px-4 py-2 text-right">Cost</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($this->byFeature as $row)
                        <tr>
                            <td class="px-4 py-2 font-mono text-xs text-slate-700 dark:text-slate-200">{{ $row->feature_key }}</td>
                            <td class="px-4 py-2 text-right font-mono tabular-nums">{{ number_format((int) $row->calls) }}</td>
                            <td class="px-4 py-2 text-right font-mono tabular-nums">{{ number_format((int) $row->tokens) }}</td>
                            <td class="px-4 py-2 text-right font-mono tabular-nums text-emerald-700 dark:text-emerald-300">${{ number_format((float) $row->cost, 4) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-xs text-slate-500">No usage in this window.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Top users --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Top users by cost</h3>
        </header>
        <table class="w-full text-sm">
            <thead class="bg-slate-50/60 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-950/40">
                <tr><th class="px-4 py-2 text-left">User</th><th class="px-4 py-2 text-right">Calls</th><th class="px-4 py-2 text-right">Tokens</th><th class="px-4 py-2 text-right">Cost</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($this->byUser as $row)
                    <tr>
                        <td class="px-4 py-2">
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $row->name ?? 'Anonymous' }}</p>
                            <p class="font-mono text-[10px] text-slate-500">{{ $row->email ?? '—' }}</p>
                        </td>
                        <td class="px-4 py-2 text-right font-mono tabular-nums">{{ number_format((int) $row->calls) }}</td>
                        <td class="px-4 py-2 text-right font-mono tabular-nums">{{ number_format((int) $row->tokens) }}</td>
                        <td class="px-4 py-2 text-right font-mono tabular-nums text-emerald-700 dark:text-emerald-300">${{ number_format((float) $row->cost, 4) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-xs text-slate-500">No user usage in this window.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Recent errors --}}
    <div class="overflow-hidden rounded-2xl border border-rose-200 bg-rose-50/40 shadow-sm dark:border-rose-500/30 dark:bg-rose-500/5">
        <header class="border-b border-rose-200/60 px-5 py-3 dark:border-rose-500/30">
            <h3 class="flex items-center gap-2 text-sm font-bold text-rose-700 dark:text-rose-300">
                <i data-lucide="triangle-alert" class="h-4 w-4"></i>
                Recent errors
            </h3>
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
                <div class="px-5 py-6 text-center text-xs text-slate-500">No errors in this window. 🎉</div>
            @endforelse
        </div>
    </div>
</div>
