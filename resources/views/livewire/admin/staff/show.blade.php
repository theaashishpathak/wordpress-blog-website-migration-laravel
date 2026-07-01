<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Staff Profile</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Profile, employment summary, and recent activity.</p>
        </div>

        <div class="flex gap-2">
            @can('staff.edit')
                <a href="{{ route('admin.staff.edit', $user) }}" wire:navigate class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Edit</a>
            @endcan
            <a href="{{ route('admin.staff.index') }}" wire:navigate class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Back</a>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Profile card --}}
        <div class="xl:col-span-1">
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col items-center text-center">
                    <img src="{{ $user->avatarUrl() }}" alt="" class="h-24 w-24 rounded-full object-cover ring-4 ring-indigo-100 dark:ring-indigo-500/20">
                    <h2 class="mt-4 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $user->name }}</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $user->job_title ?? 'No job title' }}</p>

                    @php($statusColor = match ($user->status) {
                        'active' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
                        'inactive' => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
                        'suspended' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300',
                        default => 'bg-slate-100 text-slate-700',
                    })
                    <span class="mt-2 rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusColor }}">{{ ucfirst($user->status ?? 'active') }}</span>
                </div>

                <dl class="mt-6 space-y-3 border-t border-slate-200 pt-6 text-sm dark:border-slate-800">
                    <div class="flex justify-between"><dt class="text-slate-500 dark:text-slate-400">Email</dt><dd class="font-medium text-slate-800 dark:text-slate-200">{{ $user->email }}</dd></div>
                    @if ($user->employee_id)
                        <div class="flex justify-between"><dt class="text-slate-500 dark:text-slate-400">Employee ID</dt><dd class="font-mono text-slate-800 dark:text-slate-200">{{ $user->employee_id }}</dd></div>
                    @endif
                    @if ($user->phone)
                        <div class="flex justify-between"><dt class="text-slate-500 dark:text-slate-400">Phone</dt><dd class="text-slate-800 dark:text-slate-200">{{ $user->phone }}</dd></div>
                    @endif
                    @if ($user->mobile)
                        <div class="flex justify-between"><dt class="text-slate-500 dark:text-slate-400">Mobile</dt><dd class="text-slate-800 dark:text-slate-200">{{ $user->mobile }}</dd></div>
                    @endif
                    <div class="flex justify-between"><dt class="text-slate-500 dark:text-slate-400">Department</dt><dd class="text-slate-800 dark:text-slate-200">{{ $user->department?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500 dark:text-slate-400">Manager</dt><dd class="text-slate-800 dark:text-slate-200">{{ $user->manager?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500 dark:text-slate-400">Hire Date</dt><dd class="text-slate-800 dark:text-slate-200">{{ $user->hire_date?->format('M d, Y') ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500 dark:text-slate-400">Type</dt><dd class="text-slate-800 dark:text-slate-200">{{ $user->employment_type ? Str::headline($user->employment_type) : '—' }}</dd></div>
                </dl>

                <div class="mt-6 border-t border-slate-200 pt-4 dark:border-slate-800">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Roles</h3>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @forelse ($user->roles as $role)
                            <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">{{ $role->name }}</span>
                        @empty
                            <span class="text-xs text-slate-400">No roles assigned</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Right column --}}
        <div class="xl:col-span-2 space-y-6">
            {{-- Subordinates --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">Direct Reports ({{ $subordinates->count() }})</h3>
                @if ($subordinates->isEmpty())
                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">No direct reports.</p>
                @else
                    <div class="mt-4 grid gap-2 sm:grid-cols-2">
                        @foreach ($subordinates as $sub)
                            <a href="{{ route('admin.staff.show', $sub) }}" wire:navigate class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:border-indigo-300 hover:bg-indigo-50/40 dark:border-slate-700 dark:hover:border-indigo-500/40 dark:hover:bg-indigo-500/5">
                                <img src="{{ $sub->avatarUrl() }}" class="h-8 w-8 rounded-full">
                                <div class="text-sm">
                                    <div class="font-medium text-slate-800 dark:text-slate-200">{{ $sub->name }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $sub->job_title ?? $sub->email }}</div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Activity --}}
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">Recent Activity</h3>
                @if ($recentActivity->isEmpty())
                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">No recent activity.</p>
                @else
                    <ul class="mt-4 space-y-2">
                        @foreach ($recentActivity as $log)
                            <li class="text-sm">
                                <span class="text-slate-700 dark:text-slate-200">{{ $log->description }}</span>
                                <span class="text-xs text-slate-400"> · {{ $log->created_at->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
