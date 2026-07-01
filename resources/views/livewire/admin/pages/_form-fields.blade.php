{{--
    Shared Pages form fields — used by Create.blade.php + Edit.blade.php.
    Both components expose:
      title, slug, content, metaTitle, metaDescription, isPublished,
      template, status, showInMenu, sortOrder
--}}

<div class="space-y-6">
    {{-- Title --}}
    <div>
        <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
            <i data-lucide="type" class="h-4 w-4 text-indigo-500"></i>
            Title <span class="text-rose-500">*</span>
        </label>
        <input type="text" wire:model.live.debounce.500ms="title"
               placeholder="Page title…"
               class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-lg font-medium outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950">
        @error('title') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    {{-- Slug --}}
    <div>
        <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
            <i data-lucide="link" class="h-3.5 w-3.5 text-slate-400"></i>
            URL Slug
        </label>
        <input type="text" wire:model.live.debounce.500ms="slug"
               placeholder="auto-generated-from-title"
               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-mono dark:border-slate-700 dark:bg-slate-950">
        @error('slug') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    {{-- Content — TipTap rich text editor (same wrapper as posts). --}}
    <div>
        <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
            <i data-lucide="file-text" class="h-3.5 w-3.5 text-indigo-500"></i>
            Content
        </label>

        <x-admin.rich-editor model="content" placeholder="Write the page content…" />
    </div>

    {{-- Per-locale publish toggle --}}
    <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-3 dark:border-emerald-500/30 dark:bg-emerald-500/10">
        <label class="flex items-center gap-2 text-sm font-semibold text-emerald-800 dark:text-emerald-300">
            <input type="checkbox" wire:model="isPublished"
                   class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
            Publish this language version
        </label>
        <p class="mt-1 text-xs text-emerald-700/80 dark:text-emerald-300/70 pl-6">
            The page must also have overall status = Published for the public to see this translation.
        </p>
    </div>

    {{-- SEO fields --}}
    <div class="space-y-3 rounded-xl border border-slate-200 p-4 dark:border-slate-700">
        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">SEO</h4>
        <div>
            <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Meta title</label>
            <input type="text" wire:model="metaTitle"
                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Meta description</label>
            <textarea wire:model="metaDescription" rows="2"
                      class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
        </div>
    </div>
</div>
