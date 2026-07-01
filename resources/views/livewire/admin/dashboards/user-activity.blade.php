<div class="space-y-6">
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">User Activity</span>
    </nav>

    <div class="flex items-center gap-3">
        <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-sky-500 via-blue-500 to-indigo-500 text-white shadow-sm">
            <i data-lucide="users-round" class="h-5 w-5"></i>
        </span>
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">User Activity</h1>
            <p class="text-xs text-slate-500">Who's signing in, who's shipping, who's using AI.</p>
        </div>
    </div>

    @php($t = $this->tiles)
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['label' => 'Total staff', 'value' => $t['total'], 'icon' => 'users-round', 'color' => 'from-indigo-500 to-violet-500'],
            ['label' => 'Active last 7 days', 'value' => $t['active_7d'], 'icon' => 'activity', 'color' => 'from-emerald-500 to-teal-500'],
            ['label' => 'Logins · 24h', 'value' => $t['logins_24h'], 'icon' => 'log-in', 'color' => 'from-sky-500 to-cyan-500'],
            ['label' => 'Logins · 30d', 'value' => $t['logins_30d'], 'icon' => 'calendar-range', 'color' => 'from-amber-500 to-orange-500'],
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

    {{-- Logins chart --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h3 class="mb-3 text-sm font-bold text-slate-800 dark:text-slate-100">Logins · last 14 days</h3>
        <div class="relative h-64">
            <canvas id="user-logins-chart"></canvas>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        {{-- Top authors --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Top authors · last 30 days</h3>
            </header>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($this->topAuthors as $i => $a)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 text-[10px] font-black text-white">#{{ $i + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $a->name ?? '—' }}</p>
                            <p class="truncate text-[11px] text-slate-500">{{ $a->email }}</p>
                        </div>
                        <div class="text-right text-xs">
                            <p class="font-mono font-bold tabular-nums">{{ $a->posts }} posts</p>
                            <p class="text-[10px] text-slate-400">last {{ \Illuminate\Support\Carbon::parse($a->last_post)->diffForHumans() }}</p>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-xs text-slate-500">No posts in the last 30 days.</li>
                @endforelse
            </ul>
        </div>

        {{-- Top AI users --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Top AI users · this month</h3>
            </header>
            <table class="w-full text-sm">
                <thead class="bg-slate-50/60 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-950/40">
                    <tr>
                        <th class="px-4 py-2 text-left">User</th>
                        <th class="px-4 py-2 text-right">Calls</th>
                        <th class="px-4 py-2 text-right">Tokens</th>
                        <th class="px-4 py-2 text-right">Cost</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($this->topAiUsers as $u)
                        <tr>
                            <td class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $u->name }}</td>
                            <td class="px-4 py-2 text-right font-mono text-xs tabular-nums">{{ number_format($u->calls) }}</td>
                            <td class="px-4 py-2 text-right font-mono text-xs tabular-nums">{{ number_format($u->tokens) }}</td>
                            <td class="px-4 py-2 text-right font-mono text-xs font-bold tabular-nums text-emerald-700 dark:text-emerald-300">${{ number_format($u->cost, 4) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-xs text-slate-500">No AI usage this month.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        {{-- Recent logins --}}
        @can('logs.login.view')
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Recent logins</h3>
                    <a href="{{ route('admin.logs.login.index') }}" wire:navigate class="text-xs font-semibold text-indigo-600 hover:underline">All logins →</a>
                </header>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($this->recentLogins as $log)
                        <li class="flex items-center gap-3 px-5 py-2.5">
                            <img src="{{ $log->user?->avatarUrl() ?? 'https://ui-avatars.com/api/?name=?' }}" alt="" class="h-7 w-7 rounded-full object-cover">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-xs font-semibold text-slate-700 dark:text-slate-200">
                                    {{ $log->user?->name ?? 'Unknown' }}
                                </p>
                                <p class="truncate text-[10px] text-slate-500">
                                    {{ $log->browser }} · {{ $log->platform }}
                                    @if ($log->country) · {{ $log->country }} @endif
                                </p>
                            </div>
                            <span class="text-[10px] text-slate-400">{{ $log->login_at?->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-xs text-slate-500">No login activity yet.</li>
                    @endforelse
                </ul>
            </div>
        @endcan

        {{-- Recent activity --}}
        @can('logs.activity.view')
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Profile activity</h3>
                    <a href="{{ route('admin.logs.activity.index') }}" wire:navigate class="text-xs font-semibold text-indigo-600 hover:underline">All activity →</a>
                </header>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($this->recentActivity as $log)
                        <li class="flex items-start gap-3 px-5 py-2.5">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-gradient-to-br from-sky-500 to-indigo-500 text-[10px] font-bold uppercase text-white">
                                {{ mb_substr($log->user?->name ?? '?', 0, 1) }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs">
                                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $log->user?->name ?? 'Unknown' }}</span>
                                    <span class="text-slate-500">·</span>
                                    <span class="font-mono text-[10px] text-slate-500">{{ $log->event }}</span>
                                </p>
                                <p class="truncate text-[11px] text-slate-500">{{ $log->description }}</p>
                            </div>
                            <span class="text-[10px] text-slate-400">{{ $log->created_at?->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-xs text-slate-500">No profile activity yet.</li>
                    @endforelse
                </ul>
            </div>
        @endcan
    </div>
</div>

@script
<script>
    const renderUserLogins = () => {
        const el = document.getElementById('user-logins-chart');
        if (! el || typeof Chart === 'undefined') return;
        if (window._userLoginsChart) window._userLoginsChart.destroy();

        const labels = @json($this->loginsChart['labels']);
        const data = @json($this->loginsChart['counts']);

        window._userLoginsChart = new Chart(el, {
            type: 'line',
            data: { labels, datasets: [{
                data,
                borderColor: 'rgb(14, 165, 233)',
                backgroundColor: 'rgba(14, 165, 233, 0.12)',
                borderWidth: 2,
                fill: true,
                tension: 0.35,
            }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
            },
        });
    };
    renderUserLogins();
    Livewire.hook('morph.updated', () => renderUserLogins());
</script>
@endscript
