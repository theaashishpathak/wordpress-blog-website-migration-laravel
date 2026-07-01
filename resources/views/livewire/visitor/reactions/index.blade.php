@php
    $locale = app(\App\Support\LocaleResolver::class)->current();
    $isLike = $filter === 'like';
@endphp

<div class="space-y-8">
    <x-visitor.page-header
        eyebrow="Engagement"
        title="Likes &amp; Dislikes"
        description="{{ $likeCount }} {{ \Illuminate\Support\Str::plural('like', $likeCount) }} · {{ $dislikeCount }} {{ \Illuminate\Support\Str::plural('dislike', $dislikeCount) }}.">
        <x-slot:actions>
            <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1 dark:border-slate-800 dark:bg-slate-900">
                <button type="button" wire:click="switchFilter('like')"
                        class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-bold transition {{ $isLike ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-950 dark:text-slate-100' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
                    <i data-lucide="thumbs-up" class="h-3 w-3"></i>
                    Likes <span class="text-slate-400">{{ $likeCount }}</span>
                </button>
                <button type="button" wire:click="switchFilter('dislike')"
                        class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-bold transition {{ ! $isLike ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-950 dark:text-slate-100' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
                    <i data-lucide="thumbs-down" class="h-3 w-3"></i>
                    Dislikes <span class="text-slate-400">{{ $dislikeCount }}</span>
                </button>
            </div>
        </x-slot:actions>
    </x-visitor.page-header>

    @if ($this->reactions->isEmpty())
        <x-visitor.empty-state
            icon="{{ $isLike ? 'thumbs-up' : 'thumbs-down' }}"
            title="No {{ $isLike ? 'likes' : 'dislikes' }} yet."
            description="React to articles you read to keep them gathered here." />
    @else
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->reactions as $reaction)
                @php($post = $reaction->post)
                @continue (! $post)
                <div class="group relative">
                    <x-frontend.post-card :post="$post" />
                    <button type="button" wire:click="remove({{ $post->id }})"
                            wire:loading.attr="disabled" wire:target="remove"
                            class="absolute right-3 top-3 z-10 grid h-9 w-9 place-items-center rounded-lg bg-white/95 text-slate-700 ring-1 ring-slate-200 backdrop-blur transition hover:bg-rose-50 hover:text-rose-600 hover:ring-rose-200 dark:bg-slate-900/95 dark:text-slate-300 dark:ring-slate-700 dark:hover:bg-rose-500/10 dark:hover:text-rose-300"
                            title="Remove reaction">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                    <span class="absolute left-3 top-3 z-10 inline-flex items-center gap-1 rounded-full bg-white/95 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.18em] ring-1 backdrop-blur
                                  {{ $isLike ? 'text-emerald-700 ring-emerald-200 dark:bg-slate-900/95 dark:text-emerald-300 dark:ring-emerald-500/30' : 'text-rose-700 ring-rose-200 dark:bg-slate-900/95 dark:text-rose-300 dark:ring-rose-500/30' }}">
                        <i data-lucide="{{ $isLike ? 'thumbs-up' : 'thumbs-down' }}" class="h-2.5 w-2.5"></i>
                        {{ $reaction->created_at?->diffForHumans() }}
                    </span>
                </div>
            @endforeach
        </div>

        <div class="mt-6">{{ $this->reactions->onEachSide(1)->links() }}</div>
    @endif
</div>
