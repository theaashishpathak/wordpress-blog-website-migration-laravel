<div class="space-y-5">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('admin.posts.index') }}" wire:navigate class="hover:text-indigo-600">Posts</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate class="hover:text-indigo-600 truncate max-w-[260px]">
            {{ $post->translation()?->title ?? '#'.$post->id }}
        </a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">Revisions</span>
    </nav>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-slate-500 to-slate-700 text-white shadow-sm">
                <i data-lucide="history" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">Revision history</h1>
                <p class="text-xs text-slate-500">
                    {{ $this->revisions->count() }} snapshot{{ $this->revisions->count() === 1 ? '' : 's' }} since this post was created.
                </p>
            </div>
        </div>
        <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate
           class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
            <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i> Back to editor
        </a>
    </div>

    <div class="grid gap-4 lg:grid-cols-[320px_1fr]">
        {{-- Timeline --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Timeline</h3>
            </header>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($this->revisions as $rev)
                    @php
                        $isSelected = $rev->id === $selectedRevisionId;
                        $isCompare = $rev->id === $compareRevisionId;
                    @endphp
                    <li wire:key="rev-{{ $rev->id }}"
                        class="flex items-start gap-3 px-4 py-3 transition
                               {{ $isSelected ? 'bg-violet-50 dark:bg-violet-500/10' : 'hover:bg-slate-50/60 dark:hover:bg-slate-800/40' }}">
                        <button type="button" wire:click="select({{ $rev->id }})"
                                class="grid h-7 w-7 shrink-0 place-items-center rounded-full text-[10px] font-bold
                                       {{ $isSelected ? 'bg-violet-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                            #{{ $rev->revision_number }}
                        </button>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">
                                {{ $rev->summary ?? 'Snapshot' }}
                            </p>
                            <p class="mt-0.5 truncate text-xs text-slate-500">
                                {{ $rev->author?->name ?? 'System' }} · {{ $rev->created_at?->diffForHumans() }}
                            </p>
                            <button type="button" wire:click="compareWith({{ $rev->id }})"
                                    class="mt-1 text-[10px] font-bold uppercase tracking-wider
                                           {{ $isCompare ? 'text-violet-700' : 'text-slate-400 hover:text-violet-600' }}">
                                {{ $isCompare ? '✓ comparing' : 'Compare' }}
                            </button>
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-6 text-center text-xs text-slate-500">
                        No revisions recorded yet. Revisions are written automatically on every save.
                    </li>
                @endforelse
            </ul>
        </div>

        {{-- Detail / diff panel --}}
        <div class="space-y-4">
            @if ($this->selectedRevision)
                @php
                    $snap = $this->selectedRevision->snapshot ?? [];
                    $defaultLangId = (int) ($snap['default_language_id'] ?? 0);
                    $defaultTranslation = collect((array) ($snap['translations'] ?? []))
                        ->firstWhere('language_id', $defaultLangId)
                        ?? (($snap['translations'] ?? [])[0] ?? null);
                @endphp
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <header class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                        <div>
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">
                                Revision #{{ $this->selectedRevision->revision_number }}
                            </h3>
                            <p class="text-[11px] text-slate-500">
                                {{ $this->selectedRevision->created_at?->format('M j, Y · g:i A') }} —
                                {{ $this->selectedRevision->author?->name ?? 'System' }}
                            </p>
                        </div>
                        <button type="button" wire:click="restore"
                                wire:confirm="Restore this revision onto the live post? A safety snapshot of the current state will be taken first."
                                class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 py-2 text-xs font-bold text-white shadow-sm hover:from-indigo-700 hover:to-violet-700">
                            <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i> Restore this revision
                        </button>
                    </header>

                    <div class="space-y-3 p-5">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Title</p>
                            <p class="mt-1 text-base font-semibold text-slate-800 dark:text-slate-100">
                                {{ $defaultTranslation['title'] ?? '—' }}
                            </p>
                        </div>
                        @if (! empty($defaultTranslation['excerpt']))
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Excerpt</p>
                                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $defaultTranslation['excerpt'] }}</p>
                            </div>
                        @endif
                        @if (! empty($defaultTranslation['content']))
                            <div>
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Content</p>
                                <div class="prose prose-sm mt-2 max-h-[400px] max-w-none overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm dark:prose-invert dark:border-slate-700 dark:bg-slate-950">
                                    {!! $defaultTranslation['content'] !!}
                                </div>
                            </div>
                        @endif
                        <div class="grid gap-3 sm:grid-cols-3 text-xs">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Status</p>
                                <p class="mt-0.5 font-mono text-slate-700 dark:text-slate-200">{{ $snap['status'] ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Type</p>
                                <p class="mt-0.5 font-mono text-slate-700 dark:text-slate-200">{{ $snap['type'] ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Tag IDs</p>
                                <p class="mt-0.5 font-mono text-slate-700 dark:text-slate-200">
                                    {{ implode(', ', (array) ($snap['tag_ids'] ?? [])) ?: '—' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="rounded-2xl border-2 border-dashed border-slate-200 p-12 text-center dark:border-slate-700">
                    <p class="text-sm text-slate-500">Select a revision from the timeline.</p>
                </div>
            @endif

            {{-- Diff panel --}}
            @if ($this->compareRevision && $this->selectedRevision)
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">
                            Diff — #{{ $this->compareRevision->revision_number }} → #{{ $this->selectedRevision->revision_number }}
                        </h3>
                        <p class="text-[11px] text-slate-500">Line-level diff of title + content (HTML stripped).</p>
                    </header>
                    <div class="space-y-px overflow-x-auto p-5 font-mono text-xs">
                        @forelse ($this->diff as $line)
                            <div class="rounded px-2 py-0.5
                                {{ $line['type'] === 'added' ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300' : '' }}
                                {{ $line['type'] === 'removed' ? 'bg-rose-50 text-rose-800 line-through dark:bg-rose-500/10 dark:text-rose-300' : '' }}
                                {{ $line['type'] === 'context' ? 'text-slate-500' : '' }}">
                                <span class="select-none pr-2 opacity-50">{{ $line['type'] === 'added' ? '+' : ($line['type'] === 'removed' ? '−' : ' ') }}</span>
                                {{ $line['text'] }}
                            </div>
                        @empty
                            <p class="text-center text-slate-500">No textual differences detected.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
