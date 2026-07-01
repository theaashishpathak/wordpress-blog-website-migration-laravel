<div class="space-y-5">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">AI · Writer Studio</span>
    </nav>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-violet-500 via-fuchsia-500 to-pink-500 text-white shadow-sm">
                <i data-lucide="sparkles" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">AI Writer Studio</h1>
                <p class="text-xs text-slate-500">Generate a complete draft article in seconds, then save it to your post pipeline.</p>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[420px_1fr]">
        {{-- Prompt panel --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-bold text-slate-800 dark:text-slate-100">
                <i data-lucide="settings-2" class="h-4 w-4 text-violet-500"></i> Prompt
            </h3>

            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">
                        Topic <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" wire:model="topic"
                           placeholder="The state of generative AI in newsrooms, 2026"
                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                    @error('topic') <p class="mt-1 text-xs text-rose-500">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Tone</label>
                        <select wire:model="tone"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                            <option value="professional">Professional</option>
                            <option value="conversational">Conversational</option>
                            <option value="formal">Formal</option>
                            <option value="enthusiastic">Enthusiastic</option>
                            <option value="objective">Objective / News</option>
                            <option value="humorous">Humorous</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Word count</label>
                        <input type="number" wire:model="wordCount" min="100" max="5000" step="100"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-mono dark:border-slate-700 dark:bg-slate-950">
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Audience</label>
                    <input type="text" wire:model="audience"
                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>

                <div>
                    <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Focus keyword</label>
                    <input type="text" wire:model="focusKeyword" placeholder="ai content marketing"
                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Language</label>
                        <select wire:model="languageId"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                            @foreach ($this->languages as $lang)
                                <option value="{{ $lang->id }}">{{ $lang->flag_emoji ?? '🌐' }} {{ $lang->name }} ({{ $lang->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Category (optional)</label>
                        <select wire:model="categoryId"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                            <option value="">— Uncategorised —</option>
                            @foreach ($this->categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->translation()?->name ?? '#'.$cat->id }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <button type="button" wire:click="generate"
                        wire:loading.attr="disabled" wire:target="generate"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-600 via-fuchsia-600 to-pink-600 px-4 py-3 text-sm font-bold text-white shadow-lg hover:from-violet-700 disabled:cursor-not-allowed disabled:opacity-60">
                    <i data-lucide="sparkles" class="h-4 w-4" wire:loading.remove wire:target="generate"></i>
                    <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="generate"></i>
                    <span wire:loading.remove wire:target="generate">Generate Article</span>
                    <span wire:loading wire:target="generate">Generating…</span>
                </button>
            </div>
        </div>

        {{-- Output panel --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                <h3 class="flex items-center gap-2 text-sm font-bold text-slate-800 dark:text-slate-100">
                    <i data-lucide="file-text" class="h-4 w-4 text-indigo-500"></i> Output
                </h3>
                @if ($output !== '')
                    <div class="flex items-center gap-2">
                        <button type="button"
                                x-data
                                x-on:click="navigator.clipboard.writeText($refs.body.innerText); $dispatch('toast', { message: 'Copied to clipboard', type: 'success' });"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                            <i data-lucide="copy" class="h-3.5 w-3.5"></i> Copy
                        </button>
                        @if ($this->canSaveAsDraft)
                            <button type="button" wire:click="saveAsDraft"
                                    wire:loading.attr="disabled" wire:target="saveAsDraft"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-gradient-to-r from-indigo-600 to-indigo-500 px-4 py-1.5 text-xs font-bold text-white hover:from-indigo-700">
                                <i data-lucide="save" class="h-3.5 w-3.5" wire:loading.remove wire:target="saveAsDraft"></i>
                                <i data-lucide="loader-2" class="h-3.5 w-3.5 animate-spin" wire:loading wire:target="saveAsDraft"></i>
                                Save as draft
                            </button>
                        @endif
                    </div>
                @endif
            </header>

            <div class="p-5">
                @if ($output === '' && ! $isGenerating)
                    <div class="rounded-2xl border-2 border-dashed border-slate-200 p-12 text-center dark:border-slate-700">
                        <span class="mx-auto mb-3 grid h-12 w-12 place-items-center rounded-2xl bg-violet-50 text-violet-500 dark:bg-violet-500/10">
                            <i data-lucide="wand-sparkles" class="h-6 w-6"></i>
                        </span>
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Your draft will appear here.</p>
                        <p class="mt-1 text-xs text-slate-500">Fill the prompt on the left and hit Generate Article.</p>
                    </div>
                @else
                    @if ($generatedTitle !== '')
                        <div class="mb-3">
                            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-slate-500">Headline</label>
                            <input type="text" wire:model="generatedTitle"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-base font-semibold dark:border-slate-700 dark:bg-slate-950">
                        </div>
                    @endif

                    <div x-ref="body"
                         class="prose prose-sm max-w-none overflow-y-auto rounded-xl border border-slate-200 bg-slate-50/40 p-4 text-sm leading-relaxed dark:prose-invert dark:border-slate-700 dark:bg-slate-950">
                        {!! $output !!}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
