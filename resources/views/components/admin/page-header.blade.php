@props([
    'eyebrow' => null,
    'title',
    'description' => null,
    'icon' => null,
])

{{--
    Admin page header — consistent lede block for every admin route.
    Eyebrow + title + descriptor on the left; `actions` slot on the
    right for filters / primary CTA buttons. Drop in via:

      <x-admin.page-header eyebrow="Library" title="Posts" description="...">
          <x-slot:actions>
              <button class="...">New post</button>
          </x-slot:actions>
      </x-admin.page-header>
--}}
<header class="border-b border-slate-200 pb-5 mb-6 dark:border-slate-800">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="min-w-0 flex-1">
            @if ($eyebrow)
                <p class="mb-1 flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-[0.22em] text-indigo-600 dark:text-indigo-400">
                    @if ($icon)
                        <i data-lucide="{{ $icon }}" class="h-3 w-3"></i>
                    @endif
                    {{ $eyebrow }}
                </p>
            @endif
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 md:text-3xl dark:text-slate-50">
                {{ $title }}
            </h1>
            @if ($description)
                <p class="mt-1.5 max-w-prose text-sm leading-relaxed text-slate-500 dark:text-slate-400">
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
