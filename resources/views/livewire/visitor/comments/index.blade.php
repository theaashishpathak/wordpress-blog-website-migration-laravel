@php
    $locale = app(\App\Support\LocaleResolver::class)->current();

    $statusMeta = [
        'approved' => ['label' => 'Approved', 'icon' => 'check-circle-2', 'tone' => 'emerald'],
        'pending'  => ['label' => 'Pending',  'icon' => 'clock',           'tone' => 'amber'],
        'spam'     => ['label' => 'Spam',     'icon' => 'shield-alert',    'tone' => 'rose'],
        'trash'    => ['label' => 'Trashed',  'icon' => 'trash-2',         'tone' => 'slate'],
    ];
    $toneMap = [
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30',
        'amber'   => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-200 dark:ring-amber-500/30',
        'rose'    => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/15 dark:text-rose-300 dark:ring-rose-500/30',
        'slate'   => 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700',
    ];

    $tabs = [
        'all'      => ['label' => 'All',      'count' => $counts['all']],
        'approved' => ['label' => 'Approved', 'count' => $counts['approved']],
        'pending'  => ['label' => 'Pending',  'count' => $counts['pending']],
        'spam'     => ['label' => 'Spam',     'count' => $counts['spam']],
    ];
@endphp

<div class="space-y-8">
    <x-visitor.page-header
        eyebrow="Engagement"
        title="My Comments"
        description="{{ $counts['all'] }} total · {{ $counts['approved'] }} approved · {{ $counts['pending'] }} pending review.">
        <x-slot:actions>
            <div class="inline-flex flex-wrap gap-1 rounded-lg border border-slate-200 bg-slate-50 p-1 dark:border-slate-800 dark:bg-slate-900">
                @foreach ($tabs as $key => $tab)
                    <button type="button" wire:click="switchFilter('{{ $key }}')"
                            class="rounded-md px-3 py-1.5 text-xs font-bold transition {{ $filter === $key ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-950 dark:text-slate-100' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
                        {{ $tab['label'] }}
                        <span class="ml-1 text-slate-400">{{ $tab['count'] }}</span>
                    </button>
                @endforeach
            </div>
        </x-slot:actions>
    </x-visitor.page-header>

    @if ($this->comments->isEmpty())
        <x-visitor.empty-state
            icon="message-square"
            title="{{ $filter === 'all' ? 'No comments yet.' : 'No '.($tabs[$filter]['label'] ?? $filter).' comments.' }}"
            description="Leave a comment under any article and it will show here.">
            <x-slot:action>
                <a href="{{ route('frontend.home', ['locale' => $locale?->code]) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                    <i data-lucide="newspaper" class="h-4 w-4"></i>
                    Find articles to comment on
                </a>
            </x-slot:action>
        </x-visitor.empty-state>
    @else
        <div class="space-y-4">
            @foreach ($this->comments as $comment)
                @php
                    $post = $comment->post;
                    $t = $post?->translation() ?? $post?->translations->firstWhere('language_id', $post->default_language_id) ?? $post?->translations->first();
                    $statusInfo = $statusMeta[$comment->status] ?? $statusMeta['pending'];
                @endphp

                <article class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700">
                    {{-- Post + status row --}}
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <div class="flex min-w-0 flex-wrap items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                            @if ($post && $t)
                                @if ($post->category)
                                    <a href="{{ route('frontend.category', ['locale' => $locale?->code, 'slug' => $post->category->translate('slug')]) }}"
                                       class="font-bold uppercase tracking-[0.18em] text-slate-600 hover:text-emerald-700 hover:underline dark:text-slate-400 dark:hover:text-emerald-300">
                                        {{ $post->category->translate('name') }}
                                    </a>
                                    <span class="text-slate-300 dark:text-slate-600">·</span>
                                @endif
                                <a href="{{ route('frontend.post.show', ['locale' => $locale?->code, 'slug' => $t->slug]) }}"
                                   class="line-clamp-1 font-semibold text-slate-700 hover:text-emerald-700 dark:text-slate-300 dark:hover:text-emerald-300">
                                    {{ $t->title }}
                                </a>
                            @else
                                <span class="italic text-slate-400">Article unavailable</span>
                            @endif
                        </div>
                        <span class="inline-flex shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] ring-1 {{ $toneMap[$statusInfo['tone']] }}">
                            <i data-lucide="{{ $statusInfo['icon'] }}" class="h-2.5 w-2.5"></i>
                            {{ $statusInfo['label'] }}
                        </span>
                    </div>

                    {{-- Body / edit form --}}
                    @if ($editingId === $comment->id)
                        <div class="space-y-2">
                            <textarea wire:model="editingBody" rows="3" placeholder="Edit your comment…"
                                      class="w-full rounded-lg border border-slate-200 bg-white p-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950"></textarea>
                            <p class="text-[11px] text-slate-500">
                                <i data-lucide="info" class="inline h-3 w-3"></i>
                                Edited comments go back into the moderation queue.
                            </p>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" wire:click="saveEdit"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                                    <i data-lucide="save" class="h-3 w-3"></i>
                                    Save changes
                                </button>
                                <button type="button" wire:click="cancelEditing"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    @else
                        <p class="whitespace-pre-line text-sm leading-relaxed text-slate-700 dark:text-slate-200">
                            {{ $comment->body }}
                        </p>

                        <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                            <span class="inline-flex items-center gap-1.5">
                                <i data-lucide="calendar" class="h-3 w-3"></i>
                                {{ $comment->created_at?->diffForHumans() }}
                                @if ($comment->updated_at && $comment->updated_at->gt($comment->created_at))
                                    <span class="italic text-slate-400">· edited</span>
                                @endif
                            </span>

                            <div class="flex items-center gap-3">
                                @if ($comment->status !== \App\Models\Comment::STATUS_SPAM)
                                    <button type="button" wire:click="startEditing({{ $comment->id }})"
                                            class="inline-flex items-center gap-1 font-bold text-emerald-700 hover:underline dark:text-emerald-300">
                                        <i data-lucide="edit-3" class="h-3 w-3"></i>
                                        Edit
                                    </button>
                                @endif
                                <button type="button" wire:click="delete({{ $comment->id }})"
                                        wire:confirm="Delete this comment? This cannot be undone from your side."
                                        class="inline-flex items-center gap-1 font-semibold text-slate-500 transition hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-300">
                                    <i data-lucide="trash-2" class="h-3 w-3"></i>
                                    Delete
                                </button>
                            </div>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>

        <div class="mt-6">{{ $this->comments->onEachSide(1)->links() }}</div>
    @endif
</div>
