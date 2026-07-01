{{--
    Featured Image sidebar card — included in Create.blade.php and
    Edit.blade.php. Expects on $this:
      - featuredImageId  (public ?int)
      - featuredImage    (computed ?Media)
      - openFeaturedImagePicker()
      - clearFeaturedImage()
--}}
@php($media = $this->featuredImage)

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
        <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
            <i data-lucide="image" class="h-3.5 w-3.5"></i>
            Featured Image
        </h3>
        @if ($media)
            <button type="button"
                    wire:click="clearFeaturedImage"
                    class="text-xs font-semibold text-rose-600 hover:text-rose-700">
                Remove
            </button>
        @endif
    </div>

    <div class="p-4">
        @if ($media && $media->isImage())
            <div class="space-y-3">
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-950">
                    <img src="{{ $media->url() }}"
                         alt="{{ $media->alt_text ?? $media->original_filename }}"
                         class="aspect-video w-full object-cover">
                </div>
                <div class="space-y-0.5 text-xs text-slate-600 dark:text-slate-300">
                    <p class="truncate font-semibold text-slate-700 dark:text-slate-200">
                        {{ $media->original_filename }}
                    </p>
                    @if ($media->width && $media->height)
                        <p class="text-slate-500">{{ $media->width }} × {{ $media->height }} · {{ $media->sizeFormatted() }}</p>
                    @endif
                </div>
                <button type="button"
                        wire:click="openFeaturedImagePicker"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-sky-500 hover:bg-sky-50 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                    <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i>
                    Replace Image
                </button>
            </div>
        @else
            <button type="button"
                    wire:click="openFeaturedImagePicker"
                    class="group flex aspect-video w-full flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 text-slate-500 transition hover:border-sky-500 hover:bg-sky-50/60 hover:text-sky-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-white text-slate-400 shadow-sm transition group-hover:bg-sky-100 group-hover:text-sky-600 dark:bg-slate-800 dark:text-slate-500">
                    <i data-lucide="image-plus" class="h-5 w-5"></i>
                </span>
                <p class="text-xs font-semibold">Choose Featured Image</p>
                <p class="text-[10px] text-slate-400">PNG, JPG, WebP up to 10 MB</p>
            </button>
        @endif
    </div>
</div>
