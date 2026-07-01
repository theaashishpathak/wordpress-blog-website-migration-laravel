@php
    $user = auth()->user();
    $isVisitor = $user !== null && $user->portal_type === 'visitor';
    $locale = app(\App\Support\LocaleResolver::class)->current();
@endphp

<div
    class="my-6 rounded-2xl border border-slate-200 bg-slate-50/60 p-3 dark:border-slate-800 dark:bg-slate-900/40"
    x-data="articleHighlighter({{ $isVisitor ? 'true' : 'false' }})"
    x-init="init()"
>
    <div class="flex flex-wrap items-center justify-between gap-3">
        {{-- Action buttons --}}
        <div class="flex flex-wrap items-center gap-2">
            {{-- Like / Dislike (counts visible to guests, action requires login) --}}
            <div class="inline-flex overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                <button type="button"
                        @if ($isVisitor) wire:click="react('like')" @else onclick="window.location='{{ route('login') }}'" @endif
                        class="group inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold transition border-r border-slate-200 dark:border-slate-700
                               {{ $reactionType === 'like' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800/60' }}">
                    <i data-lucide="thumbs-up" class="h-3.5 w-3.5 transition {{ $reactionType === 'like' ? 'scale-110' : 'group-hover:scale-110' }}"></i>
                    <span>{{ number_format($likesCount) }}</span>
                </button>
                <button type="button"
                        @if ($isVisitor) wire:click="react('dislike')" @else onclick="window.location='{{ route('login') }}'" @endif
                        class="group inline-flex items-center gap-1.5 px-3 py-2 text-xs font-bold transition
                               {{ $reactionType === 'dislike' ? 'bg-rose-50 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800/60' }}">
                    <i data-lucide="thumbs-down" class="h-3.5 w-3.5 transition {{ $reactionType === 'dislike' ? 'scale-110' : 'group-hover:scale-110' }}"></i>
                    <span>{{ number_format($dislikesCount) }}</span>
                </button>
            </div>

            @if ($isVisitor)
                <button type="button" wire:click="toggleBookmark"
                        class="group inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-bold transition
                               {{ $isBookmarked
                                  ? 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200'
                                  : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-800/60' }}">
                    <i data-lucide="{{ $isBookmarked ? 'bookmark-check' : 'bookmark' }}" class="h-3.5 w-3.5 transition group-hover:scale-110"></i>
                    {{ $isBookmarked ? 'Bookmarked' : 'Bookmark' }}
                </button>

                <button type="button" wire:click="toggleReadingList"
                        class="group inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-bold transition
                               {{ $isInReadingList
                                  ? 'border-sky-300 bg-sky-50 text-sky-700 dark:border-sky-500/40 dark:bg-sky-500/10 dark:text-sky-200'
                                  : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-800/60' }}">
                    <i data-lucide="{{ $isInReadingList ? 'check' : 'clock' }}" class="h-3.5 w-3.5 transition group-hover:scale-110"></i>
                    {{ $isInReadingList ? 'On Reading List' : 'Read Later' }}
                </button>

                <span class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    <i data-lucide="highlighter" class="h-3.5 w-3.5 text-emerald-700"></i>
                    {{ $highlightsCount }} highlight{{ $highlightsCount === 1 ? '' : 's' }}
                </span>
            @else
                <a href="{{ route('login') }}"
                   class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    <i data-lucide="bookmark" class="h-3.5 w-3.5"></i>
                    Sign in to bookmark, save, or highlight
                </a>
            @endif
        </div>

        @if ($isVisitor)
            <p class="hidden text-[11px] text-slate-500 md:block">
                <i data-lucide="info" class="inline h-3 w-3"></i>
                Select any text in the article to save a highlight.
            </p>
        @endif
    </div>

    {{-- Floating highlight popover — appears near the selection --}}
    @if ($isVisitor)
        <div x-show="popover.visible" x-cloak
             x-bind:style="`top: ${popover.top}px; left: ${popover.left}px;`"
             x-transition.opacity
             class="fixed z-50 flex flex-col gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl ring-1 ring-black/5 dark:border-slate-700 dark:bg-slate-900 dark:ring-white/5"
             style="transform: translate(-50%, -100%);">
            <div class="flex items-center gap-1">
                <button type="button" x-on:click="save(false)"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-slate-800">
                    <i data-lucide="highlighter" class="h-3 w-3"></i>
                    Highlight
                </button>
                <button type="button" x-on:click="popover.showNote = ! popover.showNote"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                    <i data-lucide="message-square-plus" class="h-3 w-3"></i>
                    + Note
                </button>
                <button type="button" x-on:click="closePopover()"
                        class="grid h-7 w-7 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800">
                    <i data-lucide="x" class="h-3 w-3"></i>
                </button>
            </div>
            <div x-show="popover.showNote" x-cloak class="flex flex-col gap-2">
                <textarea x-model="popover.note" rows="2" placeholder="Add a note (optional)…"
                          class="w-64 rounded-lg border border-slate-200 bg-white p-2 text-xs outline-none focus:border-emerald-500 dark:border-slate-700 dark:bg-slate-950"></textarea>
                <button type="button" x-on:click="save(true)"
                        class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-slate-900 px-2.5 py-1.5 text-xs font-bold text-white">
                    <i data-lucide="save" class="h-3 w-3"></i>
                    Save with note
                </button>
            </div>
        </div>
    @endif
</div>

@once
    @push('scripts')
        <script>
            window.articleHighlighter = function (isVisitor) {
                return {
                    popover: { visible: false, top: 0, left: 0, showNote: false, note: '', text: '' },

                    init() {
                        if (! isVisitor) return;

                        document.addEventListener('mouseup', (e) => this.handleSelection(e), { passive: true });
                        document.addEventListener('selectionchange', () => {
                            if (! window.getSelection().toString().trim()) {
                                // Only auto-hide if popover is fully closed (don't hide while user is typing a note)
                                if (! this.popover.showNote) this.closePopover();
                            }
                        });
                    },

                    handleSelection(e) {
                        // Only react to selections inside the article prose body
                        const article = document.querySelector('article .prose');
                        if (! article || ! article.contains(e.target)) return;

                        const sel = window.getSelection();
                        const text = sel?.toString().trim();
                        if (! text || text.length < 4) {
                            this.closePopover();
                            return;
                        }

                        const range = sel.getRangeAt(0);
                        const rect = range.getBoundingClientRect();
                        this.popover.text = text;
                        this.popover.top = rect.top - 12;
                        this.popover.left = rect.left + (rect.width / 2);
                        this.popover.visible = true;
                        this.popover.showNote = false;
                        this.popover.note = '';
                    },

                    save(withNote) {
                        if (! this.popover.text) return;
                        @this.call('saveHighlight', {
                            selected_text: this.popover.text,
                            note: withNote ? this.popover.note : null,
                        });
                        this.closePopover();
                    },

                    closePopover() {
                        this.popover.visible = false;
                        this.popover.showNote = false;
                        this.popover.note = '';
                        this.popover.text = '';
                    },
                };
            };

            // Toastr listener for Livewire dispatch('toast', message: ...)
            document.addEventListener('livewire:init', () => {
                window.Livewire.on('toast', (event) => {
                    if (window.toastr && event.message) window.toastr.success(event.message);
                });
            });
        </script>
    @endpush
@endonce
