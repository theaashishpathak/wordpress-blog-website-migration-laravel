<div class="space-y-5" x-data="{ formOpen: @entangle('showForm') }">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">Languages</span>
    </nav>

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-cyan-500 to-blue-500 text-white shadow-sm">
                <i data-lucide="languages" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">Languages</h1>
                <p class="text-xs text-slate-500">
                    Manage frontend + admin locales. The default language is required and cannot be deactivated.
                </p>
            </div>
        </div>

        @can('languages.create')
            <button type="button" x-on:click="formOpen = true; $wire.newLanguage()"
                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:from-indigo-700 hover:to-indigo-600">
                <i data-lucide="plus" class="h-4 w-4"></i>
                Add Language
            </button>
        @endcan
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <table class="min-w-full divide-y divide-slate-100 dark:divide-slate-800">
            <thead class="bg-slate-50/60 text-left text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-950/40">
                <tr>
                    <th class="px-4 py-2.5">#</th>
                    <th class="px-4 py-2.5">Language</th>
                    <th class="px-4 py-2.5">Code</th>
                    <th class="px-4 py-2.5">Direction</th>
                    <th class="px-4 py-2.5">Status</th>
                    <th class="px-4 py-2.5">Sort</th>
                    <th class="px-4 py-2.5 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm dark:divide-slate-800">
                @forelse ($this->languages as $language)
                    <tr wire:key="lang-{{ $language->id }}" class="transition hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $language->id }}</td>

                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="text-xl leading-none">{{ $language->flag_emoji ?? '🌐' }}</span>
                                <div>
                                    <p class="font-semibold text-slate-800 dark:text-slate-100">{{ $language->name }}</p>
                                    @if ($language->native_name)
                                        <p class="text-xs text-slate-500">{{ $language->native_name }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td class="px-4 py-3 font-mono text-xs text-slate-600 dark:text-slate-300">
                            {{ $language->code }}
                        </td>

                        <td class="px-4 py-3">
                            <span @class([
                                'rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider',
                                'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' => $language->isLtr(),
                                'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300' => $language->isRtl(),
                            ])>
                                {{ strtoupper($language->direction) }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex flex-wrap items-center gap-1.5">
                                @if ($language->is_default)
                                    <span class="rounded-md bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-800 dark:bg-amber-500/20 dark:text-amber-300">
                                        Default
                                    </span>
                                @endif

                                @if ($language->is_active)
                                    <span class="rounded-md bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300">
                                        Active
                                    </span>
                                @else
                                    <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                        Inactive
                                    </span>
                                @endif

                                @if ($language->is_admin_locale)
                                    <span class="rounded-md bg-indigo-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-indigo-800 dark:bg-indigo-500/20 dark:text-indigo-300">
                                        Admin
                                    </span>
                                @endif
                            </div>
                        </td>

                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $language->sort_order }}</td>

                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1.5">
                                @can('languages.toggle')
                                    <button type="button"
                                            wire:click="toggleActive({{ $language->id }})"
                                            wire:confirm="Toggle active state for {{ $language->name }}?"
                                            class="grid h-7 w-7 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                                            title="{{ $language->is_active ? 'Deactivate' : 'Activate' }}">
                                        <i data-lucide="{{ $language->is_active ? 'pause' : 'play' }}" class="h-3.5 w-3.5"></i>
                                    </button>
                                @endcan

                                @can('languages.edit')
                                    @if (! $language->is_default)
                                        <button type="button"
                                                wire:click="makeDefault({{ $language->id }})"
                                                wire:confirm="Promote {{ $language->name }} to the default language?"
                                                class="grid h-7 w-7 place-items-center rounded-lg text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-500/10"
                                                title="Set as default">
                                            <i data-lucide="star" class="h-3.5 w-3.5"></i>
                                        </button>
                                    @endif

                                    <button type="button"
                                            x-on:click="formOpen = true; $wire.editLanguage({{ $language->id }})"
                                            class="grid h-7 w-7 place-items-center rounded-lg text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-500/10"
                                            title="Edit">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                    </button>
                                @endcan

                                @can('languages.delete')
                                    @if (! $language->is_default)
                                        <button type="button"
                                                wire:click="deleteLanguage({{ $language->id }})"
                                                wire:confirm="Delete {{ $language->name }}? Requires no posts/pages/categories reference it."
                                                class="grid h-7 w-7 place-items-center rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10"
                                                title="Delete">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                                        </button>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-sm text-slate-400">
                            No languages yet. Add your first locale.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Create / Edit modal — Alpine-controlled visibility for instant
         open/close. Heavy form body is still mounted only while open. --}}
    <div x-show="formOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4"
         x-on:keydown.escape.window="formOpen = false; $wire.cancelForm()"
         x-on:click="formOpen = false; $wire.cancelForm()">
        <div class="w-full max-w-xl rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900"
             x-on:click.stop>

                <div class="flex items-center justify-between border-b border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                    <div class="flex items-center gap-3">
                        <span class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br from-cyan-500 to-blue-500 text-white">
                            <i data-lucide="globe" class="h-4 w-4"></i>
                        </span>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">
                            {{ $editingId ? 'Edit Language' : 'Add Language' }}
                        </h3>
                    </div>
                    <button type="button" x-on:click="formOpen = false; $wire.cancelForm()"
                            class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <div class="space-y-4 p-5">
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                                Code <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" wire:model="code" placeholder="bn"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-mono outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                            @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                                Name <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" wire:model="name" placeholder="Bangla"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Native name</label>
                            <input type="text" wire:model="nativeName" placeholder="বাংলা"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Flag emoji</label>
                            <input type="text" wire:model="flagEmoji" placeholder="🇧🇩" maxlength="10"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                        </div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Direction</label>
                            <select wire:model="direction"
                                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                                <option value="ltr">Left → Right (LTR)</option>
                                <option value="rtl">Right → Left (RTL)</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Sort order</label>
                            <input type="number" wire:model="sortOrder" min="0" max="1000"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                        </div>
                    </div>

                    <div class="space-y-2 rounded-xl bg-slate-50 p-3 dark:bg-slate-950/40">
                        <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-200">
                            <input type="checkbox" wire:model="isDefault"
                                   class="h-3.5 w-3.5 rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                            Default language (drives URL prefix and fallback)
                        </label>
                        <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-200">
                            <input type="checkbox" wire:model="isActive"
                                   class="h-3.5 w-3.5 rounded border-slate-300 text-emerald-500 focus:ring-emerald-500">
                            Active on the frontend
                        </label>
                        <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-200">
                            <input type="checkbox" wire:model="isAdminLocale"
                                   class="h-3.5 w-3.5 rounded border-slate-300 text-indigo-500 focus:ring-indigo-500">
                            Available as an admin panel locale
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                    <button type="button" x-on:click="formOpen = false; $wire.cancelForm()"
                            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                        Cancel
                    </button>
                    <button type="button" wire:click="save"
                            wire:loading.attr="disabled" wire:target="save"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-4 py-2 text-xs font-bold text-white hover:from-indigo-700 hover:to-indigo-600 disabled:opacity-60">
                        <i data-lucide="check" class="h-3.5 w-3.5" wire:loading.remove wire:target="save"></i>
                        <i data-lucide="loader-2" class="h-3.5 w-3.5 animate-spin" wire:loading wire:target="save"></i>
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Update' : 'Create' }}</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                </div>
            </div>
        </div>
</div>
