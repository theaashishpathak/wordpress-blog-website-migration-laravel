@php($title = 'Permissions')

<div class="space-y-6">
    <x-admin.page-header
        eyebrow="Access Control"
        icon="key-round"
        title="Permissions"
        description="Individual permissions; each is bound to a group and a role can attach any subset.">
        <x-slot:actions>
            <a
                href="{{ route('admin.permission-groups.index') }}"
                wire:navigate
                class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 px-3.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                <i data-lucide="folder-tree" class="h-4 w-4"></i>
                Manage groups
            </a>
            <button
                x-on:click="$wire.showModal = true; $wire.create()"
                type="button"
                class="inline-flex h-10 items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:from-indigo-700 hover:to-violet-700">
                <i data-lucide="plus" class="h-4 w-4"></i>
                New permission
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <x-admin.table-shell search-model="search" search-placeholder="Search permissions by name…">
        @if ($permissions->isEmpty())
            <div class="px-5 py-10">
                <x-admin.empty-state
                    icon="key-round"
                    title="No permissions yet"
                    description="Create a permission group first, then add permissions inside it.">
                    <x-slot:action>
                        <button
                            wire:click="create"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                            <i data-lucide="plus" class="h-4 w-4"></i>
                            New permission
                        </button>
                    </x-slot:action>
                </x-admin.empty-state>
            </div>
        @else
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50/60 dark:bg-slate-950/30">
                    <tr class="text-left text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
                        <th class="px-5 py-3">Name</th>
                        <th class="px-5 py-3">Group</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($permissions as $permission)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                            <td class="px-5 py-3">
                                <code class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-mono text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ $permission->name }}</code>
                            </td>
                            <td class="px-5 py-3">
                                @if ($permission->permissionGroup)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">
                                        <i data-lucide="folder" class="h-3 w-3"></i>
                                        {{ $permission->permissionGroup->name }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <button
                                        x-on:click="$wire.showModal = true; $wire.edit({{ $permission->id }})"
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                        <i data-lucide="pencil" class="h-3 w-3"></i> Edit
                                    </button>
                                    <button
                                        wire:click="delete({{ $permission->id }})"
                                        wire:confirm="Delete this permission? Any role using it will lose this grant."
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

        @if ($permissions->hasPages())
            <x-slot:footer>
                {{ $permissions->links() }}
            </x-slot:footer>
        @endif
    </x-admin.table-shell>

    <x-admin.dialog
        wire-show="showModal"
        icon="key-round"
        :title="$editingPermissionId ? 'Edit permission' : 'New permission'"
        :description="$editingPermissionId ? 'Rename or move this permission to a different group.' : 'Use dotted names like posts.create or settings.update.'"
        max-width="max-w-md">
        @if ($showModal)
            <livewire:admin.permission.form :permission-id="$editingPermissionId" :key="'perm-form-'.$formKey" />
        @endif
    </x-admin.dialog>
</div>
