@php
    $locale = app(\App\Support\LocaleResolver::class)->current();
    $featured = $post->featuredImage;

    // "Usable" if Media::isImage() approves, or — for legacy rows / partial
    // eager-loads that drop mime_type — if the row still has a real path.
    $featuredUsable = $featured
        && ($featured->isImage() || ($featured->path !== null && $featured->path !== ''));

    // Deterministic free placeholder (Lorem Picsum) when no real image is
    // uploaded — sized for the article hero.
    $placeholderUrl = $featuredUsable
        ? null
        : "https://picsum.photos/seed/np{$post->id}/1600/900";

    $words = str_word_count(strip_tags((string) $translation->content));
    $readTime = max(1, (int) ceil($words / 225));
@endphp

<div>
    {{-- Reading progress bar (fixed top) --}}
    <div x-data="{ progress: 0 }"
         x-init="
            const update = () => {
                const h = document.documentElement;
                const scrolled = h.scrollTop;
                const max = h.scrollHeight - h.clientHeight;
                progress = max > 0 ? Math.min(100, (scrolled / max) * 100) : 0;
            };
            window.addEventListener('scroll', update, { passive: true });
            update();
         "
         class="pointer-events-none fixed inset-x-0 top-0 z-50 h-1">
        <div class="h-full bg-emerald-600 transition-[width] duration-100" x-bind:style="`width: ${progress}%`"></div>
    </div>

    <article class="mx-auto max-w-3xl px-4 py-8 lg:py-12">
        {{-- Breadcrumb --}}
        <nav class="mb-5 flex items-center gap-2 text-xs font-semibold text-slate-500" aria-label="Breadcrumb">
            <a href="{{ route('frontend.home', ['locale' => $locale?->code]) }}" class="transition hover:text-emerald-700">Home</a>
            @if ($post->category)
                <i data-lucide="chevron-right" class="h-3 w-3 text-slate-300"></i>
                <a href="{{ route('frontend.category', ['locale' => $locale?->code, 'slug' => $post->category->translate('slug')]) }}"
                   class="transition hover:text-emerald-700">
                    {{ $post->category->translate('name') }}
                </a>
            @endif
            <i data-lucide="chevron-right" class="h-3 w-3 text-slate-300"></i>
            <span class="truncate text-slate-400">{{ \Illuminate\Support\Str::limit($translation->title, 40) }}</span>
        </nav>

        {{-- Category pill + breaking badge --}}
        <div class="mb-5 flex flex-wrap items-center gap-2">
            @if ($post->is_breaking)
                <span class="inline-flex items-center gap-1.5 rounded-md bg-rose-600 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-white shadow-md shadow-rose-600/30">
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-white"></span>
                    Breaking
                </span>
            @endif
            @if ($post->category)
                <a href="{{ route('frontend.category', ['locale' => $locale?->code, 'slug' => $post->category->translate('slug')]) }}"
                   class="inline-flex items-center gap-1.5 rounded-md bg-emerald-50 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-emerald-700 transition hover:bg-emerald-50 dark:bg-emerald-500/10 dark:text-emerald-300">
                    @if ($post->category->icon)
                        <i data-lucide="{{ $post->category->icon }}" class="h-2.5 w-2.5"></i>
                    @endif
                    {{ $post->category->translate('name') }}
                </a>
            @endif
            @if ($post->is_featured)
                <span class="inline-flex items-center gap-1 rounded-md bg-amber-100 px-2 py-1 text-[10px] font-black uppercase tracking-wider text-amber-800 dark:bg-amber-500/15 dark:text-amber-200">
                    <i data-lucide="star" class="h-2.5 w-2.5"></i>
                    Featured
                </span>
            @endif
        </div>

        {{-- Title --}}
        <header class="mb-6">
            <h1 class="mb-4 text-4xl font-black leading-[1.1] tracking-tight text-slate-900 md:text-5xl lg:text-[3.25rem] dark:text-slate-50"
                style="font-family: 'Playfair Display', serif;">
                {{ $translation->title }}
            </h1>

            @if ($translation->excerpt)
                <p class="mb-6 text-lg leading-relaxed text-slate-600 md:text-xl dark:text-slate-300">{{ $translation->excerpt }}</p>
            @endif

            {{-- Byline --}}
            <div class="flex flex-wrap items-center gap-3 border-y border-slate-200 py-4 text-sm dark:border-slate-800">
                @if ($post->author)
                    <a href="{{ route('frontend.author', ['locale' => $locale?->code, 'user' => $post->author->id]) }}"
                       class="group flex items-center gap-2.5">
                        <span class="grid h-10 w-10 place-items-center rounded-full bg-slate-900 text-sm font-black uppercase text-white ring-2 ring-white shadow-md transition group-hover:scale-105 dark:ring-slate-900">
                            {{ mb_substr($post->author->name, 0, 1) }}
                        </span>
                        <span class="flex flex-col leading-tight">
                            <span class="text-sm font-bold text-slate-900 transition group-hover:text-emerald-700 dark:text-slate-100 dark:group-hover:text-emerald-300">{{ $post->author->name }}</span>
                            <span class="text-[11px] text-slate-500">Author</span>
                        </span>
                    </a>
                @endif

                <span class="mx-1 h-6 w-px bg-slate-200 dark:bg-slate-700"></span>

                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500">
                    <i data-lucide="calendar" class="h-3.5 w-3.5"></i>
                    {{ $post->published_at?->format('M j, Y') }}
                </span>

                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500">
                    <i data-lucide="clock" class="h-3.5 w-3.5"></i>
                    {{ $readTime }} min read
                </span>

                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500">
                    <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                    {{ number_format($post->view_count) }} views
                </span>
            </div>
        </header>

        {{-- Featured image (real if uploaded, otherwise a deterministic Picsum placeholder) --}}
        <figure class="mb-10 -mx-4 overflow-hidden rounded-none sm:mx-0 sm:rounded-2xl shadow-xl shadow-slate-900/10">
            @if ($featuredUsable)
                <img src="{{ $featured->url() }}"
                     alt="{{ $featured->alt_text ?? $translation->title }}"
                     class="w-full">
                @if ($featured->caption)
                    <figcaption class="mt-3 px-4 text-center text-xs italic text-slate-500 sm:px-0">{{ $featured->caption }}</figcaption>
                @endif
            @else
                <img src="{{ $placeholderUrl }}"
                     alt="{{ $translation->title }}"
                     class="w-full">
            @endif
        </figure>

        {{-- Content --}}
        @if ($this->isPaywalled)
            <div class="prose prose-lg max-w-none dark:prose-invert">
                <p>{{ $this->paywallTeaser }}…</p>
            </div>

            <div class="mt-8 overflow-hidden rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50 p-8 text-center shadow-lg dark:border-amber-500/30 dark:from-amber-500/10 dark:to-orange-500/10">
                <span class="grid h-14 w-14 mx-auto place-items-center rounded-2xl bg-gradient-to-br from-amber-500 to-orange-500 text-white shadow-lg shadow-amber-500/30">
                    <i data-lucide="lock" class="h-6 w-6"></i>
                </span>
                <h2 class="mt-4 text-2xl font-black tracking-tight" style="font-family: 'Playfair Display', serif;">
                    This article is for subscribers
                </h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">
                    Unlock the rest of this article plus our full archive of premium content.
                </p>
                <div class="mt-5 flex flex-wrap justify-center gap-2">
                    @guest
                        <a href="{{ route('login') }}"
                           class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                            Sign in
                        </a>
                    @endguest
                    <a href="#"
                       class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-amber-600 to-orange-600 px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-amber-500/30 transition hover:from-amber-700">
                        <i data-lucide="sparkles" class="h-4 w-4"></i>
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
                <span class="mr-1 text-[10px] font-black uppercase tracking-wider text-slate-400">Tags</span>
                @foreach ($this->tags as $tag)
                    <a href="{{ route('frontend.tag', ['locale' => $locale?->code, 'tag' => $tag->slug]) }}"
                       class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-emerald-50 hover:text-emerald-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-emerald-500/10 dark:hover:text-emerald-300">
                        #{{ $tag->translate('name') ?? $tag->name }}
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Share row --}}
        <div class="mt-8 flex flex-wrap items-center gap-2.5 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/50">
            <span class="mr-1 text-[10px] font-black uppercase tracking-wider text-slate-500">Share</span>
            <a href="{{ $this->shareUrl('twitter') }}" target="_blank" rel="noopener"
               aria-label="Share on Twitter"
               class="grid h-9 w-9 place-items-center rounded-lg bg-white text-slate-700 ring-1 ring-slate-200 transition hover:bg-sky-50 hover:text-sky-600 hover:ring-sky-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                <i data-lucide="twitter" class="h-4 w-4"></i>
            </a>
            <a href="{{ $this->shareUrl('facebook') }}" target="_blank" rel="noopener"
               aria-label="Share on Facebook"
               class="grid h-9 w-9 place-items-center rounded-lg bg-white text-slate-700 ring-1 ring-slate-200 transition hover:bg-blue-50 hover:text-blue-600 hover:ring-blue-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                <i data-lucide="facebook" class="h-4 w-4"></i>
            </a>
            <a href="{{ $this->shareUrl('linkedin') }}" target="_blank" rel="noopener"
               aria-label="Share on LinkedIn"
               class="grid h-9 w-9 place-items-center rounded-lg bg-white text-slate-700 ring-1 ring-slate-200 transition hover:bg-blue-50 hover:text-blue-700 hover:ring-blue-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                <i data-lucide="linkedin" class="h-4 w-4"></i>
            </a>
            <a href="{{ $this->shareUrl('whatsapp') }}" target="_blank" rel="noopener"
               aria-label="Share on WhatsApp"
               class="grid h-9 w-9 place-items-center rounded-lg bg-white text-slate-700 ring-1 ring-slate-200 transition hover:bg-emerald-50 hover:text-emerald-600 hover:ring-emerald-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                <i data-lucide="message-circle" class="h-4 w-4"></i>
            </a>

            <span class="ml-auto"></span>

            <button type="button"
                    x-data
                    x-on:click="
                        navigator.clipboard.writeText(window.location.href);
                        $el.querySelector('span').textContent = 'Copied!';
                        setTimeout(() => $el.querySelector('span').textContent = 'Copy link', 1500);
                    "
                    class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 ring-1 ring-slate-200 transition hover:bg-slate-50 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                <i data-lucide="link" class="h-3.5 w-3.5"></i>
                <span>Copy link</span>
            </button>
        </div>

        {{-- Author bio card --}}
        @if ($post->author)
            <section class="mt-10 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-start gap-4">
                    <a href="{{ route('frontend.author', ['locale' => $locale?->code, 'user' => $post->author->id]) }}"
                       class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-slate-900 text-lg font-black uppercase text-white ring-2 ring-white shadow-md dark:ring-slate-900">
                        {{ mb_substr($post->author->name, 0, 1) }}
                    </a>
                    <div class="flex-1 min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Written by</p>
                        <a href="{{ route('frontend.author', ['locale' => $locale?->code, 'user' => $post->author->id]) }}"
                           class="text-lg font-black tracking-tight text-slate-900 hover:text-emerald-700 dark:text-slate-100"
                           style="font-family: 'Playfair Display', serif;">
                            {{ $post->author->name }}
                        </a>
                        @if ($post->author->bio ?? null)
                            <p class="mt-1 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $post->author->bio }}</p>
                        @endif
                        <a href="{{ route('frontend.author', ['locale' => $locale?->code, 'user' => $post->author->id]) }}"
                           class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-emerald-700 hover:underline dark:text-emerald-300">
                            View all posts
                            <i data-lucide="arrow-right" class="h-3 w-3"></i>
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
            <section class="mt-12 border-t-2 border-slate-900 pt-8 dark:border-slate-100">
                <h2 class="mb-6 text-2xl font-black tracking-tight" style="font-family: 'Playfair Display', serif;">
                    You might also like
                </h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    @foreach ($this->relatedPosts as $rp)
                        <x-frontend.post-card :post="$rp" />
                    @endforeach
                </div>
            </section>
        @endif
    </article>
</div>
