@props([
    'icon' => 'inbox',
    'title' => 'Nothing here yet',
    'description' => null,
])

{{--
    Admin empty state — calmer than dashed-border boxes. Use inside an
    <x-admin.section> body OR standalone on a page that has no header
    card. Slot `action` renders the optional CTA button below the copy.
--}}
<div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center dark:border-slate-700 dark:bg-slate-900">
    <span class="grid h-12 w-12 mx-auto place-items-center rounded-full bg-slate-50 text-slate-400 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-500 dark:ring-slate-700">
        <i data-lucide="{{ $icon }}" class="h-5 w-5"></i>
    </span>
    <h3 class="mt-4 text-base font-bold tracking-tight text-slate-900 dark:text-slate-100">
        {{ $title }}
    </h3>
    @if ($description)
        <p class="mx-auto mt-1.5 max-w-md text-sm leading-relaxed text-slate-500 dark:text-slate-400">
            {{ $description }}
        </p>
    @endif
    @isset($action)
        <div class="mt-5">
            {{ $action }}
        </div>
    @endisset
</div>
