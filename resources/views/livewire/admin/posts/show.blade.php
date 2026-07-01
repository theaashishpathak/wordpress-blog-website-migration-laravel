<div class="space-y-6">
    <div class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('admin.posts.index') }}" wire:navigate class="hover:text-indigo-600">Posts</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">
            {{ $post->translate('title') ?? '#'.$post->id }}
        </span>
    </div>

    {{-- Header card --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100">
                    {{ $post->translate('title') ?? '— untitled —' }}
                </h1>
                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        <i data-lucide="{{ $post->type->icon() }}" class="h-3 w-3"></i>
                        {{ $post->type->label() }}
                    </span>
                    <span class="rounded-full bg-indigo-100 px-2 py-0.5 font-semibold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">
                        {{ $post->status->label() }}
                    </span>
                    <span>by <strong class="text-slate-700 dark:text-slate-200">{{ $post->author?->name ?? '—' }}</strong></span>
                    @if ($post->published_at)
                        <span>· published {{ $post->published_at->format('M d, Y H:i') }}</span>
                    @endif
                </div>
            </div>
            @can('update', $post)
                <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate
                   class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                    <i data-lucide="pencil" class="h-4 w-4"></i>
                    Edit
                </a>
            @endcan
        </div>
    </div>

    {{-- Stub notice --}}
    <div class="rounded-2xl border border-dashed border-amber-300 bg-amber-50/60 p-6 text-center dark:border-amber-500/30 dark:bg-amber-500/10">
        <p class="text-sm text-amber-700 dark:text-amber-300/80">
            Full detail view (translations, editorial notes thread, revisions, SEO snapshot) arrives in Phase 4B.
        </p>
    </div>
</div>
