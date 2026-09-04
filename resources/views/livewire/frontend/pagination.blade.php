@php
if (! isset($scrollTo)) {
    $scrollTo = '#latest-stories';
}

$scrollJs = ($scrollTo !== false)
    ? "document.querySelector('{$scrollTo}')?.scrollIntoView({ behavior: 'smooth' })"
    : '';
@endphp

<div>
    @if ($paginator->hasPages())
        <nav role="navigation" aria-label="Pagination" class="flex items-center justify-center gap-1.5 sm:gap-2">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}"
                      class="grid h-9 w-9 place-items-center rounded-full text-slate-300 dark:text-neutral-700 cursor-not-allowed">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                    </svg>
                </span>
            @else
                <button type="button"
                        wire:click="previousPage('{{ $paginator->getPageName() }}')"
                        x-on:click="{{ $scrollJs }}"
                        wire:loading.attr="disabled"
                        aria-label="{{ __('pagination.previous') }}"
                        class="grid h-9 w-9 place-items-center rounded-full text-slate-700 hover:bg-slate-200 transition dark:text-neutral-300 dark:hover:bg-neutral-800">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <span aria-disabled="true" class="px-1 text-xs font-bold text-slate-400 dark:text-neutral-600 select-none">
                        ···
                    </span>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <span wire:key="paginator-{{ $paginator->getPageName() }}-page{{ $page }}">
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page"
                                      class="grid h-9 w-9 place-items-center rounded-full bg-slate-900 text-xs font-bold text-white shadow-sm dark:bg-emerald-600 dark:text-white">
                                    {{ $page }}
                                </span>
                            @else
                                <button type="button"
                                        wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                        x-on:click="{{ $scrollJs }}"
                                        aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                        class="grid h-9 w-9 place-items-center rounded-full text-xs font-bold text-slate-700 hover:bg-slate-200 transition dark:text-neutral-300 dark:hover:bg-neutral-800">
                                    {{ $page }}
                                </button>
                            @endif
                        </span>
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <button type="button"
                        wire:click="nextPage('{{ $paginator->getPageName() }}')"
                        x-on:click="{{ $scrollJs }}"
                        wire:loading.attr="disabled"
                        aria-label="{{ __('pagination.next') }}"
                        class="grid h-9 w-9 place-items-center rounded-full text-slate-700 hover:bg-slate-200 transition dark:text-neutral-300 dark:hover:bg-neutral-800">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            @else
                <span aria-disabled="true" aria-label="{{ __('pagination.next') }}"
                      class="grid h-9 w-9 place-items-center rounded-full text-slate-300 dark:text-neutral-700 cursor-not-allowed">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                    </svg>
                </span>
            @endif
        </nav>
    @endif
</div>
