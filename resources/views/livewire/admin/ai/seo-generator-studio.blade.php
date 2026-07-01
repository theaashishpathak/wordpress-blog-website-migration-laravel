<div class="space-y-5">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">AI · SEO Generator</span>
    </nav>

    <div class="flex items-center gap-3">
        <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white shadow-sm">
            <i data-lucide="search" class="h-5 w-5"></i>
        </span>
        <div>
            <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">SEO Generator</h1>
            <p class="text-xs text-slate-500">Turn a headline + excerpt into a complete on-page SEO bundle.</p>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[420px_1fr]">
        {{-- Input panel --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-bold text-slate-800 dark:text-slate-100">
                <i data-lucide="pen-line" class="h-4 w-4 text-emerald-500"></i> Source content
            </h3>

            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">
                        Title <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" wire:model="title"
                           placeholder="How AI is rewriting modern newsrooms"
                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                    @error('title') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Excerpt / abstract</label>
                    <textarea wire:model="excerpt" rows="5"
                              placeholder="A short paragraph or the opening of the article…"
                              class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Focus keyword</label>
                    <input type="text" wire:model="focusKeyword"
                           placeholder="ai content marketing"
                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Language</label>
                    <select wire:model="languageId"
                            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                        @foreach ($this->languages as $lang)
                            <option value="{{ $lang->id }}">{{ $lang->flag_emoji ?? '🌐' }} {{ $lang->name }} ({{ $lang->code }})</option>
                        @endforeach
                    </select>
                </div>

                <button type="button" wire:click="generate"
                        wire:loading.attr="disabled" wire:target="generate"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-3 text-sm font-bold text-white shadow-lg hover:from-emerald-700 disabled:opacity-60">
                    <i data-lucide="sparkles" class="h-4 w-4" wire:loading.remove wire:target="generate"></i>
                    <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="generate"></i>
                    <span wire:loading.remove wire:target="generate">Generate SEO meta</span>
                    <span wire:loading wire:target="generate">Generating…</span>
                </button>
            </div>
        </div>

        {{-- Output panel --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="border-b border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                <h3 class="flex items-center gap-2 text-sm font-bold text-slate-800 dark:text-slate-100">
                    <i data-lucide="badge-check" class="h-4 w-4 text-emerald-500"></i> Generated meta
                </h3>
            </header>

            <div class="p-5">
                @if ($result === null)
                    <div class="rounded-2xl border-2 border-dashed border-slate-200 p-12 text-center dark:border-slate-700">
                        <span class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-emerald-50 text-emerald-500 dark:bg-emerald-500/10">
                            <i data-lucide="target" class="h-6 w-6"></i>
                        </span>
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Your SEO output will appear here.</p>
                    </div>
                @else
                    @php($r = $result)
                    <div class="space-y-4">
                        {{-- Search snippet preview --}}
                        <div>
                            <p class="mb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">Google snippet preview</p>
                            <div class="rounded-xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-950">
                                <p class="truncate text-base font-medium text-blue-700 dark:text-blue-400">{{ $r->metaTitle ?: 'Untitled' }}</p>
                                <p class="line-clamp-2 mt-0.5 text-xs text-slate-600 dark:text-slate-400">{{ $r->metaDescription }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500">Meta title <span class="ml-1 font-mono text-slate-400">({{ mb_strlen((string) ($r->metaTitle ?? '')) }} / 60)</span></label>
                            <input type="text" value="{{ $r->metaTitle }}" readonly
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold dark:border-slate-700 dark:bg-slate-950">
                        </div>

                        <div>
                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500">Meta description <span class="ml-1 font-mono text-slate-400">({{ mb_strlen((string) ($r->metaDescription ?? '')) }} / 160)</span></label>
                            <textarea readonly rows="3"
                                      class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">{{ $r->metaDescription }}</textarea>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500">Focus keyphrase</label>
                                <input type="text" value="{{ $r->focusKeyphrase }}" readonly
                                       class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-mono dark:border-slate-700 dark:bg-slate-950">
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500">Slug</label>
                                <input type="text" value="{{ $r->slug }}" readonly
                                       class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-mono dark:border-slate-700 dark:bg-slate-950">
                            </div>
                        </div>

                        {{-- Social cards (OG) --}}
                        @if ($r->ogTitle !== '' || $r->ogDescription !== '')
                            <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3 dark:border-slate-700 dark:bg-slate-950/40">
                                <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-500">Open Graph (Facebook · LinkedIn · X)</p>
                                <div class="space-y-2">
                                    <div>
                                        <label class="mb-0.5 block text-[10px] text-slate-500">OG title <span class="font-mono text-slate-400">({{ mb_strlen($r->ogTitle) }} / 88)</span></label>
                                        <input type="text" value="{{ $r->ogTitle }}" readonly
                                               class="w-full rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-900">
                                    </div>
                                    <div>
                                        <label class="mb-0.5 block text-[10px] text-slate-500">OG description <span class="font-mono text-slate-400">({{ mb_strlen($r->ogDescription) }} / 200)</span></label>
                                        <textarea readonly rows="2"
                                                  class="w-full rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-900">{{ $r->ogDescription }}</textarea>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div>
                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500">Tags</label>
                            <div class="flex flex-wrap gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-950">
                                @forelse ($r->tags as $tag)
                                    <span class="rounded-md bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300">{{ $tag }}</span>
                                @empty
                                    <span class="text-xs text-slate-400">— none —</span>
                                @endforelse
                            </div>
                        </div>

                        @if (! empty($r->secondaryKeywords))
                            <div>
                                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500">Secondary keywords (semantic SEO)</label>
                                <div class="flex flex-wrap gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-950">
                                    @foreach ($r->secondaryKeywords as $kw)
                                        <span class="rounded-md bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-800 dark:bg-sky-500/20 dark:text-sky-300">{{ $kw }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($r->imageAlt !== '')
                            <div>
                                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500">Suggested image alt</label>
                                <input type="text" value="{{ $r->imageAlt }}" readonly
                                       class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                            </div>
                        @endif

                        <p class="text-xs text-slate-500">
                            <i data-lucide="info" class="inline h-3 w-3"></i>
                            To apply these to a specific post, open the post's editor and use the AI Assistant drawer — it pre-fills these fields with one click.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
