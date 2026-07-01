@php($title = 'Assign User Permissions')

<div class="space-y-6">
    <x-admin.page-header
        eyebrow="Access Control"
        icon="user-check"
        title="Direct User Permissions"
        description="Override roles by granting specific permissions to an individual admin or author. Use sparingly — prefer roles when possible.">
        <x-slot:actions>
            <a
                href="{{ route('admin.roles.index') }}"
                wire:navigate
                class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 px-3.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                <i data-lucide="shield" class="h-4 w-4"></i>
                Manage roles
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <x-admin.table-shell search-model="search" search-placeholder="Search admin/author users by name or email…">
        @if ($users->isEmpty())
            <div class="px-5 py-10">
                <x-admin.empty-state
                    icon="users"
                    title="No admin/author users found"
                    description="Try a different search, or onboard a new staff user." />
            </div>
        @else
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50/60 dark:bg-slate-950/30">
                    <tr class="text-left text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
                        <th class="px-5 py-3">User</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Roles</th>
                        <th class="px-5 py-3">Direct grants</th>
                        <th class="px-5 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($users as $user)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="grid h-8 w-8 place-items-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 text-xs font-bold text-white">
                                        {{ strtoupper(mb_substr($user->name, 0, 1)) }}
                                    </span>
                                    <span class="font-medium text-slate-900 dark:text-slate-100">{{ $user->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-slate-500 dark:text-slate-400">{{ $user->email }}</td>
                            <td class="px-5 py-3">
                                @if ($user->roles->isEmpty())
                                    <span class="text-xs text-slate-400">—</span>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($user->roles as $role)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">
                                                {{ $role->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if ($user->permissions->isEmpty())
                                    <span class="text-xs text-slate-400">No direct grants</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                        <i data-lucide="key-round" class="h-3 w-3"></i>
                                        {{ $user->permissions->count() }} extra
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a
                                    href="{{ route('admin.assign-user-permissions.edit', $user->id) }}"
                                    wire:navigate
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                    <i data-lucide="pencil" class="h-3 w-3"></i>
                                    Manage
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($users->hasPages())
            <x-slot:footer>
                {{ $users->links() }}
            </x-slot:footer>
        @endif
    </x-admin.table-shell>
</div>
