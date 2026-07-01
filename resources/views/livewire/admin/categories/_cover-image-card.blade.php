{{--
    Cover image picker card — used by Create.blade and Edit.blade.
    Components expose imageId + coverImage() computed + openCoverImagePicker()
    and clearCoverImage().
--}}
<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
        <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
            <i data-lucide="image" class="h-3.5 w-3.5"></i>
            Cover Image
        </h3>
    </div>

    <div class="p-4">
        @if ($this->coverImage)
            <div class="space-y-3">
                <div class="overflow-hidden rounded-xl">
                    <img src="{{ $this->coverImage->url() }}"
                         alt="{{ $this->coverImage->alt_text ?? 'Cover image' }}"
                         class="aspect-video w-full object-cover">
                </div>
                <div class="flex items-center gap-2">
                    <button type="button"
                            wire:click="openCoverImagePicker"
                            class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                        <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i>
                        Change
                    </button>
                    <button type="button"
                            wire:click="clearCoverImage"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-100 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
                        <i data-lucide="x" class="h-3.5 w-3.5"></i>
                        Clear
                    </button>
                </div>
            </div>
        @else
            <button type="button"
                    wire:click="openCoverImagePicker"
                    class="flex w-full flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-200 px-4 py-8 text-xs font-semibold text-slate-500 transition hover:border-emerald-300 hover:bg-emerald-50/40 hover:text-emerald-700 dark:border-slate-700 dark:hover:border-emerald-500/40 dark:hover:bg-emerald-500/5">
                <i data-lucide="image-plus" class="h-5 w-5"></i>
                Pick cover image
            </button>
        @endif
    </div>
</div>
