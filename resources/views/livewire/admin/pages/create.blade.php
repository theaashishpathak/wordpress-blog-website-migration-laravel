<div class="space-y-6">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('admin.pages.index') }}" wire:navigate class="hover:text-indigo-600">Pages</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">Create</span>
    </nav>

    <div class="grid gap-6 xl:grid-cols-[1fr_320px]">
        {{-- Form column --}}
        <div class="space-y-6">
            @include('livewire.admin.pages._translation-tabs')

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                @include('livewire.admin.pages._form-fields')
            </div>
        </div>

        {{-- Sidebar --}}
        <aside class="space-y-4">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                    <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                        <i data-lucide="settings" class="h-3.5 w-3.5"></i>
                        Page settings
                    </h3>
                </div>
                <div class="space-y-4 p-4">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Status</label>
                        <select wire:model="status"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                            @foreach (\App\Enums\PageStatus::cases() as $s)
                                <option value="{{ $s->value }}">{{ $s->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Template</label>
                        <select wire:model="template"
                                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                            @foreach (\App\Models\Page::TEMPLATES as $t)
                                <option value="{{ $t }}">{{ ucfirst(str_replace('-', ' ', $t)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Sort order</label>
                        <input type="number" wire:model="sortOrder" min="0"
                               class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                    </div>
                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-200">
                        <input type="checkbox" wire:model="showInMenu"
                               class="h-3.5 w-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        Show in main menu
                    </label>
                </div>
            </div>

            <button type="button" wire:click="save"
                    wire:loading.attr="disabled" wire:target="save"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-4 py-3 text-sm font-bold text-white shadow-sm hover:from-indigo-700 hover:to-indigo-600 disabled:opacity-60">
                <i data-lucide="check" class="h-4 w-4" wire:loading.remove wire:target="save"></i>
                <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="save"></i>
                <span wire:loading.remove wire:target="save">Create Page</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </aside>
    </div>
</div>
