@php
    $settings = app(\App\Services\SettingService::class);
    $siteName = (string) ($settings->get('site.name') ?? config('app.name', 'NewsPilot AI'));
    $tagline = (string) ($settings->get('site.tagline') ?? 'AI-Powered News & Magazine');
    $localeResolver = app(\App\Support\LocaleResolver::class);
    $currentLocale = $localeResolver->current();

    $footerCategories = \App\Models\Category::query()
        ->ordered()
        ->limit(6)
        ->get();

    $footerPages = \App\Models\Page::query()
        ->visibleIn($currentLocale?->id ?? 0)
        ->inMenu()
        ->ordered()
        ->limit(8)
        ->get();

    // Social links pulled from settings (with sensible defaults)
    $socials = [
        'twitter' => (string) ($settings->get('social.twitter') ?? ''),
        'facebook' => (string) ($settings->get('social.facebook') ?? ''),
        'instagram' => (string) ($settings->get('social.instagram') ?? ''),
        'linkedin' => (string) ($settings->get('social.linkedin') ?? ''),
        'youtube' => (string) ($settings->get('social.youtube') ?? ''),
        'github' => (string) ($settings->get('social.github') ?? ''),
    ];
    $socials = array_filter($socials);
@endphp

<footer class="mt-16 border-t border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950">
    {{-- Newsletter CTA hero strip --}}
    <div class="border-b border-slate-200 bg-white py-12 dark:border-slate-800 dark:bg-slate-900">
        <div class="mx-auto max-w-5xl px-4">
            <livewire:frontend.newsletter-signup source="footer_form" variant="hero" />
        </div>
    </div>

    {{-- Main footer columns --}}
    <div class="mx-auto max-w-7xl px-4 py-14">
        <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-12">
            {{-- Brand column --}}
            <div class="lg:col-span-4">
                <div class="mb-4 flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900">
                        <span class="text-lg font-black" style="font-family: 'Playfair Display', serif;">{{ mb_substr($siteName, 0, 1) }}</span>
                    </span>
                    <span class="flex flex-col leading-tight">
                        <span class="text-xl font-black tracking-tight text-slate-900 dark:text-slate-100" style="font-family: 'Playfair Display', serif;">
                            {{ $siteName }}
                        </span>
                        <span class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                            {{ $tagline }}
                        </span>
                    </span>
                </div>
                <p class="max-w-sm text-sm leading-relaxed text-slate-600 dark:text-slate-400">
                    An AI-powered newsroom. Crafted with editorial care, published worldwide. Read what matters, when it matters.
                </p>

                {{-- Social icons --}}
                @if (! empty($socials))
                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        @foreach ($socials as $platform => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener"
                               aria-label="{{ ucfirst($platform) }}"
                               class="grid h-9 w-9 place-items-center rounded-lg border border-slate-200 bg-white text-slate-500 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100">
                                <i data-lucide="{{ $platform }}" class="h-4 w-4"></i>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Categories --}}
            <div class="lg:col-span-3">
                <h4 class="mb-4 text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                    Categories
                </h4>
                <ul class="space-y-2 text-sm">
                    @foreach ($footerCategories as $cat)
                        <li>
                            <a href="{{ route('frontend.category', ['locale' => $currentLocale?->code, 'slug' => $cat->translate('slug')]) }}"
                               class="inline-flex items-center gap-1.5 text-slate-600 transition hover:text-emerald-700 dark:text-slate-400 dark:hover:text-emerald-300">
                                @if ($cat->icon)
                                    <i data-lucide="{{ $cat->icon }}" class="h-3 w-3"></i>
                                @endif
                                {{ $cat->translate('name') ?? '#'.$cat->id }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Pages --}}
            <div class="lg:col-span-3">
                <h4 class="mb-4 text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                    Company
                </h4>
                <ul class="space-y-2 text-sm">
                    @foreach ($footerPages as $page)
                        @php($trans = $page->translation($currentLocale?->code))
                        @if ($trans)
                            <li>
                                <a href="{{ route('frontend.page', ['locale' => $currentLocale?->code, 'slug' => $trans->slug]) }}"
                                   class="text-slate-600 transition hover:text-emerald-700 dark:text-slate-400 dark:hover:text-emerald-300">
                                    {{ $trans->title }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>

            {{-- Follow --}}
            <div class="lg:col-span-2">
                <h4 class="mb-4 text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                    Follow
                </h4>
                <ul class="space-y-2 text-sm">
                    <li>
                        <a href="{{ route('frontend.feed.rss', ['locale' => $currentLocale?->code]) }}"
                           class="inline-flex items-center gap-1.5 text-slate-600 transition hover:text-emerald-700 dark:text-slate-400 dark:hover:text-emerald-300">
                            <i data-lucide="rss" class="h-3.5 w-3.5"></i> RSS Feed
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('frontend.sitemap') }}"
                           class="inline-flex items-center gap-1.5 text-slate-600 transition hover:text-emerald-700 dark:text-slate-400 dark:hover:text-emerald-300">
                            <i data-lucide="map" class="h-3.5 w-3.5"></i> Sitemap
                        </a>
                    </li>
                    @auth
                        <li>
                            <a href="{{ route('dashboard') }}"
                               class="inline-flex items-center gap-1.5 text-slate-600 transition hover:text-emerald-700 dark:text-slate-400 dark:hover:text-emerald-300">
                                <i data-lucide="layout-dashboard" class="h-3.5 w-3.5"></i> Dashboard
                            </a>
                        </li>
                    @else
                        <li>
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center gap-1.5 text-slate-600 transition hover:text-emerald-700 dark:text-slate-400 dark:hover:text-emerald-300">
                                <i data-lucide="log-in" class="h-3.5 w-3.5"></i> Sign in
                            </a>
                        </li>
                    @endauth
                </ul>
            </div>
        </div>
    </div>

    {{-- Bottom bar --}}
    <div class="border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-3 px-4 py-5 text-xs text-slate-500 sm:flex-row dark:text-slate-400">
            <p class="text-center sm:text-left">
                © {{ now()->year }} <span class="font-bold text-slate-700 dark:text-slate-300" style="font-family: 'Playfair Display', serif;">{{ $siteName }}</span>. All rights reserved.
            </p>
            <p class="italic text-slate-500 dark:text-slate-400" style="font-family: 'Playfair Display', serif;">
                Built for readers who notice the details.
            </p>
        </div>
    </div>
</footer>
