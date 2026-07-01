<div class="space-y-5"
     x-data="{
         draggedId: null,
         draggedDepth: 0,
         pickUp(id, depth) {
             this.draggedId = parseInt(id, 10);
             this.draggedDepth = parseInt(depth, 10) || 0;
         },
         release() {
             this.draggedId = null;
             this.draggedDepth = 0;
         },
         dropOnRow(targetId, targetParentId, position) {
             if (this.draggedId === null) return;
             // Defensive: can't drop a node onto itself.
             if (parseInt(targetId, 10) === this.draggedId) {
                 this.release();
                 return;
             }
             const payload = this.computeNewOrder(
                 this.draggedId,
                 parseInt(targetId, 10),
                 targetParentId === null || targetParentId === '' ? null : parseInt(targetParentId, 10),
                 position,
             );
             if (payload !== null) {
                 $wire.reorder(payload);
             }
             this.release();
         },
         dropOnParent(parentId) {
             if (this.draggedId === null) return;
             const newParent = parentId === null || parentId === '' ? null : parseInt(parentId, 10);
             // Defensive: cannot become a descendant of itself.
             if (newParent !== null && this.isDescendant(this.draggedId, newParent)) {
                 this.release();
                 return;
             }
             const payload = this.computeReparentOrder(this.draggedId, newParent);
             if (payload !== null) {
                 $wire.reorder(payload);
             }
             this.release();
         },
         /* Walk DOM rows to build the flat reorder payload after the user
            drops $draggedId before/after $targetId. The new parent is
            inherited from the target row so the gesture combines reorder
            and re-parent in a single drop. */
         computeNewOrder(draggedId, targetId, targetParentId, position) {
             const rows = Array.from(this.$root.querySelectorAll('[data-cat-row]'));
             const dragged = rows.find(r => parseInt(r.dataset.catId, 10) === draggedId);
             const target  = rows.find(r => parseInt(r.dataset.catId, 10) === targetId);
             if (! dragged || ! target) return null;

             // Cannot drop onto a descendant — would orphan the subtree.
             if (this.isDescendant(draggedId, targetId)) return null;

             // Build the new sibling list under targetParentId.
             const siblings = rows
                 .filter(r => {
                     const pid = r.dataset.parentId === '' ? null : parseInt(r.dataset.parentId, 10);
                     return pid === targetParentId && parseInt(r.dataset.catId, 10) !== draggedId;
                 })
                 .map(r => parseInt(r.dataset.catId, 10));

             const targetIndex = siblings.indexOf(targetId);
             const insertIndex = position === 'before' ? targetIndex : targetIndex + 1;
             siblings.splice(insertIndex, 0, draggedId);

             const payload = siblings.map((id, idx) => ({
                 id,
                 sort_order: idx,
                 parent_id: targetParentId,
             }));

             return payload;
         },
         computeReparentOrder(draggedId, newParentId) {
             const rows = Array.from(this.$root.querySelectorAll('[data-cat-row]'));
             const siblings = rows
                 .filter(r => {
                     const pid = r.dataset.parentId === '' ? null : parseInt(r.dataset.parentId, 10);
                     return pid === newParentId && parseInt(r.dataset.catId, 10) !== draggedId;
                 })
                 .map(r => parseInt(r.dataset.catId, 10));

             siblings.push(draggedId);
             return siblings.map((id, idx) => ({
                 id,
                 sort_order: idx,
                 parent_id: newParentId,
             }));
         },
         isDescendant(ancestorId, candidateId) {
             const rows = Array.from(this.$root.querySelectorAll('[data-cat-row]'));
             let cursor = candidateId;
             const guard = 50;
             for (let i = 0; i < guard; i++) {
                 const row = rows.find(r => parseInt(r.dataset.catId, 10) === cursor);
                 if (! row) return false;
                 const parentId = row.dataset.parentId === '' ? null : parseInt(row.dataset.parentId, 10);
                 if (parentId === null) return false;
                 if (parentId === ancestorId) return true;
                 cursor = parentId;
             }
             return false;
         }
     }">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">Categories</span>
    </nav>

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white shadow-sm">
                <i data-lucide="folder-tree" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">Categories</h1>
                <p class="text-xs text-slate-500">
                    {{ $this->totalCount }} {{ \Illuminate\Support\Str::plural('category', $this->totalCount) }} •
                    Drag rows to reorder or re-parent.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @can('categories.create')
                <a href="{{ route('admin.categories.create') }}"
                   wire:navigate
                   class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 px-4 py-2 text-xs font-bold text-white shadow-sm transition hover:from-emerald-700 hover:to-emerald-600">
                    <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                    New Category
                </a>
            @endcan
        </div>
    </div>

    {{-- Filter bar --}}
    <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:grid-cols-[1fr_180px_auto_auto_auto]">
        <div class="relative">
            <i data-lucide="search" class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Search by name or slug…"
                   class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-950">
        </div>

        <select wire:model.live="languageFilter"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-emerald-500 dark:border-slate-700 dark:bg-slate-950">
            <option value="">All languages</option>
            @foreach ($this->languages as $language)
                <option value="{{ $language->id }}">{{ $language->flag_emoji ?? '🌐' }} {{ $language->name }}</option>
            @endforeach
        </select>

        <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900">
            <input type="checkbox" wire:model.live="featuredOnly"
                   class="h-3.5 w-3.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
            Featured only
        </label>

        <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900">
            <input type="checkbox" wire:model.live="inMenuOnly"
                   class="h-3.5 w-3.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
            In menu
        </label>

        <button type="button"
                wire:click="clearFilters"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
            <i data-lucide="filter-x" class="h-3.5 w-3.5"></i>
            Clear
        </button>
    </div>

    {{-- Bulk action bar — only visible when rows are selected. --}}
    @if (! empty($selectedIds))
        <div class="flex items-center justify-between rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm dark:border-emerald-500/30 dark:bg-emerald-500/10">
            <span class="font-semibold text-emerald-800 dark:text-emerald-200">
                {{ count($selectedIds) }} selected
            </span>
            @can('categories.delete')
                <button type="button"
                        wire:click="bulkDelete"
                        wire:confirm="Delete {{ count($selectedIds) }} categor{{ count($selectedIds) === 1 ? 'y' : 'ies' }}? Children will be re-parented."
                        class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">
                    <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                    Delete selected
                </button>
            @endcan
        </div>
    @endif

    {{-- Tree --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
         wire:loading.class="opacity-60"
         wire:target="reorder,bulkDelete,deleteCategory,search,languageFilter,featuredOnly,inMenuOnly">
        @php
            $rows = $this->tree;
        @endphp

        @if (empty($rows))
            <div class="flex flex-col items-center justify-center gap-3 px-6 py-16 text-center text-slate-500">
                <span class="grid h-12 w-12 place-items-center rounded-2xl bg-slate-100 dark:bg-slate-800">
                    <i data-lucide="folder-tree" class="h-6 w-6"></i>
                </span>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">No categories yet</p>
                <p class="max-w-sm text-xs">
                    Create your first category to start organising posts.
                </p>
                @can('categories.create')
                    <a href="{{ route('admin.categories.create') }}" wire:navigate
                       class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                        <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                        Create category
                    </a>
                @endcan
            </div>
        @endif
        @if (! empty($rows))
            <ul class="divide-y divide-slate-100 dark:divide-slate-800"
                x-on:dragover.prevent
                x-on:drop.prevent="dropOnParent(null)">
                @foreach ($rows as $row)
                    @php
                        $category = $row['category'];
                        $depth = (int) $row['depth'];
                        $translation = $category->translation();
                        $name = $translation?->name ?? '#'.$category->id;
                        $slug = $translation?->slug ?? '';
                        $childCount = $category->children->count();
                    @endphp

                    <li wire:key="cat-row-{{ $category->id }}"
                        data-cat-row
                        data-cat-id="{{ $category->id }}"
                        data-parent-id="{{ $category->parent_id ?? '' }}"
                        data-depth="{{ $depth }}"
                        draggable="true"
                        x-on:dragstart="pickUp({{ $category->id }}, {{ $depth }})"
                        x-on:dragend="release()"
                        class="group relative flex items-center gap-3 bg-white px-4 py-3 transition hover:bg-slate-50 dark:bg-slate-900 dark:hover:bg-slate-800/50"
                        style="padding-left: {{ 1 + $depth * 1.5 }}rem;">

                        {{-- Drop targets above/below the row for ordering. --}}
                        <span class="pointer-events-none absolute left-0 right-0 top-0 h-1.5"
                              x-on:dragover.prevent.stop="$el.classList.add('bg-emerald-400')"
                              x-on:dragleave="$el.classList.remove('bg-emerald-400')"
                              x-on:drop.prevent.stop="$el.classList.remove('bg-emerald-400'); dropOnRow('{{ $category->id }}', '{{ $category->parent_id ?? '' }}', 'before')"
                              :class="{ 'pointer-events-auto': draggedId !== null }"></span>
                        <span class="pointer-events-none absolute left-0 right-0 bottom-0 h-1.5"
                              x-on:dragover.prevent.stop="$el.classList.add('bg-emerald-400')"
                              x-on:dragleave="$el.classList.remove('bg-emerald-400')"
                              x-on:drop.prevent.stop="$el.classList.remove('bg-emerald-400'); dropOnRow('{{ $category->id }}', '{{ $category->parent_id ?? '' }}', 'after')"
                              :class="{ 'pointer-events-auto': draggedId !== null }"></span>

                        {{-- Bulk select checkbox --}}
                        @can('categories.delete')
                            <input type="checkbox"
                                   wire:model.live="selectedIds"
                                   value="{{ $category->id }}"
                                   class="h-4 w-4 shrink-0 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        @endcan

                        {{-- Drag handle --}}
                        <span class="cursor-grab text-slate-400 active:cursor-grabbing" title="Drag to reorder">
                            <i data-lucide="grip-vertical" class="h-4 w-4"></i>
                        </span>

                        {{-- Icon swatch --}}
                        @if (! empty($category->color) || ! empty($category->icon))
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl text-white shadow-sm"
                                  style="background-color: {{ $category->color ?? '#64748b' }};">
                                <i data-lucide="{{ $category->icon ?? 'folder' }}" class="h-4 w-4"></i>
                            </span>
                        @else
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-slate-100 text-slate-500 dark:bg-slate-800">
                                <i data-lucide="folder" class="h-4 w-4"></i>
                            </span>
                        @endif

                        {{-- Identity column --}}
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                @can('categories.edit')
                                    <a href="{{ route('admin.categories.edit', $category) }}"
                                       wire:navigate
                                       class="truncate text-sm font-semibold text-slate-800 transition hover:text-emerald-600 dark:text-slate-100">
                                        {{ $name }}
                                    </a>
                                @else
                                    <span class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">
                                        {{ $name }}
                                    </span>
                                @endcan

                                @if ($category->is_featured)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-700 dark:bg-amber-500/20 dark:text-amber-300">
                                        <i data-lucide="star" class="h-2.5 w-2.5"></i>
                                        Featured
                                    </span>
                                @endif

                                @if (! $category->show_in_menu)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                                        Hidden
                                    </span>
                                @endif

                                @if ($childCount > 0)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-1.5 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
                                        {{ $childCount }} child{{ $childCount === 1 ? '' : 'ren' }}
                                    </span>
                                @endif
                            </div>
                            @if ($slug !== '')
                                <p class="mt-0.5 truncate font-mono text-[11px] text-slate-500">/{{ $slug }}</p>
                            @endif
                        </div>

                        {{-- Translation badges --}}
                        <div class="hidden items-center gap-1 md:flex">
                            @foreach ($category->translations as $tx)
                                <span class="rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $tx->language?->code ?? '?' }}
                                </span>
                            @endforeach
                        </div>

                        {{-- Row actions --}}
                        <div class="flex items-center gap-1">
                            @can('categories.edit')
                                <a href="{{ route('admin.categories.edit', $category) }}"
                                   wire:navigate
                                   class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-emerald-50 hover:text-emerald-600 dark:hover:bg-emerald-500/10"
                                   title="Edit">
                                    <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                </a>
                            @endcan
                            @can('categories.delete')
                                <button type="button"
                                        wire:click="deleteCategory({{ $category->id }})"
                                        wire:confirm="Delete this category? Its children will be re-parented."
                                        class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10"
                                        title="Delete">
                                    <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                </button>
                            @endcan
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

