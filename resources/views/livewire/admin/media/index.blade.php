<div class="space-y-5">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">Media Library</span>
    </nav>

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-amber-500 to-orange-500 text-white shadow-sm">
                <i data-lucide="image" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">Media Library</h1>
                <p class="text-xs text-slate-500">{{ $this->totalCount }} files total. Drag images into posts via the picker.</p>
            </div>
        </div>

        @can('media.upload')
            <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-indigo-700 hover:to-indigo-600">
                <i data-lucide="upload-cloud" class="h-4 w-4"></i>
                <span>Upload Files</span>
                <input type="file" multiple wire:model="uploadFiles" class="hidden" accept="image/*,video/*,application/pdf">
            </label>
        @endcan
    </div>

    {{-- Tabs --}}
    <div class="flex flex-wrap items-center gap-2">
        @php($tabs = [
            'all' => ['label' => 'All', 'icon' => 'layers'],
            'images' => ['label' => 'Images', 'icon' => 'image'],
            'videos' => ['label' => 'Videos', 'icon' => 'video'],
            'documents' => ['label' => 'Documents', 'icon' => 'file-text'],
        ])
        @foreach ($tabs as $key => $meta)
            <button type="button" wire:click="setTab('{{ $key }}')"
                    @class([
                        'inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold transition',
                        'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300' => $tab === $key,
                        'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' => $tab !== $key,
                    ])>
                <i data-lucide="{{ $meta['icon'] }}" class="h-3.5 w-3.5"></i>
                {{ $meta['label'] }}
                <span class="rounded-md bg-white/70 px-1.5 py-0.5 text-[10px] font-mono dark:bg-slate-800">
                    {{ $this->tabCounts[$key] ?? 0 }}
                </span>
            </button>
        @endforeach

        <div class="ml-auto flex items-center gap-2">
            <div class="relative">
                <i data-lucide="search" class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
                <input type="text" wire:model.live.debounce.400ms="search"
                       placeholder="Search filename, alt…"
                       class="w-64 rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm dark:border-slate-700 dark:bg-slate-950">
            </div>
        </div>
    </div>

    {{-- Pending uploads --}}
    @if (! empty($uploadFiles))
        <div class="rounded-2xl border border-sky-200 bg-sky-50/60 px-4 py-3 dark:border-sky-500/30 dark:bg-sky-500/10">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-xs font-semibold text-sky-800 dark:text-sky-300">
                    {{ count($uploadFiles) }} file(s) ready to upload
                </p>
                <button type="button" wire:click="uploadAll"
                        wire:loading.attr="disabled" wire:target="uploadAll,uploadFiles"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-1.5 text-xs font-bold text-white hover:bg-emerald-700 disabled:opacity-60">
                    <i data-lucide="upload" class="h-3.5 w-3.5" wire:loading.remove wire:target="uploadAll"></i>
                    <i data-lucide="loader-2" class="h-3.5 w-3.5 animate-spin" wire:loading wire:target="uploadAll"></i>
                    <span wire:loading.remove wire:target="uploadAll">Upload Now</span>
                    <span wire:loading wire:target="uploadAll">Uploading…</span>
                </button>
            </div>
        </div>
    @endif

    {{-- Bulk action bar --}}
    @if (! empty($selectedIds))
        <div class="sticky top-2 z-20 flex items-center justify-between gap-2 rounded-2xl border border-indigo-200 bg-indigo-50/95 px-4 py-2 shadow-md backdrop-blur dark:border-indigo-500/30 dark:bg-indigo-500/10">
            <p class="text-xs font-semibold text-indigo-800 dark:text-indigo-300">
                {{ count($selectedIds) }} selected
            </p>
            <div class="flex gap-2">
                <button type="button" wire:click="clearSelection"
                        class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                    Clear
                </button>
                @can('media.delete')
                    <button type="button" wire:click="bulkDelete"
                            wire:confirm="Delete the selected files? This cannot be undone."
                            class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-rose-700">
                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                        Delete Selected
                    </button>
                @endcan
            </div>
        </div>
    @endif

    {{-- Grid --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        @if ($this->media->isEmpty())
            <div class="flex flex-col items-center justify-center gap-3 py-16 text-center">
                <span class="grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                    <i data-lucide="image-off" class="h-6 w-6"></i>
                </span>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">No media files yet</p>
                <p class="max-w-sm text-xs text-slate-500">Click "Upload Files" to add your first media.</p>
            </div>
        @else
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                @foreach ($this->media as $item)
                    @php($isSelected = in_array($item->id, $selectedIds, true))
                    <div wire:key="media-{{ $item->id }}"
                         @class([
                             'group relative overflow-hidden rounded-xl border-2 transition',
                             'border-indigo-500 ring-2 ring-indigo-200 dark:ring-indigo-500/30' => $isSelected,
                             'border-slate-200 hover:border-indigo-300 dark:border-slate-700' => ! $isSelected,
                         ])>
                        <button type="button" wire:click="toggleSelect({{ $item->id }})"
                                class="absolute left-2 top-2 z-10 grid h-5 w-5 place-items-center rounded-md border-2 bg-white/90 shadow {{ $isSelected ? 'border-indigo-500 bg-indigo-500' : 'border-slate-300' }}">
                            @if ($isSelected)
                                <i data-lucide="check" class="h-3 w-3 text-white"></i>
                            @endif
                        </button>

                        <button type="button" wire:click="editMedia({{ $item->id }})"
                                class="block aspect-square w-full overflow-hidden bg-slate-100 dark:bg-slate-800">
                            @if ($item->isImage())
                                <img src="{{ $item->url() }}"
                                     alt="{{ $item->alt_text ?? $item->original_filename }}"
                                     loading="lazy"
                                     class="h-full w-full object-cover transition group-hover:scale-105">
                            @else
                                <div class="flex h-full w-full items-center justify-center text-slate-400">
                                    <i data-lucide="{{ $item->isVideo() ? 'video' : 'file' }}" class="h-8 w-8"></i>
                                </div>
                            @endif
                        </button>

                        <div class="space-y-0.5 border-t border-slate-100 bg-white px-2 py-1.5 text-[10px] dark:border-slate-800 dark:bg-slate-900">
                            <p class="truncate font-semibold text-slate-700 dark:text-slate-200">{{ $item->original_filename }}</p>
                            <p class="text-slate-500">
                                @if ($item->width && $item->height)
                                    {{ $item->width }}×{{ $item->height }} ·
                                @endif
                                {{ $item->sizeFormatted() }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">{{ $this->media->onEachSide(1)->links() }}</div>
        @endif
    </div>

    {{-- Detail drawer --}}
    @if ($editingId)
        @php($media = \App\Models\Media::find($editingId))
        @if ($media)
            <div class="fixed inset-0 z-50 flex items-center justify-end bg-slate-900/60"
                 wire:click="cancelEdit"
                 x-data x-on:keydown.escape.window="$wire.cancelEdit()">

                <aside class="flex h-full w-full max-w-lg flex-col overflow-y-auto border-l border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900"
                       wire:click.stop>

                    <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Media Details</h3>
                        <button type="button" wire:click="cancelEdit"
                                class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                    </div>

                    <div class="space-y-4 p-5">
                        <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-950">
                            @if ($media->isImage())
                                <img src="{{ $media->url() }}" alt="{{ $media->alt_text ?? '' }}" class="w-full">
                            @else
                                <div class="flex h-48 w-full items-center justify-center text-slate-400">
                                    <i data-lucide="file" class="h-12 w-12"></i>
                                </div>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <p class="font-semibold text-slate-500">Filename</p>
                                <p class="break-all text-slate-700 dark:text-slate-200">{{ $media->original_filename }}</p>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-500">Size</p>
                                <p class="text-slate-700 dark:text-slate-200">{{ $media->sizeFormatted() }}</p>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-500">Mime</p>
                                <p class="font-mono text-slate-700 dark:text-slate-200">{{ $media->mime_type }}</p>
                            </div>
                            @if ($media->width && $media->height)
                                <div>
                                    <p class="font-semibold text-slate-500">Dimensions</p>
                                    <p class="text-slate-700 dark:text-slate-200">{{ $media->width }}×{{ $media->height }}</p>
                                </div>
                            @endif
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Alt text</label>
                            <textarea wire:model="editAltText" rows="2"
                                      class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Caption</label>
                            <textarea wire:model="editCaption" rows="2"
                                      class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
                        </div>

                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Credit</label>
                            <input type="text" wire:model="editCredit"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                        </div>
                    </div>

                    <div class="mt-auto flex items-center justify-between gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                        @can('media.delete')
                            <button type="button"
                                    wire:click="deleteOne({{ $media->id }})"
                                    wire:confirm="Delete '{{ $media->original_filename }}'? This cannot be undone."
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-2 text-xs font-bold text-white hover:bg-rose-700">
                                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                Delete
                            </button>
                        @endcan

                        <div class="ml-auto flex gap-2">
                            <button type="button" wire:click="cancelEdit"
                                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                                Cancel
                            </button>
                            <button type="button" wire:click="saveMeta"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-indigo-600 to-indigo-500 px-3 py-2 text-xs font-bold text-white hover:from-indigo-700">
                                <i data-lucide="check" class="h-3.5 w-3.5"></i>
                                Save
                            </button>
                        </div>
                    </div>
                </aside>
            </div>
        @endif
    @endif
</div>
