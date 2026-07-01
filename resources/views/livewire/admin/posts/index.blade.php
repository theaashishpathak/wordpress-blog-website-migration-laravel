<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Posts</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Manage articles, news, and other content types.
            </p>
        </div>

        @can('posts.create')
            <a href="{{ route('admin.posts.create') }}"
               wire:navigate
               class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                <i data-lucide="plus" class="h-4 w-4"></i>
                New Post
            </a>
        @endcan
    </div>

    {{-- Filters card --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="grid gap-3 lg:grid-cols-6">
            <div class="lg:col-span-2">
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Search</label>
                <input
                    type="text"
                    wire:model.live.debounce.400ms="search"
                    placeholder="Title or excerpt…"
                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950"
                >
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Type</label>
                <select wire:model.live="typeFilter"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                    <option value="">All types</option>
                    @foreach ($postTypes as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Status</label>
                <select wire:model.live="statusFilter"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                    <option value="">All statuses</option>
                    @foreach ($postStatuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Category</label>
                <select wire:model.live="categoryFilter"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                    <option value="">All categories</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->translate('name') ?? '#'.$category->id }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-500">Author</label>
                <select wire:model.live="authorFilter"
                        class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                    <option value="">All authors</option>
                    @foreach ($authors as $author)
                        <option value="{{ $author->id }}">{{ $author->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if ($search !== '' || $typeFilter !== '' || $statusFilter !== '' || $categoryFilter !== '' || $authorFilter !== '')
            <div class="mt-3 flex items-center justify-end">
                <button type="button" wire:click="clearFilters"
                        class="text-xs font-medium text-slate-500 hover:text-rose-600">
                    <i data-lucide="x" class="inline h-3 w-3"></i> Clear filters
                </button>
            </div>
        @endif
    </div>

    {{-- Bulk action toolbar — shows only when selection exists --}}
    @if (count($selectedIds) > 0)
        <div class="flex flex-wrap items-center gap-3 rounded-xl border border-indigo-200 bg-indigo-50/70 px-4 py-3 text-sm dark:border-indigo-500/30 dark:bg-indigo-500/10">
            <span class="font-semibold text-indigo-900 dark:text-indigo-200">
                {{ count($selectedIds) }} selected
            </span>

            @can('posts.publish')
                <button wire:click="bulkPublish"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                    <i data-lucide="check" class="h-3.5 w-3.5"></i>
                    Publish
                </button>
            @endcan

            @can('posts.archive')
                <button wire:click="bulkArchive"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-700">
                    <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                    Archive
                </button>
            @endcan

            <button wire:click="requestBulkDelete"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">
                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                Delete
            </button>
        </div>
    @endif

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50/80 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-950/40 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3 text-left">
                            <input type="checkbox" wire:model.live="selectAllOnPage"
                                   class="h-4 w-4 rounded border-slate-300 text-indigo-600">
                        </th>
                        <th class="px-4 py-3 text-left">Title</th>
                        <th class="px-4 py-3 text-left">Type</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Author</th>
                        <th class="px-4 py-3 text-left">
                            <button wire:click="toggleSort('published_at')" class="inline-flex items-center gap-1 hover:text-indigo-600">
                                Published
                                <i data-lucide="{{ $sortField === 'published_at' ? ($sortDirection === 'asc' ? 'chevron-up' : 'chevron-down') : 'chevrons-up-down' }}" class="h-3 w-3"></i>
                            </button>
                        </th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($posts as $post)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40" wire:key="post-row-{{ $post->id }}">
                            <td class="px-4 py-3 align-top">
                                <input type="checkbox" value="{{ $post->id }}"
                                       wire:model.live="selectedIds"
                                       class="h-4 w-4 rounded border-slate-300 text-indigo-600">
                            </td>
                            <td class="px-4 py-3 align-top">
                                <div class="font-semibold text-slate-900 dark:text-slate-100">
                                    {{ $post->translate('title') ?? '— untitled —' }}
                                </div>
                                <div class="mt-1 text-xs text-slate-500">
                                    /{{ $post->translate('slug') ?? '' }}
                                </div>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    <i data-lucide="{{ $post->type->icon() }}" class="h-3 w-3"></i>
                                    {{ $post->type->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 align-top">
                                @php($statusColor = match ($post->status->value) {
                                    'published' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
                                    'draft' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                                    'pending_review','in_review' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
                                    'changes_requested' => 'bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-300',
                                    'approved' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300',
                                    'scheduled' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300',
                                    'rejected' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
                                    'archived' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                                    'unpublished' => 'bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-300',
                                    default => 'bg-slate-100 text-slate-700',
                                })
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusColor }}">
                                    {{ $post->status->label() }}
                                </span>
                                @if ($post->is_breaking && $post->isBreakingActive())
                                    <span class="ml-1 rounded-full bg-rose-600 px-2 py-0.5 text-[10px] font-bold uppercase text-white">Breaking</span>
                                @endif
                                @if ($post->is_featured)
                                    <span class="ml-1 rounded-full bg-amber-500 px-2 py-0.5 text-[10px] font-bold uppercase text-white">Featured</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-slate-700 dark:text-slate-300">
                                {{ $post->author?->name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 align-top text-slate-500">
                                {{ $post->published_at?->format('M d, Y H:i') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 align-top text-right">
                                <div class="inline-flex items-center gap-1">
                                    @can('update', $post)
                                        <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate
                                           class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-indigo-600 dark:hover:bg-slate-800"
                                           title="Edit">
                                            <i data-lucide="pencil" class="h-4 w-4"></i>
                                        </a>
                                    @endcan
                                    @can('view', $post)
                                        <a href="{{ route('admin.posts.show', $post) }}" wire:navigate
                                           class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-indigo-600 dark:hover:bg-slate-800"
                                           title="View">
                                            <i data-lucide="eye" class="h-4 w-4"></i>
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                                <i data-lucide="inbox" class="mx-auto mb-2 h-8 w-8 text-slate-300"></i>
                                <p class="text-sm">No posts match the current filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($posts->hasPages())
            <div class="border-t border-slate-200 px-4 py-3 dark:border-slate-800">
                {{ $posts->links() }}
            </div>
        @endif
    </div>
</div>
