@php($title = 'Roles')

<div class="space-y-6">
    <x-admin.page-header
        eyebrow="Access Control"
        icon="shield"
        title="Roles"
        description="Bundle permissions into named roles, then assign roles to users on the Assign Roles page.">
        <x-slot:actions>
            <a
                href="{{ route('admin.assign-role.index') }}"
                wire:navigate
                class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 px-3.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                <i data-lucide="user-cog" class="h-4 w-4"></i>
                Assign to users
            </a>
            <a
                href="{{ route('admin.roles.create') }}"
                wire:navigate
                class="inline-flex h-10 items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:from-indigo-700 hover:to-violet-700">
                <i data-lucide="plus" class="h-4 w-4"></i>
                New role
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <x-admin.table-shell search-model="search" search-placeholder="Search roles by name…">
        @if ($roles->isEmpty())
            <div class="px-5 py-10">
                <x-admin.empty-state
                    icon="shield"
                    title="No roles yet"
                    description="Create your first role and attach the permissions it should grant.">
                    <x-slot:action>
                        <a
                            href="{{ route('admin.roles.create') }}"
                            wire:navigate
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                            <i data-lucide="plus" class="h-4 w-4"></i>
                            New role
                        </a>
                    </x-slot:action>
                </x-admin.empty-state>
            </div>
        @else
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50/60 dark:bg-slate-950/30">
                    <tr class="text-left text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
                        <th class="px-5 py-3">Role</th>
                        <th class="px-5 py-3">Permissions</th>
                        <th class="px-5 py-3">Users</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($roles as $role)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2.5">
                                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-gradient-to-br from-indigo-500 to-violet-500 text-white shadow-sm">
                                        <i data-lucide="shield" class="h-4 w-4"></i>
                                    </span>
                                    <span class="font-semibold text-slate-900 dark:text-slate-100">{{ $role->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">
                                    <i data-lucide="key-round" class="h-3 w-3"></i>
                                    {{ $role->permissions_count }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    <i data-lucide="users" class="h-3 w-3"></i>
                                    {{ $role->users_count }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a
                                        href="{{ route('admin.roles.edit', $role->id) }}"
                                        wire:navigate
                                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                        <i data-lucide="pencil" class="h-3 w-3"></i> Edit
                                    </a>
                                    <button
                                        wire:click="delete({{ $role->id }})"
                                        wire:confirm="Delete this role? All users with this role will lose its permissions."
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-lg border border-rose-200 px-2.5 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:border-rose-500/30 dark:hover:bg-rose-500/10">
                                        <i data-lucide="trash-2" class="h-3 w-3"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if ($roles->hasPages())
            <x-slot:footer>
                {{ $roles->links() }}
            </x-slot:footer>
        @endif
    </x-admin.table-shell>
</div>
