@php
    $locale = app(\App\Support\LocaleResolver::class)->current();
@endphp

<div>
    {{-- Hero with search input --}}
    <header class="relative overflow-hidden border-b border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-900">
        <div class="relative mx-auto max-w-4xl px-4 py-12 lg:py-16">
            <nav class="mb-4 flex items-center gap-2 text-xs font-semibold text-slate-500" aria-label="Breadcrumb">
                <a href="{{ route('frontend.home', ['locale' => $locale?->code]) }}" class="transition hover:text-emerald-700">Home</a>
                <i data-lucide="chevron-right" class="h-3 w-3 text-slate-300"></i>
                <span class="text-slate-400">Search</span>
            </nav>

            <h1 class="text-4xl font-black tracking-tight text-slate-900 md:text-5xl dark:text-slate-100"
                style="font-family: 'Playfair Display', serif;">
                Search Articles
            </h1>

            <div class="relative mt-6">
                <i data-lucide="search" class="pointer-events-none absolute left-5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"></i>
                <input type="text" wire:model.live.debounce.400ms="query"
                       placeholder="Type a topic, author, keyword…"
                       autofocus
                       class="w-full rounded-2xl border border-slate-200 bg-white py-4 pl-14 pr-4 text-base shadow-sm outline-none transition focus:border-emerald-500 focus:shadow-lg focus:ring-4 focus:ring-emerald-500/30 dark:border-slate-700 dark:bg-slate-900 dark:focus:ring-emerald-500/20">
                @if (trim($query) !== '')
                    <button type="button" wire:click="$set('query', '')"
                            class="absolute right-4 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                @endif
            </div>

            @if (trim($query) !== '')
                <p class="mt-3 text-sm font-semibold text-slate-600 dark:text-slate-300">
                    {{ $this->results->total() }} {{ \Illuminate\Support\Str::plural('result', $this->results->total()) }} for
                    <span class="text-emerald-700 dark:text-emerald-300">"{{ $query }}"</span>
                </p>
            @endif
        </div>
    </header>

    <section class="mx-auto max-w-7xl px-4 py-10 lg:py-12">
        @if (trim($query) === '')
            <div class="rounded-2xl border-2 border-dashed border-slate-200 p-16 text-center dark:border-slate-700">
                <i data-lucide="search" class="mx-auto h-12 w-12 text-slate-300"></i>
                <h3 class="mt-4 text-lg font-bold text-slate-900 dark:text-slate-100">Start typing to find articles</h3>
                <p class="mt-1 text-sm text-slate-500">Search by title, content, author, or keyword.</p>
            </div>
        @elseif ($this->results->isEmpty())
            <div class="rounded-2xl border-2 border-dashed border-slate-200 p-16 text-center dark:border-slate-700">
                <i data-lucide="search-x" class="mx-auto h-12 w-12 text-slate-300"></i>
                <h3 class="mt-4 text-lg font-bold text-slate-900 dark:text-slate-100">No results found</h3>
                <p class="mt-1 text-sm text-slate-500">Try different keywords or check the spelling.</p>
                <a href="{{ route('frontend.home', ['locale' => $locale?->code]) }}" class="mt-4 inline-flex items-center gap-1.5 text-sm font-bold text-emerald-700 hover:underline">
                    Browse all articles <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                </a>
            </div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->results as $post)
                    <x-frontend.post-card :post="$post" />
                @endforeach
            </div>

            <div class="mt-10">{{ $this->results->onEachSide(1)->links() }}</div>
        @endif
    </section>
</div>
