<div class="space-y-6">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('admin.categories.index') }}" wire:navigate class="hover:text-emerald-600">Categories</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">
            Edit — {{ $category->translate('name') ?? '#'.$category->id }}
        </span>
    </nav>

    <div class="grid gap-6 xl:grid-cols-[1fr_320px]">
        {{-- Form column --}}
        <div class="space-y-6">
            @include('livewire.admin.categories._translation-tabs')

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                @include('livewire.admin.categories._form-fields')
            </div>
        </div>

        {{-- Sidebar --}}
        <aside class="space-y-4">
            @include('livewire.admin.categories._cover-image-card')

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                    <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                        <i data-lucide="save" class="h-3.5 w-3.5"></i>
                        Save Changes
                    </h3>
                </div>

                <div class="space-y-2.5 p-4">
                    <button type="button"
                            wire:click="save"
                            wire:loading.attr="disabled"
                            wire:target="save"
                            class="group inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:from-emerald-700 hover:to-emerald-600 disabled:cursor-not-allowed disabled:opacity-60">
                        <i data-lucide="check" class="h-4 w-4" wire:loading.remove wire:target="save"></i>
                        <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="save"></i>
                        <span wire:loading.remove wire:target="save">Save</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>

                    <a href="{{ route('admin.categories.index') }}"
                       wire:navigate
                       class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                        <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                        Back to list
                    </a>
                </div>
            </div>

            {{-- Audit/meta card --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                    <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                        <i data-lucide="info" class="h-3.5 w-3.5"></i>
                        Meta
                    </h3>
                </div>
                <dl class="grid grid-cols-2 gap-y-2 px-5 py-4 text-xs">
                    <dt class="text-slate-500">ID</dt>
                    <dd class="font-mono text-slate-700 dark:text-slate-300">#{{ $category->id }}</dd>

                    <dt class="text-slate-500">Created</dt>
                    <dd class="text-slate-700 dark:text-slate-300">{{ $category->created_at?->diffForHumans() ?? '—' }}</dd>

                    <dt class="text-slate-500">Updated</dt>
                    <dd class="text-slate-700 dark:text-slate-300">{{ $category->updated_at?->diffForHumans() ?? '—' }}</dd>

                    <dt class="text-slate-500">Translations</dt>
                    <dd class="text-slate-700 dark:text-slate-300">{{ count($translations) }}</dd>
                </dl>
            </div>
        </aside>
    </div>

    {{-- Media picker modal --}}
    <livewire:admin.media.media-picker-modal />
</div>
