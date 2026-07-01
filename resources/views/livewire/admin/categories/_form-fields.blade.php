{{--
    Shared category form fields — used by Create.blade and Edit.blade.
    Both components expose:
      parentId, imageId, icon, color, showInMenu, showOnHomepage,
      isFeatured, sortOrder, layout, activeLanguageId, translations[]
--}}

<div class="space-y-6">
    {{-- Active language indicator + translation fields --}}
    @if ($activeLanguageId !== null && isset($translations[$activeLanguageId]))
        @php($activeLanguage = $this->languages->firstWhere('id', $activeLanguageId))
        @php($activeRow = $translations[$activeLanguageId])
        @php($isDeleted = ! empty($activeRow['delete']))

        <div class="space-y-5">
            <div class="flex items-center gap-2 rounded-xl bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-200">
                <i data-lucide="languages" class="h-3.5 w-3.5"></i>
                <span>Editing translation for:</span>
                <span class="font-bold">{{ $activeLanguage?->flag_emoji ?? '🌐' }} {{ $activeLanguage?->name ?? '#'.$activeLanguageId }}</span>
                @if ($isDeleted)
                    <span class="ml-auto rounded-md bg-rose-100 px-2 py-0.5 text-[10px] font-bold uppercase text-rose-700 dark:bg-rose-500/20 dark:text-rose-300">
                        Marked for removal
                    </span>
                @endif
            </div>

            {{-- Name --}}
            <div>
                <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                    <i data-lucide="type" class="h-4 w-4 text-emerald-500"></i>
                    Name <span class="text-rose-500">*</span>
                </label>
                <input type="text"
                       wire:model.live.debounce.400ms="translations.{{ $activeLanguageId }}.name"
                       placeholder="Category name…"
                       class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-base font-medium outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-950">
                @error('translations.'.$activeLanguageId.'.name')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        <i data-lucide="link" class="h-3.5 w-3.5 text-slate-400"></i>
                        URL Slug
                    </label>
                    <input type="text"
                           wire:model="translations.{{ $activeLanguageId }}.slug"
                           placeholder="auto-generated-from-name"
                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 font-mono text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-950">
                </div>
                <div>
                    <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        <i data-lucide="search" class="h-3.5 w-3.5 text-slate-400"></i>
                        SEO Meta Title
                    </label>
                    <input type="text"
                           wire:model="translations.{{ $activeLanguageId }}.meta_title"
                           maxlength="200"
                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-950">
                </div>
            </div>

            <div>
                <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                    <i data-lucide="align-left" class="h-3.5 w-3.5 text-slate-400"></i>
                    Description
                </label>
                <textarea wire:model="translations.{{ $activeLanguageId }}.description"
                          rows="3"
                          placeholder="Short description shown on category pages…"
                          class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-950"></textarea>
            </div>

            <div>
                <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                    <i data-lucide="text" class="h-3.5 w-3.5 text-slate-400"></i>
                    SEO Meta Description
                </label>
                <textarea wire:model="translations.{{ $activeLanguageId }}.meta_description"
                          rows="2"
                          maxlength="300"
                          placeholder="Search-engine description (max ~160 chars recommended)…"
                          class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-950"></textarea>
            </div>
        </div>
    @else
        <div class="rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700">
            No active translation. Add a language tab to start editing.
        </div>
    @endif

    <hr class="border-slate-200 dark:border-slate-700">

    {{-- Structural fields --}}
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                <i data-lucide="folder" class="h-3.5 w-3.5 text-slate-400"></i>
                Parent Category
            </label>
            <select wire:model="parentId"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-950">
                <option value="">— top-level —</option>
                @foreach ($this->parentOptions as $option)
                    <option value="{{ $option->id }}">{{ $option->translate('name') ?? '#'.$option->id }}</option>
                @endforeach
            </select>
            @error('parentId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                <i data-lucide="layout" class="h-3.5 w-3.5 text-slate-400"></i>
                Layout
            </label>
            <select wire:model="layout"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-950">
                @foreach (\App\Models\Category::LAYOUTS as $layoutOption)
                    <option value="{{ $layoutOption }}">{{ \Illuminate\Support\Str::headline($layoutOption) }}</option>
                @endforeach
            </select>
            @error('layout') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                <i data-lucide="image" class="h-3.5 w-3.5 text-slate-400"></i>
                Icon Name
            </label>
            <input type="text"
                   wire:model="icon"
                   placeholder="e.g. cpu, flame, briefcase"
                   class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 font-mono text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-950">
            <p class="mt-1 text-[10px] text-slate-500">Lucide icon name.</p>
        </div>
        <div>
            <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                <i data-lucide="palette" class="h-3.5 w-3.5 text-slate-400"></i>
                Color
            </label>
            <div class="flex items-center gap-2">
                <input type="color"
                       wire:model.live.debounce.300ms="color"
                       class="h-10 w-12 cursor-pointer rounded-lg border border-slate-200 bg-white dark:border-slate-700">
                <input type="text"
                       wire:model="color"
                       placeholder="#4f46e5"
                       class="flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2.5 font-mono text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-950">
            </div>
        </div>
    </div>

    {{-- Display flag toggles --}}
    <div>
        <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
            <i data-lucide="sparkle" class="h-3.5 w-3.5 text-slate-400"></i>
            Display Flags
        </h3>
        <div class="grid gap-2 sm:grid-cols-3">
            <label @class([
                'flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2.5 text-xs font-semibold transition',
                'border-amber-500 bg-amber-100 text-amber-800 dark:bg-amber-500/20' => $isFeatured,
                'border-slate-200 bg-white text-slate-600 hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' => ! $isFeatured,
            ])>
                <input type="checkbox" wire:model.live="isFeatured" class="h-3.5 w-3.5 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                <i data-lucide="star" class="h-3.5 w-3.5"></i>
                Featured
            </label>

            <label @class([
                'flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2.5 text-xs font-semibold transition',
                'border-emerald-500 bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20' => $showInMenu,
                'border-slate-200 bg-white text-slate-600 hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' => ! $showInMenu,
            ])>
                <input type="checkbox" wire:model.live="showInMenu" class="h-3.5 w-3.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                <i data-lucide="menu" class="h-3.5 w-3.5"></i>
                Show in Menu
            </label>

            <label @class([
                'flex cursor-pointer items-center gap-2 rounded-xl border px-3 py-2.5 text-xs font-semibold transition',
                'border-indigo-500 bg-indigo-100 text-indigo-800 dark:bg-indigo-500/20' => $showOnHomepage,
                'border-slate-200 bg-white text-slate-600 hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' => ! $showOnHomepage,
            ])>
                <input type="checkbox" wire:model.live="showOnHomepage" class="h-3.5 w-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <i data-lucide="home" class="h-3.5 w-3.5"></i>
                On Homepage
            </label>
        </div>
    </div>

    <div>
        <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
            <i data-lucide="arrow-up-down" class="h-3.5 w-3.5 text-slate-400"></i>
            Sort Order
        </label>
        <input type="number"
               wire:model="sortOrder"
               min="0"
               class="w-32 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-950">
        <p class="mt-1 text-[10px] text-slate-500">
            Lower values appear first. Drag-and-drop on the index page updates this automatically.
        </p>
    </div>
</div>
