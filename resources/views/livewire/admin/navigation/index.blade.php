<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5 dark:border-slate-800">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Header Navigation Menu</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Manage the main navigation bar links, dropdowns, order, and targets displayed on the website header.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="resetToDefaults" wire:confirm="Are you sure you want to reset header navigation to default items? Any custom menu items will be replaced." type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition">
                <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                <span>Reset to Defaults</span>
            </button>
            <button wire:click="openCreateModal()" type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-700 transition">
                <i data-lucide="plus" class="h-4 w-4"></i>
                <span>Add Menu Item</span>
            </button>
        </div>
    </div>

    {{-- Menu Items Table --}}
    <div class="rounded-2xl border border-slate-200 bg-white shadow-2xs dark:border-slate-800 dark:bg-slate-900 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/75 dark:border-slate-800 dark:bg-slate-950/50">
                        <th class="py-3.5 pl-6 pr-3 font-bold text-slate-500 uppercase tracking-wider text-[10px]">Order</th>
                        <th class="py-3.5 px-3 font-bold text-slate-500 uppercase tracking-wider text-[10px]">Title & Icon</th>
                        <th class="py-3.5 px-3 font-bold text-slate-500 uppercase tracking-wider text-[10px]">Type</th>
                        <th class="py-3.5 px-3 font-bold text-slate-500 uppercase tracking-wider text-[10px]">Target URL</th>
                        <th class="py-3.5 px-3 font-bold text-slate-500 uppercase tracking-wider text-[10px]">Target</th>
                        <th class="py-3.5 px-3 font-bold text-slate-500 uppercase tracking-wider text-[10px]">Status</th>
                        <th class="py-3.5 pr-6 pl-3 text-right font-bold text-slate-500 uppercase tracking-wider text-[10px]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($headerItems as $item)
                        {{-- Top-level Parent Row --}}
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition">
                            <td class="py-3.5 pl-6 pr-3 whitespace-nowrap">
                                <div class="flex items-center gap-1">
                                    <button wire:click="moveUp({{ $item->id }})" title="Move Up" class="p-1 rounded text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800">
                                        <i data-lucide="chevron-up" class="h-3.5 w-3.5"></i>
                                    </button>
                                    <button wire:click="moveDown({{ $item->id }})" title="Move Down" class="p-1 rounded text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800">
                                        <i data-lucide="chevron-down" class="h-3.5 w-3.5"></i>
                                    </button>
                                    <span class="ml-1 font-mono text-[11px] text-slate-400">{{ $item->order }}</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-3 font-semibold text-slate-900 dark:text-white">
                                <div class="flex items-center gap-2">
                                    @if ($item->icon)
                                        <i data-lucide="{{ $item->icon }}" class="h-4 w-4 text-slate-400"></i>
                                    @endif
                                    <span>{{ $item->title }}</span>
                                    @if ($item->isDropdown())
                                        <span class="rounded-md bg-indigo-50 px-1.5 py-0.5 text-[10px] font-bold text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300">
                                            Dropdown ({{ $item->children->count() }})
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3.5 px-3">
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300 uppercase">
                                    {{ $item->type }}
                                </span>
                            </td>
                            <td class="py-3.5 px-3 font-mono text-slate-500 dark:text-slate-400 truncate max-w-xs">
                                {{ $item->url }}
                            </td>
                            <td class="py-3.5 px-3 text-slate-500">
                                {{ $item->target === '_blank' ? 'New Tab (↗)' : 'Same Window' }}
                            </td>
                            <td class="py-3.5 px-3">
                                <button type="button" wire:click="toggleActive({{ $item->id }})"
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-[10px] font-bold transition {{ $item->is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $item->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                    {{ $item->is_active ? 'Active' : 'Hidden' }}
                                </button>
                            </td>
                            <td class="py-3.5 pr-6 pl-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button wire:click="openCreateModal({{ $item->id }})" title="Add Sub-item to this dropdown"
                                            class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-slate-100 rounded-lg dark:hover:bg-slate-800">
                                        <i data-lucide="corner-down-right" class="h-4 w-4"></i>
                                    </button>
                                    <button wire:click="edit({{ $item->id }})" title="Edit"
                                            class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-slate-100 rounded-lg dark:hover:bg-slate-800">
                                        <i data-lucide="edit-3" class="h-4 w-4"></i>
                                    </button>
                                    <button wire:click="delete({{ $item->id }})" wire:confirm="Are you sure you want to delete this menu item and all its sub-items?" title="Delete"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-slate-100 rounded-lg dark:hover:bg-slate-800">
                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        {{-- Child Items (Submenu) --}}
                        @if ($item->children->isNotEmpty())
                            @foreach ($item->children as $child)
                                <tr class="bg-slate-50/40 hover:bg-slate-50/80 dark:bg-slate-950/30 dark:hover:bg-slate-800/20 transition">
                                    <td class="py-2.5 pl-6 pr-3 whitespace-nowrap">
                                        <div class="flex items-center gap-1 pl-4">
                                            <button wire:click="moveUp({{ $child->id }})" title="Move Up" class="p-1 rounded text-slate-400 hover:text-slate-700">
                                                <i data-lucide="chevron-up" class="h-3 w-3"></i>
                                            </button>
                                            <button wire:click="moveDown({{ $child->id }})" title="Move Down" class="p-1 rounded text-slate-400 hover:text-slate-700">
                                                <i data-lucide="chevron-down" class="h-3 w-3"></i>
                                            </button>
                                            <span class="font-mono text-[10px] text-slate-400">{{ $child->order }}</span>
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <div class="flex items-center gap-2 pl-4 text-slate-700 dark:text-slate-300">
                                            <span class="text-slate-300 dark:text-slate-600">└─</span>
                                            @if ($child->icon)
                                                <i data-lucide="{{ $child->icon }}" class="h-3.5 w-3.5 text-slate-400"></i>
                                            @endif
                                            <span>{{ $child->title }}</span>
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300 uppercase">
                                            {{ $child->type }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 font-mono text-[11px] text-slate-500 dark:text-slate-400 truncate max-w-xs">
                                        {{ $child->url }}
                                    </td>
                                    <td class="py-2.5 px-3 text-[11px] text-slate-500">
                                        {{ $child->target === '_blank' ? 'New Tab (↗)' : 'Same Window' }}
                                    </td>
                                    <td class="py-2.5 px-3">
                                        <button type="button" wire:click="toggleActive({{ $child->id }})"
                                                class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[9px] font-bold {{ $child->is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-500' }}">
                                            <span class="h-1 w-1 rounded-full {{ $child->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                            {{ $child->is_active ? 'Active' : 'Hidden' }}
                                        </button>
                                    </td>
                                    <td class="py-2.5 pr-6 pl-3 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1">
                                            <button wire:click="edit({{ $child->id }})" title="Edit"
                                                    class="p-1 text-slate-400 hover:text-indigo-600 hover:bg-slate-100 rounded-lg">
                                                <i data-lucide="edit-3" class="h-3.5 w-3.5"></i>
                                            </button>
                                            <button wire:click="delete({{ $child->id }})" wire:confirm="Delete this sub-menu item?" title="Delete"
                                                    class="p-1 text-slate-400 hover:text-rose-600 hover:bg-slate-100 rounded-lg">
                                                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <i data-lucide="navigation" class="mx-auto h-8 w-8 text-slate-300 dark:text-slate-700 mb-2"></i>
                                <p class="text-sm font-semibold">No header navigation items defined</p>
                                <button type="button" wire:click="resetToDefaults" class="mt-3 text-xs font-bold text-indigo-600 hover:underline">
                                    Click here to load standard defaults (Home, Categories, About, Contacts)
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Create / Edit Modal --}}
    @if ($modalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900 border border-slate-200 dark:border-slate-800 space-y-5 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        {{ $editingId ? 'Edit Menu Item' : 'Add New Menu Item' }}
                    </h3>
                    <button type="button" wire:click="$set('modalOpen', false)" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <form wire:submit.prevent="save" class="space-y-4">
                    {{-- Link Type & Quick Picker --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Item Type</label>
                            <select wire:model.live="type"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                                <option value="custom">Custom URL</option>
                                <option value="page">CMS Page</option>
                                <option value="category">Category</option>
                                <option value="route">System Route</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Parent (Dropdown)</label>
                            <select wire:model.defer="parent_id"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                                <option value="">None (Top Level Item)</option>
                                @foreach ($topLevelParents as $parent)
                                    <option value="{{ $parent->id }}">{{ $parent->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Quick Selectors when page or category --}}
                    @if ($type === 'page')
                        <div class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-3 dark:border-indigo-900/40 dark:bg-indigo-950/20">
                            <label class="block text-xs font-bold text-indigo-900 dark:text-indigo-300 mb-1">Select Page</label>
                            <select wire:model.live="selected_page_id"
                                    class="w-full rounded-lg border border-indigo-200 bg-white px-3 py-1.5 text-xs text-slate-900 dark:border-indigo-800 dark:bg-slate-900 dark:text-white">
                                <option value="">-- Choose a published page --</option>
                                @foreach ($availablePages as $p)
                                    <option value="{{ $p->id }}">{{ $p->translation()?->title ?? ('#' . $p->id) }} (/page/{{ $p->translation()?->slug }})</option>
                                @endforeach
                            </select>
                        </div>
                    @elseif ($type === 'category')
                        <div class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-3 dark:border-indigo-900/40 dark:bg-indigo-950/20">
                            <label class="block text-xs font-bold text-indigo-900 dark:text-indigo-300 mb-1">Select Category</label>
                            <select wire:model.live="selected_category_id"
                                    class="w-full rounded-lg border border-indigo-200 bg-white px-3 py-1.5 text-xs text-slate-900 dark:border-indigo-800 dark:bg-slate-900 dark:text-white">
                                <option value="">-- Choose a category --</option>
                                @foreach ($availableCategories as $c)
                                    <option value="{{ $c->id }}">{{ $c->translate('name') ?? ('#' . $c->id) }} (/category/{{ $c->translate('slug') }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Title & URL --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Menu Title</label>
                        <input type="text" wire:model.defer="title" required placeholder="e.g. Home, Tech, About Us"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                        @error('title') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Destination URL / Path</label>
                        <input type="text" wire:model.defer="url" required placeholder="/ or https://... or /category/news"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                        @error('url') <p class="mt-1 text-[11px] text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- Target, Icon & Order --}}
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Target</label>
                            <select wire:model.defer="target"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                                <option value="_self">Same Tab</option>
                                <option value="_blank">New Tab</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Icon (optional)</label>
                            <input type="text" wire:model.defer="icon" placeholder="e.g. sparkles"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Sort Order</label>
                            <input type="number" wire:model.defer="order"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                        </div>
                    </div>

                    {{-- Active Toggle --}}
                    <div class="pt-2 flex items-center justify-between border-t border-slate-100 dark:border-slate-800">
                        <label class="flex items-center gap-2 text-xs font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" wire:model.defer="is_active" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Enable this menu item on header</span>
                        </label>
                    </div>

                    {{-- Actions --}}
                    <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100 dark:border-slate-800">
                        <button type="button" wire:click="$set('modalOpen', false)"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                            Cancel
                        </button>
                        <button type="submit"
                                class="rounded-xl bg-indigo-600 px-5 py-2 text-xs font-bold text-white hover:bg-indigo-700">
                            Save Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
