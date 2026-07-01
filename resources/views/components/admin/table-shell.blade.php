@props([
    'title' => null,
    'description' => null,
    'searchModel' => null,         // wire:model expression for search input, e.g. "search"
    'searchPlaceholder' => 'Search…',
    'searchDebounce' => 400,
])

{{--
    Universal table shell — sticky toolbar (title + search + actions),
    proper overflow, hairline-divided rows.

    Slots:
      $title       string, optional
      $description string, optional
      $actions     right side of the sticky toolbar (filter chips, "New" CTA)
      $bulkActions appears just above the table when present (use wire:if to toggle)
      default      table HTML (you bring your own <table>, headers, rows)

    Example:
      <x-admin.table-shell title="Posts" search-model="search">
          <x-slot:actions>
              <button wire:click="newPost" class="...">New post</button>
          </x-slot:actions>
          <table class="min-w-full divide-y divide-slate-200 text-sm">
              ...
          </table>
      </x-admin.table-shell>
--}}
<section class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
    {{-- Sticky toolbar — survives long-scrolling tables. --}}
    <div class="sticky top-16 z-10 flex flex-wrap items-center gap-3 rounded-t-2xl border-b border-slate-200 bg-white px-5 py-3 dark:border-slate-800 dark:bg-slate-900">
        @if ($title || $description)
            <div class="min-w-0 flex-1">
                @if ($title)
                    <h2 class="text-sm font-bold tracking-tight text-slate-900 dark:text-slate-100">
                        {{ $title }}
                    </h2>
                @endif
                @if ($description)
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        {{ $description }}
                    </p>
                @endif
            </div>
        @endif

        @if ($searchModel)
            <div class="relative w-full sm:w-72">
                <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                <input
                    type="text"
                    wire:model.live.debounce.{{ (int) $searchDebounce }}ms="{{ $searchModel }}"
                    placeholder="{{ $searchPlaceholder }}"
                    class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm outline-none transition focus:border-indigo-400 focus:bg-white focus:ring-1 focus:ring-indigo-400 dark:border-slate-700 dark:bg-slate-950"
                >
            </div>
        @endif

        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">
                {{ $actions }}
            </div>
        @endisset
    </div>

    {{-- Optional bulk-actions row (shown by parent when rows are selected). --}}
    @isset($bulkActions)
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 bg-indigo-50/60 px-5 py-2 text-xs dark:border-slate-800 dark:bg-indigo-500/5">
            {{ $bulkActions }}
        </div>
    @endisset

    <div class="overflow-x-auto">
        {{ $slot }}
    </div>

    @isset($footer)
        <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">
            {{ $footer }}
        </div>
    @endisset
</section>
