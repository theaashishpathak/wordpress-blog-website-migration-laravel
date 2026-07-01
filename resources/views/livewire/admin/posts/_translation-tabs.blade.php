{{--
    Translation tabs — rendered above the form column in Edit.blade.php.

    Expects on $this:
      - translationTabs        (computed list)
      - languagesAvailableToAdd (computed Language collection)
      - activeLanguageId       (int|null)
      - defaultLanguageId      (int|null)
      - switchLanguage(int)
      - addTranslation(int)
      - removeTranslation(int)
      - translateActiveFromDefault()
--}}
<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 bg-gradient-to-r from-sky-50/60 to-indigo-50/60 px-5 py-3 dark:border-slate-800 dark:from-sky-500/10 dark:to-indigo-500/10">
        <div class="flex items-center gap-3">
            <span class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br from-sky-500 to-indigo-500 text-white">
                <i data-lucide="languages" class="h-4 w-4"></i>
            </span>
            <div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Translations</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Each tab persists as a separate post_translations row.
                </p>
            </div>
        </div>

        @if ($activeLanguageId !== null && $activeLanguageId !== $defaultLanguageId)
            @can('ai.use_writer')
                <button type="button"
                        wire:click="translateActiveFromDefault"
                        wire:loading.attr="disabled"
                        wire:target="translateActiveFromDefault"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-violet-700 disabled:opacity-60">
                    <i data-lucide="sparkles" class="h-3.5 w-3.5" wire:loading.remove wire:target="translateActiveFromDefault"></i>
                    <i data-lucide="loader-2" class="h-3.5 w-3.5 animate-spin" wire:loading wire:target="translateActiveFromDefault"></i>
                    <span wire:loading.remove wire:target="translateActiveFromDefault">AI Translate from Default</span>
                    <span wire:loading wire:target="translateActiveFromDefault">Translating…</span>
                </button>
            @endcan
        @endif
    </div>

    <div class="flex flex-wrap items-center gap-2 px-5 py-3">
        @foreach ($this->translationTabs as $tab)
            @php
                $isActive = $tab['active'];
                $deleted = $tab['deleted'];
                $percent = (int) $tab['percent'];
                $pctColor = $percent >= 80
                    ? 'bg-emerald-500'
                    : ($percent >= 40 ? 'bg-amber-500' : 'bg-rose-500');
                $statusBadge = match ($tab['status']) {
                    \App\Models\PostTranslation::TRANSLATION_STATUS_AI_GENERATED => ['text' => 'AI', 'class' => 'bg-violet-100 text-violet-700'],
                    \App\Models\PostTranslation::TRANSLATION_STATUS_AI_REVIEWED => ['text' => 'AI ✓', 'class' => 'bg-violet-200 text-violet-800'],
                    \App\Models\PostTranslation::TRANSLATION_STATUS_HUMAN_REVIEWED => ['text' => '👤', 'class' => 'bg-emerald-100 text-emerald-700'],
                    \App\Models\PostTranslation::TRANSLATION_STATUS_PUBLISHED => ['text' => 'Live', 'class' => 'bg-emerald-200 text-emerald-800'],
                    default => null,
                };
            @endphp

            <div wire:key="lang-tab-{{ $tab['id'] }}" class="relative">
                <button type="button"
                        wire:click="switchLanguage({{ $tab['id'] }})"
                        @class([
                            'group flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold transition',
                            'border-indigo-500 bg-indigo-50 text-indigo-700 shadow-sm dark:bg-indigo-500/20 dark:text-indigo-300' => $isActive,
                            'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' => ! $isActive && ! $deleted,
                            'border-rose-200 bg-rose-50 text-rose-700 line-through opacity-60 dark:bg-rose-500/10' => $deleted,
                        ])>
                    @if (! empty($tab['flag']))
                        <span class="text-sm">{{ $tab['flag'] }}</span>
                    @endif
                    <span>{{ $tab['name'] }}</span>
                    <span class="rounded-md bg-white/70 px-1.5 py-0.5 text-[10px] font-mono text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        {{ $tab['code'] }}
                    </span>

                    @if ($tab['is_default'])
                        <span class="rounded-md bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-700">
                            Default
                        </span>
                    @endif

                    @if ($statusBadge)
                        <span class="rounded-md px-1.5 py-0.5 text-[10px] font-bold {{ $statusBadge['class'] }}">
                            {{ $statusBadge['text'] }}
                        </span>
                    @endif

                    <span class="ml-1 inline-flex items-center gap-1 text-[10px] font-mono text-slate-500">
                        <span class="block h-1.5 w-1.5 rounded-full {{ $pctColor }}"></span>
                        {{ $percent }}%
                    </span>
                </button>

                @if (! $tab['is_default'] && ! $deleted)
                    <button type="button"
                            wire:click="removeTranslation({{ $tab['id'] }})"
                            wire:confirm="Remove this translation? It will be deleted on save."
                            class="absolute -right-1.5 -top-1.5 hidden h-4 w-4 place-items-center rounded-full bg-rose-500 text-white shadow group-hover:grid hover:bg-rose-600"
                            title="Remove translation">
                        <i data-lucide="x" class="h-2.5 w-2.5"></i>
                    </button>
                @endif
            </div>
        @endforeach

        @if ($this->languagesAvailableToAdd->isNotEmpty())
            <div x-data="{ open: false }" class="relative">
                <button type="button"
                        x-on:click="open = !open"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-dashed border-slate-300 px-3 py-2 text-xs font-semibold text-slate-500 transition hover:border-indigo-500 hover:text-indigo-600 dark:border-slate-700 dark:hover:border-indigo-500">
                    <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                    Add language
                </button>

                <div x-show="open"
                     x-on:click.outside="open = false"
                     x-transition
                     class="absolute right-0 z-30 mt-2 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900">
                    @foreach ($this->languagesAvailableToAdd as $lang)
                        <button type="button"
                                wire:click="addTranslation({{ $lang->id }})"
                                x-on:click="open = false"
                                class="flex w-full items-center gap-2 px-3 py-2 text-left text-xs text-slate-600 hover:bg-indigo-50 hover:text-indigo-700 dark:text-slate-300 dark:hover:bg-slate-800">
                            <span class="text-sm">{{ $lang->flag_emoji ?? '🌐' }}</span>
                            <span class="flex-1">{{ $lang->name }}</span>
                            <span class="font-mono text-[10px] text-slate-400">{{ $lang->code }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
