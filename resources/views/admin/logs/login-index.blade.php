<div>
    <x-admin.page-header
        eyebrow="Audit"
        icon="key-round"
        title="Login Logs"
        description="Every login attempt — successful, failed, or explicit logout — with device, browser, and approximate location.">
        <x-slot:actions>
            @if ($search !== '' || $statusFilter !== '')
                <button type="button" wire:click="clearFilters"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                    <i data-lucide="filter-x" class="h-3.5 w-3.5"></i>
                    Clear filters
                </button>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.table-shell
        title="User Login History"
        description="Paginated, latest first."
        search-model="search"
        search-placeholder="Search IP, browser, user, attempted email…">

        <x-slot:actions>
            <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1 dark:border-slate-800 dark:bg-slate-900">
                <button type="button" wire:click="$set('statusFilter', '')"
                        class="rounded-md px-3 py-1.5 text-xs font-bold transition {{ $statusFilter === '' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-950 dark:text-slate-100' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
                    All
                </button>
                @foreach ($statuses as $status)
                    <button type="button" wire:click="$set('statusFilter', '{{ $status }}')"
                            class="rounded-md px-3 py-1.5 text-xs font-bold capitalize transition {{ $statusFilter === $status ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-950 dark:text-slate-100' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
                        {{ $status }}
                    </button>
                @endforeach
            </div>
        </x-slot:actions>

        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
            <thead class="bg-slate-50 text-left text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:bg-slate-950/50 dark:text-slate-300">
                <tr>
                    <th class="px-4 py-3">SL</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">IP</th>
                    <th class="px-4 py-3">Device</th>
                    <th class="px-4 py-3">Platform</th>
                    <th class="px-4 py-3">Browser</th>
                    <th class="px-4 py-3">Location</th>
                    <th class="px-4 py-3">When</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($logs as $i => $log)
                    @php
                        $statusBadge = match ($log->status) {
                            \App\Models\LoginLog::STATUS_SUCCESS => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
                            \App\Models\LoginLog::STATUS_FAILED  => 'bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
                            \App\Models\LoginLog::STATUS_LOGOUT  => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                            default                              => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
                        };
                        $statusIcon = match ($log->status) {
                            \App\Models\LoginLog::STATUS_SUCCESS => 'check-circle-2',
                            \App\Models\LoginLog::STATUS_FAILED  => 'shield-alert',
                            \App\Models\LoginLog::STATUS_LOGOUT  => 'log-out',
                            default                              => 'circle',
                        };
                    @endphp
                    <tr wire:key="login-log-{{ $log->id }}" class="transition hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                        <td class="px-4 py-3 text-slate-400">{{ $logs->firstItem() + $i }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.18em] {{ $statusBadge }}">
                                <i data-lucide="{{ $statusIcon }}" class="h-2.5 w-2.5"></i>
                                {{ $log->status ?? 'success' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if ($log->user)
                                <div class="font-semibold text-slate-900 dark:text-slate-100">{{ $log->user->name }}</div>
                                <div class="text-xs text-slate-500">{{ $log->user->email }}</div>
                            @elseif ($log->attempted_email)
                                <div class="text-xs text-slate-500">attempted</div>
                                <div class="font-mono text-xs text-rose-600 dark:text-rose-300">{{ $log->attempted_email }}</div>
                            @else
                                <span class="text-xs text-slate-400">Unknown</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-700 dark:text-slate-300">{{ $log->ip_address ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $log->device ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $log->platform ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($log->browser)
                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">
                                    {{ $log->browser }}
                                </span>
                            @endif
                            @if ($log->device_type)
                                <span class="ml-1 inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                    {{ $log->device_type }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($log->country)
                                <div class="flex items-center gap-2">
                                    @if ($log->country_code)
                                        <span class="font-mono text-[10px] uppercase tracking-wider text-slate-400">{{ $log->country_code }}</span>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="truncate text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $log->country }}</div>
                                        @if ($log->city)
                                            <div class="truncate text-[11px] text-slate-500 dark:text-slate-400">{{ $log->city }}</div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-300">
                            {{ $log->login_at?->format('d M Y · h:i A') ?? '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-0">
                            <x-admin.empty-state
                                icon="key-round"
                                title="No login logs match your filter."
                                description="Try clearing the filter, or wait — logs accumulate as users sign in." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <x-slot:footer>
            {{ $logs->links() }}
        </x-slot:footer>
    </x-admin.table-shell>
</div>
