@php
    $locale = app(\App\Support\LocaleResolver::class)->current();
@endphp

<div>
    <article class="mx-auto max-w-3xl px-4 py-8 lg:py-12">
        <nav class="mb-5 flex items-center gap-2 text-xs font-semibold text-slate-500" aria-label="Breadcrumb">
            <a href="{{ route('frontend.home', ['locale' => $locale?->code]) }}" class="transition hover:text-emerald-700">Home</a>
            <i data-lucide="chevron-right" class="h-3 w-3 text-slate-300"></i>
            <span class="truncate text-slate-400">{{ \Illuminate\Support\Str::limit($translation->title, 40) }}</span>
        </nav>

        <header class="mb-10 border-b border-slate-200 pb-6 dark:border-slate-800">
            <p class="mb-2 text-[10px] font-black uppercase tracking-[0.2em] text-emerald-700 dark:text-emerald-400">Page</p>
            <h1 class="text-4xl font-black leading-[1.1] tracking-tight text-slate-900 md:text-5xl dark:text-slate-50"
                style="font-family: 'Playfair Display', serif;">
                {{ $translation->title }}
            </h1>
            @if ($page->updated_at)
                <p class="mt-4 inline-flex items-center gap-1.5 text-xs text-slate-500">
                    <i data-lucide="calendar" class="h-3 w-3"></i>
                    Last updated {{ $page->updated_at->format('M j, Y') }}
                </p>
            @endif
        </header>

        <div class="prose prose-lg max-w-none dark:prose-invert">
            {!! $translation->content !!}
        </div>
    </article>
</div>
