<div>
    <x-admin.page-header
        eyebrow="People"
        icon="user-round"
        title="Subscribers"
        description="Manage registered subscribers and promote them to Author status.">
    </x-admin.page-header>

    <x-admin.section title="Filters" description="Narrow the list by name, email, or status." class="mb-6">
        <div class="grid gap-3 md:grid-cols-4">
            <input type="text" wire:model.live.debounce.300ms="search"
                   placeholder="Search name or email…"
                   class="md:col-span-3 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">

            <select wire:model.live="statusFilter"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-indigo-400 focus:ring-1 focus:ring-indigo-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                <option value="">All statuses</option>
                @foreach ($statuses as $statusOption)
                    <option value="{{ $statusOption }}">{{ ucfirst($statusOption) }}</option>
                @endforeach
            </select>
        </div>
    </x-admin.section>

    <x-admin.table-shell title="All subscribers" description="{{ $subscribers->total() }} total · paginated, latest first.">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
            <thead class="bg-slate-50 text-left text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:bg-slate-950/50 dark:text-slate-300">
                <tr>
                    <th class="px-4 py-3">Subscriber</th>
                    <th class="px-4 py-3">Joined</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($subscribers as $subscriber)
                    <tr wire:key="subscriber-{{ $subscriber->id }}" class="transition hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <img src="{{ $subscriber->avatarUrl() }}" alt="" class="h-9 w-9 rounded-full object-cover">
                                <div>
                                    <div class="font-semibold text-slate-800 dark:text-slate-100">{{ $subscriber->name }}</div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $subscriber->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-700 dark:text-slate-200">{{ $subscriber->created_at?->format('M d, Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @php($statusColor = match ($subscriber->status) {
                                'active' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
                                'inactive' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                                'suspended' => 'bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300',
                                default => 'bg-slate-100 text-slate-700',
                            })
                            <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-[0.18em] {{ $statusColor }}">{{ ucfirst($subscriber->status ?? 'active') }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex gap-1">
                                @can('subscribers.promote')
                                    <button wire:click="promoteToAuthor({{ $subscriber->id }})"
                                            wire:confirm="Promote {{ $subscriber->name }} to Author? This grants them content-creation access."
                                            type="button"
                                            class="rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300">
                                        Promote to Author
                                    </button>
                                @endcan
                                @can('subscribers.deactivate')
                                    @if ($subscriber->status === 'active')
                                        <button wire:click="deactivate({{ $subscriber->id }})" type="button"
                                                class="rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 transition hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300">Deactivate</button>
                                    @else
                                        <button wire:click="activate({{ $subscriber->id }})" type="button"
                                                class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">Activate</button>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-0">
                            <x-admin.empty-state
                                icon="user-round"
                                title="No subscribers match your filter."
                                description="Try clearing filters — subscribers appear here after public registration." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <x-slot:footer>
            @if ($subscribers->hasPages())
                {{ $subscribers->links() }}
            @endif
        </x-slot:footer>
    </x-admin.table-shell>
</div>
