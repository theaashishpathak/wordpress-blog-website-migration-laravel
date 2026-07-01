@props([
    'title' => null,
    'description' => null,
    'maxWidth' => 'max-w-lg',
    'icon' => null,
    'wireShow' => null,
])

{{--
    Admin centered dialog — used for small CRUD forms (Permission Group,
    Permission, Assign Role).

    Two usage modes:

    1. ENTANGLED (recommended for perf) — pass the Livewire boolean property
       name via `wire-show`. Alpine binds two-way via @entangle, so
       open / close happens 100% client-side. Server only roundtrips when
       the parent's `edit($id)` or `create()` action repopulates form data.

           <x-admin.dialog wire-show="showModal" title="Edit">
               @if ($showModal) <livewire:... /> @endif
           </x-admin.dialog>

    2. UNCONTROLLED (legacy) — omit `wire-show` and wrap the whole dialog
       with `@if ($showModal)`. Open is instant; close requires a server
       roundtrip to clear `showModal`.

    The parent component must still expose a `close()` method so Esc /
    backdrop / X can call back into Livewire to clear `editingId` /
    `formKey`. The dialog hides client-side first, then sends the
    roundtrip — so the animation is never blocked.

    Slots:
      $title       string heading
      $description muted subtitle under the heading
      $footer      sticky bottom action bar
      default      body of the form
--}}

@php
    $shownExpr = $wireShow
        ? "\$wire.entangle('".$wireShow."')"
        : 'true';
@endphp

<div
    x-data="{ shown: {{ $shownExpr }} }"
    x-show="shown"
    x-cloak
    @keydown.escape.window="shown = false; if (typeof $wire.close === 'function') $wire.close()"
    class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        x-show="shown"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-slate-900/60"
        @click="shown = false; if (typeof $wire.close === 'function') $wire.close()"
    ></div>

    {{-- Panel --}}
    <div
        x-show="shown"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        {{ $attributes->class([
            'relative z-10 flex max-h-[90vh] w-full flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-slate-900',
            $maxWidth,
        ]) }}
    >
        {{-- Header --}}
        @if ($title || $description)
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                <div class="flex min-w-0 items-start gap-3">
                    @if ($icon)
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 text-white shadow-sm">
                            <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
                        </span>
                    @endif
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
                </div>
                <button
                    type="button"
                    @click="shown = false; if (typeof $wire.close === 'function') $wire.close()"
                    class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                    aria-label="Close"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
        @endif

        {{-- Body (scrollable) --}}
        <div class="flex-1 overflow-y-auto px-6 py-5">
            {{ $slot }}
        </div>

        {{-- Footer (sticky) --}}
        @isset($footer)
            <div class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 bg-slate-50 px-6 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>
