<div class="space-y-5"
     x-data="{ formOpen: @entangle('showForm').live, mergeOpen: @entangle('showMerge').live }">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">Tags</span>
    </nav>

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-purple-500 to-fuchsia-500 text-white shadow-sm">
                <i data-lucide="tags" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">Tags</h1>
                <p class="text-xs text-slate-500">Manage editorial tags with per-language names and slugs.</p>
            </div>
        </div>

        @can('tags.create')
            <button type="button" x-on:click="formOpen = true; $wire.newTag()"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-indigo-700 hover:to-indigo-600">
                <i data-lucide="plus" class="h-4 w-4"></i>
                New Tag
            </button>
        @endcan
    </div>

    {{-- Filters --}}
    <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:grid-cols-[1fr_180px_180px]">
        <div class="relative">
            <i data-lucide="search" class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
            <input type="text" wire:model.live.debounce.400ms="search"
                   placeholder="Search by name, slug, code…"
                   class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950">
        </div>
        <select wire:model.live="typeFilter"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
            <option value="">All types</option>
            @foreach ($this->typeOptions as $t)
                <option value="{{ $t }}">{{ ucfirst($t) }}</option>
            @endforeach
        </select>
        <select wire:model.live="statusFilter"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
            <option value="">All statuses</option>
            @foreach ($this->statusOptions as $s)
                <option value="{{ $s }}">{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <table class="min-w-full divide-y divide-slate-100 text-sm dark:divide-slate-800">
            <thead class="bg-slate-50/60 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-950/40">
                <tr>
                    <th class="px-4 py-2.5">Tag</th>
                    <th class="px-4 py-2.5">Code</th>
                    <th class="px-4 py-2.5">Type</th>
                    <th class="px-4 py-2.5">Status</th>
                    <th class="px-4 py-2.5">Posts</th>
                    <th class="px-4 py-2.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($this->tags as $tag)
                    <tr wire:key="tag-{{ $tag->id }}" class="transition hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="block h-3 w-3 rounded-full" style="background-color: {{ $tag->color ?? '#6366f1' }};"></span>
                                <div>
                                    <p class="font-semibold text-slate-800 dark:text-slate-100">
                                        {{ $tag->translate('name') ?? $tag->name }}
                                    </p>
                                    <p class="font-mono text-[10px] text-slate-500">{{ $tag->slug }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $tag->code }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                {{ $tag->type }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span @class([
                                'rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300' => $tag->status === \App\Models\Tag::STATUS_PUBLISHED,
                                'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300' => $tag->status !== \App\Models\Tag::STATUS_PUBLISHED,
                            ])>
                                {{ $tag->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center font-mono text-xs">{{ $tag->posts_count ?? 0 }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1.5">
                                @can('tags.merge')
                                    <button type="button" x-on:click="mergeOpen = true; $wire.openMerge({{ $tag->id }})"
                                            class="grid h-7 w-7 place-items-center rounded-lg text-fuchsia-600 hover:bg-fuchsia-50 dark:hover:bg-fuchsia-500/10"
                                            title="Merge into this tag">
                                        <i data-lucide="combine" class="h-3.5 w-3.5"></i>
                                    </button>
                                @endcan
                                @can('tags.edit')
                                    <button type="button" x-on:click="formOpen = true; $wire.editTag({{ $tag->id }})"
                                            class="grid h-7 w-7 place-items-center rounded-lg text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-500/10"
                                            title="Edit">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                    </button>
                                @endcan
                                @can('tags.delete')
                                    <button type="button"
                                            wire:click="deleteTag({{ $tag->id }})"
                                            wire:confirm="Delete tag '{{ $tag->translate('name') ?? $tag->name }}'?"
                                            class="grid h-7 w-7 place-items-center rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10"
                                            title="Delete">
                                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">
                            No tags match your filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="border-t border-slate-100 p-3 dark:border-slate-800">
            {{ $this->tags->onEachSide(1)->links() }}
        </div>
    </div>

    {{-- Create / Edit modal — Alpine controls visibility; close is instant. --}}
    <div x-show="formOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
         x-on:keydown.escape.window="formOpen = false; $wire.cancelForm()"
         x-on:click="formOpen = false; $wire.cancelForm()">
        <div class="max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900"
             x-on:click.stop>

                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-100 bg-slate-50/95 px-5 py-3 backdrop-blur dark:border-slate-800 dark:bg-slate-950/95">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br from-purple-500 to-fuchsia-500 text-white">
                            <i data-lucide="tag" class="h-4 w-4"></i>
                        </span>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">
                            {{ $editingId ? 'Edit Tag' : 'New Tag' }}
                        </h3>
                    </div>
                    <button type="button" x-on:click="formOpen = false; $wire.cancelForm()"
                            class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <div class="space-y-5 p-5">
                    <div class="grid gap-3 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Code (auto if blank)</label>
                            <input type="text" wire:model="code"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-mono dark:border-slate-700 dark:bg-slate-950">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Color</label>
                            <div class="flex items-center gap-2">
                                <input type="color" wire:model="color"
                                       class="h-9 w-12 cursor-pointer rounded-lg border border-slate-200 bg-white dark:border-slate-700">
                                <input type="text" wire:model="color"
                                       class="flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-mono dark:border-slate-700 dark:bg-slate-950">
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Status</label>
                            <select wire:model="status"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                                @foreach ($this->statusOptions as $s)
                                    <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <h4 class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-500">Per-language names</h4>
                        <div class="space-y-3">
                            @foreach ($this->languages as $lang)
                                <div class="grid gap-2 rounded-xl border border-slate-200 p-3 dark:border-slate-700 md:grid-cols-[140px_1fr_1fr]">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg">{{ $lang->flag_emoji ?? '🌐' }}</span>
                                        <div>
                                            <p class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ $lang->name }}</p>
                                            <p class="font-mono text-[10px] text-slate-500">{{ $lang->code }}</p>
                                        </div>
                                    </div>
                                    <input type="text"
                                           wire:model="rows.{{ $lang->id }}.name"
                                           placeholder="Tag name in {{ $lang->name }}"
                                           class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                                    <input type="text"
                                           wire:model="rows.{{ $lang->id }}.slug"
                                           placeholder="auto-from-name"
                                           class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-mono dark:border-slate-700 dark:bg-slate-950">
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-slate-500">A name in the default language is required.</p>
                    </div>
                </div>

                <div class="sticky bottom-0 flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/95 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/95">
                    <button type="button" x-on:click="formOpen = false; $wire.cancelForm()"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                        Cancel
                    </button>
                    <button type="button" wire:click="save"
                            wire:loading.attr="disabled" wire:target="save"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-4 py-2 text-xs font-bold text-white hover:from-indigo-700 hover:to-indigo-600 disabled:opacity-60">
                        <i data-lucide="check" class="h-3.5 w-3.5" wire:loading.remove wire:target="save"></i>
                        <i data-lucide="loader-2" class="h-3.5 w-3.5 animate-spin" wire:loading wire:target="save"></i>
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update Tag' : 'Create Tag' }}</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                </div>
            </div>
    </div>

    {{-- Merge modal — Alpine controls visibility. Heavy body only mounts when open. --}}
    <div x-show="mergeOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
         x-on:keydown.escape.window="mergeOpen = false; $wire.cancelMerge()"
         x-on:click="mergeOpen = false; $wire.cancelMerge()">
        @if ($showMerge)
        @php($target = \App\Models\Tag::with('translations')->find($mergeTargetId))
        <div class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900"
             x-on:click.stop>
                <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">
                        Merge tags into "{{ $target?->translate('name') ?? $target?->name }}"
                    </h3>
                </div>
                <div class="space-y-3 p-5">
                    <p class="text-xs text-slate-500">
                        Posts using the source tags below will be re-tagged to the target tag.
                        Source tags will be deleted after the merge.
                    </p>
                    <div class="max-h-60 space-y-1 overflow-y-auto rounded-xl border border-slate-200 p-2 dark:border-slate-700">
                        @foreach ($this->tags as $tag)
                            @if ($tag->id !== $mergeTargetId)
                                <label class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs hover:bg-slate-50 dark:hover:bg-slate-800">
                                    <input type="checkbox" value="{{ $tag->id }}"
                                           wire:model="mergeSourceIds"
                                           class="h-3.5 w-3.5 rounded border-slate-300 text-fuchsia-600 focus:ring-fuchsia-500">
                                    <span class="block h-2 w-2 rounded-full" style="background-color: {{ $tag->color ?? '#6366f1' }};"></span>
                                    <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $tag->translate('name') ?? $tag->name }}</span>
                                    <span class="ml-auto font-mono text-[10px] text-slate-500">{{ $tag->posts_count ?? 0 }} posts</span>
                                </label>
                            @endif
                        @endforeach
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                    <button type="button" x-on:click="mergeOpen = false; $wire.cancelMerge()"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                        Cancel
                    </button>
                    <button type="button" wire:click="performMerge"
                            wire:loading.attr="disabled" wire:target="performMerge"
                            wire:confirm="Merge selected tags? This cannot be undone."
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-fuchsia-600 to-purple-500 px-4 py-2 text-xs font-bold text-white hover:from-fuchsia-700 disabled:opacity-60">
                        <i data-lucide="combine" class="h-3.5 w-3.5" wire:loading.remove wire:target="performMerge"></i>
                        <i data-lucide="loader-2" class="h-3.5 w-3.5 animate-spin" wire:loading wire:target="performMerge"></i>
                        <span wire:loading.remove wire:target="performMerge">Merge</span>
                        <span wire:loading wire:target="performMerge">Merging…</span>
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
