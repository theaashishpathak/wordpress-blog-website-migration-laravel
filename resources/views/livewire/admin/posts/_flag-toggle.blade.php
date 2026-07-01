{{-- Single colored flag-toggle card. Props:
       $name           — Livewire property name (boolean)
       $label          — visible label
       $icon           — lucide icon name
       $colorClasses   — base unselected color theme
       $activeClasses  — additional classes when toggled on
       $state          — current boolean value (used to render active styling on first paint)
--}}
<label
    class="group relative flex cursor-pointer items-center gap-2.5 rounded-xl border-2 px-3 py-2.5 transition-all duration-200 hover:shadow-sm
           {{ $state ? $activeClasses : 'border-transparent ring-1 ring-slate-200 hover:ring-slate-300 dark:ring-slate-700' }}
           {{ $colorClasses }}"
>
    <input
        type="checkbox"
        wire:model.live="{{ $name }}"
        class="peer sr-only"
    >

    {{-- Icon swatch --}}
    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-white/70 transition group-hover:scale-105 dark:bg-slate-900/60">
        <i data-lucide="{{ $icon }}" class="h-3.5 w-3.5"></i>
    </span>

    <span class="flex-1 text-sm font-semibold">{{ $label }}</span>

    {{-- Check indicator --}}
    <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full border-2 transition
                 {{ $state ? 'border-current bg-current text-white' : 'border-current/40 bg-transparent' }}">
        @if ($state)
            <i data-lucide="check" class="h-3 w-3 text-white"></i>
        @endif
    </span>
</label>
