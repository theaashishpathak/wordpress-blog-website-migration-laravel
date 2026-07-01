<div class="space-y-5">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">Editorial Calendar</span>
    </nav>

    {{-- Header + month nav --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-violet-500 to-pink-500 text-white shadow-sm">
                <i data-lucide="calendar" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">
                    {{ \Carbon\Carbon::createFromFormat('Y-m', $month)->format('F Y') }}
                </h1>
                <p class="text-xs text-slate-500">Scheduled posts, published timeline, and drafts in motion.</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" wire:click="previousMonth"
                    class="grid h-9 w-9 place-items-center rounded-xl border border-slate-200 bg-white hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900"
                    title="Previous month">
                <i data-lucide="chevron-left" class="h-4 w-4"></i>
            </button>
            <button type="button" wire:click="jumpToday"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                Today
            </button>
            <button type="button" wire:click="nextMonth"
                    class="grid h-9 w-9 place-items-center rounded-xl border border-slate-200 bg-white hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900"
                    title="Next month">
                <i data-lucide="chevron-right" class="h-4 w-4"></i>
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:grid-cols-[1fr_1fr_1fr_120px]">
        <select wire:model.live="authorFilter"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
            <option value="">All authors</option>
            @foreach ($this->authorOptions as $author)
                <option value="{{ $author->id }}">{{ $author->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="categoryFilter"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
            <option value="">All categories</option>
            @foreach ($this->categoryOptions as $cat)
                <option value="{{ $cat->id }}">{{ $cat->translate('name') ?? '#'.$cat->id }}</option>
            @endforeach
        </select>
        <select wire:model.live="statusFilter"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
            <option value="">All statuses</option>
            @foreach ($this->statusOptions as $s)
                <option value="{{ $s['value'] }}">{{ $s['label'] }}</option>
            @endforeach
        </select>
        <button type="button" wire:click="clearFilters"
                class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
            <i data-lucide="filter-x" class="h-3.5 w-3.5"></i> Clear
        </button>
    </div>

    {{-- Calendar grid --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        {{-- Day-of-week header --}}
        <div class="grid grid-cols-7 border-b border-slate-100 bg-slate-50/60 text-center text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-950/40">
            @foreach (['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dow)
                <div class="px-2 py-2">{{ $dow }}</div>
            @endforeach
        </div>

        {{-- Day cells --}}
        <div class="grid grid-cols-7 divide-x divide-y divide-slate-100 dark:divide-slate-800">
            @foreach ($this->days as $day)
                @php
                    /** @var \Carbon\Carbon $date */
                    $date = $day['date'];
                @endphp
                <div @class([
                        'min-h-[120px] p-2 transition',
                        'bg-slate-50/40 text-slate-400 dark:bg-slate-900/30' => ! $day['inMonth'],
                        'bg-white dark:bg-slate-900' => $day['inMonth'] && ! $day['isToday'],
                        'bg-indigo-50/60 dark:bg-indigo-500/10' => $day['isToday'],
                     ])>
                    <div class="flex items-center justify-between">
                        <span @class([
                            'text-xs font-bold',
                            'text-indigo-600' => $day['isToday'],
                            'text-slate-700 dark:text-slate-200' => $day['inMonth'] && ! $day['isToday'],
                            'text-slate-400' => ! $day['inMonth'],
                        ])>{{ $date->format('j') }}</span>
                        @if (count($day['posts']) > 0)
                            <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[9px] font-mono text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                {{ count($day['posts']) }}
                            </span>
                        @endif
                    </div>

                    <div class="mt-1 space-y-1">
                        @foreach (collect($day['posts'])->take(4) as $post)
                            @php
                                $t = $post->translation();
                                $title = $t?->title ?? '#'.$post->id;
                                $statusValue = $post->status->value;
                                $color = match ($statusValue) {
                                    'published' => 'border-emerald-300 bg-emerald-50 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300',
                                    'scheduled' => 'border-violet-300 bg-violet-50 text-violet-800 dark:bg-violet-500/10 dark:text-violet-300',
                                    'pending_review', 'in_review' => 'border-amber-300 bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300',
                                    'changes_requested' => 'border-orange-300 bg-orange-50 text-orange-800 dark:bg-orange-500/10 dark:text-orange-300',
                                    'approved' => 'border-indigo-300 bg-indigo-50 text-indigo-800 dark:bg-indigo-500/10 dark:text-indigo-300',
                                    'rejected' => 'border-rose-300 bg-rose-50 text-rose-800 dark:bg-rose-500/10 dark:text-rose-300',
                                    'archived' => 'border-zinc-300 bg-zinc-50 text-zinc-700 dark:bg-zinc-500/10 dark:text-zinc-300',
                                    default => 'border-slate-300 bg-slate-50 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                                };
                            @endphp
                            <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate
                               class="block truncate rounded-md border px-1.5 py-1 text-[10px] font-semibold {{ $color }} hover:shadow"
                               title="{{ $title }}">
                                {{ $title }}
                            </a>
                        @endforeach

                        @if (count($day['posts']) > 4)
                            <p class="text-center text-[10px] font-semibold text-slate-500">
                                +{{ count($day['posts']) - 4 }} more
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Legend --}}
    <div class="flex flex-wrap items-center gap-3 text-[10px] font-semibold text-slate-500">
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded bg-emerald-500"></span> Published</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded bg-violet-500"></span> Scheduled</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded bg-amber-500"></span> Pending review</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded bg-orange-500"></span> Changes requested</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded bg-indigo-500"></span> Approved</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded bg-rose-500"></span> Rejected</span>
        <span class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded bg-slate-400"></span> Draft</span>
    </div>
</div>
