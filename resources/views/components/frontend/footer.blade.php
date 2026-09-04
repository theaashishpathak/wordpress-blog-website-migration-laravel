@php
    $settings = app(\App\Services\SettingService::class);
    $siteName = (string) ($settings->get('footer.brand_text') ?? ($settings->get('site.name') ?? 'RUPANTRIX'));
    $footerIcon = (string) ($settings->get('footer.brand_icon') ?? 'sparkles');
    $logoType = (string) ($settings->get('footer.logo_type') ?? 'text');
    $logoUrl = (string) ($settings->get('footer.logo_url') ?? '');
    $logoHeight = (int) ($settings->get('footer.logo_height') ?? 32);

    $siteDescription = (string) ($settings->get('footer.description') ?? ($settings->get('site.description') ?? 'Welcome to ultimate source for fresh perspectives! Explore curated content to enlighten, entertain and engage global readers.'));
    $rawCopyright = (string) ($settings->get('footer.copyright') ?? '© {year} — {siteName}. All Rights Reserved.');
    $copyright = str_replace(['{year}', '{siteName}'], [date('Y'), $siteName], $rawCopyright);

    // Social Links
    $facebookUrl = (string) ($settings->get('footer.social_facebook') ?? '#');
    $twitterUrl = (string) ($settings->get('footer.social_twitter') ?? '#');
    $instagramUrl = (string) ($settings->get('footer.social_instagram') ?? '#');
    $linkedinUrl = (string) ($settings->get('footer.social_linkedin') ?? '#');
    $youtubeUrl = (string) ($settings->get('footer.social_youtube') ?? '');
    $githubUrl = (string) ($settings->get('footer.social_github') ?? '');

    // Column titles and limits
    $col1Title = (string) ($settings->get('footer.col1_title') ?? 'HOMEPAGES');
    $col2Title = (string) ($settings->get('footer.col2_title') ?? 'CATEGORIES');
    $col2Count = (int) ($settings->get('footer.col2_count') ?? 5);
    $col3Title = (string) ($settings->get('footer.col3_title') ?? 'PAGES');
    $col3Count = (int) ($settings->get('footer.col3_count') ?? 6);

    // Sticky Bottom Ticker
    $tickerEnabled = (bool) ($settings->get('footer.ticker_enabled') ?? true);
    $tickerCount = (int) ($settings->get('footer.ticker_count') ?? 10);
    $showBackToTop = (bool) ($settings->get('footer.show_back_to_top') ?? true);

    $localeResolver = app(\App\Support\LocaleResolver::class);
    $currentLocale = $localeResolver->current();

    // Fetch real top categories
    $footerCategories = \App\Models\Category::query()
        ->whereHas('posts', fn($q) => $q->where('status', \App\Enums\PostStatus::Published->value))
        ->withCount(['posts' => fn($q) => $q->where('status', \App\Enums\PostStatus::Published->value)])
        ->orderByDesc('posts_count')
        ->limit($col2Count)
        ->get();

    // Fetch real pages
    $footerPages = \App\Models\Page::query()
        ->visibleIn($currentLocale?->id ?? 0)
        ->inMenu()
        ->ordered()
        ->limit($col3Count)
        ->get();

    // Fetch sticky bottom ticker posts with valid slugs
    $stickyPosts = $tickerEnabled ? \App\Models\Post::query()
        ->with(['translations', 'featuredImage'])
        ->where('status', \App\Enums\PostStatus::Published->value)
        ->whereNotNull('published_at')
        ->where('published_at', '<=', now())
        ->orderByDesc('published_at')
        ->limit($tickerCount)
        ->get()
        ->filter(function($sp) {
            $t = $sp->translation() ?? ($sp->translations->firstWhere('language_id', $sp->default_language_id) ?? $sp->translations->first());
            return $t && !empty($t->slug);
        }) : collect();
@endphp

<footer class="border-t border-slate-200 bg-white text-slate-700 dark:border-[#1d1f27] dark:bg-[#111217] dark:text-neutral-300 transition-colors duration-200 {{ $tickerEnabled && $stickyPosts->isNotEmpty() ? 'pb-24' : 'pb-12' }}">
    <div class="mx-auto max-w-[1360px] px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid gap-12 lg:grid-cols-12">
            
            {{-- Brand & Bio (Left Column - 5 cols) --}}
            <div class="lg:col-span-5 space-y-4">
                <a href="{{ route('frontend.home') }}" class="flex items-center gap-2.5">
                    @if ($logoType === 'image' && !empty($logoUrl))
                        <img src="{{ $logoUrl }}" alt="{{ $siteName }}" style="height: {{ $logoHeight }}px" class="w-auto object-contain">
                    @else
                        <span class="grid h-8 w-8 place-items-center rounded-xl bg-slate-900 text-white dark:bg-white dark:text-slate-950 shadow-xs">
                            <i data-lucide="{{ $footerIcon }}" class="h-4 w-4"></i>
                        </span>
                        <span class="text-lg font-black uppercase tracking-widest text-slate-900 dark:text-white" style="font-family: 'Playfair Display', serif;">
                            {{ $siteName }}
                        </span>
                    @endif
                </a>

                <p class="max-w-md text-xs leading-relaxed text-slate-500 dark:text-neutral-400">
                    {{ $siteDescription }}
                </p>

                {{-- Social Icons --}}
                <div class="flex items-center gap-3 pt-2 text-slate-400 dark:text-neutral-400">
                    @if (!empty($facebookUrl))
                        <a href="{{ $facebookUrl }}" target="_blank" rel="noopener noreferrer" class="hover:text-slate-900 dark:hover:text-white transition" title="Facebook">
                            <i data-lucide="facebook" class="h-4 w-4"></i>
                        </a>
                    @endif
                    @if (!empty($twitterUrl))
                        <a href="{{ $twitterUrl }}" target="_blank" rel="noopener noreferrer" class="hover:text-slate-900 dark:hover:text-white transition" title="Twitter / X">
                            <i data-lucide="twitter" class="h-4 w-4"></i>
                        </a>
                    @endif
                    @if (!empty($instagramUrl))
                        <a href="{{ $instagramUrl }}" target="_blank" rel="noopener noreferrer" class="hover:text-slate-900 dark:hover:text-white transition" title="Instagram">
                            <i data-lucide="instagram" class="h-4 w-4"></i>
                        </a>
                    @endif
                    @if (!empty($linkedinUrl))
                        <a href="{{ $linkedinUrl }}" target="_blank" rel="noopener noreferrer" class="hover:text-slate-900 dark:hover:text-white transition" title="LinkedIn">
                            <i data-lucide="linkedin" class="h-4 w-4"></i>
                        </a>
                    @endif
                    @if (!empty($youtubeUrl))
                        <a href="{{ $youtubeUrl }}" target="_blank" rel="noopener noreferrer" class="hover:text-slate-900 dark:hover:text-white transition" title="YouTube">
                            <i data-lucide="youtube" class="h-4 w-4"></i>
                        </a>
                    @endif
                    @if (!empty($githubUrl))
                        <a href="{{ $githubUrl }}" target="_blank" rel="noopener noreferrer" class="hover:text-slate-900 dark:hover:text-white transition" title="GitHub">
                            <i data-lucide="github" class="h-4 w-4"></i>
                        </a>
                    @endif
                </div>

                <p class="pt-4 text-[11px] text-slate-400 dark:text-neutral-500">
                    {{ $copyright }}
                </p>
            </div>

            {{-- 3 Navigation Columns (Right - 7 cols) --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-8 lg:col-span-7">
                {{-- Column 1: HOMEPAGES / EXPLORE --}}
                <div>
                    <h5 class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-900 dark:text-white mb-4">
                        {{ $col1Title }}
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
                        {{ $col2Title }}
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
                        {{ $col3Title }}
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

    {{-- ───── Sticky Bottom Story Ticker Bar ───── --}}
    @if ($tickerEnabled && $stickyPosts->isNotEmpty())
        <div class="fixed inset-x-0 bottom-0 z-30 footer-ticker-glass px-4 py-2.5 shadow-2xl">
            <div class="mx-auto flex max-w-[1440px] items-center justify-between gap-4">
                {{-- Continuous Moving Ticker Container --}}
                <div class="relative flex-1 overflow-hidden ticker-mask py-1">
                    <div class="ticker-track gap-8">
                        {{-- Set 1 --}}
                        @foreach ($stickyPosts as $sp)
                            @php
                                $spT = $sp->translation() ?? ($sp->translations->firstWhere('language_id', $sp->default_language_id) ?? $sp->translations->first());
                                $spSlug = $spT?->slug;
                                $spUrl = route('frontend.post.show', ['slug' => $spSlug]);
                                $spImg = $sp->featuredImage?->url() ?? "https://picsum.photos/seed/np{$sp->id}/120/80";
                            @endphp
                            <a href="{{ $spUrl }}" class="group flex shrink-0 items-center gap-2.5 transition">
                                <div class="h-9 w-12 shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-[#1f212a] border border-slate-200/60 dark:border-[#262832]">
                                    <img src="{{ $spImg }}" alt="{{ $spT->title }}" class="h-full w-full object-cover group-hover:scale-105 transition" loading="lazy">
                                </div>
                                <span class="max-w-[180px] sm:max-w-[240px] truncate text-xs font-semibold text-slate-800 group-hover:text-emerald-500 dark:text-neutral-300 dark:group-hover:text-emerald-400 transition">
                                    {{ $spT->title }}
                                </span>
                            </a>
                        @endforeach

                        {{-- Set 2 (Duplicated for seamless infinite movement) --}}
                        @foreach ($stickyPosts as $sp)
                            @php
                                $spT = $sp->translation() ?? ($sp->translations->firstWhere('language_id', $sp->default_language_id) ?? $sp->translations->first());
                                $spSlug = $spT?->slug;
                                $spUrl = route('frontend.post.show', ['slug' => $spSlug]);
                                $spImg = $sp->featuredImage?->url() ?? "https://picsum.photos/seed/np{$sp->id}/120/80";
                            @endphp
                            <a href="{{ $spUrl }}" aria-hidden="true" tabindex="-1" class="group flex shrink-0 items-center gap-2.5 transition">
                                <div class="h-9 w-12 shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-[#1f212a] border border-slate-200/60 dark:border-[#262832]">
                                    <img src="{{ $spImg }}" alt="{{ $spT->title }}" class="h-full w-full object-cover group-hover:scale-105 transition" loading="lazy">
                                </div>
                                <span class="max-w-[180px] sm:max-w-[240px] truncate text-xs font-semibold text-slate-800 group-hover:text-emerald-500 dark:text-neutral-300 dark:group-hover:text-emerald-400 transition">
                                    {{ $spT->title }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Scroll to Top Button --}}
                @if ($showBackToTop)
                    <button type="button" x-on:click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                        class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-slate-200 bg-white text-slate-700 hover:bg-slate-100 dark:border-[#262832] dark:bg-[#181920] dark:text-neutral-300 dark:hover:bg-[#22242e] shadow-xs cursor-pointer z-10"
                        title="Back to Top" aria-label="Back to Top">
                        <i data-lucide="chevron-up" class="h-4 w-4"></i>
                    </button>
                @endif
            </div>
        </div>
    @endif
</footer>
