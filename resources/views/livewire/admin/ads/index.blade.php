<div class="space-y-5"
     x-data="{ zoneOpen: @entangle('showZoneForm'), creativeOpen: @entangle('showCreativeForm') }">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">Ad Manager</span>
    </nav>

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-emerald-500 to-lime-500 text-white shadow-sm">
                <i data-lucide="banknote" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">Ad Manager</h1>
                <p class="text-xs text-slate-500">Manage zone slots and the creatives that fill them.</p>
            </div>
        </div>

        @if ($tab === 'creatives')
            <button type="button" x-on:click="creativeOpen = true; $wire.newCreative()"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-indigo-700">
                <i data-lucide="plus" class="h-4 w-4"></i> New Creative
            </button>
        @else
            <button type="button" x-on:click="zoneOpen = true; $wire.newZone()"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-indigo-700">
                <i data-lucide="plus" class="h-4 w-4"></i> New Zone
            </button>
        @endif
    </div>

    {{-- Tabs --}}
    <div class="flex items-center gap-2">
        @foreach (['creatives' => 'Creatives', 'zones' => 'Zones'] as $key => $label)
            <button type="button" wire:click="$set('tab', '{{ $key }}')"
                    @class([
                        'rounded-xl border px-3 py-2 text-xs font-semibold',
                        'border-indigo-500 bg-indigo-50 text-indigo-700' => $tab === $key,
                        'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' => $tab !== $key,
                    ])>
                {{ $label }}
            </button>
        @endforeach
    </div>

    @if ($tab === 'creatives')
        {{-- Filters --}}
        <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:grid-cols-2">
            <select wire:model.live="zoneFilter"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                <option value="">All zones</option>
                @foreach ($this->zones as $z)
                    <option value="{{ $z->id }}">{{ $z->name }} ({{ $z->key }})</option>
                @endforeach
            </select>
            <select wire:model.live="statusFilter"
                    class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                <option value="">All statuses</option>
                @foreach (\App\Models\AdCreative::STATUSES as $s)
                    <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>

        {{-- Creatives table --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <table class="min-w-full divide-y divide-slate-100 text-sm dark:divide-slate-800">
                <thead class="bg-slate-50/60 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-950/40">
                    <tr>
                        <th class="px-4 py-2.5">Preview</th>
                        <th class="px-4 py-2.5">Name / Zone</th>
                        <th class="px-4 py-2.5">Type</th>
                        <th class="px-4 py-2.5">Status</th>
                        <th class="px-4 py-2.5">Impr.</th>
                        <th class="px-4 py-2.5">Clicks</th>
                        <th class="px-4 py-2.5">CTR</th>
                        <th class="px-4 py-2.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($this->creatives as $c)
                        @php
                            $statusColor = match ($c->status) {
                                'active' => 'bg-emerald-100 text-emerald-700',
                                'paused' => 'bg-amber-100 text-amber-700',
                                'expired' => 'bg-slate-200 text-slate-700',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <tr wire:key="cr-{{ $c->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                            <td class="px-4 py-3">
                                @if ($c->media)
                                    <img src="{{ $c->media->url() }}" alt="" class="h-10 w-16 rounded border border-slate-200 object-cover">
                                @elseif ($c->type === 'html')
                                    <div class="grid h-10 w-16 place-items-center rounded border border-slate-200 bg-slate-50 text-[10px] font-mono text-slate-500">HTML</div>
                                @else
                                    <div class="grid h-10 w-16 place-items-center rounded border border-dashed border-slate-300 text-slate-400">—</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $c->name }}</p>
                                <p class="text-[10px] text-slate-500">{{ $c->zone?->name ?? '—' }} · {{ $c->zone?->key }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[10px] uppercase text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $c->type }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $statusColor }}">{{ $c->status }}</span>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">{{ number_format($c->impression_count) }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ number_format($c->click_count) }}</td>
                            <td class="px-4 py-3 font-mono text-xs">{{ number_format($c->ctrPercent(), 2) }}%</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    @can('ads.edit')
                                        @if ($c->status === 'active')
                                            <button type="button" wire:click="toggleCreativeStatus({{ $c->id }})"
                                                    class="grid h-7 w-7 place-items-center rounded-lg text-amber-600 hover:bg-amber-50"
                                                    title="Pause">
                                                <i data-lucide="pause" class="h-3.5 w-3.5"></i>
                                            </button>
                                        @else
                                            <button type="button" wire:click="toggleCreativeStatus({{ $c->id }})"
                                                    class="grid h-7 w-7 place-items-center rounded-lg text-emerald-600 hover:bg-emerald-50"
                                                    title="Activate">
                                                <i data-lucide="play" class="h-3.5 w-3.5"></i>
                                            </button>
                                        @endif
                                        <button type="button" x-on:click="creativeOpen = true; $wire.editCreative({{ $c->id }})"
                                                class="grid h-7 w-7 place-items-center rounded-lg text-indigo-500 hover:bg-indigo-50"
                                                title="Edit">
                                            <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                        </button>
                                    @endcan
                                    @can('ads.delete')
                                        <button type="button" wire:click="deleteCreative({{ $c->id }})"
                                                wire:confirm="Delete this creative?"
                                                class="grid h-7 w-7 place-items-center rounded-lg text-rose-500 hover:bg-rose-50"
                                                title="Delete">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                        </button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-12 text-center text-sm text-slate-400">No creatives yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="border-t border-slate-100 p-3 dark:border-slate-800">{{ $this->creatives->links() }}</div>
        </div>
    @else
        {{-- Zones grid --}}
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($this->zones as $zone)
                <div wire:key="zn-{{ $zone->id }}"
                     class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $zone->position ?? 'inline' }}</p>
                            <h3 class="mt-0.5 text-sm font-bold text-slate-800 dark:text-slate-100">{{ $zone->name }}</h3>
                            <p class="font-mono text-[10px] text-slate-500">{{ $zone->key }}</p>
                        </div>
                        @if ($zone->is_active)
                            <span class="rounded-md bg-emerald-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-700">Active</span>
                        @else
                            <span class="rounded-md bg-slate-200 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-700">Off</span>
                        @endif
                    </div>
                    <div class="mt-2 flex items-center gap-2 text-xs text-slate-500">
                        @if ($zone->width && $zone->height)
                            <span class="font-mono">{{ $zone->width }}×{{ $zone->height }}</span>
                        @endif
                        <span>· {{ $zone->creatives_count ?? 0 }} creative(s)</span>
                        <span>· max {{ $zone->max_creatives }}</span>
                    </div>
                    <div class="mt-3 flex justify-end gap-1.5">
                        @can('ads.positions')
                            <button type="button" x-on:click="zoneOpen = true; $wire.editZone({{ $zone->id }})"
                                    class="grid h-7 w-7 place-items-center rounded-lg text-indigo-500 hover:bg-indigo-50"
                                    title="Edit">
                                <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                            </button>
                            <button type="button" wire:click="deleteZone({{ $zone->id }})"
                                    wire:confirm="Delete zone {{ $zone->name }}? Creatives will also be deleted."
                                    class="grid h-7 w-7 place-items-center rounded-lg text-rose-500 hover:bg-rose-50"
                                    title="Delete">
                                <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                            </button>
                        @endcan
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-2xl border-2 border-dashed border-slate-200 p-12 text-center text-sm text-slate-400 dark:border-slate-700">
                    No zones defined yet — create the first one to get started.
                </div>
            @endforelse
        </div>
    @endif

    {{-- Zone modal — Alpine entangled --}}
    <div x-show="zoneOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
         x-on:keydown.escape.window="zoneOpen = false; $wire.cancelZoneForm()"
         x-on:click="zoneOpen = false; $wire.cancelZoneForm()">
            <div class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900"
                 x-on:click.stop>
                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                    <h3 class="text-sm font-bold">{{ $editingZoneId ? 'Edit Ad Zone' : 'New Ad Zone' }}</h3>
                    <button type="button" x-on:click="zoneOpen = false; $wire.cancelZoneForm()" class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
                <div class="space-y-4 p-5">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Key (slot)</label>
                            <input type="text" wire:model="zoneKey" placeholder="homepage_top"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-mono dark:border-slate-700 dark:bg-slate-950">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Name <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model="zoneName"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                            @error('zoneName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold">Description</label>
                        <textarea wire:model="zoneDescription" rows="2"
                                  class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
                    </div>
                    <div class="grid gap-3 md:grid-cols-4">
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Width</label>
                            <input type="number" wire:model="zoneWidth" min="0"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Height</label>
                            <input type="number" wire:model="zoneHeight" min="0"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Position</label>
                            <select wire:model="zonePosition"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                                @foreach (\App\Models\AdZone::POSITIONS as $p)
                                    <option value="{{ $p }}">{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Max creatives</label>
                            <input type="number" wire:model="zoneMaxCreatives" min="1" max="10"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-xs font-semibold">
                        <input type="checkbox" wire:model="zoneIsActive"
                               class="h-3.5 w-3.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        Active (serve ads from this zone)
                    </label>
                </div>
                <div class="flex justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                    <button type="button" x-on:click="zoneOpen = false; $wire.cancelZoneForm()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">Cancel</button>
                    <button type="button" wire:click="saveZone" class="rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-4 py-2 text-xs font-bold text-white">
                        {{ $editingZoneId ? 'Update Zone' : 'Create Zone' }}
                    </button>
                </div>
            </div>
    </div>

    {{-- Creative modal — Alpine entangled --}}
    <div x-show="creativeOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
         x-on:keydown.escape.window="creativeOpen = false; $wire.cancelCreativeForm()"
         x-on:click="creativeOpen = false; $wire.cancelCreativeForm()">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900"
                 x-on:click.stop>
                <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-100 bg-slate-50/95 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/95">
                    <h3 class="text-sm font-bold">{{ $editingCreativeId ? 'Edit Creative' : 'New Creative' }}</h3>
                    <button type="button" x-on:click="creativeOpen = false; $wire.cancelCreativeForm()" class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
                <div class="space-y-4 p-5">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Name <span class="text-rose-500">*</span></label>
                            <input type="text" wire:model="cName"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                            @error('cName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Zone <span class="text-rose-500">*</span></label>
                            <select wire:model="cZoneId"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                                <option value="">— choose —</option>
                                @foreach ($this->zones as $z)
                                    <option value="{{ $z->id }}">{{ $z->name }} ({{ $z->key }})</option>
                                @endforeach
                            </select>
                            @error('cZoneId') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Type</label>
                            <select wire:model.live="cType"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                                @foreach (\App\Models\AdCreative::TYPES as $t)
                                    <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Status</label>
                            <select wire:model="cStatus"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                                @foreach (\App\Models\AdCreative::STATUSES as $s)
                                    <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if ($cType === 'html')
                        <div>
                            <label class="mb-1 block text-xs font-semibold">HTML code snippet</label>
                            <textarea wire:model="cHtmlCode" rows="6"
                                      class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 font-mono text-xs dark:border-slate-700 dark:bg-slate-950"
                                      placeholder="<script async src='...'></script>"></textarea>
                        </div>
                    @else
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Image</label>
                            @if ($this->selectedMedia)
                                <div class="flex items-center gap-3 rounded-lg border border-slate-200 p-2 dark:border-slate-700">
                                    <img src="{{ $this->selectedMedia->url() }}" alt="" class="h-16 w-24 rounded object-cover">
                                    <button type="button" wire:click="$set('cMediaId', null)"
                                            class="text-xs font-semibold text-rose-600 hover:underline">Remove</button>
                                    <button type="button" wire:click="openMediaPickerForCreative"
                                            class="ml-auto text-xs font-semibold text-indigo-600 hover:underline">Replace</button>
                                </div>
                            @else
                                <button type="button" wire:click="openMediaPickerForCreative"
                                        class="flex aspect-[2/1] w-full items-center justify-center rounded-xl border-2 border-dashed border-slate-300 text-xs font-semibold text-slate-500 hover:border-indigo-500">
                                    <span class="inline-flex items-center gap-2"><i data-lucide="image-plus" class="h-4 w-4"></i> Choose image</span>
                                </button>
                            @endif
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Target URL</label>
                            <input type="url" wire:model="cTargetUrl" placeholder="https://"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                            @error('cTargetUrl') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Alt text / Caption</label>
                            <input type="text" wire:model="cAltText"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                        </div>
                    @endif

                    <div class="grid gap-3 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Start at</label>
                            <input type="datetime-local" wire:model="cStartAt"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold">End at</label>
                            <input type="datetime-local" wire:model="cEndAt"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold">Priority (lower = more)</label>
                            <input type="number" wire:model="cPriority" min="1" max="1000"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                        </div>
                    </div>
                </div>
                <div class="sticky bottom-0 flex justify-end gap-2 border-t border-slate-100 bg-slate-50/95 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/95">
                    <button type="button" x-on:click="creativeOpen = false; $wire.cancelCreativeForm()" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">Cancel</button>
                    <button type="button" wire:click="saveCreative" class="rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-4 py-2 text-xs font-bold text-white">
                        {{ $editingCreativeId ? 'Update Creative' : 'Create Creative' }}
                    </button>
                </div>
            </div>
    </div>

    <livewire:admin.media.media-picker-modal />
</div>
