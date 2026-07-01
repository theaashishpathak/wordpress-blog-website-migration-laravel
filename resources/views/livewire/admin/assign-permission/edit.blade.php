@php
    $title = 'Edit User Permissions';
@endphp

<div>
    <form wire:submit="save" class="space-y-6">
        <x-admin.page-header
            eyebrow="Access Control"
            icon="user-check"
            :title="'Direct permissions: '.$userName"
            :description="$userEmail.' — grants applied on top of any role permissions.'">
            <x-slot:actions>
                <a
                    href="{{ route('admin.assign-user-permissions.index') }}"
                    wire:navigate
                    class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 px-3.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Back
                </a>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="inline-flex h-10 items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:from-indigo-700 hover:to-violet-700 disabled:opacity-60">
                    <i data-lucide="save" class="h-4 w-4" wire:loading.remove wire:target="save"></i>
                    <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="save"></i>
                    Save grants
                </button>
            </x-slot:actions>
        </x-admin.page-header>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Left: user identity & summary --}}
            <div class="lg:col-span-1">
                <x-admin.section title="User" description="Direct grants stack with role permissions — both are honoured.">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-500 text-base font-bold text-white shadow-sm">
                                {{ strtoupper(mb_substr($userName, 0, 1)) }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">{{ $userName }}</p>
                                <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $userEmail }}</p>
                            </div>
                        </div>

                        <div>
                            <p class="mb-1.5 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Roles</p>
                            @if (empty($userRoles))
                                <p class="text-xs text-slate-400">No roles assigned</p>
                            @else
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($userRoles as $roleName)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">
                                            <i data-lucide="shield" class="h-3 w-3"></i>
                                            {{ $roleName }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 px-3.5 py-3 dark:border-indigo-500/20 dark:bg-indigo-500/5">
                            <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-indigo-700 dark:text-indigo-300">
                                Direct grants
                            </p>
                            <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-slate-100">
                                {{ count($selectedPermissions) }}<span class="text-base font-medium text-slate-500 dark:text-slate-400"> / {{ $totalPermissions }}</span>
                            </p>
                        </div>

                        <button
                            wire:click="clearAll"
                            type="button"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:border-rose-500/30 dark:hover:bg-rose-500/10">
                            <i data-lucide="x" class="h-3.5 w-3.5"></i>
                            Clear all grants
                        </button>
                    </div>
                </x-admin.section>
            </div>

            {{-- Right: permission picker --}}
            <div class="lg:col-span-2">
                <x-admin.section
                    title="Permissions"
                    description="Click a group header to toggle every permission inside it.">
                    @if ($permissionGroups->isEmpty())
                        <x-admin.empty-state
                            icon="key-round"
                            title="No permissions defined yet"
                            description="Set up permission groups and permissions before granting direct rights." />
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
    </form>
</div>
