<div class="space-y-5" x-data="{ editorOpen: @entangle('editorOpen') }">
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <a href="{{ route('admin.seo.tools') }}" wire:navigate class="hover:text-indigo-600">SEO Tools</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">Redirects</span>
    </nav>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-500 text-white shadow-sm">
                <i data-lucide="corner-down-right" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">Redirects</h1>
                <p class="text-xs text-slate-500">301/302 rules consumed by the HandleRedirects middleware.</p>
            </div>
        </div>
        <button type="button" x-on:click="editorOpen = true; $wire.openCreate()"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-sky-600 to-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:from-sky-700 hover:to-indigo-700">
            <i data-lucide="plus" class="h-4 w-4"></i> New redirect
        </button>
    </div>

    <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:grid-cols-[1fr_140px]">
        <div class="relative">
            <i data-lucide="search" class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
            <input type="text" wire:model.live.debounce.300ms="search"
                   placeholder="Search by source or target URL…"
                   class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm dark:border-slate-700 dark:bg-slate-950">
        </div>
        <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900">
            <input type="checkbox" wire:model.live="activeOnly" class="h-3.5 w-3.5 rounded border-slate-300 text-sky-600">
            Active only
        </label>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <table class="w-full text-sm">
            <thead class="bg-slate-50/60 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-950/40">
                <tr>
                    <th class="px-4 py-3 text-left">From path</th>
                    <th class="px-4 py-3 text-left">To URL</th>
                    <th class="px-4 py-3 text-left">Code</th>
                    <th class="px-4 py-3 text-left">Active</th>
                    <th class="px-4 py-3 text-right">Hits</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($this->redirects as $r)
                    <tr wire:key="rd-{{ $r->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                        <td class="px-4 py-3 font-mono text-xs text-slate-800 dark:text-slate-100">{{ $r->from_path }}</td>
                        <td class="px-4 py-3 truncate font-mono text-xs text-slate-600">{{ $r->to_url }}</td>
                        <td class="px-4 py-3 font-mono text-xs">{{ $r->status_code }}</td>
                        <td class="px-4 py-3">
                            <button type="button" wire:click="toggleActive({{ $r->id }})"
                                    class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider
                                           {{ $r->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $r->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                {{ $r->is_active ? 'Active' : 'Off' }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-right font-mono text-xs tabular-nums">{{ number_format($r->hit_count) }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-1">
                                <button type="button" x-on:click="editorOpen = true; $wire.edit({{ $r->id }})"
                                        class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-sky-50 hover:text-sky-600">
                                    <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                </button>
                                <button type="button" wire:click="delete({{ $r->id }})"
                                        wire:confirm="Delete this redirect rule?"
                                        class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-rose-50 hover:text-rose-600">
                                    <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-xs text-slate-500">No redirects configured.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-100 px-4 py-2 dark:border-slate-800">
            {{ $this->redirects->onEachSide(1)->links() }}
        </div>
    </div>

    <div x-show="editorOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-on:keydown.escape.window="editorOpen = false; $wire.closeEditor()">
        <div class="absolute inset-0 bg-slate-900/60" x-on:click="editorOpen = false; $wire.closeEditor()"></div>
        @if ($editorOpen)
            <div class="relative z-10 w-full max-w-xl rounded-3xl bg-white shadow-2xl dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">
                        {{ $editingId ? 'Edit redirect' : 'New redirect' }}
                    </h2>
                    <button type="button" x-on:click="editorOpen = false; $wire.closeEditor()" class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>
                <div class="space-y-4 p-6">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">From path</label>
                        <input type="text" wire:model="form_from_path" placeholder="/old-article"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 font-mono text-sm dark:border-slate-700 dark:bg-slate-950">
                        @error('form_from_path') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">To URL</label>
                        <input type="text" wire:model="form_to_url" placeholder="/new-article or https://example.com/elsewhere"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 font-mono text-sm dark:border-slate-700 dark:bg-slate-950">
                    </div>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Status code</label>
                            <select wire:model="form_status_code"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                                <option value="301">301 (Permanent)</option>
                                <option value="302">302 (Temporary)</option>
                                <option value="307">307 (Temporary preserve method)</option>
                                <option value="308">308 (Permanent preserve method)</option>
                            </select>
                        </div>
                        <label class="mt-5 inline-flex items-center gap-2 text-xs font-semibold text-slate-600">
                            <input type="checkbox" wire:model="form_is_active" class="h-3.5 w-3.5 rounded border-slate-300 text-sky-600">
                            Active
                        </label>
                        <label class="mt-5 inline-flex items-center gap-2 text-xs font-semibold text-slate-600">
                            <input type="checkbox" wire:model="form_preserve_query" class="h-3.5 w-3.5 rounded border-slate-300 text-sky-600">
                            Preserve query
                        </label>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Notes</label>
                        <textarea wire:model="form_notes" rows="2"
                                  class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50/60 px-6 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                    <button type="button" x-on:click="editorOpen = false; $wire.closeEditor()"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900">
                        Cancel
                    </button>
                    <button type="button" wire:click="save"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-sky-600 to-indigo-600 px-4 py-2 text-xs font-bold text-white hover:from-sky-700">
                        <i data-lucide="save" class="h-3.5 w-3.5"></i> Save
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
