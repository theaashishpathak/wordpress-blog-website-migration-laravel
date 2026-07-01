@props([
    'id',
    'title' => 'Filters',
    'width' => 'max-w-md',
])

<div data-modal="{{ $id }}" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="{{ $id }}"></div>

    <div class="absolute right-0 top-0 flex h-full w-full {{ $width }} translate-x-full transform flex-col bg-white shadow-2xl transition-transform duration-300 dark:bg-slate-900" data-modal-panel>
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
            <h2 class="text-lg font-semibold">{{ $title }}</h2>
            <button type="button" class="rounded-xl p-2 hover:bg-slate-100 dark:hover:bg-slate-800" data-modal-close="{{ $id }}">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-6">
            {{ $slot }}
        </div>
    </div>
</div>

