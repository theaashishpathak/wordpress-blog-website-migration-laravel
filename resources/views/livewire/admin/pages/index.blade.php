<div class="space-y-5">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">Pages</span>
    </nav>

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white shadow-sm">
                <i data-lucide="file-text" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">Pages</h1>
                <p class="text-xs text-slate-500">Static pages — About, Contact, Privacy, Terms, etc.</p>
            </div>
        </div>

        @can('pages.create')
            <a href="{{ route('admin.pages.create') }}" wire:navigate
               class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-indigo-700 hover:to-indigo-600">
                <i data-lucide="plus" class="h-4 w-4"></i>
                New Page
            </a>
        @endcan
    </div>

    {{-- Filters --}}
    <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:grid-cols-[1fr_180px_180px_120px]">
        <div class="relative">
            <i data-lucide="search" class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
            <input type="text" wire:model.live.debounce.400ms="search"
                   placeholder="Search title or slug…"
                   class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm dark:border-slate-700 dark:bg-slate-950">
        </div>
        <select wire:model.live="statusFilter"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
            <option value="">All statuses</option>
            @foreach ($this->statusOptions as $s)
                <option value="{{ $s['value'] }}">{{ $s['label'] }}</option>
            @endforeach
        </select>
        <select wire:model.live="templateFilter"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
            <option value="">All templates</option>
            @foreach ($this->templateOptions as $t)
                <option value="{{ $t }}">{{ ucfirst(str_replace('-', ' ', $t)) }}</option>
            @endforeach
        </select>
        <button type="button" wire:click="clearFilters"
                class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
            <i data-lucide="filter-x" class="h-3.5 w-3.5"></i>
            Clear
        </button>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <table class="min-w-full divide-y divide-slate-100 text-sm dark:divide-slate-800">
            <thead class="bg-slate-50/60 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-950/40">
                <tr>
                    <th class="px-4 py-2.5">Title</th>
                    <th class="px-4 py-2.5">Slug</th>
                    <th class="px-4 py-2.5">Translations</th>
                    <th class="px-4 py-2.5">Status</th>
                    <th class="px-4 py-2.5">Template</th>
                    <th class="px-4 py-2.5">Menu</th>
                    <th class="px-4 py-2.5">Updated</th>
                    <th class="px-4 py-2.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($this->pages as $page)
                    @php
                        $translation = $page->translation();
                        $title = $translation?->title ?? ('#'.$page->id);
                        $slug = $translation?->slug ?? '';
                        $statusValue = $page->status->value;
                        $statusColor = match ($statusValue) {
                            'published' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
                            'draft' => 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-300',
                            'archived' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700/40 dark:text-zinc-300',
                            default => 'bg-slate-100 text-slate-600',
                        };
                    @endphp
                    <tr wire:key="page-{{ $page->id }}" class="transition hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.pages.edit', $page) }}" wire:navigate
                               class="font-semibold text-slate-800 hover:text-indigo-600 dark:text-slate-100">
                                {{ $title }}
                            </a>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">/{{ $slug }}</td>
                        <td class="px-4 py-3">
                            <div class="flex gap-1">
                                @foreach ($page->translations as $tr)
                                    <span @class([
                                        'rounded-md px-1.5 py-0.5 text-[10px] font-mono uppercase',
                                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300' => $tr->is_published,
                                        'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' => ! $tr->is_published,
                                    ])>
                                        {{ $tr->language?->code ?? '?' }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $statusColor }}">
                                {{ $page->status->label() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $page->template }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($page->show_in_menu)
                                <i data-lucide="check" class="h-3.5 w-3.5 text-emerald-500"></i>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $page->updated_at?->diffForHumans() }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1.5">
                                @can('pages.publish')
                                    @if ($page->status !== \App\Enums\PageStatus::Published)
                                        <button type="button" wire:click="publish({{ $page->id }})"
                                                wire:confirm="Publish this page?"
                                                class="grid h-7 w-7 place-items-center rounded-lg text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-500/10"
                                                title="Publish">
                                            <i data-lucide="zap" class="h-3.5 w-3.5"></i>
                                        </button>
                                    @endif
                                @endcan
                                @can('pages.edit')
                                    <a href="{{ route('admin.pages.edit', $page) }}" wire:navigate
                                       class="grid h-7 w-7 place-items-center rounded-lg text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-500/10"
                                       title="Edit">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                    </a>
                                @endcan
                                @can('pages.archive')
                                    @if ($page->status !== \App\Enums\PageStatus::Archived)
                                        <button type="button" wire:click="archive({{ $page->id }})"
                                                wire:confirm="Archive this page?"
                                                class="grid h-7 w-7 place-items-center rounded-lg text-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-700"
                                                title="Archive">
                                            <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                                        </button>
                                    @endif
                                @endcan
                                @can('pages.delete')
                                    <button type="button" wire:click="deletePage({{ $page->id }})"
                                            wire:confirm="Delete '{{ $title }}'? This cannot be undone."
                                            class="grid h-7 w-7 place-items-center rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10"
                                            title="Delete">
                                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-400">
                            No pages match your filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="border-t border-slate-100 p-3 dark:border-slate-800">
            {{ $this->pages->onEachSide(1)->links() }}
        </div>
    </div>
</div>
