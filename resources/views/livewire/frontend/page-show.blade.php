@php
    $template = $page->template ?? \App\Models\Page::TEMPLATE_DEFAULT;
@endphp

<div>
@if ($template === \App\Models\Page::TEMPLATE_FULL_WIDTH || $template === 'full-width')
    {{-- Full-width Layout (max-w-[1360px]) --}}
    <article class="mx-auto max-w-[1360px] px-4 sm:px-6 lg:px-8 py-8 lg:py-12">
        <nav class="mb-6 flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-neutral-400" aria-label="Breadcrumb">
            <a href="{{ route('frontend.home') }}" class="transition hover:text-emerald-600 dark:hover:text-emerald-400">Home</a>
            <i data-lucide="chevron-right" class="h-3 w-3 text-slate-400"></i>
            <span class="truncate text-slate-400 dark:text-neutral-500">{{ \Illuminate\Support\Str::limit($translation->title, 40) }}</span>
        </nav>

        <header class="mb-10 border-b border-slate-200/80 pb-6 dark:border-neutral-800">
            <h1 class="text-4xl font-black leading-[1.15] tracking-tight text-slate-900 md:text-5xl lg:text-6xl dark:text-white"
                style="font-family: 'Playfair Display', serif;">
                {{ $translation->title }}
            </h1>
            @if ($page->updated_at)
                <p class="mt-4 inline-flex items-center gap-1.5 text-xs text-slate-500 dark:text-neutral-400">
                    <i data-lucide="calendar" class="h-3.5 w-3.5"></i>
                    Last updated {{ $page->updated_at->format('M j, Y') }}
                </p>
            @endif
        </header>

        <div class="prose prose-lg max-w-none text-slate-800 dark:text-neutral-200 dark:prose-invert leading-relaxed">
            {!! $translation->content !!}
        </div>
    </article>
@elseif ($template === \App\Models\Page::TEMPLATE_LANDING || $template === 'landing')
    {{-- Landing Page Layout (Centered Hero + Elevated Card) --}}
    <article class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-10 lg:py-16">
        <header class="mb-12 text-center">
            <nav class="mb-6 inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/80 px-4 py-1.5 text-xs font-semibold text-slate-500 shadow-2xs backdrop-blur-xs dark:border-[#262832] dark:bg-[#181920]/80 dark:text-neutral-400" aria-label="Breadcrumb">
                <a href="{{ route('frontend.home') }}" class="transition hover:text-emerald-600 dark:hover:text-emerald-400">Home</a>
                <i data-lucide="chevron-right" class="h-3 w-3 text-slate-400"></i>
                <span class="truncate text-slate-700 dark:text-neutral-200">{{ \Illuminate\Support\Str::limit($translation->title, 35) }}</span>
            </nav>

            <p class="mb-3 text-[11px] font-black uppercase tracking-[0.25em] text-emerald-600 dark:text-emerald-400">Featured Page</p>
            <h1 class="mx-auto max-w-3xl text-4xl font-black leading-[1.15] tracking-tight text-slate-900 sm:text-5xl lg:text-6xl dark:text-white"
                style="font-family: 'Playfair Display', serif;">
                {{ $translation->title }}
            </h1>
            @if ($page->updated_at)
                <p class="mt-4 inline-flex items-center justify-center gap-1.5 text-xs text-slate-500 dark:text-neutral-400">
                    <i data-lucide="calendar" class="h-3.5 w-3.5"></i>
                    Last updated {{ $page->updated_at->format('M j, Y') }}
                </p>
            @endif
        </header>

        <div class="mx-auto max-w-4xl rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-10 lg:p-12 shadow-xs dark:border-[#22242e] dark:bg-[#13141a]">
            <div class="prose prose-lg max-w-none text-slate-800 dark:text-neutral-200 dark:prose-invert leading-relaxed">
                {!! $translation->content !!}
            </div>
        </div>
    </article>
@else
    {{-- Default Editorial Column (max-w-4xl) --}}
    <article class="mx-auto max-w-4xl px-4 sm:px-6 py-8 lg:py-12">
        <nav class="mb-6 flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-neutral-400" aria-label="Breadcrumb">
            <a href="{{ route('frontend.home') }}" class="transition hover:text-emerald-600 dark:hover:text-emerald-400">Home</a>
            <i data-lucide="chevron-right" class="h-3 w-3 text-slate-400"></i>
            <span class="truncate text-slate-400 dark:text-neutral-500">{{ \Illuminate\Support\Str::limit($translation->title, 40) }}</span>
        </nav>

        <header class="mb-10 border-b border-slate-200/80 pb-6 dark:border-neutral-800">
            <h1 class="text-4xl font-black leading-[1.15] tracking-tight text-slate-900 md:text-5xl dark:text-white"
                style="font-family: 'Playfair Display', serif;">
                {{ $translation->title }}
            </h1>
            @if ($page->updated_at)
                <p class="mt-4 inline-flex items-center gap-1.5 text-xs text-slate-500 dark:text-neutral-400">
                    <i data-lucide="calendar" class="h-3.5 w-3.5"></i>
                    Last updated {{ $page->updated_at->format('M j, Y') }}
                </p>
            @endif
        </header>

        <div class="prose prose-lg max-w-none text-slate-800 dark:text-neutral-200 dark:prose-invert leading-relaxed">
            {!! $translation->content !!}
        </div>
    </article>
@endif
</div>
