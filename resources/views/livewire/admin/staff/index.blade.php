<div>
    <x-admin.page-header
        eyebrow="People"
        icon="users"
        title="Staff Directory"
        description="Manage team members, their roles, and employment details.">
        <x-slot:actions>
            @can('staff.create')
                <a href="{{ route('admin.staff.create') }}" wire:navigate
                   class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    Add staff
                </a>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <x-admin.section title="Filters" description="Narrow the directory by department, role, or status." class="mb-6">
        <div class="grid gap-3 md:grid-cols-5">
            <input type="text" wire:model.live.debounce.300ms="search"
                   placeholder="Search name, email, employee ID…"
                   class="md:col-span-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">

            <select wire:model.live="departmentFilter"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                <option value="">All departments</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="roleFilter"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                <option value="">All roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="statusFilter"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                <option value="">All statuses</option>
                @foreach ($statuses as $statusOption)
                    <option value="{{ $statusOption }}">{{ ucfirst($statusOption) }}</option>
                @endforeach
            </select>
        </div>
    </x-admin.section>

    <x-admin.table-shell title="All staff" description="{{ $staff->total() }} total · paginated, latest first.">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
            <thead class="bg-slate-50 text-left text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:bg-slate-950/50 dark:text-slate-300">
                <tr>
                    <th class="px-4 py-3">Staff</th>
                    <th class="px-4 py-3">Department</th>
                    <th class="px-4 py-3">Manager</th>
                    <th class="px-4 py-3">Role(s)</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($staff as $member)
                    <tr wire:key="staff-{{ $member->id }}" class="transition hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <img src="{{ $member->avatarUrl() }}" alt="" class="h-9 w-9 rounded-full object-cover">
                                <div>
                                    <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $member->name }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $member->email }}@if ($member->employee_id) · {{ $member->employee_id }}@endif</div>
                                    @if ($member->job_title)
                                        <div class="text-xs text-slate-500 dark:text-slate-400">{{ $member->job_title }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ $member->department?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ $member->manager?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @forelse ($member->roles as $role)
                                    <span class="inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">{{ $role->name }}</span>
                                @empty
                                    <span class="text-xs text-slate-400">—</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-300">
                            {{ $member->employment_type ? Str::headline($member->employment_type) : '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @php($statusColor = match ($member->status) {
                                'active' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
                                'inactive' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                                'suspended' => 'bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
                                default => 'bg-slate-100 text-slate-700',
                            })
                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.18em] {{ $statusColor }}">{{ ucfirst($member->status ?? 'active') }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex gap-1">
                                @can('staff.view')
                                    <a href="{{ route('admin.staff.show', $member) }}" wire:navigate
                                       class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">View</a>
                                @endcan
                                @can('staff.edit')
                                    <a href="{{ route('admin.staff.edit', $member) }}" wire:navigate
                                       class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Edit</a>
                                @endcan
                                @can('staff.deactivate')
                                    @if ($member->status === 'active')
                                        <button wire:click="deactivate({{ $member->id }})" type="button"
                                                class="rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 transition hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">Deactivate</button>
                                    @else
                                        <button wire:click="activate({{ $member->id }})" type="button"
                                                class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">Activate</button>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-0">
                            <x-admin.empty-state
                                icon="users"
                                title="No staff matches your filter."
                                description="Try clearing filters or add a new team member." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <x-slot:footer>
            @if ($staff->hasPages())
                {{ $staff->links() }}
            @endif
        </x-slot:footer>
    </x-admin.table-shell>
</div>
