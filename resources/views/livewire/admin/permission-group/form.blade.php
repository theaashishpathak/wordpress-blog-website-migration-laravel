{{--
    Form lives inside the parent's <x-admin.dialog>. We don't render the
    dialog footer here (the parent owns the chrome); save / cancel are
    inline at the bottom of the form so the entire panel scrolls together.
--}}
<form wire:submit="save" class="space-y-4">
    <div>
        <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
            Display name
        </label>
        <input
            wire:model.live.debounce.300ms="name"
            type="text"
            placeholder="e.g. Posts, Settings, Media…"
            autofocus
            class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
        @error('name')
            <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-1.5 block text-xs font-bold uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
            Slug
        </label>
        <div class="flex items-stretch overflow-hidden rounded-xl border border-slate-200 bg-slate-50 focus-within:border-indigo-400 focus-within:ring-2 focus-within:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950">
            <span class="inline-flex items-center bg-slate-100 px-3 text-xs font-mono text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                /
            </span>
            <input
                wire:model="slug"
                type="text"
                class="flex-1 border-0 bg-transparent px-3 py-2.5 text-sm font-mono text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-0 dark:text-slate-200">
        </div>
        <p class="mt-1.5 text-[11px] text-slate-500 dark:text-slate-400">
            Auto-generated from name. Used internally — keep it stable to avoid breaking references.
        </p>
        @error('slug')
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
            Save group
        </button>
    </div>
</form>
