@php
    $settings = app(\App\Services\SettingService::class);
    $siteName = (string) ($settings->get('site.name') ?? 'REVISION');
    $siteDescription = (string) ($settings->get('site.description') ?? 'Welcome to ultimate source for fresh perspectives! Explore curated content to enlighten, entertain and engage global readers.');
    $localeResolver = app(\App\Support\LocaleResolver::class);
    $currentLocale = $localeResolver->current();

    // Fetch real top categories
    $footerCategories = \App\Models\Category::query()
        ->withCount(['posts' => fn($q) => $q->where('status', \App\Enums\PostStatus::Published->value)])
        ->having('posts_count', '>', 0)
        ->orderByDesc('posts_count')
        ->limit(5)
        ->get();

    // Fetch real pages
    $footerPages = \App\Models\Page::query()
        ->visibleIn($currentLocale?->id ?? 0)
        ->inMenu()
        ->ordered()
        ->limit(6)
        ->get();

    // Fetch sticky bottom ticker posts with valid slugs
    $stickyPosts = \App\Models\Post::query()
        ->with(['translations', 'featuredImage'])
        ->where('status', \App\Enums\PostStatus::Published->value)
        ->whereNotNull('published_at')
        ->where('published_at', '<=', now())
        ->orderByDesc('published_at')
        ->limit(10)
        ->get()
        ->filter(function($sp) {
            $t = $sp->translation() ?? ($sp->translations->firstWhere('language_id', $sp->default_language_id) ?? $sp->translations->first());
            return $t && !empty($t->slug);
        });
@endphp

<footer class="border-t border-slate-200 bg-white text-slate-700 dark:border-[#1d1f27] dark:bg-[#111217] dark:text-neutral-300 transition-colors duration-200 pb-20">
    <div class="mx-auto max-w-[1360px] px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid gap-12 lg:grid-cols-12">
            
            {{-- Brand & Bio (Left Column - 5 cols) --}}
            <div class="lg:col-span-5 space-y-4">
                <a href="{{ route('frontend.home') }}" class="flex items-center gap-2.5">
                    <span class="grid h-8 w-8 place-items-center rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-950 shadow-xs">
                        <i data-lucide="sparkles" class="h-4 w-4"></i>
                    </span>
                    <span class="text-lg font-black uppercase tracking-widest text-slate-900 dark:text-white" style="font-family: 'Playfair Display', serif;">
                        {{ $siteName }}
                    </span>
                </a>

                <p class="max-w-md text-xs leading-relaxed text-slate-500 dark:text-neutral-400">
                    {{ $siteDescription }}
                </p>

                {{-- Social Icons --}}
                <div class="flex items-center gap-3 pt-2 text-slate-400 dark:text-neutral-400">
                    <a href="#" class="hover:text-slate-900 dark:hover:text-white transition"><i data-lucide="facebook" class="h-4 w-4"></i></a>
                    <a href="#" class="hover:text-slate-900 dark:hover:text-white transition"><i data-lucide="twitter" class="h-4 w-4"></i></a>
                    <a href="#" class="hover:text-slate-900 dark:hover:text-white transition"><i data-lucide="instagram" class="h-4 w-4"></i></a>
                    <a href="#" class="hover:text-slate-900 dark:hover:text-white transition"><i data-lucide="linkedin" class="h-4 w-4"></i></a>
                </div>

                <p class="pt-4 text-[11px] text-slate-400 dark:text-neutral-500">
                    © {{ date('Y') }} — {{ $siteName }}. All Rights Reserved.
                </p>
            </div>

            {{-- 3 Navigation Columns (Right - 7 cols) --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-8 lg:col-span-7">
                {{-- Column 1: HOMEPAGES --}}
                <div>
                    <h5 class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-900 dark:text-white mb-4">
                        HOMEPAGES
                    </h5>
                    <ul class="space-y-2.5 text-xs text-slate-500 dark:text-neutral-400">
                        <li><a href="{{ route('frontend.home') }}" class="hover:text-slate-900 dark:hover:text-white transition">Classic List</a></li>
                        <li><a href="{{ route('frontend.home') }}" class="hover:text-slate-900 dark:hover:text-white transition">Classic Grid</a></li>
                        <li><a href="{{ route('frontend.home') }}" class="hover:text-slate-900 dark:hover:text-white transition">Classic Overlay</a></li>
                        <li><a href="{{ route('frontend.home') }}" class="hover:text-slate-900 dark:hover:text-white transition">Hero Slider</a></li>
                        <li><a href="{{ route('frontend.home') }}" class="hover:text-slate-900 dark:hover:text-white transition">Featured Posts</a></li>
                    </ul>
                </div>

                {{-- Column 2: CATEGORIES --}}
                <div>
                    <h5 class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-900 dark:text-white mb-4">
                        CATEGORIES
                    </h5>
                    <ul class="space-y-2.5 text-xs text-slate-500 dark:text-neutral-400">
                        @if ($footerCategories->isNotEmpty())
                            @foreach ($footerCategories as $cat)
                                @php
                                    $catTitle = html_entity_decode((string) ($cat->translate('name') ?? ('#' . $cat->id)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                                @endphp
                                <li>
                                    <a href="{{ route('frontend.category', ['slug' => $cat->translate('slug')]) }}" class="hover:text-slate-900 dark:hover:text-white transition">
                                        {{ $catTitle }}
                                    </a>
                                </li>
                            @endforeach
                        @else
                            <li><a href="{{ route('frontend.home') }}" class="hover:text-slate-900 dark:hover:text-white">Technology</a></li>
                            <li><a href="{{ route('frontend.home') }}" class="hover:text-slate-900 dark:hover:text-white">Travel</a></li>
                            <li><a href="{{ route('frontend.home') }}" class="hover:text-slate-900 dark:hover:text-white">Sport</a></li>
                            <li><a href="{{ route('frontend.home') }}" class="hover:text-slate-900 dark:hover:text-white">Business</a></li>
                        @endif
                    </ul>
                </div>

                {{-- Column 3: PAGES --}}
                <div>
                    <h5 class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-900 dark:text-white mb-4">
                        PAGES
                    </h5>
                    <ul class="space-y-2.5 text-xs text-slate-500 dark:text-neutral-400">
                        @if ($footerPages->isNotEmpty())
                            @foreach ($footerPages as $page)
                                @php
                                    $trans = $page->translation($currentLocale?->code) ?? $page->translation();
                                    $pageTitle = $trans ? html_entity_decode((string) $trans->title, ENT_QUOTES | ENT_HTML5, 'UTF-8') : null;
                                @endphp
                                @if ($trans && $pageTitle)
                                    <li>
                                        <a href="{{ route('frontend.page', ['slug' => $trans->slug]) }}" class="hover:text-slate-900 dark:hover:text-white transition">
                                            {{ $pageTitle }}
                                        </a>
                                    </li>
                                @endif
                            @endforeach
                        @else
                            <li><a href="{{ route('frontend.home') }}" class="hover:text-slate-900 dark:hover:text-white">About</a></li>
                            <li><a href="{{ route('frontend.home') }}" class="hover:text-slate-900 dark:hover:text-white">Categories</a></li>
                            <li><a href="{{ route('frontend.home') }}" class="hover:text-slate-900 dark:hover:text-white">Contacts</a></li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- ───── Sticky Bottom Story Ticker Bar (Matching Reference Screenshot 5) ───── --}}
    @if ($stickyPosts->isNotEmpty())
        <div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 px-4 py-2.5 backdrop-blur-md dark:border-[#1d1f27] dark:bg-[#111217]/95 shadow-lg">
            <div class="mx-auto flex max-w-[1360px] items-center justify-between gap-4">
                <div class="flex flex-1 items-center gap-6 overflow-x-auto scrollbar-hide py-1">
                    @foreach ($stickyPosts as $sp)
                        @php
                            $spT = $sp->translation() ?? ($sp->translations->firstWhere('language_id', $sp->default_language_id) ?? $sp->translations->first());
                            $spSlug = $spT?->slug;
                            $spUrl = route('frontend.post.show', ['slug' => $spSlug]);
                            $spImg = $sp->featuredImage?->url() ?? "https://picsum.photos/seed/np{$sp->id}/120/80";
                        @endphp
                        <a href="{{ $spUrl }}" class="group flex shrink-0 items-center gap-2.5">
                            <div class="h-9 w-12 shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-[#1f212a]">
                                <img src="{{ $spImg }}" alt="{{ $spT->title }}" class="h-full w-full object-cover group-hover:scale-105 transition">
                            </div>
                            <span class="max-w-[180px] sm:max-w-[220px] truncate text-xs font-semibold text-slate-800 group-hover:text-emerald-500 dark:text-neutral-300 dark:group-hover:text-white transition">
                                {{ $spT->title }}
                            </span>
                        </a>
                    @endforeach
                </div>

                {{-- Scroll to Top Button --}}
                <button type="button" x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                    class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 dark:border-[#262832] dark:bg-[#181920] dark:text-neutral-300 dark:hover:bg-[#22242e] shadow-xs"
                    title="Back to Top" aria-label="Back to Top">
                    <i data-lucide="chevron-up" class="h-4 w-4"></i>
                </button>
            </div>
        </div>
    @endif
</footer>
