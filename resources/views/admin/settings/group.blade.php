@php($title = $currentGroup['label'] ?? 'Settings Group')

<div class="space-y-6">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
        <a href="{{ route('admin.settings.index') }}" wire:navigate
            class="hover:text-indigo-600 dark:hover:text-indigo-300">General Settings</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span
            class="font-semibold text-slate-700 dark:text-slate-200">{{ $currentGroup['label'] ?? 'Settings Group' }}</span>
    </nav>

    <div class="grid gap-6 xl:grid-cols-[300px_minmax(0,1fr)]">
        @include('admin.settings._sidebar', ['groups' => $groups, 'activeGroup' => $group])

        <div class="space-y-5">
            {{-- Group header card --}}
            <div
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-4">
                        <span
                            class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl {{ $colorIconLg[$currentGroup['color'] ?? 'slate'] ?? $colorIconLg['slate'] }}">
                            <i data-lucide="{{ $currentGroup['icon'] ?? 'settings-2' }}" class="h-6 w-6"></i>
                        </span>
                        <div>
                            <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100">
                                {{ $currentGroup['label'] ?? 'Settings Group' }}</h1>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                {{ $currentGroup['description'] ?? '' }}</p>
                            <p
                                class="mt-2 inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                <i data-lucide="hash" class="h-3 w-3"></i>
                                {{ count($fields) }} field{{ count($fields) === 1 ? '' : 's' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admin.settings.index') }}" wire:navigate
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                            <i data-lucide="arrow-left" class="h-4 w-4"></i>
                            All Groups
                        </a>
                        <button form="settings-group-form" type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                            <i data-lucide="save" class="h-4 w-4"></i>
                            Save Changes
                        </button>
                    </div>
                </div>
            </div>

            {{-- Form --}}
            <form id="settings-group-form" wire:submit.prevent="save" method="POST"
                action="{{ route('admin.settings.save') }}" enctype="multipart/form-data"
                class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                @csrf
                <input type="hidden" name="group" value="{{ $group }}">

                <div class="grid gap-5 md:grid-cols-2">
                    @foreach ($fields as $field)
                    @php($fieldSpan = in_array($field['input'], ['textarea', 'image'], true) ? 'md:col-span-2' : '')
                    <div class="{{ $fieldSpan }}" wire:key="settings-field-{{ $field['state_key'] }}">
                        <label
                            class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">{{ $field['label'] }}</label>

                        @if ($field['input'] === 'toggle')
                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 px-4 py-3 transition hover:border-indigo-300 dark:border-slate-700 dark:hover:border-indigo-500/40">
                                <input type="hidden" name="values[{{ $field['state_key'] }}]" value="0">
                                <input type="checkbox" name="values[{{ $field['state_key'] }}]" value="1"
                                    wire:model.live="values.{{ $field['state_key'] }}"
                                    class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="text-sm text-slate-600 dark:text-slate-300">Enable</span>
                            </label>
                        @elseif ($field['input'] === 'select')
                            <select name="values[{{ $field['state_key'] }}]"
                                wire:model.live="values.{{ $field['state_key'] }}"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                                <option value="">Select an option</option>
                                @foreach ((array) ($field['options'] ?? []) as $optionValue => $optionLabel)
                                    <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                @endforeach
                            </select>
                        @elseif ($field['input'] === 'textarea')
                            <textarea name="values[{{ $field['state_key'] }}]" rows="4"
                                wire:model.live="values.{{ $field['state_key'] }}"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950"></textarea>
                        @elseif ($field['input'] === 'number')
                            <input type="number" step="any" name="values[{{ $field['state_key'] }}]"
                                wire:model.live="values.{{ $field['state_key'] }}"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                        @elseif ($field['input'] === 'color')
                        @php($colorValue = $values[$field['state_key']] ?? '')
                        @php($colorPreview = $colorValue && preg_match('/^#[0-9a-fA-F]{6}$/', $colorValue) ? $colorValue : '#4f46e5')
                            <div class="flex items-center gap-2">
                                <input type="color" wire:model.live.debounce.250ms="values.{{ $field['state_key'] }}"
                                    value="{{ $colorPreview }}"
                                    class="h-[46px] w-16 shrink-0 cursor-pointer rounded-xl border border-slate-200 p-1 dark:border-slate-700">
                                <input type="text" name="values[{{ $field['state_key'] }}]"
                                    wire:model.live.debounce.300ms="values.{{ $field['state_key'] }}" placeholder="#4f46e5"
                                    pattern="^#[0-9a-fA-F]{6}$"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-mono uppercase outline-none transition focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                                <span
                                    class="grid h-[46px] w-[46px] shrink-0 place-items-center rounded-xl border border-slate-200 dark:border-slate-700"
                                    style="background-color: {{ $colorPreview }}">
                                    <i data-lucide="check" class="h-4 w-4 text-white drop-shadow"></i>
                                </span>
                            </div>
                        @elseif ($field['input'] === 'image')
                            <div class="space-y-3">
                                <input type="file" name="uploads[{{ $field['state_key'] }}]"
                                    wire:model="uploads.{{ $field['state_key'] }}">
                                <input type="hidden" name="values[{{ $field['state_key'] }}]"
                                    value="{{ $values[$field['state_key']] ?? '' }}">

                                @if (!empty($values[$field['state_key']]))
                                    <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-800">
                                        <p
                                            class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            Current File</p>
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($values[$field['state_key']]) }}"
                                            alt="{{ $field['label'] }}" class="h-20 w-20 rounded-xl object-cover">
                                    </div>
                                @endif
                            </div>
                        @elseif ($field['input'] === 'password')
                            <div class="space-y-1">
                                <div class="relative">
                                    <input type="password" name="values[{{ $field['state_key'] }}]"
                                        wire:model.live="values.{{ $field['state_key'] }}" autocomplete="new-password"
                                        spellcheck="false" data-password-field
                                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 pr-12 text-sm font-mono outline-none transition focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                                    <button type="button"
                                        onclick="(function(btn){var i=btn.previousElementSibling;i.type=i.type==='password'?'text':'password';})(this)"
                                        class="absolute inset-y-0 right-2 my-1 inline-flex items-center justify-center rounded-lg px-2 text-slate-500 transition hover:text-indigo-600 dark:hover:text-indigo-300"
                                        aria-label="Toggle visibility">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </button>
                                </div>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Stored encrypted at rest. Leave
                                    blank to keep current value.</p>
                            </div>
                        @else
                        <input type="text" name="values[{{ $field['state_key'] }}]"
                            wire:model.live="values.{{ $field['state_key'] }}"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none transition focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                        @endif

                        @error('values.' . $field['state_key'])<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                        @error('uploads.' . $field['state_key'])<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                    @endforeach
                </div>

                <div
                    class="flex flex-col items-stretch justify-end gap-3 border-t border-slate-200 pt-5 dark:border-slate-800 sm:flex-row sm:items-center">
                    <a href="{{ route('admin.settings.index') }}" wire:navigate
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                        <i data-lucide="x" class="h-4 w-4"></i>
                        Cancel
                    </a>
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>