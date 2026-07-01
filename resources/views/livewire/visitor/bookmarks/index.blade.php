@php
    $locale = app(\App\Support\LocaleResolver::class)->current();
    $total = $this->bookmarks->total();
@endphp

<div class="space-y-8">
    <x-visitor.page-header
        eyebrow="My Library"
        title="Bookmarks"
        description="{{ $total }} saved {{ \Illuminate\Support\Str::plural('article', $total) }}. Tap the bookmark icon on any article to keep it here forever.">
        <x-slot:actions>
            <div class="relative w-full sm:w-80">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search bookmarks…"
                       class="w-full rounded-lg border border-slate-200 bg-white py-2 pl-10 pr-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-900">
            </div>
        </x-slot:actions>
    </x-visitor.page-header>

    @if ($this->bookmarks->isEmpty())
        @php
            $emptyTitle = $search !== '' ? 'No bookmarks match “'.$search.'”' : 'No bookmarks yet.';
        @endphp
        <x-visitor.empty-state
            icon="bookmark"
            :title="$emptyTitle"
            description="Tap the bookmark icon on any article to save it for later — they'll all gather here.">
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
            @foreach ($this->bookmarks as $bookmark)
                @php($post = $bookmark->post)
                @continue (! $post)
                <div class="group relative">
                    <x-frontend.post-card :post="$post" />
                    <button type="button" wire:click="unbookmark({{ $post->id }})"
                            wire:loading.attr="disabled" wire:target="unbookmark"
                            class="absolute right-3 top-3 z-10 grid h-9 w-9 place-items-center rounded-lg bg-white/95 text-slate-700 ring-1 ring-slate-200 backdrop-blur transition hover:bg-rose-50 hover:text-rose-600 hover:ring-rose-200 dark:bg-slate-900/95 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-rose-500/10 dark:hover:text-rose-300"
                            title="Remove bookmark">
                        <i data-lucide="bookmark-x" class="h-4 w-4"></i>
                    </button>
                    <span class="absolute left-3 top-3 z-10 rounded-full bg-white/95 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-600 ring-1 ring-slate-200 backdrop-blur dark:bg-slate-900/95 dark:text-slate-300 dark:ring-slate-700">
                        Saved {{ $bookmark->created_at?->diffForHumans() }}
                    </span>
                </div>
            @endforeach
        </div>

        <div class="mt-6">{{ $this->bookmarks->onEachSide(1)->links() }}</div>
    @endif
</div>
