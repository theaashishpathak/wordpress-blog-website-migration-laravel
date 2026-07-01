{{--
    Admin global search popover.

    Search lives in the layout, not as a routable page, so the
    GlobalSearch component intentionally does NOT sync `$query` to the
    URL — doing so left ?q=… on every wire:navigate destination and
    corrupted Livewire's snapshot when a debounced update raced the
    navigation. The popover is purely client-state.
--}}
<div
    class="relative flex-1"
    x-data="globalSearchPopover()"
    x-init="init()"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    @keydown.window.prevent.ctrl.k="$refs.input.focus()"
    @keydown.window.prevent.meta.k="$refs.input.focus()"
>
    {{-- Input --}}
    <div class="relative">
        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
        <input
            type="text"
            x-ref="input"
            wire:model.live.debounce.300ms="query"
            placeholder="Search posts, pages, tags, media, comments, staff…"
            @focus="if ($wire.query.length > 0) open = true"
            @keydown.arrow-down.prevent="moveCursor(1)"
            @keydown.arrow-up.prevent="moveCursor(-1)"
            @keydown.enter.prevent="navigateToCursor()"
            class="h-10 w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-10 text-sm placeholder:text-slate-400 focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-800 dark:focus:bg-slate-900"
        >

        {{-- Ctrl+K hint removed — the kbd rendered as a near-invisible
             empty pill on most viewports (text-slate-400 on bg-white at
             10px was lost in the bundle's hover/responsive variants).
             The search shortcut still works; the placeholder already
             tells users what the field is for. --}}

        {{-- Clear --}}
        @if ($query !== '')
            <button
                type="button"
                wire:click="close"
                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1 text-slate-400 hover:bg-slate-200 hover:text-slate-600 dark:hover:bg-slate-700"
                aria-label="Clear search"
            >
                <i data-lucide="x" class="h-4 w-4"></i>
            </button>
        @endif
    </div>

    {{-- Dropdown --}}
    @if ($open && $query !== '')
        <div
            x-show="open"
            x-transition.opacity.duration.150ms
            class="absolute left-0 right-0 top-12 z-40 max-h-[28rem] overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900"
        >
            @if ($total === 0)
                <div wire:loading.remove wire:target="query" class="px-6 py-10 text-center text-sm">
                    <i data-lucide="search-x" class="mx-auto h-8 w-8 text-slate-300"></i>
                    <p class="mt-3 font-semibold text-slate-700 dark:text-slate-200">No matches found</p>
                    <p class="mt-1 text-xs text-slate-500">Try a different keyword.</p>
                </div>
                <div wire:loading wire:target="query" class="px-6 py-10 text-center text-sm">
                    <i data-lucide="loader-2" class="mx-auto h-8 w-8 animate-spin text-indigo-400"></i>
                    <p class="mt-3 font-semibold text-slate-700 dark:text-slate-200">Searching…</p>
                </div>
            @else
                <header class="flex items-center justify-between border-b border-slate-100 bg-slate-50/60 px-4 py-2 dark:border-slate-800 dark:bg-slate-950/40">
                    <span class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">
                        {{ $total }} {{ \Illuminate\Support\Str::plural('result', $total) }} across {{ count($groups) }} {{ \Illuminate\Support\Str::plural('module', count($groups)) }}
                    </span>
                    <span class="hidden items-center gap-1 text-[10px] text-slate-400 sm:flex">
                        <kbd class="rounded border border-slate-200 bg-white px-1.5 py-0.5 font-sans text-[10px] font-semibold dark:border-slate-700 dark:bg-slate-900">Up</kbd>
                        <kbd class="rounded border border-slate-200 bg-white px-1.5 py-0.5 font-sans text-[10px] font-semibold dark:border-slate-700 dark:bg-slate-900">Down</kbd>
                        to navigate
                        <kbd class="ml-1 rounded border border-slate-200 bg-white px-1.5 py-0.5 font-sans text-[10px] font-semibold dark:border-slate-700 dark:bg-slate-900">Enter</kbd>
                        to open
                    </span>
                </header>

                @php($flatIndex = 0)
                @foreach ($groups as $key => $group)
                    <div class="border-b border-slate-100 dark:border-slate-800">
                        <header class="flex items-center justify-between gap-3 bg-slate-50/40 px-4 py-2 dark:bg-slate-950/30">
                            <div class="flex items-center gap-2">
                                <span class="grid h-5 w-5 place-items-center rounded-md bg-gradient-to-br {{ $group['color'] }} text-white">
                                    <i data-lucide="{{ $group['icon'] }}" class="h-3 w-3"></i>
                                </span>
                                <span class="text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">
                                    {{ $group['label'] }} ({{ $group['items']->count() }})
                                </span>
                            </div>
                            @if (! empty($group['see_all_url']))
                                <a href="{{ $group['see_all_url'] }}" wire:navigate class="text-[10px] font-semibold text-indigo-600 hover:underline dark:text-indigo-300">
                                    See all →
                                </a>
                            @endif
                        </header>
                        <ul>
                            @foreach ($group['items'] as $item)
                                @php($index = $flatIndex++)
                                <li>
                                    <a
                                        href="{{ $item['url'] ?? '#' }}"
                                        wire:navigate
                                        class="flex items-start gap-3 px-4 py-2.5 transition hover:bg-indigo-50/60 dark:hover:bg-indigo-500/10"
                                        :class="cursor === {{ $index }} ? 'bg-indigo-50 dark:bg-indigo-500/10' : ''"
                                        @mouseenter="cursor = {{ $index }}"
                                    >
                                        <span class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                                            <i data-lucide="{{ $item['icon'] ?? $group['icon'] }}" class="h-3.5 w-3.5"></i>
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $item['label'] }}</p>
                                            @if (! empty($item['sublabel']))
                                                <p class="truncate text-xs text-slate-500">{{ $item['sublabel'] }}</p>
                                            @endif
                                        </div>
                                        <i data-lucide="arrow-up-right" class="mt-1 h-3.5 w-3.5 shrink-0 text-slate-300"></i>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            @endif
        </div>
    @endif
</div>

@script
<script>
    /**
     * Alpine data factory for the popover.
     *
     * - `open` is the dropdown's visible flag (mirrors $wire.open).
     * - `cursor` tracks the keyboard-highlighted row index. We resolve
     *   the destination URL via the data-* attribute set by the server
     *   so navigation works without re-rendering the entire list.
     */
    Alpine.data('globalSearchPopover', () => ({
        open: false,
        cursor: 0,

        init() {
            this.$watch('$wire.open', (val) => this.open = !!val);
            this.$watch('$wire.query', () => this.cursor = 0);
        },

        moveCursor(delta) {
            const links = this.$root.querySelectorAll('a[wire\\:navigate]');
            if (links.length === 0) return;

            this.cursor = (this.cursor + delta + links.length) % links.length;
            const target = links[this.cursor];
            target?.scrollIntoView({ block: 'nearest' });
        },

        navigateToCursor() {
            const links = this.$root.querySelectorAll('a[wire\\:navigate]');
            const link = links[this.cursor] ?? links[0];
            if (link) link.click();
        },
    }));
</script>
@endscript
