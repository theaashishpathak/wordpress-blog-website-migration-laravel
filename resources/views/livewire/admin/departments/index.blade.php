<div class="space-y-6" x-data="{ formOpen: @entangle('showFormModal') }">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Departments</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Organise your staff into departments and assign department heads.</p>
        </div>

        @can('departments.create')
            <button x-on:click="formOpen = true; $wire.create()" type="button" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
                Add Department
            </button>
        @endcan
    </div>

    {{-- Filters --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="grid gap-3 md:grid-cols-3">
            <div class="md:col-span-2">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search by name, code or slug..." class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
            </div>
            <div>
                <select wire:model.live="statusFilter" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- List --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-950 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Head</th>
                        <th class="px-4 py-3 text-center">Members</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($departments as $department)
                        <tr wire:key="department-{{ $department->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                            <td class="px-4 py-3 font-mono text-xs text-slate-500 dark:text-slate-400">{{ $department->code }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $department->name }}</div>
                                @if ($department->description)
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ Str::limit($department->description, 80) }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ $department->head?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">{{ $department->members_count }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $department->status === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300' : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200' }}">{{ ucfirst($department->status) }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex gap-1">
                                    @can('departments.edit')
                                        <button x-on:click="formOpen = true; $wire.edit({{ $department->id }})" type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Edit</button>
                                    @endcan
                                    @can('departments.delete')
                                        <button wire:click="requestDelete({{ $department->id }})" type="button" class="rounded-lg border border-rose-200 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:border-rose-500/20 dark:hover:bg-rose-500/10">Delete</button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">No departments found. Create one to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($departments->hasPages())
            <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">
                {{ $departments->links() }}
            </div>
        @endif
    </div>

    {{-- Form Modal — Alpine entangled for instant open/close. --}}
    <div x-show="formOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
         x-on:keydown.escape.window="formOpen = false; $wire.closeFormModal()"
         x-on:click="formOpen = false; $wire.closeFormModal()">
            <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-slate-900" x-on:click.stop>
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
                        {{ $editingId ? 'Edit Department' : 'Create Department' }}
                    </h2>
                    <button x-on:click="formOpen = false; $wire.closeFormModal()" type="button" class="rounded-xl p-2 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5"><path d="M6 18L18 6M6 6l12 12" stroke-linecap="round"/></svg>
                    </button>
                </div>

                <form wire:submit="save" class="space-y-4 px-6 py-5">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Name <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="name" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Description</label>
                        <textarea wire:model="description" rows="3" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"></textarea>
                        @error('description')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Department Head</label>
                            <select wire:model="headUserId" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                <option value="">— Select head —</option>
                                @foreach ($staffOptions as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->name }} ({{ $staff->email }})</option>
                                @endforeach
                            </select>
                            @error('headUserId')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Status</label>
                            <select wire:model="status" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                                @foreach ($statuses as $statusOption)
                                    <option value="{{ $statusOption }}">{{ ucfirst($statusOption) }}</option>
                                @endforeach
                            </select>
                            @error('status')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Sort Order</label>
                        <input type="number" min="0" wire:model="sortOrder" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        @error('sortOrder')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-800">
                        <button x-on:click="formOpen = false; $wire.closeFormModal()" type="button" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Cancel</button>
                        <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ $editingId ? 'Update' : 'Create' }}</button>
                    </div>
                </form>
            </div>
    </div>
</div>
