<div class="space-y-5">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">Newsletter Subscribers</span>
    </nav>

    {{-- Header + counts --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-pink-500 to-rose-500 text-white shadow-sm">
                <i data-lucide="send" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">Newsletter Subscribers</h1>
                <p class="text-xs text-slate-500">
                    {{ $this->counts['confirmed'] }} confirmed · {{ $this->counts['pending'] }} pending · {{ $this->counts['unsubscribed'] }} unsubscribed
                </p>
            </div>
        </div>

        <button type="button" wire:click="exportCsv"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-indigo-700">
            <i data-lucide="download" class="h-4 w-4"></i>
            Export CSV
        </button>
    </div>

    {{-- Stat tiles --}}
    <div class="grid gap-3 sm:grid-cols-4">
        @php
            $tiles = [
                ['label' => 'Total', 'value' => $this->counts['total'], 'color' => 'slate'],
                ['label' => 'Confirmed', 'value' => $this->counts['confirmed'], 'color' => 'emerald'],
                ['label' => 'Pending', 'value' => $this->counts['pending'], 'color' => 'amber'],
                ['label' => 'Unsubscribed', 'value' => $this->counts['unsubscribed'], 'color' => 'rose'],
            ];
        @endphp
        @foreach ($tiles as $tile)
            @php
                $accent = match ($tile['color']) {
                    'emerald' => 'from-emerald-500 to-teal-500',
                    'amber' => 'from-amber-500 to-orange-500',
                    'rose' => 'from-rose-500 to-pink-500',
                    default => 'from-slate-400 to-slate-500',
                };
            @endphp
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $tile['label'] }}</p>
                    <span class="h-6 w-6 rounded-lg bg-gradient-to-br {{ $accent }}"></span>
                </div>
                <p class="mt-2 text-2xl font-black">{{ number_format($tile['value']) }}</p>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:grid-cols-[1fr_180px_180px_120px]">
        <div class="relative">
            <i data-lucide="search" class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
            <input type="text" wire:model.live.debounce.400ms="search"
                   placeholder="Search email or name…"
                   class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm dark:border-slate-700 dark:bg-slate-950">
        </div>
        <select wire:model.live="statusFilter"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
            <option value="">All statuses</option>
            @foreach (\App\Models\NewsletterSubscriber::STATUSES as $s)
                <option value="{{ $s }}">{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <select wire:model.live="sourceFilter"
                class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
            <option value="">All sources</option>
            @foreach ($this->knownSources as $source)
                <option value="{{ $source }}">{{ $source }}</option>
            @endforeach
        </select>
        <button type="button" wire:click="clearFilters"
                class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
            <i data-lucide="filter-x" class="h-3.5 w-3.5"></i>
            Clear
        </button>
    </div>

    {{-- Bulk action bar --}}
    @if (! empty($selectedIds))
        <div class="flex items-center justify-between gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 dark:border-indigo-500/30 dark:bg-indigo-500/10">
            <p class="text-xs font-semibold text-indigo-800 dark:text-indigo-300">
                {{ count($selectedIds) }} selected
            </p>
            <button type="button" wire:click="bulkDelete"
                    wire:confirm="Delete selected subscribers? This cannot be undone."
                    class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-rose-700">
                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                Delete
            </button>
        </div>
    @endif

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <table class="min-w-full divide-y divide-slate-100 text-sm dark:divide-slate-800">
            <thead class="bg-slate-50/60 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-950/40">
                <tr>
                    <th class="px-4 py-2.5 w-8"></th>
                    <th class="px-4 py-2.5">Email</th>
                    <th class="px-4 py-2.5">Name</th>
                    <th class="px-4 py-2.5">Status</th>
                    <th class="px-4 py-2.5">Source</th>
                    <th class="px-4 py-2.5">Locale</th>
                    <th class="px-4 py-2.5">Signed up</th>
                    <th class="px-4 py-2.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($this->subscribers as $sub)
                    @php
                        $statusColor = match ($sub->status) {
                            'confirmed' => 'bg-emerald-100 text-emerald-700',
                            'pending' => 'bg-amber-100 text-amber-700',
                            'unsubscribed' => 'bg-slate-100 text-slate-700',
                            'bounced' => 'bg-rose-100 text-rose-700',
                            'complained' => 'bg-orange-100 text-orange-700',
                            default => 'bg-slate-100 text-slate-700',
                        };
                    @endphp
                    <tr wire:key="sub-{{ $sub->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                        <td class="px-4 py-3">
                            <input type="checkbox" value="{{ $sub->id }}" wire:model="selectedIds"
                                   class="h-3.5 w-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-700 dark:text-slate-200">{{ $sub->email }}</td>
                        <td class="px-4 py-3">{{ $sub->name ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $statusColor }}">
                                {{ $sub->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $sub->source ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs">
                            @if ($sub->language)
                                <span title="{{ $sub->language->name }}">{{ $sub->language->flag_emoji ?? $sub->language->code }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $sub->created_at?->diffForHumans() }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1.5">
                                @if (! $sub->isUnsubscribed())
                                    <button type="button"
                                            wire:click="markUnsubscribed({{ $sub->id }})"
                                            wire:confirm="Mark {{ $sub->email }} as unsubscribed?"
                                            class="grid h-7 w-7 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
                                            title="Mark unsubscribed">
                                        <i data-lucide="user-x" class="h-3.5 w-3.5"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-400">
                            No subscribers match your filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="border-t border-slate-100 p-3 dark:border-slate-800">
            {{ $this->subscribers->onEachSide(1)->links() }}
        </div>
    </div>
</div>
