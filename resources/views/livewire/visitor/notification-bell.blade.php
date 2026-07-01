@php
    $count = $this->unreadCount;
@endphp

<div class="relative" x-data="{ open: @entangle('open') }" x-on:keydown.escape.window="open = false">
    {{-- Bell button --}}
    <button type="button" x-on:click="open = ! open"
            class="relative grid h-10 w-10 place-items-center rounded-xl border border-slate-200 text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
            title="Notifications" aria-label="Notifications">
        <i data-lucide="bell" class="h-4 w-4"></i>
        @if ($count > 0)
            <span class="absolute -right-1 -top-1 inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-black text-white ring-2 ring-white dark:ring-slate-900">
                {{ $count > 99 ? '99+' : $count }}
            </span>
        @endif
    </button>

    {{-- Dropdown --}}
    <div x-show="open" x-cloak
         x-on:click.outside="open = false"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl ring-1 ring-black/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-white/5">

        <header class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-800">
            <h3 class="text-sm font-black tracking-tight" style="font-family: 'Playfair Display', serif;">
                Notifications
            </h3>
            @if ($count > 0)
                <button type="button" wire:click="markAllRead"
                        class="text-[11px] font-bold text-indigo-600 hover:underline dark:text-indigo-300">
                    Mark all read
                </button>
            @endif
        </header>

        @if ($this->recent->isEmpty())
            <div class="px-4 py-10 text-center">
                <i data-lucide="bell-off" class="mx-auto h-8 w-8 text-slate-300"></i>
                <p class="mt-3 text-sm font-semibold text-slate-700 dark:text-slate-200">All caught up</p>
                <p class="mt-0.5 text-[11px] text-slate-500">New replies, follows, and digests will appear here.</p>
            </div>
        @else
            <ul class="max-h-96 divide-y divide-slate-100 overflow-y-auto dark:divide-slate-800">
                @foreach ($this->recent as $n)
                    @php
                        $data = $n->data ?? [];
                        $icon = $data['icon'] ?? 'bell';
                        $color = $data['color'] ?? 'indigo';
                        $colorClasses = [
                            'indigo'  => 'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300',
                            'emerald' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300',
                            'rose'    => 'bg-rose-100 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300',
                            'amber'   => 'bg-amber-100 text-amber-600 dark:bg-amber-500/15 dark:text-amber-300',
                            'violet'  => 'bg-violet-100 text-violet-600 dark:bg-violet-500/15 dark:text-violet-300',
                            'sky'     => 'bg-sky-100 text-sky-600 dark:bg-sky-500/15 dark:text-sky-300',
                        ][$color] ?? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300';
                    @endphp
                    <li class="{{ $n->read_at === null ? 'bg-indigo-50/40 dark:bg-indigo-500/5' : '' }}">
                        <a href="{{ $data['url'] ?? route('visitor.notifications') }}"
                           wire:click="markRead('{{ $n->id }}')"
                           class="flex gap-3 px-4 py-3 transition hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $colorClasses }}">
                                <i data-lucide="{{ $icon }}" class="h-4 w-4"></i>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs font-bold text-slate-900 dark:text-slate-100">
                                    {{ $data['title'] ?? 'New notification' }}
                                </span>
                                @if (! empty($data['message']))
                                    <span class="block line-clamp-2 text-[11px] text-slate-500">{{ $data['message'] }}</span>
                                @endif
                                <span class="mt-0.5 block text-[10px] text-slate-400">{{ $n->created_at?->diffForHumans() }}</span>
                            </span>
                            @if ($n->read_at === null)
                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-indigo-500"></span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        <footer class="border-t border-slate-100 px-4 py-2 text-center dark:border-slate-800">
            <a href="{{ route('visitor.notifications') }}"
               class="inline-flex items-center gap-1 text-[11px] font-bold text-indigo-600 hover:underline dark:text-indigo-300">
                See all
                <i data-lucide="arrow-right" class="h-3 w-3"></i>
            </a>
        </footer>
    </div>
</div>
