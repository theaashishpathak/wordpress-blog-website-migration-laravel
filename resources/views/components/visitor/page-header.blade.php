@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'icon' => null,
])

{{--
    Editorial page header — replaces the boxed gradient-icon card pattern.
    Renders as a borderless lede with a small Playfair italic eyebrow,
    a large Playfair display title, and a muted descriptor. Right-aligned
    actions slot in via $slot (search, filters, primary CTA).
--}}
<header class="border-b border-slate-200 pb-6 dark:border-slate-800">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0 flex-1">
            @if ($eyebrow)
                <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.24em] text-emerald-700 dark:text-emerald-300">
                    {{ $eyebrow }}
                </p>
            @endif
            <h1 class="text-3xl font-black tracking-tight text-slate-900 md:text-4xl dark:text-slate-50"
                style="font-family: 'Playfair Display', serif;">
                {{ $title }}
            </h1>
            @if ($description)
                <p class="mt-2 max-w-prose text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                    {{ $description }}
                </p>
            @endif
        </div>

        @isset($actions)
            <div class="flex shrink-0 flex-wrap items-center gap-2">
                {{ $actions }}
            </div>
        @endisset
    </div>
</header>
