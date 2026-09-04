@php
    $settings = app(\App\Services\SettingService::class);
    $siteName = (string) ($settings->get('site.name') ?? 'Revision');
    $siteDescription = (string) ($settings->get('site.description') ?? 'Welcome to ultimate source for fresh perspectives! Explore curated content to enlighten, entertain and engage global readers.');

    // Fetch real top categories with post counts
    $popularCategory = \App\Models\Category::query()
        ->withCount(['posts' => fn($q) => $q->where('status', \App\Enums\PostStatus::Published->value)])
        ->having('posts_count', '>', 0)
        ->orderByDesc('posts_count')
        ->take(12)
        ->get();

    // Fetch popular tags
    $popularTags = \App\Models\Tag::query()
        ->withCount('posts')
        ->having('posts_count', '>', 0)
        ->orderByDesc('posts_count')
        ->take(10)
        ->get();

    // Lead author or fallback
    $leadAuthor = \App\Models\User::whereHas('posts')->first() ?? auth()->user();

    // Curated / trending posts for editorial widget
    $curatedStories = $this->trending->isNotEmpty() ? $this->trending->take(3) : $this->latest->take(3);
@endphp

<div class="min-h-screen bg-[#f8f9fa] text-slate-900 transition-colors duration-200 dark:bg-[#0d0e12] dark:text-neutral-100">
    {{-- ───── Breaking news ticker ───── --}}
    @if ($this->breaking->isNotEmpty())
        <div class="border-b border-rose-200/80 bg-rose-50/60 dark:border-rose-500/20 dark:bg-rose-500/10">
            <div class="mx-auto flex max-w-[1360px] items-center gap-3 px-4 py-2 text-sm sm:px-6 lg:px-8">
                <span class="inline-flex shrink-0 items-center gap-1.5 rounded-md bg-rose-600 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.18em] text-white shadow-xs">
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-white"></span>
                    Breaking
                </span>
                <div class="flex flex-1 items-center gap-6 overflow-x-auto scrollbar-hide">
                    @foreach ($this->breaking as $bp)
                        @php
                            $t = $bp->translation() ?? ($bp->translations->firstWhere('language_id', $bp->default_language_id) ?? $bp->translations->first());
                        @endphp
                        @if ($t && !empty($t->slug))
                            <a href="{{ route('frontend.post.show', ['slug' => $t->slug]) }}"
                                class="whitespace-nowrap text-xs font-semibold text-rose-900 transition hover:text-rose-700 hover:underline dark:text-rose-200 dark:hover:text-rose-100">
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

    {{-- ───── Header Hero Statement & Topic Pills (Revision Design Reference) ───── --}}
    <header class="relative mx-auto max-w-4xl px-4 pt-14 pb-12 text-center">
        <h1 class="hero-display-title">
            Heartfelt Reflections: Stories of Love, Loss, and Growth
        </h1>
        <p class="mx-auto mt-4 max-w-2xl text-sm sm:text-base leading-relaxed text-slate-600 dark:text-neutral-400">
            {{ $siteDescription }}
        </p>

        {{-- Explore trending topics --}}
        <div class="mt-8">
            <p class="mb-3.5 text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400 dark:text-neutral-500">
                EXPLORE TRENDING TOPICS
            </p>
            <div class="flex flex-wrap items-center justify-center gap-2 max-w-3xl mx-auto">
                @if ($popularCategory->isNotEmpty())
                    @foreach ($popularCategory->take(8) as $cat)
                        @php
                            $catName = html_entity_decode((string) ($cat->translate('name') ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        @endphp
                        <a href="{{ route('frontend.category', ['slug' => $cat->translate('slug')]) }}" class="topic-pill">
                            @if ($cat->icon)
                                <i data-lucide="{{ $cat->icon }}" class="h-3.5 w-3.5 text-slate-500 dark:text-neutral-400"></i>
                            @else
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            @endif
                            <span>{{ $catName }}</span>
                        </a>
                    @endforeach
                @else
                    <span class="topic-pill"><i data-lucide="laptop" class="h-3.5 w-3.5"></i> Technology</span>
                    <span class="topic-pill"><i data-lucide="plane" class="h-3.5 w-3.5"></i> Travel</span>
                    <span class="topic-pill"><i data-lucide="trophy" class="h-3.5 w-3.5"></i> Sport</span>
                    <span class="topic-pill"><i data-lucide="briefcase" class="h-3.5 w-3.5"></i> Business</span>
                    <span class="topic-pill"><i data-lucide="trending-up" class="h-3.5 w-3.5"></i> Trends</span>
                @endif
            </div>
        </div>
    </header>

    {{-- ───── Main Magazine Stream + Multi-Widget Sidebar ───── --}}
    <section class="mx-auto max-w-[1360px] px-4 py-6 sm:px-6 lg:px-8">
        <div class="magazine-grid">
            
            {{-- Left Column: Main Story Rows --}}
            <div class="min-w-0">
                @if ($this->latest->isEmpty())
                    <div class="rounded-3xl border-2 border-dashed border-slate-200 p-16 text-center dark:border-[#22242c]">
                        <i data-lucide="newspaper" class="mx-auto h-12 w-12 text-slate-300 dark:text-neutral-600"></i>
                        <h3 class="mt-4 text-lg font-bold text-slate-900 dark:text-white">No articles published yet</h3>
                        <p class="mt-1 text-sm text-slate-500">Check back soon for fresh stories.</p>
                    </div>
                @else
                    <div class="space-y-10">
                        @foreach ($this->latest as $post)
                            @php
                                $postT = $post->translation() ?? ($post->translations->firstWhere('language_id', $post->default_language_id) ?? $post->translations->first());
                                $slug = $postT?->slug;
                                $url = $slug ? route('frontend.post.show', ['slug' => $slug]) : null;
                                $feat = $post->featuredImage;
                                $hasImg = $feat && ($feat->isImage() || ($feat->path !== null && $feat->path !== ''));
                                $imgUrl = $hasImg ? $feat->url() : "https://picsum.photos/seed/np{$post->id}/800/500";
                                $postCatName = html_entity_decode((string) ($post->category?->translate('name') ?? 'GENERAL'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                $authorName = $post->author?->name ?? ($leadAuthor?->name ?? 'Author');
                                $postDate = $post->published_at?->format('F d, Y') ?? 'Recent';
                            @endphp

                            @if ($postT && $slug)
                                <article class="story-card group">
                                    {{-- Thumbnail Media Card --}}
                                    <div class="story-card-media">
                                        <a href="{{ $url }}" class="block h-full w-full">
                                            <img src="{{ $imgUrl }}" alt="{{ $postT->title }}" loading="lazy">
                                        </a>
                                        <div class="absolute left-3.5 top-3.5 flex items-center gap-1.5 z-10">
                                            <span class="rounded-md bg-white px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-slate-900 shadow-sm">
                                                {{ $postCatName }}
                                            </span>
                                            @if ($post->is_featured)
                                                <span class="rounded-md bg-amber-500 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-white shadow-sm">
                                                    Featured
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Content Column --}}
                                    <div class="story-card-content">
                                        {{-- Author & Date --}}
                                        <p class="text-xs font-medium text-slate-500 dark:text-neutral-400">
                                            <span class="font-bold text-slate-800 dark:text-neutral-200">{{ $authorName }}</span> on {{ $postDate }}
                                        </p>

                                        {{-- Title --}}
                                        <a href="{{ $url }}" class="mt-2 block">
                                            <h2 class="story-card-title">
                                                {{ $postT->title }}
                                            </h2>
                                        </a>

                                        {{-- Excerpt with diamond bullet --}}
                                        <p class="mt-2.5 text-xs sm:text-sm text-slate-600 dark:text-neutral-400 leading-relaxed line-clamp-2">
                                            <span class="text-neutral-400 mr-1.5 font-bold">✣</span>{{ $postT->excerpt ?: 'Revision offers a unique space blending personal narratives and professional insights to foster real connections.' }}
                                        </p>

                                        {{-- Discover More Button --}}
                                        <div class="mt-4 pt-0.5">
                                            <a href="{{ $url }}" class="discover-btn">
                                                <span>Discover More</span>
                                            </a>
                                        </div>
                                    </div>
                                </article>

                                @if (!$loop->last)
                                    <hr class="border-slate-100 dark:border-[#1e2028]">
                                @endif
                            @endif
                        @endforeach
                    </div>
                @endif

                {{-- Pagination (1 2 ··· 4 >) --}}
                <div class="flex items-center justify-center gap-2 pt-12 pb-6">
                    <span class="grid h-9 w-9 place-items-center rounded-full bg-slate-900 text-xs font-bold text-white dark:bg-white dark:text-slate-900">1</span>
                    <a href="{{ route('frontend.search') }}" class="grid h-9 w-9 place-items-center rounded-full text-xs font-bold text-slate-600 transition hover:bg-slate-200 dark:text-neutral-400 dark:hover:bg-[#20222a]">2</a>
                    <span class="px-1 text-slate-400">···</span>
                    <a href="{{ route('frontend.search') }}" class="grid h-9 w-9 place-items-center rounded-full text-xs font-bold text-slate-600 transition hover:bg-slate-200 dark:text-neutral-400 dark:hover:bg-[#20222a]">4</a>
                    <a href="{{ route('frontend.search') }}" class="grid h-9 w-9 place-items-center rounded-full text-xs font-bold text-slate-600 transition hover:bg-slate-200 dark:text-neutral-400 dark:hover:bg-[#20222a]">
                        <i data-lucide="chevron-right" class="h-4 w-4"></i>
                    </a>
                </div>
            </div>

            {{-- Right Sidebar: 360px sticky/proportional column --}}
            <aside class="space-y-8 min-w-0">
                
                {{-- ───── Widget 1: ABOUT ───── --}}
                <div class="sidebar-widget-card">
                    <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400 dark:text-neutral-500 mb-5">
                        ABOUT
                    </p>
                    <div class="flex items-center gap-3.5">
                        <div class="relative h-12 w-12 shrink-0 overflow-hidden rounded-full ring-2 ring-emerald-500/30 bg-slate-100 dark:bg-[#1f212a]">
                            <img src="{{ $leadAuthor?->avatarUrl() ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150' }}"
                                 alt="{{ $leadAuthor?->name ?? 'Editor' }}"
                                 class="h-full w-full object-cover">
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-slate-900 dark:text-white text-sm truncate">{{ $leadAuthor?->name ?? 'Ethan Caldwell' }}</h4>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">REFLECTIVE BLOGGER</p>
                        </div>
                    </div>
                    <p class="mt-4 text-xs leading-relaxed text-slate-600 dark:text-neutral-400">
                        {{ $leadAuthor?->bio ?: 'Sharing thoughtful insights and reflections on technology, culture, and personal growth. Exploring intersections of creativity and experience.' }}
                    </p>
                    <div class="mt-4 flex items-center gap-1.5 text-xs font-medium text-slate-500 dark:text-neutral-400">
                        <i data-lucide="map-pin" class="h-3.5 w-3.5 text-neutral-400"></i>
                        <span>Global Editorial Team</span>
                    </div>
                    {{-- Social links row --}}
                    <div class="mt-5 flex items-center gap-3 border-t border-slate-100 pt-4 dark:border-[#22242e] text-slate-400 dark:text-neutral-400">
                        <a href="#" class="hover:text-slate-900 dark:hover:text-white transition"><i data-lucide="twitter" class="h-4 w-4"></i></a>
                        <a href="#" class="hover:text-slate-900 dark:hover:text-white transition"><i data-lucide="facebook" class="h-4 w-4"></i></a>
                        <a href="#" class="hover:text-slate-900 dark:hover:text-white transition"><i data-lucide="instagram" class="h-4 w-4"></i></a>
                        <a href="#" class="hover:text-slate-900 dark:hover:text-white transition"><i data-lucide="linkedin" class="h-4 w-4"></i></a>
                    </div>
                </div>

                {{-- ───── Widget 2: FEATURED POSTS ───── --}}
                @if ($this->featured->isNotEmpty())
                    @php
                        $fCard = $this->featured->first();
                        $fcT = $fCard->translation() ?? ($fCard->translations->firstWhere('language_id', $fCard->default_language_id) ?? $fCard->translations->first());
                        $fcSlug = $fcT?->slug;
                        $fcUrl = $fcSlug ? route('frontend.post.show', ['slug' => $fcSlug]) : '#';
                        $fcImg = $fCard->featuredImage?->url() ?? "https://picsum.photos/seed/np{$fCard->id}/600/400";
                        $fcCatName = html_entity_decode((string) ($fCard->category?->translate('name') ?? 'FEATURED'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                    @endphp
                    @if ($fcT && $fcSlug)
                        <div class="sidebar-widget-card">
                            <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400 dark:text-neutral-500 mb-5">
                                FEATURED POSTS
                            </p>
                            <div class="group relative overflow-hidden rounded-2xl bg-slate-100 dark:bg-[#1f212a] aspect-[16/11]">
                                <img src="{{ $fcImg }}" alt="{{ $fcT->title }}" class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent"></div>
                                <div class="absolute left-3.5 top-3.5">
                                    <span class="rounded-md bg-white px-2.5 py-1 text-[9px] font-black uppercase tracking-wider text-slate-900 shadow-sm">
                                        {{ $fcCatName }}
                                    </span>
                                </div>
                                <div class="absolute inset-x-0 bottom-0 p-4">
                                    <p class="text-[10px] font-semibold text-neutral-300">
                                        {{ $fCard->published_at?->format('F d, Y') ?? 'Recent' }}
                                    </p>
                                    <a href="{{ $fcUrl }}">
                                        <h4 class="mt-1 text-sm font-bold text-white leading-snug hover:text-emerald-300 line-clamp-2" style="font-family: 'Playfair Display', serif;">
                                            {{ $fcT->title }}
                                        </h4>
                                    </a>
                                </div>
                            </div>
                            <div class="mt-3.5 flex items-center justify-center gap-1.5">
                                <span class="h-1.5 w-6 rounded-full bg-slate-900 dark:bg-white"></span>
                                <span class="h-1.5 w-1.5 rounded-full bg-slate-300 dark:bg-[#2c2e3a]"></span>
                                <span class="h-1.5 w-1.5 rounded-full bg-slate-300 dark:bg-[#2c2e3a]"></span>
                            </div>
                        </div>
                    @endif
                @endif

                {{-- ───── Widget 3: TOP CATEGORIES ───── --}}
                @if ($popularCategory->isNotEmpty())
                    <div class="sidebar-widget-card">
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400 dark:text-neutral-500 mb-5">
                            TOP CATEGORIES
                        </p>
                        <div class="space-y-3">
                            @foreach ($popularCategory->take(5) as $cat)
                                @php
                                    $cName = html_entity_decode((string) ($cat->translate('name') ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                @endphp
                                <a href="{{ route('frontend.category', ['slug' => $cat->translate('slug')]) }}"
                                   class="flex items-center justify-between py-1.5 text-xs font-semibold text-slate-700 dark:text-neutral-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition group border-b border-slate-100 dark:border-[#20222a] last:border-0">
                                    <span class="flex items-center gap-2">
                                        @if ($cat->icon)
                                            <i data-lucide="{{ $cat->icon }}" class="h-3.5 w-3.5 text-slate-400 group-hover:text-emerald-500"></i>
                                        @else
                                            <span class="h-1.5 w-1.5 rounded-full bg-slate-400 group-hover:bg-emerald-500"></span>
                                        @endif
                                        <span>{{ $cName }}</span>
                                    </span>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500 dark:bg-[#20222c] dark:text-neutral-400">
                                        {{ $cat->posts_count }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- ───── Widget 4: POPULAR TAGS ───── --}}
                @if ($popularTags->isNotEmpty())
                    <div class="sidebar-widget-card">
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400 dark:text-neutral-500 mb-5">
                            POPULAR TOPICS
                        </p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($popularTags as $t)
                                @php
                                    $tagName = html_entity_decode((string) ($t->translate('name') ?? $t->name), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                    $tagSlug = $t->translate('slug') ?? $t->slug;
                                @endphp
                                <a href="{{ route('frontend.tag', ['tag' => $tagSlug]) }}"
                                   class="inline-flex items-center gap-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-[11px] font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-100 dark:border-[#262832] dark:bg-[#181920] dark:text-neutral-300 dark:hover:bg-[#22242e]">
                                    <span>#{{ $tagName }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- ───── Widget 5: CURATED EDITORIAL PICKS ───── --}}
                @if ($curatedStories->isNotEmpty())
                    <div class="sidebar-widget-card">
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400 dark:text-neutral-500 mb-5">
                            EDITORIAL PICKS
                        </p>
                        <div class="space-y-4 text-xs">
                            @foreach ($curatedStories as $cs)
                                @php
                                    $csT = $cs->translation() ?? ($cs->translations->firstWhere('language_id', $cs->default_language_id) ?? $cs->translations->first());
                                    $csSlug = $csT?->slug;
                                    $csUrl = $csSlug ? route('frontend.post.show', ['slug' => $csSlug]) : '#';
                                @endphp
                                @if ($csT && $csSlug)
                                    <div class="@if (!$loop->first) border-t border-slate-100 pt-3.5 dark:border-[#20222a] @endif">
                                        <a href="{{ $csUrl }}" class="group flex items-start justify-between gap-2 font-bold text-slate-900 dark:text-white hover:text-emerald-500 transition">
                                            <span class="line-clamp-2 leading-snug" style="font-family: 'Playfair Display', serif;">{{ $csT->title }}</span>
                                            <i data-lucide="arrow-up-right" class="h-3.5 w-3.5 shrink-0 text-slate-400 dark:text-neutral-500 group-hover:text-emerald-500 transition"></i>
                                        </a>
                                        @if ($csT->excerpt)
                                            <p class="mt-1 text-[11px] text-slate-500 dark:text-neutral-400 line-clamp-2">{{ $csT->excerpt }}</p>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

            </aside>
        </div>
    </section>

    {{-- ───── Centered Newsletter Section (Revision Reference) ───── --}}
    <section class="border-t border-slate-200 py-20 text-center dark:border-[#1d1f27] dark:bg-[#0d0e12]">
        <div class="mx-auto max-w-xl px-4">
            <h2 class="text-3xl font-black tracking-tight text-slate-900 sm:text-4xl dark:text-white"
                style="font-family: 'Playfair Display', serif;">
                Subscribe to our Newsletter
            </h2>
            <p class="mt-3 text-xs sm:text-sm text-slate-600 dark:text-neutral-400">
                Subscribe to our email newsletter to get the latest posts delivered right to your email.
            </p>
            
            <form action="{{ route('frontend.search') }}" method="GET" class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-2 max-w-md mx-auto">
                <input type="email" placeholder="Enter Your Email..." required
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs outline-none focus:border-emerald-500 focus:bg-white dark:border-[#262832] dark:bg-[#16171d] dark:focus:bg-[#1c1e26] dark:text-white">
                <button type="submit"
                    class="w-full sm:w-auto shrink-0 rounded-2xl bg-neutral-900 px-6 py-3 text-xs font-bold text-white shadow-xs transition hover:bg-neutral-800 dark:bg-[#20222a] dark:text-white dark:hover:bg-[#2a2c36] border dark:border-[#2e313d]">
                    Subscribe
                </button>
            </form>

            <p class="mt-4 text-[11px] text-slate-400 dark:text-neutral-500">
                Pure inspiration, zero spam ✨
            </p>
        </div>
    </section>
</div>
