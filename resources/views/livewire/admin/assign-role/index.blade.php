@php($title = 'Assign Roles')

<div class="space-y-6">
    <x-admin.page-header
        eyebrow="Access Control"
        icon="user-cog"
        title="Assign Roles"
        description="Attach one or more roles to each user. Roles bundle permissions for quick rights management.">
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

    <x-admin.table-shell search-model="search" search-placeholder="Search users by name or email…">
        @if ($users->isEmpty())
            <div class="px-5 py-10">
                <x-admin.empty-state
                    icon="users"
                    title="No users match"
                    description="Try a different name or email." />
            </div>
        @else
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50/60 dark:bg-slate-950/30">
                    <tr class="text-left text-[11px] font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
                        <th class="px-5 py-3">User</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Current roles</th>
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
                                    <span class="text-xs text-slate-400">No roles</span>
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
                            <td class="px-5 py-3 text-right">
                                <button
                                    x-on:click="$wire.showModal = true; $wire.editUser({{ $user->id }})"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                                    <i data-lucide="pencil" class="h-3 w-3"></i>
                                    Edit roles
                                </button>
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

    <x-admin.dialog
        wire-show="showModal"
        icon="user-cog"
        title="Update user roles"
        :description="$editingUserName.' ('.$editingUserEmail.')'"
        max-width="max-w-lg">
        @if ($showModal)
            <form wire:submit="saveRoles" class="space-y-4">
                @if ($roles->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400">
                        No roles available. <a href="{{ route('admin.roles.index') }}" wire:navigate class="font-semibold text-indigo-600 hover:underline">Create a role first.</a>
                    </div>
                @else
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Select all roles that apply. Multiple roles stack — permissions are the union of all selected roles.
                    </p>

                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($roles as $role)
                            <label class="flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-3 py-2.5 transition hover:border-indigo-300 hover:bg-indigo-50/40 has-[:checked]:border-indigo-400 has-[:checked]:bg-indigo-50 dark:border-slate-700 dark:bg-slate-950 dark:hover:bg-indigo-500/5 dark:has-[:checked]:border-indigo-500 dark:has-[:checked]:bg-indigo-500/10">
                                <input
                                    wire:model="selectedRoles"
                                    type="checkbox"
                                    value="{{ $role->id }}"
                                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-2 focus:ring-indigo-200">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ $role->name }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif

                @error('selectedRoles.*')
                    <p class="text-xs text-rose-600">{{ $message }}</p>
                @enderror

                <div class="-mx-6 -mb-5 flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-6 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                    <button
                        wire:click="cancelEdit"
                        type="button"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="saveRoles"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-60">
                        <i data-lucide="check" class="h-4 w-4" wire:loading.remove wire:target="saveRoles"></i>
                        <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="saveRoles"></i>
                        Save roles
                    </button>
                </div>
            </form>
        @endif
    </x-admin.dialog>
</div>

    </x-admin.dialog>
</div>
