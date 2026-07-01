@props([
    'label',
    'value',
    'icon' => null,
    'tone' => 'indigo',   // indigo | emerald | amber | rose | violet | sky | slate
    'href' => null,
    'delta' => null,      // optional small delta string like "+12% vs last week"
    'deltaTone' => 'emerald',  // emerald | rose | slate
])

@php
    $tones = [
        'indigo'  => ['bg' => 'bg-indigo-50 dark:bg-indigo-500/15',   'icon' => 'text-indigo-600 dark:text-indigo-300'],
        'emerald' => ['bg' => 'bg-emerald-50 dark:bg-emerald-500/15', 'icon' => 'text-emerald-600 dark:text-emerald-300'],
        'amber'   => ['bg' => 'bg-amber-50 dark:bg-amber-500/15',     'icon' => 'text-amber-600 dark:text-amber-300'],
        'rose'    => ['bg' => 'bg-rose-50 dark:bg-rose-500/15',       'icon' => 'text-rose-600 dark:text-rose-300'],
        'violet'  => ['bg' => 'bg-violet-50 dark:bg-violet-500/15',   'icon' => 'text-violet-600 dark:text-violet-300'],
        'sky'     => ['bg' => 'bg-sky-50 dark:bg-sky-500/15',         'icon' => 'text-sky-600 dark:text-sky-300'],
        'slate'   => ['bg' => 'bg-slate-100 dark:bg-slate-800',       'icon' => 'text-slate-600 dark:text-slate-300'],
    ];
    $t = $tones[$tone] ?? $tones['indigo'];

    $deltaClasses = [
        'emerald' => 'text-emerald-600 dark:text-emerald-300',
        'rose'    => 'text-rose-600 dark:text-rose-300',
        'slate'   => 'text-slate-500 dark:text-slate-400',
    ][$deltaTone] ?? 'text-slate-500 dark:text-slate-400';

    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" wire:navigate @endif
   class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition {{ $href ? 'hover:-translate-y-0.5 hover:shadow-md' : '' }} dark:border-slate-800 dark:bg-slate-900">
    <div class="flex items-center justify-between">
        @if ($icon)
            <span class="grid h-10 w-10 place-items-center rounded-xl {{ $t['bg'] }}">
                <i data-lucide="{{ $icon }}" class="h-4 w-4 {{ $t['icon'] }}"></i>
            </span>
        @endif
        @if ($href)
            <i data-lucide="arrow-up-right" class="h-3.5 w-3.5 text-slate-300 transition group-hover:text-slate-500"></i>
        @endif
    </div>
    <p class="mt-4 text-2xl font-black tracking-tight text-slate-900 dark:text-slate-50">
        {{ $value }}
    </p>
    <p class="mt-0.5 text-xs font-medium text-slate-500 dark:text-slate-400">{{ $label }}</p>
    @if ($delta)
        <p class="mt-2 text-[11px] font-semibold {{ $deltaClasses }}">
            {{ $delta }}
        </p>
    @endif
</{{ $tag }}>
