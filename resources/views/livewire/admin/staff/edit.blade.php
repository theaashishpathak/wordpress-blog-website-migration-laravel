<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Edit Staff: {{ $user->name }}</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Update employment details, roles, or status.</p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('admin.staff.show', $user) }}" wire:navigate class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">View Profile</a>
            <a href="{{ route('admin.staff.index') }}" wire:navigate class="inline-flex items-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Back to Staff</a>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        @include('livewire.admin.staff._form-fields', [
            'departments' => $departments,
            'managers' => $managers,
            'roles' => $roles,
            'statuses' => $statuses,
            'employmentTypes' => $employmentTypes,
            'isCreate' => false,
        ])

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.staff.index') }}" wire:navigate class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Cancel</a>
            <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Update Staff</button>
        </div>
    </form>
</div>
