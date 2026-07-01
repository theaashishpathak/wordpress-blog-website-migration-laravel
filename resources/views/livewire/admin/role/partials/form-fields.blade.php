{{--
    Shared role form body — used by both Create and Edit pages.
    Caller provides the wire:submit method via the surrounding <form>.

    Available variables:
      $permissionGroups   Collection of PermissionGroup eager-loading
                          ->permissions (ordered by name)
      $totalPermissions   total count across all groups (header counter)
--}}
<div class="grid gap-6 lg:grid-cols-3">
    {{-- Left column: role identity --}}
    <div class="lg:col-span-1">
        <x-admin.section title="Role details" description="Pick a short, capitalised name. Examples: Editor, Author, Reviewer.">
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
                        Role name
                    </label>
                    <input
                        wire:model="name"
                        type="text"
                        placeholder="e.g. Editor"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 transition focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    @error('name')
                        <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 px-3.5 py-3 dark:border-indigo-500/20 dark:bg-indigo-500/5">
                    <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-indigo-700 dark:text-indigo-300">
                        Selected
                    </p>
                    <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-slate-100">
                        {{ count($selectedPermissions) }}<span class="text-base font-medium text-slate-500 dark:text-slate-400"> / {{ $totalPermissions }}</span>
                    </p>
                    <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                        permissions attached
                    </p>
                </div>

                <div class="flex flex-col gap-2">
                    <button
                        wire:click="selectAll"
                        type="button"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                        <i data-lucide="check-check" class="h-3.5 w-3.5"></i>
                        Select all
                    </button>
                    <button
                        wire:click="clearAll"
                        type="button"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                        <i data-lucide="x" class="h-3.5 w-3.5"></i>
                        Clear all
                    </button>
                </div>
            </div>
        </x-admin.section>
    </div>

    {{-- Right column: permission picker --}}
    <div class="lg:col-span-2">
        <x-admin.section
            title="Permissions"
            description="Group-by-group checklist. Click a group header to toggle all of its permissions at once.">
            @if ($permissionGroups->isEmpty())
                <x-admin.empty-state
                    icon="key-round"
                    title="No permissions defined yet"
                    description="Create permission groups and permissions first.">
                    <x-slot:action>
                        <a
                            href="{{ route('admin.permission-groups.index') }}"
                            wire:navigate
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                            <i data-lucide="folder-tree" class="h-4 w-4"></i>
                            Manage groups
                        </a>
                    </x-slot:action>
                </x-admin.empty-state>
            @else
                <div class="space-y-3">
                    @foreach ($permissionGroups as $permissionGroup)
                        @php
                            $groupPermissionIds = $permissionGroup->permissions->pluck('id')->map(fn ($i) => (int) $i)->all();
                            $selectedInGroup = count(array_intersect($groupPermissionIds, $selectedPermissions));
                            $totalInGroup = count($groupPermissionIds);
                            $allSelected = $totalInGroup > 0 && $selectedInGroup === $totalInGroup;
                        @endphp
                        <div class="overflow-hidden rounded-xl border border-slate-200 transition hover:border-slate-300 dark:border-slate-700 dark:hover:border-slate-600">
                            <button
                                wire:click="toggleGroup({{ $permissionGroup->id }})"
                                type="button"
                                class="flex w-full items-center justify-between gap-3 bg-slate-50/60 px-4 py-2.5 text-left transition hover:bg-slate-100 dark:bg-slate-950/30 dark:hover:bg-slate-800/50">
                                <div class="flex items-center gap-2.5">
                                    <span @class([
                                        'grid h-7 w-7 place-items-center rounded-lg text-xs',
                                        'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300' => $selectedInGroup > 0,
                                        'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500' => $selectedInGroup === 0,
                                    ])>
                                        <i data-lucide="{{ $allSelected ? 'check-check' : ($selectedInGroup > 0 ? 'minus' : 'folder') }}" class="h-3.5 w-3.5"></i>
                                    </span>
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ $permissionGroup->name }}</h3>
                                </div>
                                <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
                                    {{ $selectedInGroup }} / {{ $totalInGroup }}
                                </span>
                            </button>

                            <div class="grid gap-1.5 bg-white p-3 sm:grid-cols-2 lg:grid-cols-3 dark:bg-slate-900">
                                @foreach ($permissionGroup->permissions as $permission)
                                    <label class="flex items-center gap-2 rounded-lg px-2 py-1.5 transition hover:bg-slate-50 has-[:checked]:bg-indigo-50/60 dark:hover:bg-slate-800 dark:has-[:checked]:bg-indigo-500/10">
                                        <input
                                            wire:model.live="selectedPermissions"
                                            type="checkbox"
                                            value="{{ $permission->id }}"
                                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-2 focus:ring-indigo-200">
                                        <span class="font-mono text-[12px] text-slate-700 dark:text-slate-300">{{ $permission->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @error('selectedPermissions.*')
                <p class="mt-3 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </x-admin.section>
    </div>
</div>
