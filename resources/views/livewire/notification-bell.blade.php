<div
    x-data="{}"
    @click.outside="$wire && typeof $wire.close === 'function' && $wire.close()"
    @keydown.escape.window="$wire && typeof $wire.close === 'function' && $wire.close()"
    class="relative"
    wire:poll.30s
>
    <button
        type="button"
        wire:click="toggle"
        class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
        aria-label="Notifications"
    >
        <i data-lucide="bell" class="h-5 w-5"></i>

        @if ($this->unreadCount > 0)
            <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white shadow">
                {{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-show="$wire.open"
        x-transition.opacity.duration.150ms
        x-cloak
        class="absolute right-0 top-12 z-40 w-[22rem] max-w-[90vw] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900"
    >
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-800">
            <div>
                <h3 class="text-sm font-semibold text-slate-800 dark:text-slate-100">Notifications</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    @if ($this->unreadCount > 0)
                        {{ $this->unreadCount }} unread
                    @else
                        All caught up
                    @endif
                </p>
            </div>

            @if ($this->unreadCount > 0)
                <button
                    type="button"
                    wire:click="markAllRead"
                    class="rounded-lg px-2 py-1 text-xs font-semibold text-indigo-600 transition hover:bg-indigo-50 dark:text-indigo-300 dark:hover:bg-indigo-500/10"
                >
                    Mark all read
                </button>
            @endif
        </div>

        <div class="max-h-[24rem] overflow-y-auto">
            @forelse ($this->notifications as $notification)
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
                    // Legacy rows may only have `message`. Fall back so the
                    // heading is never empty.
                    $titleText = $data['title'] ?? $data['message'] ?? 'Notification';
                    $subText = ($data['title'] ?? null) ? ($data['message'] ?? '') : '';
                @endphp

                <a
                    href="{{ $data['url'] ?? '#' }}"
                    wire:click.prevent="markRead('{{ $notification->id }}')"
                    onclick="setTimeout(() => { window.location = @js($data['url'] ?? '#'); }, 80)"
                    class="flex items-start gap-3 border-b border-slate-50 px-4 py-3 transition hover:bg-slate-50 dark:border-slate-800/80 dark:hover:bg-slate-800 {{ $notification->read_at === null ? 'bg-indigo-50/30 dark:bg-indigo-500/5' : '' }}"
                >
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $chipClass }}">
                        <i data-lucide="{{ $data['icon'] ?? 'bell' }}" class="h-4 w-4"></i>
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="flex items-start justify-between gap-2">
                            <span class="block truncate text-sm font-semibold text-slate-800 dark:text-slate-100">
                                {{ $titleText }}
                            </span>
                            @if ($notification->read_at === null)
                                <span class="mt-1.5 inline-block h-2 w-2 shrink-0 rounded-full bg-indigo-500"></span>
                            @endif
                        </span>
                        @if ($subText !== '')
                            <span class="mt-0.5 block truncate text-xs text-slate-500 dark:text-slate-400">
                                {{ $subText }}
                            </span>
                        @endif
                        <span class="mt-1 block text-[11px] uppercase tracking-wide text-slate-400 dark:text-slate-500">
                            {{ $notification->created_at?->diffForHumans() }}
                        </span>
                    </span>
                </a>
            @empty
                <div class="px-6 py-10 text-center">
                    <div class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-emerald-50 text-emerald-500 dark:bg-emerald-500/10 dark:text-emerald-300">
                        <i data-lucide="check-circle-2" class="h-6 w-6"></i>
                    </div>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">You're all caught up!</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">No notifications right now.</p>
                </div>
            @endforelse
        </div>

        <a
            href="{{ $this->viewAllUrl() }}"
            wire:navigate
            class="block border-t border-slate-100 px-4 py-3 text-center text-xs font-semibold text-indigo-600 transition hover:bg-slate-50 dark:border-slate-800 dark:text-indigo-300 dark:hover:bg-slate-800"
        >
            View all notifications
        </a>
    </div>
</div>

@script
<script>
    Livewire.hook('morph.updated', () => {
        if (window.lucide) window.lucide.createIcons();
    });
</script>
@endscript
