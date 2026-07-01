<div
    {{-- Keep .live here: this drawer is opened by an event dispatched
         from the parent Post Create/Edit page. The server flips
         $open = true; .live syncs that back to Alpine so the drawer
         actually appears. Other admin modals don't need .live because
         they open via a direct user click on a button inside their
         own scope, where Alpine can flip state synchronously. --}}
    x-data="{ open: @entangle('open').live }"
    x-cloak
>
    {{-- Backdrop — Alpine flips `open` first for instant visual close,
         then $wire.close() resets server-side form state in background. --}}
    <div
        x-show="open"
        x-transition.opacity.duration.200ms
        @click="open = false; $wire && typeof $wire.close === 'function' && $wire.close()"
        class="fixed inset-0 z-40 bg-slate-900/50"
    ></div>

    {{-- Slide-over panel --}}
    <aside
        x-show="open"
        x-transition:enter="transition transform duration-300"
        x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition transform duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 z-50 flex w-full max-w-xl flex-col border-l border-slate-200 bg-white shadow-2xl dark:border-slate-800 dark:bg-slate-900"
        @keydown.escape.window="open && (open = false, $wire && typeof $wire.close === 'function' && $wire.close())"
    >
        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
            <div class="flex items-center gap-3">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-violet-500 to-indigo-500 text-white shadow-sm">
                    <i data-lucide="sparkles" class="h-4 w-4"></i>
                </span>
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-slate-100">AI Assistant</h2>
                    <p class="text-xs text-slate-500">Generate, optimise, rewrite — powered by your configured provider.</p>
                </div>
            </div>
            <button type="button"
                    @click="open = false; $wire.close()"
                    class="rounded-xl p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
                    aria-label="Close">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        {{-- Mode tabs --}}
        <div class="flex gap-1 border-b border-slate-200 px-3 py-2 text-xs font-semibold dark:border-slate-800">
            <button type="button" wire:click="$set('mode', 'article')"
                    class="rounded-lg px-3 py-1.5 transition {{ $mode === 'article' ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800' }}">
                <i data-lucide="file-text" class="mr-1 inline h-3.5 w-3.5"></i>
                Article
            </button>
            <button type="button" wire:click="$set('mode', 'seo')"
                    class="rounded-lg px-3 py-1.5 transition {{ $mode === 'seo' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800' }}">
                <i data-lucide="search" class="mr-1 inline h-3.5 w-3.5"></i>
                SEO Meta
            </button>
            <button type="button" wire:click="$set('mode', 'rewrite')"
                    class="rounded-lg px-3 py-1.5 transition {{ $mode === 'rewrite' ? 'bg-orange-100 text-orange-700 dark:bg-orange-500/15 dark:text-orange-300' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800' }}">
                <i data-lucide="refresh-cw" class="mr-1 inline h-3.5 w-3.5"></i>
                Rewrite
            </button>
        </div>

        {{-- Body (scrollable) --}}
        <div class="flex-1 overflow-y-auto p-5">
            {{-- ============================================
                 ARTICLE WRITER
                 ============================================ --}}
            @if ($mode === 'article')
                <div class="space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Topic <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="topic" placeholder="e.g., The future of AI tools in journalism"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Tone</label>
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
                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Word Count</label>
                            <input type="number" wire:model="wordCount" min="200" max="3000" step="100"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Audience</label>
                        <input type="text" wire:model="audience" placeholder="e.g., journalists, developers, casual readers"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Focus Keyword</label>
                        <input type="text" wire:model="focusKeyword" placeholder="primary SEO keyword"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                    </div>

                    <button type="button" wire:click="generateArticle" wire:loading.attr="disabled"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-violet-500 to-indigo-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:from-violet-600 hover:to-indigo-600 disabled:cursor-not-allowed disabled:opacity-60">
                        <i data-lucide="sparkles" class="h-4 w-4" wire:loading.remove wire:target="generateArticle"></i>
                        <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="generateArticle"></i>
                        <span wire:loading.remove wire:target="generateArticle">Generate Article</span>
                        <span wire:loading wire:target="generateArticle">Generating…</span>
                    </button>
                </div>
            @endif

            {{-- ============================================
                 SEO META
                 ============================================ --}}
            @if ($mode === 'seo')
                <div class="space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Article Title <span class="text-rose-500">*</span></label>
                        <input type="text" wire:model="seoTitle"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Excerpt / Summary</label>
                        <textarea wire:model="seoExcerpt" rows="3"
                                  class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Focus Keyword</label>
                        <input type="text" wire:model="seoFocusKeyword"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                    </div>

                    <button type="button" wire:click="generateSeoMeta" wire:loading.attr="disabled"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60">
                        <i data-lucide="search" class="h-4 w-4" wire:loading.remove wire:target="generateSeoMeta"></i>
                        <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="generateSeoMeta"></i>
                        <span wire:loading.remove wire:target="generateSeoMeta">Generate SEO Meta</span>
                        <span wire:loading wire:target="generateSeoMeta">Generating…</span>
                    </button>
                </div>
            @endif

            {{-- ============================================
                 REWRITE PARAGRAPH
                 ============================================ --}}
            @if ($mode === 'rewrite')
                <div class="space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Paragraph to Rewrite <span class="text-rose-500">*</span></label>
                        <textarea wire:model="paragraph" rows="6" placeholder="Paste the paragraph you want rewritten…"
                                  class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Target Tone</label>
                        <select wire:model="rewriteTone"
                                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                            <option value="professional">Professional</option>
                            <option value="conversational">Conversational</option>
                            <option value="simpler">Simpler</option>
                            <option value="more concise">More concise</option>
                            <option value="more detailed">More detailed</option>
                        </select>
                    </div>

                    <button type="button" wire:click="rewriteParagraph" wire:loading.attr="disabled"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-60">
                        <i data-lucide="refresh-cw" class="h-4 w-4" wire:loading.remove wire:target="rewriteParagraph"></i>
                        <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="rewriteParagraph"></i>
                        <span wire:loading.remove wire:target="rewriteParagraph">Rewrite</span>
                        <span wire:loading wire:target="rewriteParagraph">Rewriting…</span>
                    </button>
                </div>
            @endif

            {{-- ============================================
                 OUTPUT PREVIEW
                 ============================================ --}}
            @if ($mode === 'seo' && $seoOutput !== null)
                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950">
                    <h3 class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-500">Generated Metadata</h3>
                    <dl class="space-y-2 text-xs">
                        <div>
                            <dt class="font-semibold text-slate-700 dark:text-slate-200">Meta Title</dt>
                            <dd class="mt-0.5 rounded-lg bg-white px-2 py-1 font-mono text-slate-800 dark:bg-slate-900 dark:text-slate-200">{{ $seoOutput['meta_title'] ?? '' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-700 dark:text-slate-200">Meta Description</dt>
                            <dd class="mt-0.5 rounded-lg bg-white px-2 py-1 text-slate-800 dark:bg-slate-900 dark:text-slate-200">{{ $seoOutput['meta_description'] ?? '' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-700 dark:text-slate-200">Slug</dt>
                            <dd class="mt-0.5 rounded-lg bg-white px-2 py-1 font-mono text-slate-800 dark:bg-slate-900 dark:text-slate-200">{{ $seoOutput['slug'] ?? '' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-700 dark:text-slate-200">Tags</dt>
                            <dd class="mt-0.5 flex flex-wrap gap-1">
                                @foreach (($seoOutput['tags'] ?? []) as $tag)
                                    <span class="rounded-full bg-indigo-100 px-2 py-0.5 font-medium text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">{{ $tag }}</span>
                                @endforeach
                            </dd>
                        </div>
                    </dl>
                </div>
            @endif

            @if ($mode !== 'seo' && $output !== '')
                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950">
                    <h3 class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Generated Output</h3>
                    <div class="max-h-80 overflow-y-auto rounded-lg bg-white p-3 text-sm leading-relaxed text-slate-800 dark:bg-slate-900 dark:text-slate-200">
                        <pre class="whitespace-pre-wrap font-sans">{{ $output }}</pre>
                    </div>
                </div>
            @endif
        </div>

        {{-- Footer: apply actions --}}
        @if (($mode !== 'seo' && $output !== '') || ($mode === 'seo' && $seoOutput !== null))
            <div class="border-t border-slate-200 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                <div class="flex flex-wrap gap-2">
                    <button type="button" wire:click="applyToEditor('replace')"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                        <i data-lucide="check" class="h-3.5 w-3.5"></i>
                        Insert into Editor (replace)
                    </button>

                    @if ($mode !== 'seo')
                        <button type="button" wire:click="applyToEditor('append')"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-800">
                            <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                            Append
                        </button>
                    @endif

                    <button type="button" wire:click="regenerate"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-800">
                        <i data-lucide="rotate-cw" class="h-3.5 w-3.5"></i>
                        Regenerate
                    </button>
                </div>
            </div>
        @endif
    </aside>
</div>
