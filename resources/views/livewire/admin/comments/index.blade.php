<div class="space-y-5">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">Comment Moderation</span>
    </nav>

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-500 text-white shadow-sm">
                <i data-lucide="message-square" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">Comment Moderation</h1>
                <p class="text-xs text-slate-500">
                    {{ $this->counts['pending'] }} awaiting review · {{ $this->counts['spam'] }} marked as spam
                </p>
            </div>
        </div>
    </div>

    {{-- Tab bar --}}
    <div class="flex flex-wrap items-center gap-2">
        @php
            $tabs = [
                ['key' => \App\Models\Comment::STATUS_PENDING, 'label' => 'Pending', 'icon' => 'clock', 'color' => 'amber'],
                ['key' => \App\Models\Comment::STATUS_APPROVED, 'label' => 'Approved', 'icon' => 'check', 'color' => 'emerald'],
                ['key' => \App\Models\Comment::STATUS_SPAM, 'label' => 'Spam', 'icon' => 'shield-alert', 'color' => 'rose'],
            ];
        @endphp
        @foreach ($tabs as $tab)
            @php
                $isActive = $statusFilter === $tab['key'];
            @endphp
            <button type="button"
                    wire:click="$set('statusFilter', '{{ $tab['key'] }}')"
                    @class([
                        'inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold transition',
                        'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300' => $isActive,
                        'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' => ! $isActive,
                    ])>
                <i data-lucide="{{ $tab['icon'] }}" class="h-3.5 w-3.5"></i>
                {{ $tab['label'] }}
                <span class="rounded-md bg-white/70 px-1.5 py-0.5 text-[10px] font-mono dark:bg-slate-800">
                    {{ $this->counts[$tab['key']] ?? 0 }}
                </span>
            </button>
        @endforeach

        <div class="ml-auto">
            <div class="relative">
                <i data-lucide="search" class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
                <input type="text" wire:model.live.debounce.400ms="search"
                       placeholder="Search comments…"
                       class="w-72 rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm dark:border-slate-700 dark:bg-slate-950">
            </div>
        </div>
    </div>

    {{-- Bulk action bar --}}
    @if (! empty($selectedIds))
        <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 dark:border-indigo-500/30 dark:bg-indigo-500/10">
            <p class="text-xs font-semibold text-indigo-800 dark:text-indigo-300">
                {{ count($selectedIds) }} selected
            </p>
            <div class="flex gap-2">
                @can('comments.approve')
                    <button type="button" wire:click="bulkApprove"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700">
                        <i data-lucide="check" class="h-3.5 w-3.5"></i> Approve
                    </button>
                @endcan
                @can('comments.spam')
                    <button type="button" wire:click="bulkSpam"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-amber-700">
                        <i data-lucide="shield-alert" class="h-3.5 w-3.5"></i> Spam
                    </button>
                @endcan
                @can('comments.delete')
                    <button type="button" wire:click="bulkDelete"
                            wire:confirm="Delete selected comments? They'll be soft-deleted."
                            class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-rose-700">
                        <i data-lucide="trash-2" class="h-3.5 w-3.5"></i> Delete
                    </button>
                @endcan
            </div>
        </div>
    @endif

    {{-- Comments list (card style — comments often have long bodies) --}}
    <div class="space-y-3">
        @if ($this->comments->count() === 0)
            <div class="rounded-2xl border-2 border-dashed border-slate-200 p-12 text-center text-sm text-slate-400 dark:border-slate-700">
                No comments in this view.
            </div>
        @endif
        @foreach ($this->comments as $comment)
            @php
                $statusColor = match ($comment->status) {
                    'approved' => 'bg-emerald-100 text-emerald-700',
                    'pending' => 'bg-amber-100 text-amber-700',
                    'spam' => 'bg-rose-100 text-rose-700',
                    'trash' => 'bg-slate-200 text-slate-700',
                    default => 'bg-slate-100 text-slate-700',
                };
                $postTitle = $comment->post?->translation()?->title ?? '#'.$comment->post_id;
            @endphp
            <article wire:key="cm-{{ $comment->id }}"
                     class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                <header class="flex flex-wrap items-center gap-3 border-b border-slate-100 bg-slate-50/60 px-4 py-2.5 dark:border-slate-800 dark:bg-slate-950/40">
                    <input type="checkbox" value="{{ $comment->id }}" wire:model="selectedIds"
                           class="h-3.5 w-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">

                    @if ($url = $comment->avatarUrl())
                        <img src="{{ $url }}" alt="" class="h-7 w-7 rounded-full border border-slate-200">
                    @else
                        <span class="grid h-7 w-7 place-items-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 text-[10px] font-bold uppercase text-white">
                            {{ mb_substr($comment->authorName(), 0, 1) }}
                        </span>
                    @endif

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100">
                            {{ $comment->authorName() }}
                            @if ($comment->authorEmail())
                                <span class="ml-1 font-mono text-[10px] font-normal text-slate-500">&lt;{{ $comment->authorEmail() }}&gt;</span>
                            @endif
                        </p>
                        <p class="truncate text-[11px] text-slate-500">
                            on
                            <a href="{{ $comment->post ? route('admin.posts.edit', $comment->post) : '#' }}" wire:navigate
                               class="font-semibold hover:text-indigo-600">{{ $postTitle }}</a>
                            · {{ $comment->created_at?->diffForHumans() }}
                            @if ($comment->parent)
                                · replying to <strong>{{ $comment->parent->author?->name ?? $comment->parent->guest_name ?? '#'.$comment->parent_id }}</strong>
                            @endif
                        </p>
                    </div>

                    <span class="rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $statusColor }}">
                        {{ $comment->status }}
                    </span>
                </header>

                <div class="px-4 py-3 text-sm text-slate-700 dark:text-slate-200">
                    {{ $comment->body }}
                </div>

                <footer class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/30 px-4 py-2 dark:border-slate-800 dark:bg-slate-950/20">
                    @can('comments.approve')
                        @if ($comment->status !== 'approved')
                            <button type="button" wire:click="approve({{ $comment->id }})"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800 hover:bg-emerald-200 dark:bg-emerald-500/20 dark:text-emerald-300">
                                <i data-lucide="check" class="h-3.5 w-3.5"></i> Approve
                            </button>
                        @endif
                    @endcan
                    @can('comments.spam')
                        @if ($comment->status !== 'spam')
                            <button type="button" wire:click="markSpam({{ $comment->id }})"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800 hover:bg-amber-200 dark:bg-amber-500/20 dark:text-amber-300">
                                <i data-lucide="shield-alert" class="h-3.5 w-3.5"></i> Spam
                            </button>
                        @endif
                    @endcan
                    @can('comments.delete')
                        <button type="button" wire:click="deleteComment({{ $comment->id }})"
                                wire:confirm="Delete this comment?"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-rose-100 px-3 py-1 text-xs font-semibold text-rose-800 hover:bg-rose-200 dark:bg-rose-500/20 dark:text-rose-300">
                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i> Delete
                        </button>
                    @endcan
                </footer>
            </article>
        @endforeach

        <div>{{ $this->comments->onEachSide(1)->links() }}</div>
    </div>
</div>
