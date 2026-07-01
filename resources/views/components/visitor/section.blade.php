@props([
    'title' => null,
    'description' => null,
    'compact' => false,
])

{{--
    Editorial section card — calmer than the original (less shadow, thinner
    border, restrained color). Use {{ $actions }} for top-right controls
    next to the heading; the body slot is the default $slot.
--}}
<section {{ $attributes->class([
    'rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900',
    $compact ? 'p-4 sm:p-5' : 'p-5 sm:p-6',
]) }}>
    @if ($title || isset($actions))
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div>
                @if ($title)
                    <h2 class="text-lg font-black tracking-tight text-slate-900 dark:text-slate-100"
                        style="font-family: 'Playfair Display', serif;">
                        {{ $title }}
                    </h2>
                @endif
                @if ($description)
                    <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
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
    @endif

    {{ $slot }}
</section>
