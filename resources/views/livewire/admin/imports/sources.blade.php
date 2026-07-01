<div class="space-y-5" x-data="{ formOpen: @entangle('showForm') }">
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">RSS Sources</span>
    </nav>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-orange-500 to-rose-500 text-white shadow-sm">
                <i data-lucide="rss" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">RSS Sources</h1>
                <p class="text-xs text-slate-500">Configure feeds that auto-import as draft (or published) posts.</p>
            </div>
        </div>

        <button type="button" x-on:click="formOpen = true; $wire.newSource()"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-indigo-700">
            <i data-lucide="plus" class="h-4 w-4"></i> New Source
        </button>
    </div>

    {{-- Sources table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <table class="min-w-full divide-y divide-slate-100 text-sm dark:divide-slate-800">
            <thead class="bg-slate-50/60 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-950/40">
                <tr>
                    <th class="px-4 py-2.5">Source</th>
                    <th class="px-4 py-2.5">Category</th>
                    <th class="px-4 py-2.5">Locale</th>
                    <th class="px-4 py-2.5">Status</th>
                    <th class="px-4 py-2.5">Auto-publish</th>
                    <th class="px-4 py-2.5">Interval</th>
                    <th class="px-4 py-2.5">Last fetched</th>
                    <th class="px-4 py-2.5">Items</th>
                    <th class="px-4 py-2.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($this->sources as $s)
                    @php
                        $statusColor = match ($s->status) {
                            'active' => 'bg-emerald-100 text-emerald-700',
                            'paused' => 'bg-amber-100 text-amber-700',
                            'error' => 'bg-rose-100 text-rose-700',
                            default => 'bg-slate-100 text-slate-700',
                        };
                    @endphp
                    <tr wire:key="src-{{ $s->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $s->name }}</p>
                            <p class="truncate font-mono text-[10px] text-slate-500">{{ $s->feed_url }}</p>
                        </td>
                        <td class="px-4 py-3 text-xs">{{ $s->category?->translate('name') ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs">
                            {{ $s->defaultLanguage?->flag_emoji ?? '🌐' }} {{ $s->defaultLanguage?->code ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $statusColor }}">
                                {{ $s->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($s->auto_publish)
                                <i data-lucide="check" class="h-3.5 w-3.5 text-emerald-500"></i>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $s->fetch_interval_minutes }}m</td>
                        <td class="px-4 py-3 text-xs text-slate-500">
                            {{ $s->last_fetched_at?->diffForHumans() ?? '—' }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs">{{ number_format($s->item_count) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" wire:click="fetchNow({{ $s->id }})"
                                        wire:loading.attr="disabled" wire:target="fetchNow"
                                        class="grid h-7 w-7 place-items-center rounded-lg text-indigo-600 hover:bg-indigo-50"
                                        title="Fetch now">
                                    <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i>
                                </button>
                                <button type="button" wire:click="togglePause({{ $s->id }})"
                                        class="grid h-7 w-7 place-items-center rounded-lg text-amber-600 hover:bg-amber-50"
                                        title="{{ $s->status === 'active' ? 'Pause' : 'Activate' }}">
                                    <i data-lucide="{{ $s->status === 'active' ? 'pause' : 'play' }}" class="h-3.5 w-3.5"></i>
                                </button>
                                <button type="button" x-on:click="formOpen = true; $wire.editSource({{ $s->id }})"
                                        class="grid h-7 w-7 place-items-center rounded-lg text-slate-500 hover:bg-slate-100"
                                        title="Edit">
                                    <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                </button>
                                <button type="button" wire:click="deleteSource({{ $s->id }})"
                                        wire:confirm="Delete source '{{ $s->name }}'? Imported items will be kept for history."
                                        class="grid h-7 w-7 place-items-center rounded-lg text-rose-500 hover:bg-rose-50"
                                        title="Delete">
                                    <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                </button>
                            </div>

                            @if ($s->last_error)
                                <p class="mt-1 max-w-xs truncate text-right text-[10px] text-rose-600" title="{{ $s->last_error }}">
                                    {{ $s->last_error }}
                                </p>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-12 text-center text-sm text-slate-400">
                            No RSS sources yet. Add the first one to start auto-importing.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-100 p-3 dark:border-slate-800">{{ $this->sources->links() }}</div>
    </div>

    {{-- Modal — Alpine entangled, instant open/close --}}
    <div x-show="formOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
         x-on:keydown.escape.window="formOpen = false; $wire.cancelForm()"
         x-on:click="formOpen = false; $wire.cancelForm()">
            <div class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900"
                 x-on:click.stop>
                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                    <h3 class="text-sm font-bold">{{ $editingId ? 'Edit RSS Source' : 'New RSS Source' }}</h3>
                    <button type="button" x-on:click="formOpen = false; $wire.cancelForm()" class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
                <div class="space-y-4 p-5">
                    <div>
                        <label class="mb-1 block text-xs font-semibold">Name <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="name" placeholder="e.g. The Verge"
                               class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold">Feed URL <span class="text-rose-500">*</span></label>
                        <input type="url" wire:model="feedUrl" placeholder="https://www.theverge.com/rss/index.xml"
                               class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-mono dark:border-slate-700 dark:bg-slate-950">
                        @error('feedUrl') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Category</label>
                            <select wire:model="categoryId"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                                <option value="">— uncategorised —</option>
                                @foreach ($this->categoryOptions as $c)
                                    <option value="{{ $c->id }}">{{ $c->translate('name') ?? '#'.$c->id }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Language</label>
                            <select wire:model="defaultLanguageId"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                                @foreach ($this->languageOptions as $l)
                                    <option value="{{ $l->id }}">{{ $l->flag_emoji }} {{ $l->name }} ({{ $l->code }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Post type</label>
                            <select wire:model="defaultPostType"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                                <option value="news">News</option>
                                <option value="post">Post</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Status</label>
                            <select wire:model="status"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                                @foreach (\App\Models\ImportSource::STATUSES as $st)
                                    <option value="{{ $st }}">{{ ucfirst($st) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Interval (minutes)</label>
                            <input type="number" wire:model="fetchIntervalMinutes" min="5" max="1440"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                        </div>
                    </div>

                    <label class="flex items-center gap-2 rounded-xl bg-amber-50/60 p-3 text-sm font-semibold text-amber-900 dark:bg-amber-500/10 dark:text-amber-300">
                        <input type="checkbox" wire:model="autoPublish"
                               class="h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                        Auto-publish imported items (skip the draft queue)
                    </label>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                    <button type="button" x-on:click="formOpen = false; $wire.cancelForm()"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                        Cancel
                    </button>
                    <button type="button" wire:click="save"
                            class="rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-4 py-2 text-xs font-bold text-white">
                        {{ $editingId ? 'Update' : 'Create' }}
                    </button>
                </div>
            </div>
    </div>
</div>
