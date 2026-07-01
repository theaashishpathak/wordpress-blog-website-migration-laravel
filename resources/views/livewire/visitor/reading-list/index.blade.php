@php
    $locale = app(\App\Support\LocaleResolver::class)->current();
    $activeCount = auth()->user()->readingListItems()->active()->count();
    $dismissedCount = auth()->user()->readingListItems()->dismissed()->count();
@endphp

<div class="space-y-8">
    <x-visitor.page-header
        eyebrow="My Library"
        title="Reading List"
        description="A queue of articles to come back to. Bookmarks are for keeps — the queue is for now.">
        <x-slot:actions>
            <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1 dark:border-slate-800 dark:bg-slate-900">
                <button type="button" wire:click="switchFilter('active')"
                        class="rounded-md px-3 py-1.5 text-xs font-bold transition {{ $filter === 'active' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-950 dark:text-slate-100' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
                    Queue <span class="ml-1 text-slate-400">{{ $activeCount }}</span>
                </button>
                <button type="button" wire:click="switchFilter('dismissed')"
                        class="rounded-md px-3 py-1.5 text-xs font-bold transition {{ $filter === 'dismissed' ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-950 dark:text-slate-100' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
                    Archived <span class="ml-1 text-slate-400">{{ $dismissedCount }}</span>
                </button>
            </div>
        </x-slot:actions>
    </x-visitor.page-header>

    @if ($this->items->isEmpty())
        <x-visitor.empty-state
            icon="clock"
            title="{{ $filter === 'dismissed' ? 'Nothing archived yet.' : 'Your queue is empty.' }}"
            description="{{ $filter === 'dismissed'
                ? 'Items you dismiss from the queue will land here for safekeeping.'
                : 'Tap “Read Later” on any article to drop it into the queue.' }}">
            <x-slot:action>
                <a href="{{ route('frontend.home', ['locale' => $locale?->code]) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                    <i data-lucide="newspaper" class="h-4 w-4"></i>
                    Browse articles
                </a>
            </x-slot:action>
        </x-visitor.empty-state>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->items as $item)
                @php($post = $item->post)
                @continue (! $post)
                <div class="group relative">
                    <x-frontend.post-card :post="$post" />

                    @if ($filter === 'active')
                        <button type="button" wire:click="dismiss({{ $post->id }})"
                                wire:loading.attr="disabled" wire:target="dismiss"
                                class="absolute right-3 top-3 z-10 grid h-9 w-9 place-items-center rounded-lg bg-white/95 text-slate-600 ring-1 ring-slate-200 backdrop-blur transition hover:bg-rose-50 hover:text-rose-600 hover:ring-rose-200 dark:bg-slate-900/95 dark:text-slate-300 dark:ring-slate-700"
                                title="Dismiss from queue">
                            <i data-lucide="x" class="h-4 w-4"></i>
                        </button>
                        <span class="absolute left-3 top-3 z-10 rounded-full bg-white/95 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-700 ring-1 ring-emerald-200 backdrop-blur dark:bg-slate-900/95 dark:text-emerald-300 dark:ring-emerald-500/30">
                            Queued {{ $item->added_at?->diffForHumans() }}
                        </span>
                    @else
                        <button type="button" wire:click="restore({{ $post->id }})"
                                wire:loading.attr="disabled" wire:target="restore"
                                class="absolute right-3 top-3 z-10 inline-flex items-center gap-1.5 rounded-lg bg-white/95 px-3 py-1.5 text-[11px] font-bold text-emerald-700 ring-1 ring-emerald-200 backdrop-blur transition hover:bg-emerald-50 dark:bg-slate-900/95 dark:text-emerald-300 dark:ring-emerald-500/30"
                                title="Restore to queue">
                            <i data-lucide="undo-2" class="h-3 w-3"></i>
                            Restore
                        </button>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-6">{{ $this->items->onEachSide(1)->links() }}</div>
    @endif
</div>
