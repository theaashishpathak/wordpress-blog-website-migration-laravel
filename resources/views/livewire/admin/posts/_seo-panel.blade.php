{{--
    SEO Panel — included from Create.php and Edit.php.

    Expects on $this:
      - seoMetaTitle, seoMetaDescription, seoFocusKeyword,
        seoCanonicalUrl, seoMetaKeywords, seoRobots, seoSchemaType,
        seoOgTitle, seoOgDescription, seoTwitterTitle,
        seoTwitterDescription   (all public properties)
      - seoScore        (computed App\Services\Seo\DataTransferObjects\SeoScoreResult)
      - schemaTypeOptions (computed array)
--}}

@php
    $score = $this->seoScore;
    $gaugeColor = match ($score->level()) {
        'good' => 'text-emerald-500',
        'warning' => 'text-amber-500',
        default => 'text-rose-500',
    };
    $gaugeRing = match ($score->level()) {
        'good' => 'stroke-emerald-500',
        'warning' => 'stroke-amber-500',
        default => 'stroke-rose-500',
    };
    $gaugeBadge = match ($score->level()) {
        'good' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
        'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
        default => 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300',
    };
    $gaugeLabel = match ($score->level()) {
        'good' => 'Good',
        'warning' => 'Needs Work',
        default => 'Poor',
    };
    // Circumference of an r=42 circle for the stroke-dasharray gauge.
    $circumference = 2 * 3.14159 * 42;
    $dashOffset = $circumference - ($circumference * $score->overall / 100);
@endphp

<div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
     x-data="{ open: true, openAdvanced: false }">

    {{-- Header — collapsible toggle --}}
    <button type="button"
            x-on:click="open = !open"
            class="flex w-full items-center justify-between border-b border-slate-100 bg-gradient-to-r from-emerald-50/40 to-teal-50/40 px-5 py-3 text-left transition hover:bg-emerald-50/70 dark:border-slate-800 dark:from-emerald-500/5 dark:to-teal-500/5 dark:hover:bg-emerald-500/10">
        <div class="flex items-center gap-3">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white shadow-sm">
                <i data-lucide="search" class="h-4 w-4"></i>
            </span>
            <div>
                <h3 class="flex items-center gap-2 text-sm font-bold text-slate-800 dark:text-slate-100">
                    SEO Panel
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $gaugeBadge }}">
                        {{ $gaugeLabel }}
                    </span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Live score updates as you type
                </p>
            </div>
        </div>
        <i data-lucide="chevron-down" class="h-4 w-4 text-slate-400 transition" x-bind:class="open ? 'rotate-180' : ''"></i>
    </button>

    <div x-show="open" x-collapse class="p-5">
        <div class="grid gap-6 lg:grid-cols-[180px_1fr]">

            {{-- Score gauge --}}
            <div class="flex flex-col items-center gap-3">
                <div class="relative h-32 w-32">
                    <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90">
                        <circle cx="50" cy="50" r="42" stroke-width="8" fill="none"
                                class="stroke-slate-200 dark:stroke-slate-700"></circle>
                        <circle cx="50" cy="50" r="42" stroke-width="8" fill="none"
                                stroke-linecap="round"
                                stroke-dasharray="{{ number_format($circumference, 4, '.', '') }}"
                                stroke-dashoffset="{{ number_format($dashOffset, 4, '.', '') }}"
                                class="{{ $gaugeRing }} transition-all duration-500"></circle>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-3xl font-black {{ $gaugeColor }}">{{ $score->overall }}</span>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">/ 100</span>
                    </div>
                </div>
                <p class="text-center text-xs text-slate-500 dark:text-slate-400">
                    Composite SEO health<br>across {{ count($score->checks) }} checks
                </p>
            </div>

            {{-- Checklist --}}
            <div class="space-y-1.5">
                @foreach ($score->checks as $check)
                    @php
                        $dot = match ($check->level) {
                            'good' => 'bg-emerald-500',
                            'warning' => 'bg-amber-500',
                            default => 'bg-rose-500',
                        };
                        $iconName = match ($check->level) {
                            'good' => 'check',
                            'warning' => 'alert-triangle',
                            default => 'x',
                        };
                        $textTone = match ($check->level) {
                            'good' => 'text-slate-600 dark:text-slate-400',
                            'warning' => 'text-amber-700 dark:text-amber-300',
                            default => 'text-rose-700 dark:text-rose-300',
                        };
                    @endphp
                    <div class="flex items-start gap-2.5 rounded-lg px-2 py-1.5 transition hover:bg-slate-50 dark:hover:bg-slate-800/40">
                        <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full {{ $dot }} text-white">
                            <i data-lucide="{{ $iconName }}" class="h-3 w-3"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                {{ $check->label }}
                            </p>
                            <p class="text-xs {{ $textTone }}">{{ $check->message }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Editable fields --}}
        <div class="mt-6 space-y-5 border-t border-slate-100 pt-5 dark:border-slate-800">
            {{-- Focus keyword --}}
            <div>
                <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                    <i data-lucide="target" class="h-3.5 w-3.5 text-emerald-500"></i>
                    Focus Keyword
                </label>
                <input type="text"
                       wire:model.live.debounce.400ms="seoFocusKeyword"
                       placeholder="e.g. ai content marketing"
                       class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-emerald-500/20">
                @error('seoFocusKeyword') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            {{-- Meta title --}}
            <div>
                <div class="mb-1.5 flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        <i data-lucide="type" class="h-3.5 w-3.5 text-emerald-500"></i>
                        Meta Title
                    </label>
                    @php($mtLen = mb_strlen($seoMetaTitle))
                    <span class="font-mono text-xs {{ $mtLen === 0 || $mtLen > 70 ? 'text-rose-500' : ($mtLen >= 50 && $mtLen <= 60 ? 'text-emerald-600' : 'text-amber-500') }}">
                        {{ $mtLen }} / 60
                    </span>
                </div>
                <input type="text"
                       wire:model.live.debounce.400ms="seoMetaTitle"
                       placeholder="Falls back to post title if blank"
                       class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-emerald-500/20">
                @error('seoMetaTitle') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            {{-- Meta description --}}
            <div>
                <div class="mb-1.5 flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        <i data-lucide="align-left" class="h-3.5 w-3.5 text-emerald-500"></i>
                        Meta Description
                    </label>
                    @php($mdLen = mb_strlen($seoMetaDescription))
                    <span class="font-mono text-xs {{ $mdLen === 0 || $mdLen > 180 ? 'text-rose-500' : ($mdLen >= 120 && $mdLen <= 160 ? 'text-emerald-600' : 'text-amber-500') }}">
                        {{ $mdLen }} / 160
                    </span>
                </div>
                <textarea wire:model.live.debounce.400ms="seoMetaDescription"
                          rows="3"
                          placeholder="Compelling summary shown in Google search results"
                          class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-emerald-500/20"></textarea>
                @error('seoMetaDescription') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            {{-- Canonical + robots row --}}
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        <i data-lucide="link-2" class="h-3.5 w-3.5 text-slate-400"></i>
                        Canonical URL
                    </label>
                    <input type="url"
                           wire:model="seoCanonicalUrl"
                           placeholder="https://example.com/canonical-path"
                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-mono outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-950">
                    @error('seoCanonicalUrl') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        <i data-lucide="shield" class="h-3.5 w-3.5 text-slate-400"></i>
                        Robots
                    </label>
                    <select wire:model="seoRobots"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-950">
                        <option value="">Default (index, follow)</option>
                        <option value="index,follow">index, follow</option>
                        <option value="noindex,follow">noindex, follow</option>
                        <option value="index,nofollow">index, nofollow</option>
                        <option value="noindex,nofollow">noindex, nofollow</option>
                    </select>
                </div>
            </div>

            {{-- Schema type + meta keywords row --}}
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        <i data-lucide="code-2" class="h-3.5 w-3.5 text-slate-400"></i>
                        Schema.org Type
                    </label>
                    <select wire:model="seoSchemaType"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-950">
                        <option value="">— auto (Article) —</option>
                        @foreach ($this->schemaTypeOptions as $schemaType)
                            <option value="{{ $schemaType }}">{{ $schemaType }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        <i data-lucide="hash" class="h-3.5 w-3.5 text-slate-400"></i>
                        Meta Keywords
                    </label>
                    <input type="text"
                           wire:model="seoMetaKeywords"
                           placeholder="comma, separated, keywords"
                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100 dark:border-slate-700 dark:bg-slate-950">
                </div>
            </div>

            {{-- Advanced: Social cards (collapsed by default) --}}
            <div class="rounded-xl border border-slate-200 dark:border-slate-800">
                <button type="button"
                        x-on:click="openAdvanced = !openAdvanced"
                        class="flex w-full items-center justify-between px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wider text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">
                    <span class="flex items-center gap-2">
                        <i data-lucide="share-2" class="h-3.5 w-3.5"></i>
                        Social Card Overrides
                    </span>
                    <i data-lucide="chevron-down" class="h-3.5 w-3.5 transition" x-bind:class="openAdvanced ? 'rotate-180' : ''"></i>
                </button>

                <div x-show="openAdvanced" x-collapse class="space-y-4 border-t border-slate-200 px-4 py-4 dark:border-slate-800">
                    <p class="text-xs text-slate-500">
                        Leave blank to inherit meta title / description for OpenGraph + Twitter cards.
                    </p>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">OG Title</label>
                            <input type="text" wire:model="seoOgTitle"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Twitter Title</label>
                            <input type="text" wire:model="seoTwitterTitle"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                        </div>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">OG Description</label>
                            <textarea wire:model="seoOgDescription" rows="2"
                                      class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">Twitter Description</label>
                            <textarea wire:model="seoTwitterDescription" rows="2"
                                      class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
