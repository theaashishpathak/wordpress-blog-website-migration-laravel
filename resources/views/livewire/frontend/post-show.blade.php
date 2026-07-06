@php
    $locale = app(\App\Support\LocaleResolver::class)->current();
    $featured = $post->featuredImage;

    // "Usable" if Media::isImage() approves, or — for legacy rows / partial
    // eager-loads that drop mime_type — if the row still has a real path.
    $featuredUsable = $featured && ($featured->isImage() || ($featured->path !== null && $featured->path !== ''));

    // Deterministic free placeholder (Lorem Picsum) when no real image is
    // uploaded — sized for the article hero.
    $placeholderUrl = $featuredUsable ? null : "https://picsum.photos/seed/np{$post->id}/1600/900";

    $words = str_word_count(strip_tags((string) $translation->content));
    $readTime = max(1, (int) ceil($words / 225));

    // Popular tags
    $popularTags = \App\Models\Tag::query()
        ->select(['tags.id', 'tags.slug'])
        ->withCount(['posts' => fn($q) => $q->where('status', \App\Enums\PostStatus::Published->value)])
        ->orderByDesc('posts_count')
        ->limit(15)
        ->get();

    $popularCategory = \App\Models\Category::query()
        ->withCount([
            'posts' => fn($query) => $query->where('status', \App\Enums\PostStatus::Published->value),
        ])
        ->having('posts_count', '>', 0)
        ->orderByDesc('posts_count')
        ->limit(12)
        ->get();
@endphp

<div>
    {{-- Reading progress bar (fixed top) --}}
    <div x-data="{ progress: 0 }" x-init="const update = () => {
        const h = document.documentElement;
        const scrolled = h.scrollTop;
        const max = h.scrollHeight - h.clientHeight;
        progress = max > 0 ? Math.min(100, (scrolled / max) * 100) : 0;
    };
    window.addEventListener('scroll', update, { passive: true });
    update();" class="pointer-events-none fixed inset-x-0 top-0 z-50 h-1">
        <div class="h-full bg-gradient-to-r from-emerald-500 to-emerald-400 transition-[width] duration-100 shadow-lg shadow-emerald-500/30"
            x-bind:style="`width: ${progress}%`"></div>
    </div>

    {{-- Hero Section with Featured Image --}}
    <div class="relative bg-gradient-to-b from-slate-50 to-white dark:from-slate-950 dark:to-slate-900">
        <div class="mx-auto max-w-7xl px-4 pt-8 pb-4 lg:pt-12">
            {{-- Breadcrumb --}}
            <nav class="mb-6 flex items-center gap-2 text-sm font-medium text-slate-500" aria-label="Breadcrumb">
                <a href="{{ route('frontend.home', ['locale' => $locale?->code]) }}"
                    class="transition hover:text-emerald-600 dark:hover:text-emerald-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                </a>
                <svg class="h-3 w-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
                @if ($post->category)
                    <a href="{{ route('frontend.category', ['locale' => $locale?->code, 'slug' => $post->category->translate('slug')]) }}"
                        class="transition hover:text-emerald-600 dark:hover:text-emerald-400">
                        {{ $post->category->translate('name') }}
                    </a>
                    <svg class="h-3 w-3 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                @endif
                <span
                    class="truncate text-slate-400">{{ \Illuminate\Support\Str::limit($translation->title, 40) }}</span>
            </nav>

            {{-- Category pill + badges --}}
            <div class="mb-5 flex flex-wrap items-center gap-2.5">
                @if ($post->is_breaking)
                    <span
                        class="inline-flex items-center gap-2 rounded-full bg-rose-600 px-3.5 py-1.5 text-[11px] font-black uppercase tracking-wider text-white shadow-lg shadow-rose-600/30">
                        <span class="h-2 w-2 animate-pulse rounded-full bg-white"></span>
                        Breaking
                    </span>
                @endif
                @if ($post->category)
                    <a href="{{ route('frontend.category', ['locale' => $locale?->code, 'slug' => $post->category->translate('slug')]) }}"
                        class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3.5 py-1.5 text-[11px] font-black uppercase tracking-wider text-emerald-700 transition hover:bg-emerald-200 dark:bg-emerald-500/20 dark:text-emerald-300 dark:hover:bg-emerald-500/30">
                        @if ($post->category->icon)
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                        @endif
                        {{ $post->category->translate('name') }}
                    </a>
                @endif
                @if ($post->is_featured)
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-3.5 py-1.5 text-[11px] font-black uppercase tracking-wider text-amber-800 dark:bg-amber-500/20 dark:text-amber-200">
                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        Featured
                    </span>
                @endif
            </div>

            {{-- Title --}}
            <header class="mb-6 max-w-7xl">
                <h1 class="text-4xl font-black leading-[1.1] tracking-tight text-slate-900 md:text-5xl lg:text-[3.5rem] dark:text-slate-50"
                    style="font-family: 'Playfair Display', serif;">
                    {{ $translation->title }}
                </h1>
            </header>


            {{-- Featured Image - Full width hero --}}
            <div class="mx-auto max-w-7xl px-4 pb-6">
                <figure class="overflow-hidden rounded-3xl shadow-2xl shadow-slate-900/20 dark:shadow-black/40">
                    @if ($featuredUsable)
                        <img src="{{ $featured->url() }}" alt="{{ $featured->alt_text ?? $translation->title }}"
                            class="w-full object-cover max-h-[600px]" loading="eager">
                        @if ($featured->caption)
                            <figcaption
                                class="mt-3 px-6 pb-4 text-center text-sm italic text-slate-500 dark:text-slate-400">
                                {{ $featured->caption }}
                            </figcaption>
                        @endif
                    @else
                        <img src="{{ $placeholderUrl }}" alt="{{ $translation->title }}"
                            class="w-full object-cover max-h-[600px]" loading="eager">
                    @endif
                </figure>
            </div>



            {{-- Byline --}}
            <div class="mt-6 flex flex-wrap items-center gap-4 border-t border-slate-200 pt-6 dark:border-slate-800">
                @if ($post->author)
                    <a href="{{ route('frontend.author', ['locale' => $locale?->code, 'user' => $post->author->id]) }}"
                        class="group flex items-center gap-3">
                        <span
                            class="grid h-11 w-11 place-items-center rounded-full bg-gradient-to-br from-slate-700 to-slate-900 text-sm font-black uppercase text-white ring-2 ring-white shadow-lg transition group-hover:scale-105 dark:ring-slate-800">
                            {{ mb_substr($post->author->name, 0, 1) }}
                        </span>
                        <span class="flex flex-col leading-tight">
                            <span
                                class="text-sm font-bold text-slate-900 transition group-hover:text-emerald-600 dark:text-slate-100 dark:group-hover:text-emerald-400">
                                {{ $post->author->name }}
                            </span>
                            <span class="text-xs text-slate-500">Author</span>
                        </span>
                    </a>
                @endif

                <span class="h-8 w-px bg-slate-200 dark:bg-slate-700"></span>

                <div class="flex flex-wrap items-center gap-4 text-sm font-medium text-slate-500">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ $post->published_at?->format('M j, Y') }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ $readTime }} min read
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        {{ number_format($post->view_count) }} views
                    </span>
                </div>
            </div>
        </div>



        {{-- Main Content with Sidebar --}}
        <div class="mx-auto max-w-7xl px-4 py-8 lg:py-12">
            <div class="grid gap-10 lg:grid-cols-[2fr_1fr]">
                {{-- Article Content --}}
                <div>
                    @if ($this->isPaywalled)
                        <div class="prose prose-lg max-w-none dark:prose-invert">
                            <p>{{ $this->paywallTeaser }}…</p>
                        </div>

                        <div
                            class="mt-8 overflow-hidden rounded-3xl border border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50 p-8 text-center shadow-xl dark:border-amber-500/30 dark:from-amber-500/10 dark:to-orange-500/10">
                            <span
                                class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-gradient-to-br from-amber-500 to-orange-500 text-white shadow-lg shadow-amber-500/30">
                                <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </span>
                            <h2 class="mt-4 text-2xl font-black tracking-tight"
                                style="font-family: 'Playfair Display', serif;">
                                This article is for subscribers
                            </h2>
                            <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                                Unlock the rest of this article plus our full archive of premium content.
                            </p>
                            <div class="mt-6 flex flex-wrap justify-center gap-3">
                                @guest
                                    <a href="{{ route('login') }}"
                                        class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                        Sign in
                                    </a>
                                @endguest
                                <a href="#"
                                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-600 to-orange-600 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/30 transition hover:from-amber-700 hover:to-orange-700 hover:shadow-xl active:scale-95">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    Subscribe
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="prose prose-lg max-w-none dark:prose-invert">
                            {!! $translation->content !!}
                        </div>

                        <x-frontend.ad-zone slot="in_article" class="my-10 flex justify-center" />
                    @endif

                    {{-- Visitor actions — bookmark, read later, highlight capture --}}
                    <livewire:frontend.article-actions :post="$post" :wire:key="'article-actions-'.$post->id" />

                    {{-- Tags --}}
                    @if ($this->tags->isNotEmpty())
                        <div class="mt-10 flex flex-wrap items-center gap-2">
                            <span
                                class="mr-2 text-[11px] font-black uppercase tracking-wider text-slate-400">Tags</span>
                            @foreach ($this->tags as $tag)
                                <a href="{{ route('frontend.tag', ['locale' => $locale?->code, 'tag' => $tag->slug]) }}"
                                    class="rounded-full bg-slate-100 px-3.5 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-emerald-100 hover:text-emerald-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-emerald-500/20 dark:hover:text-emerald-300">
                                    #{{ $tag->translate('name') ?? $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Share row --}}
                    <div
                        class="mt-8 flex flex-wrap items-center gap-2.5 rounded-2xl border border-slate-200 bg-slate-50/80 px-4 py-3 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900/50">
                        <span class="mr-1 text-[11px] font-black uppercase tracking-wider text-slate-500">Share</span>
                        <a href="{{ $this->shareUrl('twitter') }}" target="_blank" rel="noopener"
                            aria-label="Share on Twitter"
                            class="group grid h-9 w-9 place-items-center rounded-lg bg-white text-slate-700 ring-1 ring-slate-200 transition hover:bg-sky-50 hover:text-sky-600 hover:ring-sky-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                            <svg class="h-4 w-4 transition-transform group-hover:scale-110" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                            </svg>
                        </a>
                        <a href="{{ $this->shareUrl('facebook') }}" target="_blank" rel="noopener"
                            aria-label="Share on Facebook"
                            class="group grid h-9 w-9 place-items-center rounded-lg bg-white text-slate-700 ring-1 ring-slate-200 transition hover:bg-blue-50 hover:text-blue-600 hover:ring-blue-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                            <svg class="h-4 w-4 transition-transform group-hover:scale-110" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                            </svg>
                        </a>
                        <a href="{{ $this->shareUrl('linkedin') }}" target="_blank" rel="noopener"
                            aria-label="Share on LinkedIn"
                            class="group grid h-9 w-9 place-items-center rounded-lg bg-white text-slate-700 ring-1 ring-slate-200 transition hover:bg-blue-50 hover:text-blue-700 hover:ring-blue-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                            <svg class="h-4 w-4 transition-transform group-hover:scale-110" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                            </svg>
                        </a>
                        <a href="{{ $this->shareUrl('whatsapp') }}" target="_blank" rel="noopener"
                            aria-label="Share on WhatsApp"
                            class="group grid h-9 w-9 place-items-center rounded-lg bg-white text-slate-700 ring-1 ring-slate-200 transition hover:bg-emerald-50 hover:text-emerald-600 hover:ring-emerald-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                            <svg class="h-4 w-4 transition-transform group-hover:scale-110" fill="currentColor"
                                viewBox="0 0 24 24">
                                <path
                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                            </svg>
                        </a>

                        <span class="ml-auto"></span>

                        <button type="button" x-data
                            x-on:click="
                                navigator.clipboard.writeText(window.location.href);
                                $el.querySelector('span').textContent = 'Copied!';
                                setTimeout(() => $el.querySelector('span').textContent = 'Copy link', 1500);
                            "
                            class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3.5 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                            <span>Copy link</span>
                        </button>
                    </div>

                    {{-- Author bio card --}}
                    @if ($post->author)
                        <section
                            class="mt-10 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-start gap-5">
                                <a href="{{ route('frontend.author', ['locale' => $locale?->code, 'user' => $post->author->id]) }}"
                                    class="grid h-16 w-16 shrink-0 place-items-center rounded-full bg-gradient-to-br from-slate-700 to-slate-900 text-xl font-black uppercase text-white ring-2 ring-white shadow-lg transition hover:scale-105 dark:ring-slate-800">
                                    {{ mb_substr($post->author->name, 0, 1) }}
                                </a>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Written by
                                    </p>
                                    <a href="{{ route('frontend.author', ['locale' => $locale?->code, 'user' => $post->author->id]) }}"
                                        class="text-xl font-black tracking-tight text-slate-900 hover:text-emerald-600 dark:text-slate-100 dark:hover:text-emerald-400"
                                        style="font-family: 'Playfair Display', serif;">
                                        {{ $post->author->name }}
                                    </a>
                                    @if ($post->author->bio ?? null)
                                        <p class="mt-1 text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                                            {{ $post->author->bio }}</p>
                                    @endif
                                    <a href="{{ route('frontend.author', ['locale' => $locale?->code, 'user' => $post->author->id]) }}"
                                        class="mt-2 inline-flex items-center gap-1 text-sm font-bold text-emerald-600 hover:underline dark:text-emerald-400">
                                        View all posts
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </section>
                    @endif

                    {{-- Comments --}}
                    @if ($post->allow_comments)
                        <section class="mt-12">
                            <livewire:frontend.post-comments :post="$post" :wire:key="'comments-'.$post->id" />
                        </section>
                    @endif

                    {{-- Related posts --}}
                    @if ($this->relatedPosts->isNotEmpty())
                        <section class="mt-12">
                            <div class="mb-6 flex items-center gap-3">
                                <div class="h-6 w-1 rounded-full bg-emerald-500"></div>
                                <h2 class="text-2xl font-black tracking-tight"
                                    style="font-family: 'Playfair Display', serif;">
                                    You might also like
                                </h2>
                            </div>
                            <div class="grid gap-5 sm:grid-cols-2">
                                @foreach ($this->relatedPosts as $rp)
                                    <x-frontend.post-card :post="$rp" />
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>

                {{-- Sidebar --}}
                <aside class="space-y-8">
                    {{-- Author Mini Card --}}
                    @if ($post->author)
                        <div
                            class="rounded-3xl border border-slate-200 bg-white p-6 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div
                                class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-gradient-to-br from-slate-700 to-slate-900 text-2xl font-black uppercase text-white ring-4 ring-white shadow-xl dark:ring-slate-800">
                                {{ mb_substr($post->author->name, 0, 1) }}
                            </div>
                            <h3 class="mt-3 text-lg font-black tracking-tight"
                                style="font-family: 'Playfair Display', serif;">
                                {{ $post->author->name }}
                            </h3>
                            @if ($post->author->bio ?? null)
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-400 line-clamp-3">
                                    {{ $post->author->bio }}
                                </p>
                            @endif
                            <a href="{{ route('frontend.author', ['locale' => $locale?->code, 'user' => $post->author->id]) }}"
                                class="mt-3 inline-flex items-center gap-1 text-sm font-bold text-emerald-600 hover:underline dark:text-emerald-400">
                                View profile
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    @endif

                    {{-- Popular Categories --}}
                    @if ($popularCategory->isNotEmpty())
                        <div
                            class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="h-5 w-1 rounded-full bg-emerald-500"></div>
                                <p
                                    class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">
                                    Categories
                                </p>
                            </div>
                            <h3 class="mb-4 text-xl font-black tracking-tight text-slate-900 dark:text-slate-100"
                                style="font-family:'Playfair Display', serif;">
                                Explore Topics
                            </h3>
                            <div class="space-y-2">
                                @foreach ($popularCategory as $category)
                                    <a href="{{ route('frontend.category', ['locale' => $locale?->code, 'slug' => $category->translate('slug')]) }}"
                                        class="group flex items-center justify-between rounded-xl bg-slate-50 px-4 py-2.5 transition hover:bg-emerald-50 dark:bg-slate-800/50 dark:hover:bg-emerald-500/10">
                                        <span
                                            class="text-sm font-semibold text-slate-700 transition group-hover:text-emerald-600 dark:text-slate-300 dark:group-hover:text-emerald-400">
                                            {{ $category->translate('name') }}
                                        </span>
                                        <span
                                            class="rounded-full bg-white px-2.5 py-0.5 text-[10px] font-bold text-slate-500 dark:bg-slate-700 dark:text-slate-400">
                                            {{ $category->posts_count }}
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Popular Tags --}}
                    @if ($popularTags->isNotEmpty())
                        <div
                            class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div class="flex items-center gap-2 mb-4">
                                <div class="h-5 w-1 rounded-full bg-emerald-500"></div>
                                <p
                                    class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">
                                    Popular Tags
                                </p>
                            </div>
                            <h3 class="mb-4 text-xl font-black tracking-tight text-slate-900 dark:text-slate-100"
                                style="font-family: 'Playfair Display', serif;">
                                Tag Cloud
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($popularTags as $tag)
                                    <a href="{{ route('frontend.tag', ['locale' => $locale?->code, 'tag' => $tag->slug]) }}"
                                        class="inline-flex items-center rounded-full bg-slate-100 px-3.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-900 hover:text-white dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-100 dark:hover:text-slate-900">
                                        #{{ $tag->slug }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Newsletter Mini CTA --}}
                    <div
                        class="rounded-3xl bg-gradient-to-br from-emerald-600 to-emerald-700 p-6 text-white shadow-xl shadow-emerald-600/30">
                        <span class="grid h-12 w-12 place-items-center rounded-xl bg-white/20 backdrop-blur-sm">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </span>
                        <p class="mt-4 text-[11px] font-bold uppercase tracking-[0.24em] text-white/90">Newsletter</p>
                        <h3 class="mt-1 text-xl font-black leading-tight"
                            style="font-family: 'Playfair Display', serif;">
                            Get the morning brief
                        </h3>
                        <p class="mt-2 text-sm leading-relaxed text-white/90">
                            Top stories handpicked by our editors, delivered every morning.
                        </p>
                        <a href="#newsletter"
                            class="mt-4 inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-emerald-600 shadow-lg transition hover:scale-105 hover:shadow-xl active:scale-95">
                            Subscribe free
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>

                    {{-- Sidebar ad --}}
                    <x-frontend.ad-zone slot="sidebar_box" class="block" />
                </aside>
            </div>
        </div>
    </div>
