@php
    $locale = app(\App\Support\LocaleResolver::class)->current();
    $picks = $this->picks;
@endphp

<div class="space-y-8">
    <x-visitor.page-header
        eyebrow="Engagement"
        title="For You"
        description="{{ $hasSignal
            ? 'Hand-picked from your reading history and reactions.'
            : 'Browse a few articles or hit Like — recommendations sharpen as you go.' }}">
        <x-slot:actions>
            <button type="button" wire:click="refreshFeed"
                    wire:loading.attr="disabled" wire:target="refreshFeed"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 disabled:opacity-60 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                <i data-lucide="refresh-cw" class="h-3.5 w-3.5" wire:loading.remove wire:target="refreshFeed"></i>
                <i data-lucide="loader-2" class="h-3.5 w-3.5 animate-spin" wire:loading wire:target="refreshFeed"></i>
                <span wire:loading.remove wire:target="refreshFeed">Refresh feed</span>
                <span wire:loading wire:target="refreshFeed">Refreshing…</span>
            </button>
        </x-slot:actions>
    </x-visitor.page-header>

    @if (! $hasSignal)
        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-300">
            <p class="flex items-center gap-2 font-bold">
                <i data-lucide="info" class="h-4 w-4 text-emerald-600 dark:text-emerald-400"></i>
                Showing fresh articles for now
            </p>
            <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                Once you read or react to a few articles, we'll personalise this feed by topic, category, and recency.
            </p>
        </div>
    @endif

    @if ($picks->isEmpty())
        <x-visitor.empty-state
            icon="sparkles"
            title="No recommendations yet."
            description="There are no published articles matching your interests right now.">
            <x-slot:action>
                <a href="{{ route('frontend.home', ['locale' => $locale?->code]) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                    <i data-lucide="newspaper" class="h-4 w-4"></i>
                    Browse latest
                </a>
            </x-slot:action>
        </x-visitor.empty-state>
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($picks as $post)
                <x-frontend.post-card :post="$post" />
            @endforeach
        </div>
    @endif
</div>
