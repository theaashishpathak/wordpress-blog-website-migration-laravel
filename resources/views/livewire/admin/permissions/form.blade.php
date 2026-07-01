{{--
    Permission form lives inside <x-admin.dialog>. Sticky footer is part
    of the form body (negative margin trick to bleed into the dialog
    border) so the form's wire:submit fires when the user clicks Save.
--}}
<form wire:submit="save" class="space-y-4">
    <div>
        <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
            Permission name
        </label>
        <input
            wire:model="name"
            type="text"
            placeholder="e.g. posts.create"
            autofocus
            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-mono text-slate-900 placeholder:text-slate-400 transition focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
        <p class="mt-1.5 text-[11px] text-slate-500 dark:text-slate-400">
            Convention: <code class="rounded bg-slate-100 px-1 dark:bg-slate-800">module.action</code> — e.g. <code class="rounded bg-slate-100 px-1 dark:bg-slate-800">posts.publish</code>.
        </p>
        @error('name')
            <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
            Permission group
        </label>
        <select
            wire:model="permission_group_id"
            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 transition focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
            <option value="">Select a group…</option>
            @foreach ($permissionGroups as $permissionGroup)
                <option value="{{ $permissionGroup->id }}">{{ $permissionGroup->name }}</option>
            @endforeach
        </select>
        @error('permission_group_id')
            <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="-mx-6 -mb-5 flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-6 py-3 dark:border-slate-800 dark:bg-slate-950/40">
        <button
            wire:click="cancel"
            type="button"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
            Cancel
        </button>
        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="save"
            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-60">
            <i data-lucide="check" class="h-4 w-4" wire:loading.remove wire:target="save"></i>
            <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="save"></i>
            Save permission
        </button>
    </div>
</form>
