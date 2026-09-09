@php
    $featured = $post->featuredImage;
    $featuredUsable = $featured && ($featured->isImage() || ($featured->path !== null && $featured->path !== ''));
    $placeholderUrl = $featuredUsable ? null : "https://picsum.photos/seed/np{$post->id}/800/600";

    $words = str_word_count(strip_tags((string) $translation->content));
    $readTime = max(1, (int) ceil($words / 225));

    $author = $post->author;
    $authorRawBio = strip_tags((string) ($author?->bio ?? ''));
    $isSpammyBio = preg_match('/(slot|gacor|judi|casino|rtp|togel)/i', $authorRawBio);
    $authorBio = (! $isSpammyBio && strlen($authorRawBio) > 10)
        ? $authorRawBio
        : 'Editorial writer and staff contributor covering industry insights, investigative stories, and breaking developments.';
    $authorRole = $author?->job_title ?: 'Editorial Contributor';
    $authorSocials = is_array($author?->social_links) ? $author->social_links : [];

    $previous = $this->previousPost;
    $next = $this->nextPost;
    $highlights = $this->storyHighlights;
    $inlineRelated = $this->inlineRelatedPosts;
    $featuredSidebar = $this->featuredSidebarPost;
    $authorOtherPosts = $this->authorPosts;
    $popularCategories = $this->popularCategories;
    $popularTags = $this->popularTags;
    $readNextPosts = $this->relatedPosts;
@endphp

<div class="min-h-screen bg-slate-50/50 dark:bg-[#0d0e12] text-slate-900 dark:text-neutral-100 selection:bg-emerald-500 selection:text-white pb-20">
    {{-- Reading progress bar (fixed top) --}}
    <div x-data="{ progress: 0 }" x-init="const update = () => {
        const h = document.documentElement;
        const scrolled = h.scrollTop;
        const max = h.scrollHeight - h.clientHeight;
        progress = max > 0 ? Math.min(100, (scrolled / max) * 100) : 0;
    };
    window.addEventListener('scroll', update, { passive: true });
    update();" class="pointer-events-none fixed inset-x-0 top-0 z-50 h-1">
        <div class="h-full bg-gradient-to-r from-emerald-500 via-teal-400 to-emerald-400 transition-[width] duration-150 shadow-md shadow-emerald-500/30"
            x-bind:style="`width: ${progress}%`"></div>
    </div>

    {{-- Header / Hero Section with Side-by-Side Featured Image (Matching reference PDF Page 1) --}}
    <header class="relative pt-6 pb-10 sm:pt-10 sm:pb-12 border-b border-slate-200/80 dark:border-[#1d1f27]">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            {{-- Breadcrumb Bar --}}
            <nav class="mb-6 flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-neutral-400 overflow-x-auto whitespace-nowrap" aria-label="Breadcrumb">
                <a href="{{ route('frontend.home') }}"
                    class="inline-flex items-center gap-1.5 transition hover:text-emerald-600 dark:hover:text-emerald-400">
                    <i data-lucide="home" class="h-3.5 w-3.5"></i>
                    <span>Home</span>
                </a>
                <i data-lucide="chevron-right" class="h-3 w-3 text-slate-400 dark:text-neutral-600 shrink-0"></i>
                @if ($post->category)
                    <a href="{{ route('frontend.category', ['slug' => $post->category->translate('slug')]) }}"
                        class="inline-flex items-center gap-1 transition hover:text-emerald-600 dark:hover:text-emerald-400 font-semibold text-slate-700 dark:text-neutral-300">
                        @if ($post->category->icon)
                            <i data-lucide="{{ $post->category->icon }}" class="h-3 w-3 text-emerald-500"></i>
                        @endif
                        <span>{{ $post->category->translate('name') }}</span>
                    </a>
                    <i data-lucide="chevron-right" class="h-3 w-3 text-slate-400 dark:text-neutral-600 shrink-0"></i>
                @endif
                <span class="truncate max-w-xs sm:max-w-md text-slate-400 dark:text-neutral-500">{{ $translation->title }}</span>
            </nav>

            {{-- Split Hero Layout: Left Title & Meta + Right Compact Image --}}
            <div class="flex flex-col lg:flex-row items-center gap-8 lg:gap-12">
                {{-- Left Column: Title, Subtitle, and Unified Elegant Meta Bar --}}
                <div class="flex-1 min-w-0 w-full break-words">
                    {{-- Headline --}}
                    <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-[2.65rem] font-black leading-[1.16] tracking-tight text-slate-900 dark:text-white"
                        style="font-family: 'Playfair Display', Georgia, serif;">
                        {{ html_entity_decode(str_replace(["\xc2\xa0", '&nbsp;'], ' ', $translation->title), ENT_QUOTES | ENT_HTML5, 'UTF-8') }}
                    </h1>

                    {{-- Lead Subtitle / Excerpt --}}
                    @if ($translation->excerpt)
                        <p class="mt-4 text-base sm:text-lg text-slate-600 dark:text-neutral-300 leading-relaxed font-normal">
                            {{ $this->renderedExcerpt }}
                        </p>
                    @endif

                    {{-- Unified Elegant Metadata & Category Bar (Below Title & Excerpt) --}}
                    <div class="mt-6 flex flex-wrap items-center gap-3.5 sm:gap-5 border-t border-slate-200/80 dark:border-[#262832] pt-5 text-xs sm:text-sm text-slate-500 dark:text-neutral-400">
                        {{-- Category Badge --}}
                        @if ($post->category)
                            <a href="{{ route('frontend.category', ['slug' => $post->category->translate('slug')]) }}"
                                class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/25 bg-emerald-500/10 px-3 py-1 text-[11px] font-black uppercase tracking-wider text-emerald-700 transition hover:bg-emerald-500/20 dark:text-emerald-400">
                                @if ($post->category->icon)
                                    <i data-lucide="{{ $post->category->icon }}" class="h-3 w-3"></i>
                                @endif
                                {{ $post->category->translate('name') }}
                            </a>
                        @endif

                        @if ($post->is_breaking)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-600 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-white shadow-xs">
                                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-white"></span>
                                Breaking
                            </span>
                        @endif

                        @if ($post->is_featured)
                            <span class="inline-flex items-center gap-1 rounded-full border border-amber-500/25 bg-amber-500/10 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-amber-600 dark:text-amber-400">
                                <i data-lucide="sparkles" class="h-3 w-3 text-amber-500"></i>
                                Featured
                            </span>
                        @endif

                        @if ($post->is_premium)
                            <span class="inline-flex items-center gap-1 rounded-full border border-purple-500/25 bg-purple-500/10 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-purple-700 dark:text-purple-300">
                                <i data-lucide="lock" class="h-3 w-3 text-purple-500"></i>
                                Premium
                            </span>
                        @endif

                        @if ($author)
                            <span class="hidden sm:inline h-4 w-px bg-slate-200 dark:bg-[#262832]"></span>
                            <a href="{{ route('frontend.author', ['user' => $author->id]) }}"
                                class="group flex items-center gap-2">
                                <img src="{{ $author->avatarUrl() }}" alt="{{ $author->name }}"
                                    class="h-7 w-7 rounded-full object-cover ring-1 ring-emerald-500/30 group-hover:ring-emerald-500 transition">
                                <span class="font-bold text-slate-800 transition group-hover:text-emerald-600 dark:text-neutral-200 dark:group-hover:text-emerald-400 text-xs">
                                    {{ $author->name }}
                                </span>
                            </a>
                        @endif

                        <span class="hidden sm:inline h-4 w-px bg-slate-200 dark:bg-[#262832]"></span>

                        {{-- Date & Metrics --}}
                        <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-neutral-400 font-medium">
                            <time datetime="{{ $post->published_at?->toIso8601String() }}" class="flex items-center gap-1">
                                <i data-lucide="calendar" class="h-3.5 w-3.5 text-emerald-500"></i>
                                {{ $post->published_at?->format('M d, Y') }}
                            </time>
                            <span>·</span>
                            <span class="inline-flex items-center gap-1">
                                <i data-lucide="clock" class="h-3.5 w-3.5 text-emerald-500"></i>
                                {{ $readTime }}m read
                            </span>
                            <span>·</span>
                            <span class="inline-flex items-center gap-1">
                                <i data-lucide="eye" class="h-3.5 w-3.5 text-emerald-500"></i>
                                {{ number_format($post->view_count) }} views
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Right Column: Compact, Proportionate Featured Image --}}
                <div class="w-full lg:w-[420px] xl:w-[460px] shrink-0">
                    <figure class="group relative overflow-hidden rounded-3xl border border-slate-200/80 dark:border-[#262832] bg-slate-900 shadow-xl shadow-slate-900/10 dark:shadow-black/50">
                        <img src="{{ $featuredUsable ? $featured->url() : $placeholderUrl }}"
                            alt="{{ $featured?->alt_text ?? $translation->title }}"
                            class="w-full h-[260px] sm:h-[300px] lg:h-[320px] object-cover transition duration-700 group-hover:scale-105"
                            loading="eager">
                        @if ($featured?->caption)
                            <figcaption class="px-4 py-2 text-center text-xs italic text-slate-500 dark:text-neutral-400 bg-white/90 dark:bg-[#111217]/90 backdrop-blur-sm border-t border-slate-200/80 dark:border-[#262832]">
                                {{ $featured->caption }}
                            </figcaption>
                        @endif
                    </figure>
                </div>
            </div>
        </div>
    </header>

    {{-- Main Article Container: Sticky Share Bar + Content + Sidebar (Flex Layout) --}}
    <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-10">
        <div class="flex flex-col lg:flex-row gap-8 xl:gap-12 items-start">

            {{-- Floating Left Social Share Sidebar (on xl+) --}}
            <div class="hidden xl:flex flex-col items-center gap-3.5 sticky top-24 w-12 shrink-0">
                {{-- Read Time Pill --}}
                <div class="flex flex-col items-center rounded-2xl border border-slate-200/80 bg-white px-2 py-2 text-center shadow-xs dark:border-[#262832] dark:bg-[#15161e] w-full">
                    <i data-lucide="clock" class="h-3.5 w-3.5 text-emerald-500 mb-0.5"></i>
                    <span class="text-[10px] font-extrabold uppercase tracking-tight text-slate-700 dark:text-neutral-300">{{ $readTime }}m</span>
                    <span class="text-[8px] text-slate-400">read</span>
                </div>

                <div class="w-6 h-px bg-slate-200 dark:bg-[#262832]"></div>

                {{-- Social Links --}}
                <a href="{{ $this->shareUrl('twitter') }}" target="_blank" rel="noopener"
                    aria-label="Share on X"
                    class="group grid h-10 w-10 place-items-center rounded-xl border border-slate-200/80 bg-white text-slate-600 shadow-xs transition hover:border-sky-500 hover:bg-sky-50 hover:text-sky-600 dark:border-[#262832] dark:bg-[#15161e] dark:text-neutral-300 dark:hover:border-sky-500/50 dark:hover:bg-sky-500/10 dark:hover:text-sky-400">
                    <svg class="h-4 w-4 transition group-hover:scale-110" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                    </svg>
                </a>

                <a href="{{ $this->shareUrl('facebook') }}" target="_blank" rel="noopener"
                    aria-label="Share on Facebook"
                    class="group grid h-10 w-10 place-items-center rounded-xl border border-slate-200/80 bg-white text-slate-600 shadow-xs transition hover:border-blue-500 hover:bg-blue-50 hover:text-blue-600 dark:border-[#262832] dark:bg-[#15161e] dark:text-neutral-300 dark:hover:border-blue-500/50 dark:hover:bg-blue-500/10 dark:hover:text-blue-400">
                    <svg class="h-4 w-4 transition group-hover:scale-110" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                    </svg>
                </a>

                <a href="{{ $this->shareUrl('linkedin') }}" target="_blank" rel="noopener"
                    aria-label="Share on LinkedIn"
                    class="group grid h-10 w-10 place-items-center rounded-xl border border-slate-200/80 bg-white text-slate-600 shadow-xs transition hover:border-blue-600 hover:bg-blue-50 hover:text-blue-700 dark:border-[#262832] dark:bg-[#15161e] dark:text-neutral-300 dark:hover:border-blue-500/50 dark:hover:bg-blue-500/10 dark:hover:text-blue-400">
                    <svg class="h-4 w-4 transition group-hover:scale-110" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                    </svg>
                </a>

                <a href="{{ $this->shareUrl('whatsapp') }}" target="_blank" rel="noopener"
                    aria-label="Share on WhatsApp"
                    class="group grid h-10 w-10 place-items-center rounded-xl border border-slate-200/80 bg-white text-slate-600 shadow-xs transition hover:border-emerald-500 hover:bg-emerald-50 hover:text-emerald-600 dark:border-[#262832] dark:bg-[#15161e] dark:text-neutral-300 dark:hover:border-emerald-500/50 dark:hover:bg-emerald-500/10 dark:hover:text-emerald-400">
                    <svg class="h-4 w-4 transition group-hover:scale-110" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                    </svg>
                </a>

                <button type="button" x-data="{ copied: false }"
                    x-on:click="
                        navigator.clipboard.writeText(window.location.href);
                        copied = true;
                        setTimeout(() => copied = false, 2000);
                    "
                    class="group relative grid h-10 w-10 place-items-center rounded-xl border border-slate-200/80 bg-white text-slate-600 shadow-xs transition hover:border-emerald-500 hover:bg-emerald-50 hover:text-emerald-600 dark:border-[#262832] dark:bg-[#15161e] dark:text-neutral-300 dark:hover:border-emerald-500/50 dark:hover:bg-emerald-500/10 dark:hover:text-emerald-400"
                    title="Copy link">
                    <i data-lucide="link" class="h-4 w-4" x-show="!copied"></i>
                    <i data-lucide="check" class="h-4 w-4 text-emerald-500" x-show="copied" x-cloak></i>
                </button>
            </div>

            {{-- Main Content Column --}}
            <article class="flex-1 min-w-0 w-full overflow-hidden">

                {{-- Story Highlights / Key Takeaways Card (Inspired by reference PDF) --}}
                @if (! empty($highlights))
                    <div class="mb-10 rounded-3xl border border-slate-200/80 bg-gradient-to-br from-slate-50 via-white to-slate-50/50 p-6 sm:p-7 shadow-xs dark:border-[#262832] dark:bg-gradient-to-br dark:from-[#15161f] dark:via-[#111217] dark:to-[#15161f]">
                        <div class="flex items-center justify-between gap-4 pb-4 border-b border-slate-200/80 dark:border-[#262832]">
                            <div class="flex items-center gap-2.5">
                                <span class="grid h-8 w-8 place-items-center rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
                                    <i data-lucide="sparkles" class="h-4 w-4"></i>
                                </span>
                                <div>
                                    <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">
                                        Looking for a Quick Overview?
                                    </h2>
                                    <p class="text-xs text-slate-500 dark:text-neutral-400">Key moments and takeaways from this analysis</p>
                                </div>
                            </div>
                            <span class="hidden sm:inline-flex items-center gap-1 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                                <i data-lucide="zap" class="h-3 w-3"></i>
                                Highlights
                            </span>
                        </div>

                        <div class="mt-5 space-y-3.5">
                            @foreach ($highlights as $index => $item)
                                <div class="group flex items-start gap-3.5 rounded-2xl p-2.5 transition hover:bg-slate-100/60 dark:hover:bg-[#1a1b24]/60">
                                    <span class="grid h-6 w-6 shrink-0 place-items-center rounded-lg bg-emerald-100 text-xs font-black text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300">
                                        {{ $index + 1 }}
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-sm font-bold text-slate-900 group-hover:text-emerald-600 transition dark:text-white dark:group-hover:text-emerald-400">
                                            {{ $item['title'] }}
                                        </h3>
                                        <p class="mt-1 text-xs sm:text-sm text-slate-600 dark:text-neutral-300 leading-relaxed">
                                            {{ $item['text'] }}
                                        </p>
                                    </div>
                                    <i data-lucide="arrow-right" class="h-4 w-4 text-slate-400 transition group-hover:translate-x-1 group-hover:text-emerald-500 shrink-0 mt-1 opacity-0 group-hover:opacity-100"></i>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Paywall Teaser or Full Article Prose --}}
                @if ($this->isPaywalled)
                    <div class="prose prose-lg dark:prose-invert max-w-none text-slate-700 dark:text-neutral-200">
                        <p class="text-lg leading-relaxed">{{ $this->paywallTeaser }}…</p>
                    </div>

                    {{-- Premium Paywall Box --}}
                    <div class="mt-10 overflow-hidden rounded-3xl border border-amber-300/60 bg-gradient-to-br from-amber-50/80 via-white to-orange-50/80 p-8 sm:p-10 text-center shadow-xl dark:border-amber-500/30 dark:from-[#1a1714] dark:via-[#131215] dark:to-[#1a1714]">
                        <span class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-gradient-to-br from-amber-500 to-orange-500 text-white shadow-lg shadow-amber-500/30">
                            <i data-lucide="lock" class="h-7 w-7"></i>
                        </span>
                        <h2 class="mt-5 text-2xl sm:text-3xl font-black tracking-tight text-slate-900 dark:text-white"
                            style="font-family: 'Playfair Display', serif;">
                            Exclusive Subscriber Story
                        </h2>
                        <p class="mx-auto mt-3 max-w-md text-sm sm:text-base text-slate-600 dark:text-neutral-300 leading-relaxed">
                            Subscribe now to unlock this complete story, investigative insights, and our entire premium journalism archive.
                        </p>
                        <div class="mt-7 flex flex-wrap justify-center gap-3.5">
                            @guest
                                <a href="{{ route('login') }}"
                                    class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-[#262832] dark:bg-[#181920] dark:text-neutral-200">
                                    Sign In
                                </a>
                            @endguest
                            <a href="#subscribe"
                                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/25 transition hover:brightness-110 active:scale-95">
                                <i data-lucide="sparkles" class="h-4 w-4"></i>
                                <span>Get Unlimited Access</span>
                            </a>
                        </div>
                    </div>
                @else
                    {{-- Full Content Prose --}}
                    <div class="prose prose-lg dark:prose-invert max-w-none text-slate-700 dark:text-neutral-200
                        prose-headings:font-serif prose-headings:font-black prose-headings:tracking-tight prose-headings:text-slate-900 dark:prose-headings:text-white
                        prose-h2:text-2xl sm:prose-h2:3xl prose-h2:mt-10 prose-h2:mb-4
                        prose-h3:text-xl sm:prose-h3:2xl prose-h3:mt-8 prose-h3:mb-3
                        prose-p:leading-relaxed prose-p:mb-6
                        prose-a:text-emerald-600 dark:prose-a:text-emerald-400 prose-a:font-semibold hover:prose-a:underline
                        prose-blockquote:border-l-4 prose-blockquote:border-emerald-500 prose-blockquote:bg-emerald-50/50 dark:prose-blockquote:bg-emerald-500/5 prose-blockquote:py-3 prose-blockquote:px-6 prose-blockquote:rounded-r-2xl prose-blockquote:font-serif prose-blockquote:text-lg sm:prose-blockquote:text-xl prose-blockquote:not-italic prose-blockquote:text-slate-800 dark:prose-blockquote:text-neutral-200
                        prose-img:rounded-3xl prose-img:border prose-img:border-slate-200/80 dark:prose-img:border-[#262832] prose-img:shadow-xl
                        prose-figure:my-8 prose-figcaption:text-center prose-figcaption:text-xs prose-figcaption:italic prose-figcaption:text-slate-500
                        prose-ul:list-disc prose-ol:list-decimal prose-li:my-1">
                        {!! $this->renderedContent !!}
                    </div>

                    {{-- In-article Ad Zone --}}
                    <x-frontend.ad-zone slot="in_article" class="my-10 flex justify-center" />

                    {{-- In-article "Read Also" Callout (Inspired by reference PDF page 2) --}}
                    @if ($inlineRelated->isNotEmpty())
                        <div class="my-10 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-[#262832] dark:bg-[#14151b]">
                            <div class="flex items-center gap-2 mb-4 pb-3 border-b border-slate-100 dark:border-[#1d1f27]">
                                <i data-lucide="bookmark" class="h-4 w-4 text-emerald-500"></i>
                                <span class="text-xs font-black uppercase tracking-wider text-slate-500 dark:text-neutral-400">Read Also</span>
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                @foreach ($inlineRelated as $inPost)
                                    @php
                                        $inTr = $inPost->translations->first();
                                        $inImg = $inPost->featuredImage;
                                    @endphp
                                    <a href="{{ route('frontend.post.show', ['slug' => $inTr?->slug ?? $inPost->id]) }}"
                                        class="group flex items-center gap-3.5 rounded-2xl p-2 transition hover:bg-slate-50 dark:hover:bg-[#1a1b24]">
                                        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-slate-200 dark:bg-slate-800">
                                            <img src="{{ $inImg?->url() ?? 'https://picsum.photos/seed/np'.$inPost->id.'/200/200' }}"
                                                alt="{{ $inTr?->title }}"
                                                class="h-full w-full object-cover transition group-hover:scale-105">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-xs sm:text-sm font-bold leading-snug text-slate-900 group-hover:text-emerald-600 transition dark:text-white dark:group-hover:text-emerald-400 line-clamp-2">
                                                {{ $inTr?->title }}
                                            </h4>
                                            <span class="mt-1 text-[11px] text-slate-400 flex items-center gap-1">
                                                <i data-lucide="clock" class="h-3 w-3"></i>
                                                {{ $inPost->published_at?->format('M d, Y') }}
                                            </span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif

                {{-- Interactive Visitor Actions (Bookmark, Highlight, Like) --}}
                <div class="mt-10">
                    <livewire:frontend.article-actions :post="$post" :wire:key="'article-actions-'.$post->id" />
                </div>

                {{-- Tag Cloud --}}
                @if ($this->tags->isNotEmpty())
                    <div class="mt-8 flex flex-wrap items-center gap-2 pt-6 border-t border-slate-200/80 dark:border-[#262832]">
                        <span class="mr-2 text-[11px] font-black uppercase tracking-wider text-slate-400 dark:text-neutral-500">Related Tags:</span>
                        @foreach ($this->tags as $tag)
                            <a href="{{ route('frontend.tag', ['tag' => $tag->slug]) }}"
                                class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-3.5 py-1 text-xs font-semibold text-slate-700 transition hover:border-emerald-500 hover:text-emerald-600 dark:border-[#262832] dark:bg-[#15161e] dark:text-neutral-300 dark:hover:border-emerald-500/50 dark:hover:text-emerald-400">
                                #{{ $tag->translate('name') ?? $tag->name }}
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- Mobile Share Row (visible below xl) --}}
                <div class="mt-8 flex xl:hidden flex-wrap items-center gap-2.5 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs dark:border-[#262832] dark:bg-[#14151b]">
                    <span class="mr-1 text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-neutral-400">Share Story:</span>
                    <a href="{{ $this->shareUrl('twitter') }}" target="_blank" rel="noopener"
                        class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-slate-700 transition hover:bg-sky-500 hover:text-white dark:bg-[#1d1f27] dark:text-neutral-200">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                    </a>
                    <a href="{{ $this->shareUrl('facebook') }}" target="_blank" rel="noopener"
                        class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-slate-700 transition hover:bg-blue-600 hover:text-white dark:bg-[#1d1f27] dark:text-neutral-200">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="{{ $this->shareUrl('linkedin') }}" target="_blank" rel="noopener"
                        class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-slate-700 transition hover:bg-blue-700 hover:text-white dark:bg-[#1d1f27] dark:text-neutral-200">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
                    <a href="{{ $this->shareUrl('whatsapp') }}" target="_blank" rel="noopener"
                        class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-slate-700 transition hover:bg-emerald-600 hover:text-white dark:bg-[#1d1f27] dark:text-neutral-200">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    </a>
                </div>

                {{-- Previous / Next Article Dual Split Card (Inspired by reference PDF page 4) --}}
                @if ($previous || $next)
                    <div class="mt-12 grid gap-5 sm:grid-cols-2 pt-8 border-t border-slate-200/80 dark:border-[#262832]">
                        @if ($previous)
                            @php
                                $prevTr = $previous->translations->first();
                            @endphp
                            <a href="{{ route('frontend.post.show', ['slug' => $prevTr?->slug ?? $previous->id]) }}"
                                class="group flex flex-col justify-between rounded-3xl border border-slate-200/80 bg-white p-5 shadow-xs transition hover:border-emerald-500/50 hover:shadow-md dark:border-[#262832] dark:bg-[#14151b] dark:hover:border-emerald-500/30">
                                <div class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-neutral-500">
                                    <i data-lucide="arrow-left" class="h-3.5 w-3.5 transition group-hover:-translate-x-1"></i>
                                    <span>Previous Article</span>
                                </div>
                                <h4 class="mt-3 text-sm sm:text-base font-bold text-slate-900 group-hover:text-emerald-600 transition dark:text-white dark:group-hover:text-emerald-400 line-clamp-2"
                                    style="font-family: 'Playfair Display', serif;">
                                    {{ $prevTr?->title }}
                                </h4>
                                <span class="mt-3 text-xs text-slate-400">
                                    {{ $previous->published_at?->format('M d, Y') }}
                                </span>
                            </a>
                        @else
                            <div></div>
                        @endif

                        @if ($next)
                            @php
                                $nextTr = $next->translations->first();
                            @endphp
                            <a href="{{ route('frontend.post.show', ['slug' => $nextTr?->slug ?? $next->id]) }}"
                                class="group flex flex-col justify-between rounded-3xl border border-slate-200/80 bg-white p-5 text-right shadow-xs transition hover:border-emerald-500/50 hover:shadow-md dark:border-[#262832] dark:bg-[#14151b] dark:hover:border-emerald-500/30">
                                <div class="flex items-center justify-end gap-1.5 text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-neutral-500">
                                    <span>Next Article</span>
                                    <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-1"></i>
                                </div>
                                <h4 class="mt-3 text-sm sm:text-base font-bold text-slate-900 group-hover:text-emerald-600 transition dark:text-white dark:group-hover:text-emerald-400 line-clamp-2"
                                    style="font-family: 'Playfair Display', serif;">
                                    {{ $nextTr?->title }}
                                </h4>
                                <span class="mt-3 text-xs text-slate-400">
                                    {{ $next->published_at?->format('M d, Y') }}
                                </span>
                            </a>
                        @endif
                    </div>
                @endif

                {{-- Reader Comments Section --}}
                @if ($post->allow_comments)
                    <section class="mt-14 pt-8 border-t border-slate-200/80 dark:border-[#262832]">
                        <livewire:frontend.post-comments :post="$post" :wire:key="'comments-'.$post->id" />
                    </section>
                @endif
            </article>

            {{-- Right Sidebar --}}
            <aside class="w-full lg:w-[350px] xl:w-[370px] shrink-0 space-y-8">

                {{-- Author Profile Card (Inspired by reference PDF page 1 "About Author") --}}
                @if ($author)
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-7 shadow-xs dark:border-[#262832] dark:bg-[#14151b]">
                        <div class="mb-4">
                            <span class="text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-neutral-500">About the Author</span>
                        </div>

                        <div class="flex items-center gap-4">
                            <img src="{{ $author->avatarUrl() }}" alt="{{ $author->name }}"
                                class="h-14 w-14 rounded-full object-cover ring-2 ring-emerald-500/30">
                            <div>
                                <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white"
                                    style="font-family: 'Playfair Display', serif;">
                                    {{ $author->name }}
                                </h3>
                                <span class="inline-block rounded-md bg-emerald-50 dark:bg-emerald-500/10 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">
                                    {{ $authorRole }}
                                </span>
                            </div>
                        </div>

                        <p class="mt-4 text-xs sm:text-sm text-slate-600 dark:text-neutral-300 leading-relaxed">
                            {{ $authorBio }}
                        </p>

                        {{-- Social Links --}}
                        @if (! empty($authorSocials))
                            <div class="mt-5 flex items-center gap-2 border-t border-slate-100 pt-4 dark:border-[#1d1f27]">
                                @foreach ($authorSocials as $net => $link)
                                    @if ($link)
                                        <a href="{{ $link }}" target="_blank" rel="noopener"
                                            class="grid h-8 w-8 place-items-center rounded-lg border border-slate-200 bg-slate-50 text-slate-600 transition hover:border-emerald-500 hover:text-emerald-600 dark:border-[#262832] dark:bg-[#1a1b24] dark:text-neutral-300 dark:hover:border-emerald-500/50">
                                            <i data-lucide="{{ $net === 'twitter' || $net === 'x' ? 'twitter' : ($net === 'linkedin' ? 'linkedin' : ($net === 'facebook' ? 'facebook' : 'globe')) }}" class="h-3.5 w-3.5"></i>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        <a href="{{ route('frontend.author', ['user' => $author->id]) }}"
                            class="mt-5 flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200/80 bg-slate-50/80 py-2.5 text-xs font-bold text-slate-700 transition hover:border-emerald-500 hover:bg-emerald-50 hover:text-emerald-700 dark:border-[#262832] dark:bg-[#1a1b24] dark:text-neutral-200 dark:hover:border-emerald-500/30 dark:hover:bg-emerald-500/10 dark:hover:text-emerald-400">
                            <span>View Author's Archive</span>
                            <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                        </a>
                    </div>
                @endif

                {{-- Featured Story Spotlight (Inspired by reference PDF "Featured Posts") --}}
                @if ($featuredSidebar)
                    @php
                        $sideTr = $featuredSidebar->translations->first();
                        $sideImg = $featuredSidebar->featuredImage;
                    @endphp
                    <div class="group relative overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-xs dark:border-[#262832] dark:bg-[#14151b]">
                        <div class="relative h-44 w-full overflow-hidden bg-slate-900">
                            <img src="{{ $sideImg?->url() ?? 'https://picsum.photos/seed/np'.$featuredSidebar->id.'/600/400' }}"
                                alt="{{ $sideTr?->title }}"
                                class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
                            <div class="absolute top-3 left-3">
                                <span class="rounded-full bg-emerald-500/90 px-2.5 py-0.5 text-[10px] font-black uppercase tracking-wider text-white shadow-sm backdrop-blur-sm">
                                    Editor's Pick
                                </span>
                            </div>
                            <div class="absolute bottom-3 left-4 right-4 text-white">
                                <span class="text-[11px] font-medium text-slate-300">
                                    {{ $featuredSidebar->published_at?->format('M d, Y') }}
                                </span>
                                <h4 class="mt-1 text-sm font-bold leading-snug line-clamp-2 group-hover:text-emerald-300 transition"
                                    style="font-family: 'Playfair Display', serif;">
                                    {{ $sideTr?->title }}
                                </h4>
                            </div>
                        </div>
                        <div class="p-3.5">
                            <a href="{{ route('frontend.post.show', ['slug' => $sideTr?->slug ?? $featuredSidebar->id]) }}"
                                class="flex items-center justify-between text-xs font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">
                                <span>Read full story</span>
                                <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-1"></i>
                            </a>
                        </div>
                    </div>
                @endif

                {{-- Explore Topics / Categories --}}
                @if ($popularCategories->isNotEmpty())
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-[#262832] dark:bg-[#14151b]">
                        <div class="mb-4">
                            <span class="text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-neutral-500">Explore Topics</span>
                        </div>
                        <div class="space-y-1">
                            @foreach ($popularCategories as $cat)
                                <a href="{{ route('frontend.category', ['slug' => $cat->translate('slug')]) }}"
                                    class="group flex items-center justify-between rounded-xl px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700 dark:text-neutral-300 dark:hover:bg-emerald-500/10 dark:hover:text-emerald-400">
                                    <span class="flex items-center gap-2">
                                        @if ($cat->icon)
                                            <i data-lucide="{{ $cat->icon }}" class="h-3.5 w-3.5 text-emerald-500"></i>
                                        @endif
                                        <span>{{ $cat->translate('name') }}</span>
                                    </span>
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-500 group-hover:bg-emerald-100 group-hover:text-emerald-800 dark:bg-[#1d1f27] dark:text-neutral-400 dark:group-hover:bg-emerald-500/20 dark:group-hover:text-emerald-300">
                                        {{ $cat->posts_count }}
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Popular Tags --}}
                @if ($popularTags->isNotEmpty())
                    <div class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs dark:border-[#262832] dark:bg-[#14151b]">
                        <div class="mb-4">
                            <span class="text-[11px] font-black uppercase tracking-widest text-slate-400 dark:text-neutral-500">Trending Tags</span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($popularTags as $t)
                                <a href="{{ route('frontend.tag', ['tag' => $t->slug]) }}"
                                    class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] font-semibold text-slate-700 transition hover:border-emerald-500 hover:bg-emerald-50 hover:text-emerald-700 dark:border-[#262832] dark:bg-[#1a1b24] dark:text-neutral-300 dark:hover:border-emerald-500/30 dark:hover:bg-emerald-500/10 dark:hover:text-emerald-400">
                                    #{{ $t->translate('name') ?? $t->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Sidebar Newsletter CTA --}}
                <div class="relative overflow-hidden rounded-3xl border border-emerald-500/30 bg-gradient-to-br from-emerald-600 via-teal-700 to-slate-900 p-6 sm:p-7 text-white shadow-xl shadow-emerald-900/20">
                    <div class="absolute -right-6 -bottom-6 h-32 w-32 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-white/15 backdrop-blur-md">
                        <i data-lucide="mail" class="h-5 w-5"></i>
                    </span>
                    <h3 class="mt-4 text-xl font-bold tracking-tight" style="font-family: 'Playfair Display', serif;">
                        Stay Ahead with {{ config('app.name', 'Rupantrix') }}
                    </h3>
                    <p class="mt-2 text-xs text-white/80 leading-relaxed">
                        Curated briefings, investigative reports, and breakthrough stories delivered right to your inbox.
                    </p>
                    <form action="#" method="POST" class="mt-5 space-y-2.5">
                        @csrf
                        <input type="email" placeholder="Enter your email address..."
                            class="w-full rounded-xl border border-white/20 bg-white/10 px-3.5 py-2.5 text-xs text-white placeholder-white/60 backdrop-blur-md focus:border-white focus:outline-none focus:ring-1 focus:ring-white">
                        <button type="button"
                            class="w-full rounded-xl bg-white py-2.5 text-xs font-bold text-slate-900 shadow-md transition hover:bg-emerald-50 active:scale-98">
                            Subscribe to Daily Brief
                        </button>
                    </form>
                </div>

                {{-- Sidebar Ad Zone --}}
                <x-frontend.ad-zone slot="sidebar_box" class="block" />
            </aside>
        </div>
    </main>

    {{-- Bottom "Read Next" 3-Column Grid (Inspired by reference PDF page 4) --}}
    @if ($readNextPosts->isNotEmpty())
        <section class="mt-20 border-t border-slate-200/80 dark:border-[#262832] bg-white/60 dark:bg-[#111217]/60 py-16">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between mb-10">
                    <div>
                        <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900 dark:text-white"
                            style="font-family: 'Playfair Display', serif;">
                            Read Next
                        </h2>
                    </div>
                    @if ($post->category)
                        <a href="{{ route('frontend.category', ['slug' => $post->category->translate('slug')]) }}"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">
                            <span>More in {{ $post->category->translate('name') }}</span>
                            <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                        </a>
                    @endif
                </div>

                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($readNextPosts as $rnPost)
                        @php
                            $rnTr = $rnPost->translations->first();
                            $rnImg = $rnPost->featuredImage;
                            $rnWords = str_word_count(strip_tags((string) $rnTr?->content));
                            $rnRead = max(1, (int) ceil($rnWords / 225));
                        @endphp
                        <article class="group flex flex-col justify-between overflow-hidden rounded-3xl border border-slate-300/90 bg-white/85 backdrop-blur-md shadow-xs transition hover:-translate-y-1 hover:border-emerald-500/40 hover:shadow-xl dark:border-white/10 dark:bg-[#14151b]/80 dark:hover:border-emerald-500/30">
                            <div>
                                <a href="{{ route('frontend.post.show', ['slug' => $rnTr?->slug ?? $rnPost->id]) }}"
                                    class="block relative h-48 sm:h-52 overflow-hidden bg-slate-900">
                                    <img src="{{ $rnImg?->url() ?? 'https://picsum.photos/seed/np'.$rnPost->id.'/600/375' }}"
                                        alt="{{ $rnTr?->title }}"
                                        class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                        loading="lazy">
                                    @if ($rnPost->category)
                                        <div class="absolute top-3 left-3">
                                            <span class="category-glass-pill">
                                                <span class="pill-dot"></span>
                                                <span>{{ $rnPost->category->translate('name') }}</span>
                                            </span>
                                        </div>
                                    @endif
                                </a>

                                <div class="p-5 sm:p-6">
                                    <div class="flex items-center gap-3 text-xs text-slate-400 dark:text-neutral-400 mb-3">
                                        @if ($rnPost->author)
                                            <span class="font-semibold text-slate-700 dark:text-neutral-300">{{ $rnPost->author->name }}</span>
                                            <span>·</span>
                                        @endif
                                        <span>{{ $rnPost->published_at?->format('M d, Y') }}</span>
                                        <span>·</span>
                                        <span>{{ $rnRead }}m read</span>
                                    </div>

                                    <h3 class="text-base sm:text-lg font-bold leading-snug text-slate-900 group-hover:text-emerald-600 transition dark:text-white dark:group-hover:text-emerald-400 line-clamp-2"
                                        style="font-family: 'Playfair Display', serif;">
                                        <a href="{{ route('frontend.post.show', ['slug' => $rnTr?->slug ?? $rnPost->id]) }}">
                                            {{ $rnTr?->title }}
                                        </a>
                                    </h3>

                                    @if ($rnTr?->excerpt)
                                        <p class="mt-2 text-xs sm:text-sm text-slate-600 dark:text-neutral-400 line-clamp-2">
                                            {{ $rnTr->excerpt }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <div class="px-5 pb-5 sm:px-6 sm:pb-6 pt-0">
                                <a href="{{ route('frontend.post.show', ['slug' => $rnTr?->slug ?? $rnPost->id]) }}"
                                    class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-600 group-hover:text-emerald-700 dark:text-emerald-400">
                                    <span>Read Full Article</span>
                                    <i data-lucide="arrow-right" class="h-3.5 w-3.5 transition group-hover:translate-x-1"></i>
                                </a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>
