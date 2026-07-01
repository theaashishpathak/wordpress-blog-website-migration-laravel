<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-500 text-white shadow-sm">
                <i data-lucide="bell" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Notifications</h1>
                <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                    Editorial workflow, comments, newsletter, RSS imports, and more.
                </p>
            </div>
        </div>

        @if ($unreadCount > 0)
            <button
                type="button"
                wire:click="markAllRead"
                class="inline-flex h-10 items-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-semibold text-white transition hover:bg-indigo-700"
            >
                <i data-lucide="check-check" class="h-4 w-4"></i>
                Mark all as read
            </button>
        @endif
    </div>

    {{-- Filter pills --}}
    <div class="flex flex-wrap gap-2">
        @php
            $pillBase = 'inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-xs font-semibold transition';
            $pillActive = 'border-indigo-600 bg-indigo-600 text-white';
            $pillInactive = 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800';
        @endphp

        <button type="button" wire:click="setFilter('all')" class="{{ $pillBase }} {{ $filter === 'all' ? $pillActive : $pillInactive }}">
            All
        </button>
        <button type="button" wire:click="setFilter('unread')" class="{{ $pillBase }} {{ $filter === 'unread' ? $pillActive : $pillInactive }}">
            Unread
            @if ($unreadCount > 0)
                <span class="rounded-full bg-white/20 px-1.5 py-0.5 text-[10px]">{{ $unreadCount }}</span>
            @endif
        </button>
        <button type="button" wire:click="setFilter('read')" class="{{ $pillBase }} {{ $filter === 'read' ? $pillActive : $pillInactive }}">
            Read
        </button>
    </div>

    {{-- List --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        @forelse ($notifications as $notification)
            @php
                $data = $notification->data;
                $color = $data['color'] ?? $data['icon_color'] ?? 'indigo';
                $colorMap = [
                    'indigo'  => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-300',
                    'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
                    'amber'   => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
                    'rose'    => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300',
                    'sky'     => 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300',
                    'pink'    => 'bg-pink-50 text-pink-600 dark:bg-pink-500/10 dark:text-pink-300',
                    'violet'  => 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300',
                    'orange'  => 'bg-orange-50 text-orange-600 dark:bg-orange-500/10 dark:text-orange-300',
                    'slate'   => 'bg-slate-100 text-slate-700 dark:bg-slate-700/40 dark:text-slate-200',
                ];
                $chipClass = $colorMap[$color] ?? $colorMap['indigo'];
            @endphp

            <div class="flex items-start gap-3 border-b border-slate-100 px-4 py-4 transition last:border-0 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800 {{ $notification->read_at === null ? 'bg-indigo-50/30 dark:bg-indigo-500/5' : '' }}">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl {{ $chipClass }}">
                    <i data-lucide="{{ $data['icon'] ?? 'bell' }}" class="h-4 w-4"></i>
                </span>

                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <a
                            href="{{ $data['url'] ?? '#' }}"
                            wire:click="markRead('{{ $notification->id }}')"
                            class="block text-sm font-semibold text-slate-800 hover:text-indigo-600 dark:text-slate-100 dark:hover:text-indigo-300"
                        >
                            {{ $data['title'] ?? $data['message'] ?? 'Notification' }}
                            @if ($notification->read_at === null)
                                <span class="ml-2 inline-block h-2 w-2 rounded-full bg-indigo-500 align-middle"></span>
                            @endif
                        </a>
                        <span class="text-[11px] uppercase tracking-wide text-slate-400 dark:text-slate-500">
                            {{ $notification->created_at?->diffForHumans() }}
                        </span>
                    </div>

                    @if (! empty($data['title']) && ! empty($data['message']))
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            {{ $data['message'] }}
                        </p>
                    @endif

                    <div class="mt-3 flex items-center gap-2">
                        @if ($notification->read_at === null)
                            <button
                                type="button"
                                wire:click="markRead('{{ $notification->id }}')"
                                class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1 text-[11px] font-semibold text-slate-600 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                <i data-lucide="check" class="h-3 w-3"></i>
                                Mark read
                            </button>
                        @endif

                        <button
                            type="button"
                            wire:click="delete('{{ $notification->id }}')"
                            wire:confirm="Delete this notification?"
                            class="inline-flex items-center gap-1 rounded-lg border border-rose-200 px-3 py-1 text-[11px] font-semibold text-rose-600 transition hover:bg-rose-50 dark:border-rose-500/30 dark:text-rose-300 dark:hover:bg-rose-500/10"
                        >
                            <i data-lucide="trash-2" class="h-3 w-3"></i>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="px-6 py-16 text-center">
                <div class="mx-auto mb-3 grid h-14 w-14 place-items-center rounded-2xl bg-emerald-50 text-emerald-500 dark:bg-emerald-500/10 dark:text-emerald-300">
                    <i data-lucide="check-circle-2" class="h-7 w-7"></i>
                </div>
                <p class="text-base font-semibold text-slate-700 dark:text-slate-200">You're all caught up!</p>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    @if ($filter === 'unread')
                        No unread notifications.
                    @elseif ($filter === 'read')
                        No read notifications yet.
                    @else
                        Nothing here yet — new notifications will appear in this list.
                    @endif
                </p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($notifications->hasPages())
        <div>
            {{ $notifications->links() }}
        </div>
    @endif
</div>

@script
<script>
    Livewire.hook('morph.updated', () => {
        if (window.lucide) window.lucide.createIcons();
    });
</script>
@endscript
