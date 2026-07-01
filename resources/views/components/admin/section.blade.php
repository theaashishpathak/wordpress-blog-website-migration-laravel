@props([
    'title' => null,
    'description' => null,
    'compact' => false,
    'padded' => true,
])

{{--
    Admin section card — the universal "white panel with a heading" used
    across dashboards, settings, forms. Cleaner than rolling your own
    `<div class="rounded-xl border bg-white p-6 shadow-sm">` every time.

    Slots:
      $title       → string heading
      $description → muted subtitle below the heading
      $actions     → right-aligned top-right actions (filters / button)
      default      → body content
--}}
<section {{ $attributes->class([
    'rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900',
    $padded ? ($compact ? 'p-4 sm:p-5' : 'p-5 sm:p-6') : '',
]) }}>
    @if ($title || isset($actions))
        <div @class([
            'flex flex-wrap items-start justify-between gap-3',
            'mb-4' => $padded,
            'px-5 py-4 sm:px-6 border-b border-slate-200 dark:border-slate-800' => ! $padded,
        ])>
            <div class="min-w-0">
                @if ($title)
                    <h2 class="text-base font-bold tracking-tight text-slate-900 dark:text-slate-100">
                        {{ $title }}
                    </h2>
                @endif
                @if ($description)
                    <p class="mt-0.5 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
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
