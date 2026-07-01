@props([
    'icon' => 'inbox',
    'title' => 'Nothing here yet',
    'description' => null,
])

{{--
    Editorial empty state — no dashed boxes, no gradient buttons. A quiet
    centered block with a thin icon, serif title, and a muted descriptor.
    Slot in a single anchor/button for the optional CTA.
--}}
<div class="rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center dark:border-slate-800 dark:bg-slate-900">
    <span class="grid h-12 w-12 mx-auto place-items-center rounded-full bg-slate-50 text-slate-400 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-500 dark:ring-slate-700">
        <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
    </span>
    <h3 class="mt-5 text-xl font-black tracking-tight text-slate-900 dark:text-slate-100"
        style="font-family: 'Playfair Display', serif;">
        {{ $title }}
    </h3>
    @if ($description)
        <p class="mx-auto mt-2 max-w-sm text-sm leading-relaxed text-slate-500 dark:text-slate-400">
            {{ $description }}
        </p>
    @endif
    @isset($action)
        <div class="mt-6">
            {{ $action }}
        </div>
    @endisset
</div>
