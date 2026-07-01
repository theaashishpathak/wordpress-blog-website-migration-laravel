@php
    $locale = app(\App\Support\LocaleResolver::class)->current();
    $totalArticles = auth()->user()->readingHistory()->count();
    $totalReads = auth()->user()->readingHistory()->sum('read_count');
@endphp

<div class="space-y-8">
    <x-visitor.page-header
        eyebrow="My Library"
        title="Reading History"
        description="{{ $totalArticles }} {{ \Illuminate\Support\Str::plural('article', $totalArticles) }} · {{ number_format($totalReads) }} total {{ \Illuminate\Support\Str::plural('read', $totalReads) }}. Everything you've opened, grouped by day." />

    @if ($this->history->isEmpty())
        <x-visitor.empty-state
            icon="book-open"
            title="Nothing read yet."
            description="Articles you open will appear here automatically, neatly grouped by day.">
            <x-slot:action>
                <a href="{{ route('frontend.home', ['locale' => $locale?->code]) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                    <i data-lucide="newspaper" class="h-4 w-4"></i>
                    Start reading
                </a>
            </x-slot:action>
        </x-visitor.empty-state>
    @else
        <div class="space-y-10">
            @foreach ($grouped as $date => $entries)
                <section>
                    <h2 class="mb-4 flex items-center gap-2 border-b border-slate-200 pb-2 text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400 dark:border-slate-800 dark:text-slate-500">
                        <i data-lucide="calendar" class="h-3 w-3"></i>
                        {{ \Illuminate\Support\Carbon::parse($date)->isToday()
                            ? 'Today'
                            : (\Illuminate\Support\Carbon::parse($date)->isYesterday()
                                ? 'Yesterday'
                                : \Illuminate\Support\Carbon::parse($date)->format('l, F j, Y')) }}
                    </h2>

                    <div class="space-y-3">
                        @foreach ($entries as $h)
                            @php($post = $h->post)
                            @continue (! $post)
                            @php($t = $post->translation() ?? $post->translations->firstWhere('language_id', $post->default_language_id) ?? $post->translations->first())
                            @continue (! $t || empty($t->slug))
                            <article class="group flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-3 transition hover:border-slate-300 hover:shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700">
                                <a href="{{ route('frontend.post.show', ['locale' => $locale?->code, 'slug' => $t->slug]) }}"
                                   class="relative h-16 w-20 shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-800">
                                    @php
                                        $pf = $post->featuredImage;
                                        $pfOk = $pf && ($pf->isImage() || ($pf->path !== null && $pf->path !== ''));
                                    @endphp
                                    @if ($pfOk)
                                        <img src="{{ $pf->url() }}" alt="" loading="lazy" class="h-full w-full object-cover transition group-hover:scale-105">
                                    @else
                                        <img src="https://picsum.photos/seed/np{{ $post->id }}/320/240" alt="" loading="lazy" class="h-full w-full object-cover">
                                    @endif
                                </a>

                                <div class="min-w-0 flex-1">
                                    @if ($post->category)
                                        <p class="text-[9px] font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                            {{ $post->category->translate('name') }}
                                        </p>
                                    @endif
                                    <a href="{{ route('frontend.post.show', ['locale' => $locale?->code, 'slug' => $t->slug]) }}"
                                       class="block">
                                        <h3 class="line-clamp-2 text-sm font-bold leading-snug text-slate-900 transition group-hover:text-emerald-700 dark:text-slate-100 dark:group-hover:text-emerald-300" style="font-family: 'Playfair Display', serif;">
                                            {{ $t->title }}
                                        </h3>
                                    </a>
                                    <p class="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                                        <span class="inline-flex items-center gap-1"><i data-lucide="clock" class="h-3 w-3"></i> {{ $h->last_read_at?->diffForHumans() }}</span>
                                        @if ($h->read_count > 1)
                                            <span class="text-slate-300">·</span>
                                            <span class="inline-flex items-center gap-1"><i data-lucide="rotate-cw" class="h-3 w-3"></i> Read {{ $h->read_count }}×</span>
                                        @endif
                                        @if ($h->completed)
                                            <span class="text-slate-300">·</span>
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-[0.18em] text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                                <i data-lucide="check" class="h-2.5 w-2.5"></i>
                                                Finished
                                            </span>
                                        @endif
                                    </p>
                                </div>

                                <button type="button" wire:click="clear({{ $post->id }})"
                                        wire:loading.attr="disabled" wire:target="clear"
                                        class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-300"
                                        title="Remove from history">
                                    <i data-lucide="x" class="h-4 w-4"></i>
                                </button>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>

        <div class="mt-6">{{ $this->history->onEachSide(1)->links() }}</div>
    @endif
</div>
