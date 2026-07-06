@php
    $locale = app(\App\Support\LocaleResolver::class)->current();
    // Popular tags pulled live for sidebar
    $popularTags = \App\Models\Tag::query()
        ->select(['tags.id', 'tags.slug'])
        ->withCount(['posts' => fn($q) => $q->where('status', \App\Enums\PostStatus::Published->value)])
        ->orderByDesc('posts_count')
        ->get();

    $popularCategory = \App\Models\Category::query()
        ->withCount([
            'posts' => fn($query) => $query->where('status', \App\Enums\PostStatus::Published->value),
        ])
        ->having('posts_count', '>', 0)
        ->orderByDesc('posts_count')
        ->get();
@endphp

<div>
    {{-- ───── Breaking news ticker ───── --}}
    @if ($this->breaking->isNotEmpty())
        <div class="border-b border-rose-200 bg-rose-50/60 dark:border-rose-500/30 dark:bg-rose-500/10">
            <div class="mx-auto flex max-w-7xl items-center gap-3 px-4 py-2.5 text-sm">
                <span
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-md bg-rose-600 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-white">
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-white"></span>
                    Heart Breaking
                </span>
                <div class="flex flex-1 items-center gap-6 overflow-x-auto scrollbar-hide">
                    @foreach ($this->breaking as $bp)
                        @php
                            $t =
                                $bp->translation() ??
                                ($bp->translations->firstWhere('language_id', $bp->default_language_id) ??
                                    $bp->translations->first());
                        @endphp
                        @if ($t && !empty($t->slug))
                            <a href="{{ route('frontend.post.show', ['locale' => $locale?->code, 'slug' => $t->slug]) }}"
                                class="whitespace-nowrap text-sm font-semibold text-rose-900 transition hover:text-rose-700 hover:underline dark:text-rose-200 dark:hover:text-rose-100">
                                {{ $t->title }}
                            </a>
                            @if (!$loop->last)
                                <span class="shrink-0 text-rose-500/70 dark:text-rose-500/60">●</span>
                            @endif
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- ───── HERO — editorial split layout, title-first ─────
    Lead headline reads above the fold without scrolling. The right
    column uses compact horizontal cards so all three titles are
    immediately visible. --}}
    @if ($this->featured->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pt-6 pb-12">
            <p class="mb-4 text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">
                Today's edition · {{ now()->translatedFormat('F j, Y') }}
            </p>

            <div class="flex">
                {{-- Lead story — title before the image, newspaper-style.
                    Takes 2/3 of the row on md+, right rail gets 1/3. --}}
                @php
                    $hero = $this->featured->first();
                    // $wpHero = $this->latestWordPressPost;
                    $heroT =
                        $hero->translation() ??
                        ($hero->translations->firstWhere('language_id', $hero->default_language_id) ??
                            $hero->translations->first());
                    $heroSlug = $heroT?->slug;
                    $heroUrl = $heroSlug
                        ? route('frontend.post.show', ['locale' => $locale?->code, 'slug' => $heroSlug])
                        : '#';
                @endphp
                @if ($heroT && $heroSlug)
                    <article class="group md:col-span-2 pb-5">
                        @php
                            // Same defensive check as post-card: if mime_type
                            // wasn't eager-loaded but the image has a path,
// still render it instead of falling back.
$hf = $hero->featuredImage;
$heroHasImage = $hf && ($hf->isImage() || ($hf->path !== null && $hf->path !== ''));
                        @endphp
                        <a href="{{ $heroUrl }}"
                            class="my-5 block aspect-[16/9] overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-800">
                            @if ($heroHasImage)
                                <img src="{{ $hf->url() }}" alt="{{ $hf->alt_text ?? $heroT->title }}"
                                    loading="eager"
                                    class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                            @else
                                <img src="https://picsum.photos/seed/np{{ $hero->id }}/1280/720"
                                    alt="{{ $heroT->title }}" loading="eager"
                                    class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                            @endif
                        </a>
                        <div
                            class="flex flex-wrap items-center gap-2 text-[10px] font-bold uppercase tracking-[0.22em]">

                            @if ($hero->is_breaking)
                                <span
                                    class="inline-flex items-center gap-1 rounded-md bg-rose-600 px-2 py-0.5 text-white">
                                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-white"></span>
                                    Heart Breaking
                                </span>
                            @endif
                            @if ($hero->category)
                                <a href="{{ route('frontend.category', ['locale' => $locale?->code, 'slug' => $hero->category->translate('slug')]) }}"
                                    class="text-slate-600  hover:text-emerald-700 dark:text-slate-400 mt-5 p-2.5">
                                    {{ $hero->category->translate('name') }}
                                </a>
                            @endif
                        </div>

                        <a href="{{ $heroUrl }}">
                            <h2 class="mt-3 text-3xl font-black leading-tight tracking-tight text-slate-900 transition group-hover:text-emerald-700 md:text-4xl lg:text-5xl dark:text-slate-100"
                                style="font-family: 'Playfair Display', serif;">
                                {{ $wpHero?->title ?? $heroT->title }}
                            </h2>
                        </a>

                        @if ($heroT->excerpt)
                            <p class="mt-3  text-sm leading-relaxed text-slate-600 md:text-base dark:text-slate-400">
                                {{ $heroT->excerpt }}
                            </p>
                        @endif

                        <div
                            class="mt-3 flex flex-wrap items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                            @if ($hero->author?->name)
                                <span
                                    class="font-semibold text-slate-700 dark:text-slate-300">{{ $hero->author->name }}</span>
                                <span class="text-slate-300 dark:text-slate-600">·</span>
                            @endif
                            <span>{{ $hero->published_at?->diffForHumans() }}</span>
                        </div>


                    </article>
                @endif

                {{-- Right rail — three featured horizontals, then a
                    compact "More headlines" list to fill the column so
                    the hero image's height doesn't leave dead space. --}}
                <div>
                    <div class="divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach ($this->featured->skip(1)->take(3) as $idx => $f)
                            <div class="{{ $idx === 0 ? 'pb-4' : 'py-4' }}">
                                <x-frontend.post-card :post="$f" size="horizontal" :showExcerpt="false" />
                            </div>
                        @endforeach
                    </div>

                    @php
                        // Use trending posts not already shown as featured.
                        $featuredIds = $this->featured->pluck('id')->all();
                        $moreHeadlines = $this->trending
                            ->reject(fn($p) => in_array($p->id, $featuredIds, true))
                            ->take(5);
                    @endphp
                    @if ($moreHeadlines->isNotEmpty())
                        <div class="mt-6 border-t border-slate-200 pt-5 dark:border-slate-800">
                            <p
                                class="mb-3 text-[10px] font-bold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">
                                More headlines
                            </p>
                            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                                @foreach ($moreHeadlines as $mh)
                                    @php
                                        $mhT =
                                            $mh->translation() ??
                                            ($mh->translations->firstWhere('language_id', $mh->default_language_id) ??
                                                $mh->translations->first());
                                    @endphp
                                    @if ($mhT && !empty($mhT->slug))
                                        <li class="py-3 first:pt-0 last:pb-0">
                                            <a href="{{ route('frontend.post.show', ['locale' => $locale?->code, 'slug' => $mhT->slug]) }}"
                                                class="group block">
                                                @if ($mh->category)
                                                    <p
                                                        class="text-[9px] font-bold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">
                                                        {{ $mh->category->translate('name') }}
                                                    </p>
                                                @endif
                                                <p class="mt-0.5 line-clamp-2 text-sm font-bold leading-snug text-slate-900 group-hover:text-emerald-700 dark:text-slate-100"
                                                    style="font-family: 'Playfair Display', serif;">
                                                    {{ $mhT->title }}
                                                </p>
                                                <p class="mt-1 text-[10px] text-slate-400 dark:text-slate-500">
                                                    {{ $mh->published_at?->diffForHumans() }}
                                                </p>
                                            </a>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- ───── Trending row ───── --}}
    {{-- @if ($this->trending->isNotEmpty())
        <section class="border-y border-slate-200 bg-slate-50/50 dark:border-slate-800 dark:bg-slate-900/30">
            <div class="mx-auto max-w-7xl px-4 py-12">
                <div class="mb-6 flex items-end justify-between gap-3 border-b border-slate-200 pb-3 dark:border-slate-800">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">
                            Trending now</p>
                        <h2 class="mt-1 text-3xl font-black tracking-tight text-slate-900 dark:text-slate-100"
                            style="font-family: 'Playfair Display', serif;">
                            What readers are talking about
                        </h2>
                    </div>
                </div>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($this->trending as $tp)
                        <x-frontend.post-card :post="$tp" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif --}}

    {{-- ───── Editor's pick ───── --}}
    @if ($this->editorsPick->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 ">
            <div class="mb-6 flex items-end justify-between gap-3 border-b border-slate-200 pb-3 dark:border-slate-800">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-emerald-700 dark:text-emerald-300">
                        Editor's pick</p>
                    <h2 class="mt-1 text-3xl font-black tracking-tight text-slate-900 dark:text-slate-100"
                        style="font-family: 'Playfair Display', serif;">
                        Hand-picked, worth your time
                    </h2>
                </div>
            </div>
            <div class="grid gap-5 md:grid-cols-3">
                @foreach ($this->editorsPick as $ep)
                    <x-frontend.post-card :post="$ep" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- ───── Ad zone — homepage middle ───── --}}
    <section class="mx-auto max-w-7xl px-4 pt-4">
        <x-frontend.ad-zone slot="homepage_middle" class="flex justify-center" />
    </section>

    {{-- ───── Latest + sidebar ───── --}}
    <section class="mx-auto max-w-7xl px-4">
        <div class="grid gap-10 lg:grid-cols-[2fr_1fr]">
            {{-- Main column --}}
            <div>
                <div
                    class="mb-6 flex items-end justify-between gap-3 border-b border-slate-200 pb-3 dark:border-slate-800">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">
                            Fresh off the press</p>
                        <h2 class="mt-1 text-3xl font-black tracking-tight text-slate-900 dark:text-slate-100"
                            style="font-family: 'Playfair Display', serif;">
                            Latest articles
                        </h2>
                    </div>
                </div>

                @if ($this->latest->isEmpty())
                    <div
                        class="rounded-2xl border border-dashed border-slate-200 px-6 py-16 text-center dark:border-slate-700">
                        <i data-lucide="newspaper" class="mx-auto h-10 w-10 text-slate-300"></i>
                        <p class="mt-3 text-sm text-slate-500">No posts published yet.</p>
                    </div>
                @else
                    <div class="grid gap-6 sm:grid-cols-2">
                        @foreach ($this->latest as $lp)
                            <x-frontend.post-card :post="$lp" />
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <aside class="space-y-8">
                {{-- Most read --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">Most
                        read</p>
                    <h3 class="mt-1 text-xl font-black tracking-tight text-slate-900 dark:text-slate-100"
                        style="font-family: 'Playfair Display', serif;">
                        The week's standouts
                    </h3>
                    <div class="mt-5 space-y-5">
                        @foreach ($this->trending->take(5) as $i => $tp)
                            @php($t = $tp->translation() ?? ($tp->translations->firstWhere('language_id', $tp->default_language_id) ?? $tp->translations->first()))
                            @continue (!$t || empty($t->slug))
                            <div
                                class="flex gap-3 border-b border-slate-100 pb-5 last:border-b-0 last:pb-0 dark:border-slate-800">
                                <span class="text-2xl font-black leading-none text-slate-300 dark:text-slate-600"
                                    style="font-family: 'Playfair Display', serif;">
                                    {{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('frontend.post.show', ['locale' => $locale?->code, 'slug' => $t->slug]) }}"
                                        class="line-clamp-2 text-sm font-bold leading-snug text-slate-900 transition hover:text-emerald-700 dark:text-slate-100 dark:hover:text-emerald-300"
                                        style="font-family: 'Playfair Display', serif;">
                                        {{ $t->title }}
                                    </a>
                                    <p class="mt-1 text-[10px] text-slate-500">
                                        {{ $tp->published_at?->diffForHumans() }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Newsletter mini-CTA — solid emerald. Built only from
                classes that are present in the production CSS bundle. --}}
                <div class="rounded-2xl bg-emerald-600 p-6 text-white shadow-md">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-emerald-500">
                        <i data-lucide="mail" class="h-5 w-5 text-white"></i>
                    </span>
                    <p class="mt-4 text-[11px] font-bold uppercase tracking-[0.24em] text-white opacity-90">The daily
                        edit</p>
                    <h3 class="mt-1 text-xl font-black leading-tight text-white"
                        style="font-family: 'Playfair Display', serif;">
                        Get the morning brief.
                    </h3>
                    <p class="mt-2 text-xs leading-relaxed text-white opacity-90">
                        Top stories handpicked by our editors, delivered every morning. Five minutes, well spent.
                    </p>
                    <a href="#newsletter"
                        class="mt-4 inline-flex items-center gap-1.5 rounded-md bg-white px-3.5 py-2 text-xs font-bold text-emerald-600 shadow-sm">
                        Subscribe free
                        <i data-lucide="arrow-right" class="h-3 w-3"></i>
                    </a>
                </div>

                {{-- Popular tags --}}
                @if ($popularTags->isNotEmpty())
                    <div
                        class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                        <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">
                            Popular topics</p>
                        <h3 class="mt-1 text-xl font-black tracking-tight text-slate-900 dark:text-slate-100"
                            style="font-family: 'Playfair Display', serif;">
                            Tag cloud
                        </h3>
                        <div class="mt-4 flex flex-wrap gap-1.5">
                            @foreach ($popularTags as $tag)
                                <a href="{{ route('frontend.tag', ['locale' => $locale?->code, 'tag' => $tag->slug]) }}"
                                    class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-700 transition hover:bg-slate-900 hover:text-white dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-100 dark:hover:text-slate-900">
                                    #{{ $tag->slug }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($popularCategory->isNotEmpty())
                    <div
                        class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">

                        <p
                            class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">
                            Popular Categories
                        </p>

                        <h3 class="mt-1 text-xl font-black tracking-tight text-slate-900 dark:text-slate-100"
                            style="font-family:'Playfair Display', serif;">
                            Explore Topics
                        </h3>

                        <div class="mt-4 flex flex-wrap gap-2">

                            @foreach ($popularCategory as $category)
                                <a href="{{ route('frontend.category', [
                                    'locale' => $locale?->code,
                                    'slug' => $category->translate('slug'),
                                ]) }}"
                                    class="inline-flex items-center rounded-full bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-emerald-600 hover:text-white dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-emerald-500">

                                    {{ $category->translate('name') }}

                                    <span class="ml-2 rounded-full bg-white/20 px-2 py-0.5 text-[10px]">
                                        {{ $category->posts_count }}
                                    </span>
                                </a>
                            @endforeach

                        </div>
                    </div>
                @endif

                {{-- Sidebar ad --}}
                <x-frontend.ad-zone slot="sidebar_box" class="block" />
            </aside>
        </div>
    </section>
</div>
