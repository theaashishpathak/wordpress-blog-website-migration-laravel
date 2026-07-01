@php
    $locale = app(\App\Support\LocaleResolver::class)->current();
    $tabs = [
        'all'    => ['label' => 'All',    'count' => $counts['all']],
        'unread' => ['label' => 'Unread', 'count' => $counts['unread']],
        'read'   => ['label' => 'Read',   'count' => $counts['read']],
    ];
@endphp

<div class="space-y-8">
    <x-visitor.page-header
        eyebrow="Notifications"
        title="Notifications"
        description="{{ $counts['unread'] }} unread of {{ $counts['all'] }} total.">
        <x-slot:actions>
            @if ($counts['unread'] > 0)
                <button type="button" wire:click="markAllRead"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                    <i data-lucide="check-check" class="h-3.5 w-3.5"></i>
                    Mark all read
                </button>
            @endif
            @if ($counts['all'] > 0)
                <button type="button" wire:click="clearAll"
                        wire:confirm="Permanently delete all your notifications?"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 dark:border-slate-700 dark:text-slate-300 dark:hover:border-rose-500/40 dark:hover:bg-rose-500/10 dark:hover:text-rose-300">
                    <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                    Clear all
                </button>
            @endif
        </x-slot:actions>
    </x-visitor.page-header>

    <div class="inline-flex flex-wrap gap-1 rounded-lg border border-slate-200 bg-slate-50 p-1 dark:border-slate-800 dark:bg-slate-900">
        @foreach ($tabs as $key => $tab)
            <button type="button" wire:click="switchFilter('{{ $key }}')"
                    class="rounded-md px-3 py-1.5 text-xs font-bold transition {{ $filter === $key ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-950 dark:text-slate-100' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
                {{ $tab['label'] }} <span class="ml-1 text-slate-400">{{ $tab['count'] }}</span>
            </button>
        @endforeach
    </div>

    @if ($this->notifications->isEmpty())
        <x-visitor.empty-state
            icon="bell-off"
            title="{{ $filter === 'unread' ? 'No unread notifications.' : ($filter === 'read' ? 'Nothing read yet.' : 'No notifications yet.') }}"
            description="Comment replies, followed-author publishes, and digests will appear here." />
    @else
        <div class="space-y-2">
            @foreach ($this->notifications as $n)
                @php
                    $data = $n->data ?? [];
                    $icon = $data['icon'] ?? 'bell';
                    $isUnread = $n->read_at === null;
                @endphp
                <article class="flex items-start gap-3 rounded-2xl border p-4 transition
                                {{ $isUnread
                                   ? 'border-emerald-200 bg-emerald-50/40 dark:border-emerald-500/30 dark:bg-emerald-500/5'
                                   : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700' }}">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-slate-50 text-slate-600 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                        <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-900 dark:text-slate-100">
                                    {{ $data['title'] ?? 'Notification' }}
                                    @if ($isUnread)
                                        <span class="ml-1 inline-block h-2 w-2 rounded-full bg-emerald-500 align-middle"></span>
                                    @endif
                                </p>
                                @if (! empty($data['message']))
                                    <p class="mt-0.5 text-xs leading-relaxed text-slate-600 dark:text-slate-300">{{ $data['message'] }}</p>
                                @endif
                                @if (! empty($data['post_title']))
                                    <p class="mt-1 inline-flex items-center gap-1 text-[11px] text-slate-500 dark:text-slate-400">
                                        <i data-lucide="newspaper" class="h-3 w-3"></i>
                                        {{ \Illuminate\Support\Str::limit($data['post_title'], 60) }}
                                    </p>
                                @endif
                            </div>
                            <span class="shrink-0 text-[11px] text-slate-400 dark:text-slate-500">{{ $n->created_at?->diffForHumans() }}</span>
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-3 text-[11px]">
                            @if (! empty($data['url']))
                                <a href="{{ $data['url'] }}" wire:click="markRead('{{ $n->id }}')"
                                   class="inline-flex items-center gap-1 font-bold text-emerald-700 hover:underline dark:text-emerald-300">
                                    <i data-lucide="arrow-up-right" class="h-3 w-3"></i>
                                    Open
                                </a>
                            @endif

                            @if ($isUnread)
                                <button type="button" wire:click="markRead('{{ $n->id }}')"
                                        class="inline-flex items-center gap-1 font-semibold text-slate-500 hover:text-slate-700 hover:underline dark:text-slate-400 dark:hover:text-slate-200">
                                    <i data-lucide="check" class="h-3 w-3"></i>
                                    Mark read
                                </button>
                            @else
                                <button type="button" wire:click="markUnread('{{ $n->id }}')"
                                        class="inline-flex items-center gap-1 font-semibold text-slate-500 hover:text-slate-700 hover:underline dark:text-slate-400 dark:hover:text-slate-200">
                                    <i data-lucide="undo-2" class="h-3 w-3"></i>
                                    Mark unread
                                </button>
                            @endif

                            <button type="button" wire:click="delete('{{ $n->id }}')"
                                    class="inline-flex items-center gap-1 font-semibold text-slate-500 transition hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-300">
                                <i data-lucide="trash-2" class="h-3 w-3"></i>
                                Remove
                            </button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">{{ $this->notifications->onEachSide(1)->links() }}</div>
    @endif
</div>
