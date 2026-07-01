@props([
    'post',
    'size' => 'md',  // sm | md | lg | xl | horizontal
    'showExcerpt' => true,
    'showCategory' => true,
    'showMeta' => true,
])

@php
    $locale = app(\App\Support\LocaleResolver::class)->current();

    // Try current-locale translation first; fall back to the post's
    // default-language translation, then any translation row at all.
    // Without a slug we can't generate a URL, so the card guards itself
    // with @if($translation && $translation->slug) below.
    $translation = $post->translation()
        ?? $post->translations->firstWhere('language_id', $post->default_language_id)
        ?? $post->translations->first();

    $hasTranslation = $translation && ! empty($translation->slug);
    $title = $hasTranslation ? ($translation->title ?? ('#'.$post->id)) : '';
    $slug = $hasTranslation ? $translation->slug : '';
    $excerpt = $hasTranslation ? ($translation->excerpt ?? '') : '';
    $url = $hasTranslation
        ? route('frontend.post.show', ['locale' => $locale?->code, 'slug' => $slug])
        : '#';
    $featured = $post->featuredImage;

    // A featured image is "usable" if Media::isImage() says so OR — for
    // legacy rows / partial eager-loads where mime_type wasn't selected —
    // it has a non-empty path. This stops the Picsum placeholder taking
    // over whenever the listing query forgot to select mime_type.
    $hasUsableFeatured = $featured
        && (
            $featured->isImage()
            || ($featured->path !== null && $featured->path !== '')
        );

    // When no featured image is uploaded, fall back to a deterministic free
    // photo from Lorem Picsum (https://picsum.photos). Seeding by post ID
    // means the same post always gets the same image — no shuffle on reload.
    // Sized to match each variant's aspect ratio so the browser doesn't have
    // to crop hard.
    $placeholderUrl = null;
    if (! $hasUsableFeatured) {
        $picsumSize = match ($size) {
            'xl' => '1280/800',
            'lg' => '960/540',
            'sm' => '480/360',
            'horizontal' => '320/240',
            default => '720/480',
        };
        $placeholderUrl = "https://picsum.photos/seed/np{$post->id}/{$picsumSize}";
    }

    // Estimated read time — fall back to ~225 wpm on excerpt+content length
    $words = str_word_count(strip_tags((string) ($translation?->content ?? $excerpt)));
    $readTime = max(1, (int) ceil($words / 225));

    $isBreaking = (bool) $post->is_breaking;
    $isFeatured = (bool) $post->is_featured;

    $sizeMap = match ($size) {
        'xl' => [
            'aspect' => 'aspect-[16/10]',
            'title' => 'text-3xl font-black md:text-4xl lg:text-5xl',
            'excerpt' => 'text-base md:text-lg',
            'pad' => 'p-6 md:p-7',
            'meta' => 'text-xs',
        ],
        'lg' => [
            'aspect' => 'aspect-[16/9]',
            'title' => 'text-2xl font-black md:text-3xl',
            'excerpt' => 'text-sm md:text-base',
            'pad' => 'p-5',
            'meta' => 'text-xs',
        ],
        'sm' => [
            'aspect' => 'aspect-[4/3]',
            'title' => 'text-sm font-bold',
            'excerpt' => 'text-xs',
            'pad' => 'p-3',
            'meta' => 'text-[10px]',
        ],
        default => [
            'aspect' => 'aspect-[3/2]',
            'title' => 'text-lg font-bold md:text-xl',
            'excerpt' => 'text-sm',
            'pad' => 'p-4',
            'meta' => 'text-[11px]',
        ],
    };
@endphp

@if (! $hasTranslation)
    {{-- Skip cards without a usable translation in any language --}}
@elseif ($size === 'horizontal')
    {{-- Horizontal layout for sidebar lists --}}
    <article class="group flex gap-3">
        <a href="{{ $url }}" class="relative block h-20 w-24 shrink-0 overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-800">
            @if ($hasUsableFeatured)
                <img src="{{ $featured->url() }}"
                     alt="{{ $featured->alt_text ?? $title }}"
                     loading="lazy"
                     class="h-full w-full object-cover transition duration-500 group-hover:scale-110">
            @else
                <img src="{{ $placeholderUrl }}"
                     alt="{{ $title }}"
                     loading="lazy"
                     class="h-full w-full object-cover transition duration-500 group-hover:scale-110">
            @endif
        </a>
        <div class="min-w-0 flex-1">
            @if ($showCategory && $post->category)
                <a href="{{ route('frontend.category', ['locale' => $locale?->code, 'slug' => $post->category->translate('slug')]) }}"
                   class="text-[9px] font-bold uppercase tracking-[0.18em] text-slate-500 hover:text-emerald-700 hover:underline dark:text-slate-400 dark:hover:text-emerald-300">
                    {{ $post->category->translate('name') ?? '#'.$post->category_id }}
                </a>
            @endif
            <a href="{{ $url }}">
                <h3 class="line-clamp-3 text-sm font-bold leading-snug text-slate-900 transition group-hover:text-emerald-700 dark:text-slate-100 dark:group-hover:text-emerald-300"
                    style="font-family: 'Playfair Display', serif;">
                    {{ $title }}
                </h3>
            </a>
            @if ($showMeta)
                <p class="mt-1 text-[10px] text-slate-500">
                    {{ $post->published_at?->diffForHumans() }} · {{ $readTime }} min read
                </p>
            @endif
        </div>
    </article>
@else
    {{--
        Hero variants (xl, lg) get `h-full min-h-[420px]` so they stretch to
        match a taller adjacent column (typical hero+small-cards layout on
        the homepage). The anchor flex-grows + image switches to h-full so
        there's no empty space below the aspect-ratio frame.
    --}}
    <article class="group relative flex flex-col overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/70 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-slate-900/5 hover:ring-slate-300 dark:bg-slate-900 dark:ring-slate-800 dark:hover:ring-slate-700 {{ in_array($size, ['lg', 'xl']) ? 'h-full min-h-[420px] md:min-h-[480px]' : '' }}">
        {{-- Image with overlay badges --}}
        <a href="{{ $url }}" class="relative overflow-hidden {{ in_array($size, ['lg', 'xl']) ? 'flex flex-1' : 'block' }}">
            <div class="{{ in_array($size, ['lg', 'xl']) ? 'h-full w-full' : $sizeMap['aspect'].' w-full' }} overflow-hidden bg-slate-100 dark:bg-slate-800">
                @if ($hasUsableFeatured)
                    <img src="{{ $featured->url() }}"
                         alt="{{ $featured->alt_text ?? $title }}"
                         loading="lazy"
                         class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                @else
                    <img src="{{ $placeholderUrl }}"
                         alt="{{ $title }}"
                         loading="lazy"
                         class="h-full w-full object-cover transition duration-700 group-hover:scale-105">
                @endif
            </div>

            {{-- Overlay gradient — only on lg/xl for hero --}}
            @if (in_array($size, ['lg', 'xl']))
                <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-slate-900/80 via-slate-900/30 to-transparent"></div>
            @endif

            {{-- Top-left badges --}}
            <div class="absolute left-3 top-3 flex flex-wrap items-center gap-1.5">
                @if ($isBreaking)
                    <span class="inline-flex items-center gap-1 rounded-md bg-rose-600 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-white shadow-lg shadow-rose-600/30">
                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-white"></span>
                        Breaking
                    </span>
                @endif
                @if ($isFeatured && ! $isBreaking)
                    <span class="inline-flex items-center gap-1 rounded-md bg-amber-500 px-2 py-0.5 text-[10px] font-black uppercase tracking-wider text-white shadow-lg shadow-amber-500/30">
                        <i data-lucide="star" class="h-2.5 w-2.5"></i>
                        Featured
                    </span>
                @endif
            </div>

            {{-- Hero overlay title (xl/lg only) --}}
            @if (in_array($size, ['lg', 'xl']))
                <div class="absolute inset-x-0 bottom-0 p-5 md:p-6">
                    @if ($showCategory && $post->category)
                        <span class="mb-2 inline-block rounded bg-white/95 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.22em] text-slate-800">
                            {{ $post->category->translate('name') ?? '#'.$post->category_id }}
                        </span>
                    @endif
                    <h3 class="{{ $sizeMap['title'] }} leading-tight text-white drop-shadow-lg" style="font-family: 'Playfair Display', serif;">
                        {{ $title }}
                    </h3>
                    @if ($showExcerpt && $excerpt && $size === 'xl')
                        <p class="mt-2 line-clamp-2 {{ $sizeMap['excerpt'] }} text-white/90 drop-shadow">{{ $excerpt }}</p>
                    @endif
                    @if ($showMeta)
                        <div class="mt-3 flex flex-wrap items-center gap-2 {{ $sizeMap['meta'] }} text-white/80">
                            @if ($post->author?->name)
                                <span class="font-semibold">{{ $post->author->name }}</span>
                                <span class="opacity-50">·</span>
                            @endif
                            <span>{{ $post->published_at?->diffForHumans() }}</span>
                            <span class="opacity-50">·</span>
                            <span class="inline-flex items-center gap-0.5"><i data-lucide="clock" class="h-3 w-3"></i> {{ $readTime }} min</span>
                        </div>
                    @endif
                </div>
            @endif
        </a>

        {{-- Body — for sm/md only (lg/xl use overlay) --}}
        @if (! in_array($size, ['lg', 'xl']))
            <div class="flex flex-1 flex-col gap-2 {{ $sizeMap['pad'] }}">
                @if ($showCategory && $post->category)
                    <a href="{{ route('frontend.category', ['locale' => $locale?->code, 'slug' => $post->category->translate('slug')]) }}"
                       class="inline-flex w-fit items-center gap-1 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500 hover:text-emerald-700 hover:underline dark:text-slate-400 dark:hover:text-emerald-300">
                        @if ($post->category->icon)
                            <i data-lucide="{{ $post->category->icon }}" class="h-3 w-3"></i>
                        @endif
                        {{ $post->category->translate('name') ?? '#'.$post->category_id }}
                    </a>
                @endif

                <a href="{{ $url }}">
                    <h3 class="{{ $sizeMap['title'] }} leading-snug text-slate-900 transition group-hover:text-emerald-700 dark:text-slate-100 dark:group-hover:text-emerald-300"
                        style="font-family: 'Playfair Display', serif;">
                        {{ $title }}
                    </h3>
                </a>

                @if ($showExcerpt && $excerpt)
                    <p class="line-clamp-2 {{ $sizeMap['excerpt'] }} text-slate-600 dark:text-slate-400">{{ $excerpt }}</p>
                @endif

                @if ($showMeta)
                    <div class="mt-auto flex flex-wrap items-center gap-1.5 pt-2 {{ $sizeMap['meta'] }} text-slate-500 dark:text-slate-400">
                        @if ($post->author?->name)
                            <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $post->author->name }}</span>
                            <span class="opacity-50">·</span>
                        @endif
                        <span>{{ $post->published_at?->diffForHumans() }}</span>
                        <span class="opacity-50">·</span>
                        <span class="inline-flex items-center gap-0.5"><i data-lucide="clock" class="h-3 w-3"></i> {{ $readTime }} min</span>
                        @if ($post->view_count > 100)
                            <span class="opacity-50">·</span>
                            <span class="inline-flex items-center gap-0.5"><i data-lucide="eye" class="h-3 w-3"></i> {{ number_format($post->view_count) }}</span>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </article>
@endif
