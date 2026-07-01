<div class="space-y-5" x-data="{ editorOpen: @entangle('editorOpen') }">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">AI · Prompt Templates</span>
    </nav>

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-violet-500 to-fuchsia-500 text-white shadow-sm">
                <i data-lucide="scroll-text" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">Prompt Templates</h1>
                <p class="text-xs text-slate-500">Versioned prompts powering every AI generation across the app.</p>
            </div>
        </div>
        <button type="button" x-on:click="editorOpen = true; $wire.openCreate()"
                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:from-violet-700 hover:to-fuchsia-700">
            <i data-lucide="plus" class="h-4 w-4"></i> New Template
        </button>
    </div>

    {{-- Filters --}}
    <div class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:grid-cols-[1fr_180px_180px_120px_120px]">
        <div class="relative">
            <i data-lucide="search" class="absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400"></i>
            <input type="text" wire:model.live.debounce.300ms="search"
                   placeholder="Search key, system or user prompt…"
                   class="w-full rounded-xl border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm dark:border-slate-700 dark:bg-slate-950">
        </div>
        <select wire:model.live="keyFilter" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
            <option value="">All keys</option>
            @foreach ($this->keyOptions as $k)
                <option value="{{ $k }}">{{ $k }}</option>
            @endforeach
        </select>
        <select wire:model.live="localeFilter" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
            <option value="">All locales</option>
            @foreach ($this->locales as $code)
                <option value="{{ $code }}">{{ $code }}</option>
            @endforeach
        </select>
        <label class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900">
            <input type="checkbox" wire:model.live="activeOnly" class="h-3.5 w-3.5 rounded border-slate-300 text-violet-600">
            Active only
        </label>
        <button type="button" wire:click="clearFilters"
                class="inline-flex items-center justify-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900">
            <i data-lucide="filter-x" class="h-3.5 w-3.5"></i> Clear
        </button>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50/60 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-950/40">
                <tr>
                    <th class="px-4 py-3">Key</th>
                    <th class="px-4 py-3">Locale</th>
                    <th class="px-4 py-3">Version</th>
                    <th class="px-4 py-3">Active</th>
                    <th class="px-4 py-3">Model hint</th>
                    <th class="px-4 py-3">Updated</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($this->templates as $tpl)
                    <tr wire:key="tpl-{{ $tpl->id }}" class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                        <td class="px-4 py-3 font-mono text-xs text-slate-800 dark:text-slate-100">{{ $tpl->key }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-md bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] text-slate-700 dark:bg-slate-800 dark:text-slate-300">{{ $tpl->locale }}</span>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-600">v{{ $tpl->version }}</td>
                        <td class="px-4 py-3">
                            <button type="button" wire:click="toggleActive({{ $tpl->id }})"
                                    class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider transition
                                           {{ $tpl->is_active
                                              ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-500/20 dark:text-emerald-300'
                                              : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $tpl->is_active ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                {{ $tpl->is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-600">{{ $tpl->model_hint ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $tpl->updated_at?->diffForHumans() }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex items-center gap-1">
                                <button type="button" x-on:click="editorOpen = true; $wire.edit({{ $tpl->id }})"
                                        class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-violet-50 hover:text-violet-600">
                                    <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                </button>
                                <button type="button" wire:click="delete({{ $tpl->id }})"
                                        wire:confirm="Delete this template? Templates referenced by usage logs are deactivated instead."
                                        class="grid h-8 w-8 place-items-center rounded-lg text-slate-500 hover:bg-rose-50 hover:text-rose-600">
                                    <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500">
                            No templates match these filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-100 px-4 py-2 dark:border-slate-800">
            {{ $this->templates->onEachSide(1)->links() }}
        </div>
    </div>

    {{-- Editor modal — Alpine entangled. Heavy form mounts only when open. --}}
    <div x-show="editorOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-on:keydown.escape.window="editorOpen = false; $wire.closeEditor()">
        <div class="absolute inset-0 bg-slate-900/60" x-on:click="editorOpen = false; $wire.closeEditor()"></div>
        @if ($editorOpen)
            <div class="relative z-10 max-h-[90vh] w-full max-w-3xl overflow-y-auto rounded-3xl bg-white shadow-2xl dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-500 text-white">
                            <i data-lucide="{{ $editingId ? 'edit-3' : 'plus' }}" class="h-4 w-4"></i>
                        </span>
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">
                                {{ $editingId ? 'Edit (creates new version)' : 'New Prompt Template' }}
                            </h2>
                            <p class="text-xs text-slate-500">Edits never mutate the existing row — a new version is appended.</p>
                        </div>
                    </div>
                    <button type="button" x-on:click="editorOpen = false; $wire.closeEditor()" class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <div class="space-y-4 p-6">
                    <div class="grid gap-4 md:grid-cols-[1fr_140px]">
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Key</label>
                            <input type="text" wire:model="form_key" placeholder="article.generate"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 font-mono text-sm dark:border-slate-700 dark:bg-slate-950"
                                   {{ $editingId ? 'disabled' : '' }}>
                            @error('form_key') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Locale</label>
                            <select wire:model="form_locale"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"
                                    {{ $editingId ? 'disabled' : '' }}>
                                @foreach ($this->locales as $code)
                                    <option value="{{ $code }}">{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">System Prompt</label>
                        <textarea wire:model="form_system_prompt" rows="4"
                                  placeholder="You are an experienced editorial assistant…"
                                  class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
                        @error('form_system_prompt') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">
                            User Prompt Template
                            <span class="ml-1 text-[10px] font-normal text-slate-400">— use @{{variable}} placeholders</span>
                        </label>
                        <textarea wire:model="form_user_prompt_template" rows="6"
                                  placeholder="Write a @{{tone}} article about @{{topic}} for @{{audience}}…"
                                  class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 font-mono text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
                        @error('form_user_prompt_template') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">
                            Required variables
                            <span class="ml-1 text-[10px] font-normal text-slate-400">— comma separated</span>
                        </label>
                        <input type="text" wire:model="form_variables_csv"
                               placeholder="topic, tone, audience, word_count"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 font-mono text-sm dark:border-slate-700 dark:bg-slate-950">
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Model hint</label>
                            <input type="text" wire:model="form_model_hint" placeholder="gpt-4o-mini"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 font-mono text-sm dark:border-slate-700 dark:bg-slate-950">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Temperature</label>
                            <input type="number" step="0.05" min="0" max="2" wire:model="form_temperature_hint"
                                   placeholder="0.7"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 font-mono text-sm dark:border-slate-700 dark:bg-slate-950">
                        </div>
                        <div>
                            <label class="mb-1 flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-500">
                                <input type="checkbox" wire:model="form_is_active" class="h-3.5 w-3.5 rounded border-slate-300 text-violet-600">
                                Active (this version)
                            </label>
                            <p class="mt-1 text-[10px] text-slate-400">Only one row per (key + locale) is active at a time.</p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-200 bg-slate-50/60 px-6 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                    <button type="button" x-on:click="editorOpen = false; $wire.closeEditor()"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900">
                        Cancel
                    </button>
                    <button type="button" wire:click="save"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-violet-600 to-fuchsia-600 px-4 py-2 text-xs font-bold text-white hover:from-violet-700 hover:to-fuchsia-700">
                        <i data-lucide="save" class="h-3.5 w-3.5"></i>
                        {{ $editingId ? 'Save as new version' : 'Create template' }}
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
