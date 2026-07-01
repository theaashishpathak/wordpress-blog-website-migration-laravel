@php
    $locale = app(\App\Support\LocaleResolver::class)->current();
    $social = is_array($author->social_links) ? $author->social_links : [];
    $socialIcons = [
        'twitter' => 'twitter',
        'facebook' => 'facebook',
        'linkedin' => 'linkedin',
        'instagram' => 'instagram',
        'youtube' => 'youtube',
        'website' => 'globe',
    ];
@endphp

<div>
    {{-- Hero profile banner --}}
    <header class="relative overflow-hidden border-b border-slate-200 dark:border-slate-800">
        <div class="absolute inset-0 bg-slate-900"></div>
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_25%_25%,rgba(255,255,255,0.18)_0%,transparent_50%)]"></div>
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_75%_75%,rgba(255,255,255,0.1)_0%,transparent_50%)]"></div>

        <div class="relative mx-auto max-w-4xl px-4 py-14 lg:py-20">
            <nav class="mb-6 flex items-center gap-2 text-xs font-semibold text-white/80" aria-label="Breadcrumb">
                <a href="{{ route('frontend.home', ['locale' => $locale?->code]) }}" class="transition hover:text-white">Home</a>
                <i data-lucide="chevron-right" class="h-3 w-3 text-white/60"></i>
                <span class="text-white/60">Author</span>
            </nav>

            <div class="flex flex-col items-center gap-5 text-center">
                <span class="grid h-24 w-24 place-items-center rounded-full bg-white text-3xl font-black uppercase text-emerald-700 ring-4 ring-white/30 shadow-2xl">
                    {{ mb_substr($author->name, 0, 1) }}
                </span>
                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-white/80">Author</p>
                <h1 class="text-4xl font-black tracking-tight text-white md:text-5xl"
                    style="font-family: 'Playfair Display', serif;">
                    {{ $author->name }}
                </h1>

                @if ($author->bio)
                    <p class="max-w-2xl text-base leading-relaxed text-white/90">{{ $author->bio }}</p>
                @endif

                @if (! empty($social))
                    <div class="flex flex-wrap items-center justify-center gap-2">
                        @foreach ($socialIcons as $platform => $icon)
                            @if (! empty($social[$platform]))
                                @php
                                    $href = $social[$platform];
                                    if (! str_starts_with($href, 'http') && $platform !== 'website') {
                                        $handle = ltrim($href, '@');
                                        $href = match ($platform) {
                                            'twitter' => "https://twitter.com/{$handle}",
                                            'facebook' => "https://facebook.com/{$handle}",
                                            'linkedin' => "https://linkedin.com/in/{$handle}",
                                            'instagram' => "https://instagram.com/{$handle}",
                                            'youtube' => "https://youtube.com/@{$handle}",
                                            default => $href,
                                        };
                                    }
                                @endphp
                                <a href="{{ $href }}" target="_blank" rel="noopener"
                                   aria-label="{{ $platform }}"
                                   class="grid h-10 w-10 place-items-center rounded-xl bg-white/15 text-white ring-1 ring-white/25 backdrop-blur-sm transition hover:scale-110 hover:bg-white hover:text-emerald-700">
                                    <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif

                <div class="flex flex-wrap items-center justify-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1.5 font-bold text-white ring-1 ring-white/25 backdrop-blur-sm">
                        <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                        {{ $this->posts->total() }} {{ \Illuminate\Support\Str::plural('article', $this->posts->total()) }}
                    </span>
                    <span class="rounded-full bg-white px-3 py-1.5 shadow-sm">
                        <livewire:frontend.follow-button targetType="author" :targetId="$author->id" :wire:key="'follow-author-'.$author->id" />
                    </span>
                </div>
            </div>
        </div>
    </header>

    {{-- Author's posts --}}
    <section class="mx-auto max-w-7xl px-4 py-12">
        <div class="mb-6 flex items-end justify-between gap-3 border-b-2 border-slate-900 pb-3 dark:border-slate-100">
            <h2 class="text-xl font-black tracking-tight text-slate-900 dark:text-slate-100" style="font-family: 'Playfair Display', serif;">
                Latest from {{ $author->name }}
            </h2>
        </div>

        @if ($this->posts->isEmpty())
            <div class="rounded-2xl border-2 border-dashed border-slate-200 p-16 text-center dark:border-slate-700">
                <i data-lucide="user" class="mx-auto h-12 w-12 text-slate-300"></i>
                <h3 class="mt-4 text-lg font-bold text-slate-900 dark:text-slate-100">No articles yet</h3>
                <p class="mt-1 text-sm text-slate-500">This author hasn't published any articles yet.</p>
            </div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->posts as $post)
                    <x-frontend.post-card :post="$post" />
                @endforeach
            </div>

            <div class="mt-10">{{ $this->posts->onEachSide(1)->links() }}</div>
        @endif
    </section>
</div>
