{{--
    Reusable media picker modal.

    The OUTER wrapper stays mounted in the DOM and is shown/hidden by
    Alpine via @entangle('open').live (two-way bind with the Livewire
    boolean). This makes close instant client-side; the X / backdrop
    play the fade animation before the server roundtrip lands.

    The INNER body (search + grid) is still wrapped in @if ($open) so
    we never render the 24 image tiles when the picker is closed.
--}}
<div
    x-data="{ open: @entangle('open').live }"
    x-cloak
    @keydown.escape.window="open && (open = false, $wire.close())"
    class="fixed inset-0 z-[60]"
    :class="open ? '' : 'pointer-events-none'"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="transition-opacity ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/60"
        @click="open = false; $wire.close()"
    ></div>

    {{-- Panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="fixed inset-0 flex items-center justify-center p-4 sm:p-6"
    >
        <div class="relative flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900"
             @click.stop>

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                <div class="flex items-center gap-3">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-sky-500 to-cyan-500 text-white">
                        <i data-lucide="image" class="h-4 w-4"></i>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Media Library</h3>
                        <p class="text-xs text-slate-500">Pick or upload an image</p>
                    </div>
                </div>
                <button type="button"
                        @click="open = false; $wire.close()"
                        class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            {{-- Body — only renders when actually open, to skip the 24-image
                 query + lazy-loaded <img> tags while the picker is closed. --}}
            @if ($open)
                {{-- Search + Upload bar --}}
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-3 dark:border-slate-800 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <i data-lucide="search" class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
                        <input type="text"
                               wire:model.live.debounce.400ms="search"
                               placeholder="Search filename, alt text, caption…"
                               class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-sky-500/20">
                    </div>

                    <label class="inline-flex shrink-0 cursor-pointer items-center gap-2 rounded-xl bg-sky-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-sky-700">
                        <i data-lucide="upload-cloud" class="h-3.5 w-3.5"></i>
                        <span>Upload New</span>
                        <input type="file" wire:model="uploadFile" class="hidden" accept="image/*">
                    </label>
                </div>

                {{-- Pending upload pane --}}
                @if ($uploadFile)
                    <div class="border-b border-sky-200 bg-sky-50/60 px-5 py-3 dark:border-sky-500/30 dark:bg-sky-500/10">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <div class="flex-1">
                                <div class="mb-1 flex items-center gap-2 text-xs font-semibold text-sky-800 dark:text-sky-300">
                                    <i data-lucide="file-image" class="h-3.5 w-3.5"></i>
                                    <span>{{ $uploadFile->getClientOriginalName() }}</span>
                                </div>
                                <input type="text"
                                       wire:model="uploadAltText"
                                       placeholder="Alt text (for accessibility + SEO)"
                                       class="w-full rounded-lg border border-sky-200 bg-white px-3 py-2 text-xs outline-none focus:border-sky-500 dark:border-sky-500/30 dark:bg-slate-950">
                                @error('uploadFile') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                                @error('file') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex gap-2">
                                <button type="button"
                                        wire:click="$set('uploadFile', null)"
                                        class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                                    Cancel
                                </button>
                                <button type="button"
                                        wire:click="uploadAndSelect"
                                        wire:loading.attr="disabled"
                                        wire:target="uploadAndSelect"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">
                                    <i data-lucide="check" class="h-3.5 w-3.5" wire:loading.remove wire:target="uploadAndSelect"></i>
                                    <i data-lucide="loader-2" class="h-3.5 w-3.5 animate-spin" wire:loading wire:target="uploadAndSelect"></i>
                                    <span wire:loading.remove wire:target="uploadAndSelect">Use This Image</span>
                                    <span wire:loading wire:target="uploadAndSelect">Uploading…</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Grid --}}
                <div class="flex-1 overflow-y-auto p-5"
                     wire:loading.class.delay="opacity-60"
                     wire:target="search,results">
                    @php($results = $this->results)
                    @if ($results->isEmpty())
                        <div class="flex flex-col items-center justify-center gap-3 py-16 text-center">
                            <span class="grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                                <i data-lucide="image-off" class="h-6 w-6"></i>
                            </span>
                            <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">No media yet</p>
                            <p class="max-w-sm text-xs text-slate-500">
                                Upload your first image using the button above, or search by filename / alt text.
                            </p>
                        </div>
                    @else
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                            @foreach ($results as $media)
                                <button type="button"
                                        wire:click="select({{ $media->id }})"
                                        wire:key="media-tile-{{ $media->id }}"
                                        class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white transition hover:border-sky-500 hover:shadow-md dark:border-slate-700 dark:bg-slate-950">
                                    <div class="aspect-square w-full overflow-hidden bg-slate-100 dark:bg-slate-800">
                                        @if ($media->isImage())
                                            <img src="{{ $media->url() }}"
                                                 alt="{{ $media->alt_text ?? $media->original_filename }}"
                                                 class="h-full w-full object-cover transition group-hover:scale-105"
                                                 loading="lazy"
                                                 decoding="async">
                                        @else
                                            <div class="flex h-full w-full items-center justify-center text-slate-400">
                                                <i data-lucide="file" class="h-8 w-8"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="truncate px-2 py-1.5 text-left text-[10px] text-slate-600 dark:text-slate-300">
                                        {{ $media->original_filename }}
                                    </div>
                                    <span class="pointer-events-none absolute inset-0 hidden items-center justify-center bg-sky-500/80 text-white group-hover:flex">
                                        <i data-lucide="check-circle" class="h-7 w-7"></i>
                                    </span>
                                </button>
                            @endforeach
                        </div>

                        <div class="mt-4">{{ $results->onEachSide(1)->links() }}</div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
