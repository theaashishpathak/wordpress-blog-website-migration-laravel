<div class="space-y-5"
     x-data="{
         draggedId: null,
         pickUp(id) { this.draggedId = id; },
         drop(status) {
             if (this.draggedId === null) return;
             $wire.move(status, this.draggedId);
             this.draggedId = null;
         }
     }">

    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">Editorial Queue</span>
    </nav>

    {{-- Header bar --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-500 text-white shadow-sm">
                <i data-lucide="kanban" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">Editorial Review Queue</h1>
                <p class="text-xs text-slate-500">Drag cards between columns to move posts through the editorial pipeline.</p>
            </div>
        </div>

        <button type="button"
                wire:click="clearFilters"
                class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
            <i data-lucide="filter-x" class="h-3.5 w-3.5"></i>
            Clear Filters
        </button>
    </div>

    {{-- Filter bar --}}
    <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:grid-cols-[1fr_180px_180px_160px]">
        <div class="relative">
            <i data-lucide="search" class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
            <input type="text"
                   wire:model.live.debounce.400ms="search"
                   placeholder="Search post titles…"
                   class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950">
        </div>

        <select wire:model.live="categoryFilter"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
            <option value="">All categories</option>
            @foreach ($this->categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->translate('name') ?? '#'.$cat->id }}</option>
            @endforeach
        </select>

        <select wire:model.live="authorFilter"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
            <option value="">All authors</option>
            @foreach ($this->authors as $author)
                <option value="{{ $author->id }}">{{ $author->name }}</option>
            @endforeach
        </select>

        <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900">
            <input type="checkbox" wire:model.live="onlyMine"
                   class="h-3.5 w-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
            My queue only
        </label>
    </div>

    {{-- Columns --}}
    <div class="grid gap-4 overflow-x-auto pb-4 lg:grid-cols-5"
         wire:loading.class="opacity-60"
         wire:target="move,search,categoryFilter,authorFilter,onlyMine">
        @foreach ($this->columns as $statusValue => $col)
            @php
                $color = $col['color'];
                $colHeader = match ($color) {
                    'amber' => 'border-amber-200 bg-amber-50 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300',
                    'sky' => 'border-sky-200 bg-sky-50 text-sky-800 dark:bg-sky-500/10 dark:text-sky-300',
                    'orange' => 'border-orange-200 bg-orange-50 text-orange-800 dark:bg-orange-500/10 dark:text-orange-300',
                    'indigo' => 'border-indigo-200 bg-indigo-50 text-indigo-800 dark:bg-indigo-500/10 dark:text-indigo-300',
                    'violet' => 'border-violet-200 bg-violet-50 text-violet-800 dark:bg-violet-500/10 dark:text-violet-300',
                    default => 'border-slate-200 bg-slate-50 text-slate-700 dark:bg-slate-800',
                };
                $colDot = match ($color) {
                    'amber' => 'bg-amber-500',
                    'sky' => 'bg-sky-500',
                    'orange' => 'bg-orange-500',
                    'indigo' => 'bg-indigo-500',
                    'violet' => 'bg-violet-500',
                    default => 'bg-slate-400',
                };
            @endphp

            <div class="flex min-w-[300px] flex-col rounded-2xl border border-slate-200 bg-slate-50/40 dark:border-slate-800 dark:bg-slate-950/40"
                 x-on:dragover.prevent="$el.classList.add('ring-2', 'ring-indigo-400')"
                 x-on:dragleave="$el.classList.remove('ring-2', 'ring-indigo-400')"
                 x-on:drop.prevent="$el.classList.remove('ring-2', 'ring-indigo-400'); drop('{{ $statusValue }}')"
                 data-column-status="{{ $statusValue }}">

                <div class="flex items-center justify-between rounded-t-2xl border-b px-4 py-2.5 {{ $colHeader }}">
                    <div class="flex items-center gap-2">
                        <span class="block h-2 w-2 rounded-full {{ $colDot }}"></span>
                        <h3 class="text-xs font-bold uppercase tracking-wider">{{ $col['label'] }}</h3>
                    </div>
                    <span class="rounded-full bg-white/70 px-2 py-0.5 text-[10px] font-bold dark:bg-slate-900/60">
                        {{ $col['count'] }}
                    </span>
                </div>

                <div class="flex-1 space-y-2 p-3">
                    @forelse ($col['posts'] as $post)
                        @php
                            $translation = $post->translation();
                            $title = $translation?->title ?? ('#'.$post->id);
                            $lastNote = $post->editorialNotes->first();
                            $featured = $post->featuredImage;
                            $age = $post->updated_at?->diffForHumans(syntax: \Carbon\Carbon::DIFF_RELATIVE_TO_NOW, short: true);
                        @endphp

                        <article wire:key="kanban-card-{{ $post->id }}"
                                 draggable="true"
                                 x-on:dragstart="pickUp({{ $post->id }})"
                                 x-on:dragend="draggedId = null"
                                 class="group cursor-grab rounded-xl border border-slate-200 bg-white p-3 shadow-sm transition hover:border-indigo-300 hover:shadow-md active:cursor-grabbing dark:border-slate-700 dark:bg-slate-900">

                            @if ($featured && ($featured->isImage() || ($featured->path !== null && $featured->path !== '')))
                                <div class="mb-2 overflow-hidden rounded-lg">
                                    <img src="{{ $featured->url() }}"
                                         alt="{{ $featured->alt_text ?? $title }}"
                                         loading="lazy"
                                         class="aspect-video w-full object-cover">
                                </div>
                            @endif

                            <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate
                               class="line-clamp-2 text-sm font-semibold text-slate-800 transition hover:text-indigo-600 dark:text-slate-100">
                                {{ $title }}
                            </a>

                            @if ($lastNote)
                                <p class="mt-1.5 line-clamp-2 rounded-lg bg-slate-50 px-2 py-1.5 text-xs italic text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    "{{ $lastNote->body }}"
                                </p>
                            @endif

                            <div class="mt-2.5 flex items-center justify-between text-[10px] text-slate-500">
                                <span class="inline-flex items-center gap-1.5">
                                    <span class="grid h-5 w-5 place-items-center rounded-full bg-gradient-to-br from-slate-300 to-slate-400 text-[9px] font-bold uppercase text-white">
                                        {{ mb_substr($post->author?->name ?? '?', 0, 1) }}
                                    </span>
                                    <span class="truncate">{{ $post->author?->name ?? 'Unknown' }}</span>
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <i data-lucide="clock" class="h-3 w-3"></i>
                                    {{ $age ?? '—' }}
                                </span>
                            </div>
                        </article>
                    @empty
                        <div class="flex flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-slate-200 px-4 py-8 text-center text-xs text-slate-400 dark:border-slate-700">
                            <i data-lucide="inbox" class="h-5 w-5"></i>
                            <span>Drop posts here</span>
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
</div>
