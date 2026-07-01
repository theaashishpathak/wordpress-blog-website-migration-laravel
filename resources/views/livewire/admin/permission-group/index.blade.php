@php($title = 'Permission Groups')

<div class="space-y-6">
    <x-admin.page-header
        eyebrow="Access Control"
        icon="folder-tree"
        title="Permission Groups"
        description="Logical buckets used to organize related permissions (e.g. Posts, Settings, AI).">
        <x-slot:actions>
            <button
                x-on:click="$wire.showModal = true; $wire.create()"
                type="button"
                class="inline-flex h-10 items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:from-indigo-700 hover:to-violet-700">
                <i data-lucide="plus" class="h-4 w-4"></i>
                New group
            </button>
        </x-slot:actions>
    </x-admin.page-header>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <x-admin.table-shell search-model="search" search-placeholder="Search by name or slug…">
        @if ($permissionGroups->isEmpty())
            <div class="px-5 py-10">
                <x-admin.empty-state
                    icon="folder-tree"
                    title="No permission groups yet"
                    description="Create your first group to start organising the permissions catalogue.">
                    <x-slot:action>
                        <button
                            wire:click="create"
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                            <i data-lucide="plus" class="h-4 w-4"></i>
                            New group
                        </button>
                    </x-slot:action>
                </x-admin.empty-state>
            </div>
        @else
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50/60 dark:bg-slate-950/30">
                    <tr class="text-left text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
                        <th class="px-5 py-3">Name</th>
                        <th class="px-5 py-3">Slug</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($permissionGroups as $permissionGroup)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30">
                            <td class="px-5 py-3 font-medium text-slate-900 dark:text-slate-100">{{ $permissionGroup->name }}</td>
                            <td class="px-5 py-3">
                                <code class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-mono text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $permissionGroup->slug }}</code>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <button
                                        x-on:click="$wire.showModal = true; $wire.edit({{ $permissionGroup->id }})"
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                        <i data-lucide="pencil" class="h-3 w-3"></i> Edit
                                    </button>
                                    <button
                                        wire:click="delete({{ $permissionGroup->id }})"
                                        wire:confirm="Delete this permission group? Permissions inside will become ungrouped."
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

        @if ($permissionGroups->hasPages())
            <x-slot:footer>
                {{ $permissionGroups->links() }}
            </x-slot:footer>
        @endif
    </x-admin.table-shell>

    {{-- Dialog ALWAYS in DOM, controlled by Alpine via @entangle on $showModal.
         Open/close is instant client-side; the form Livewire component
         only mounts while the modal is open (saves resources). --}}
    <x-admin.dialog
        wire-show="showModal"
        icon="folder-tree"
        :title="$editingPermissionGroupId ? 'Edit permission group' : 'New permission group'"
        :description="$editingPermissionGroupId ? 'Update the display name; slug will stay locked to avoid breaking references.' : 'Pick a clear, plural name. The slug is auto-generated and used internally.'"
        max-width="max-w-md">
        @if ($showModal)
            <livewire:admin.permission-group.form :permission-group-id="$editingPermissionGroupId" :key="'pg-form-'.$formKey" />
        @endif
    </x-admin.dialog>
</div>
