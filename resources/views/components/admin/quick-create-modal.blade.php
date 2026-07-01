@php
    /**
     * Quick Create — a per-user "command palette" of every create action
     * across the admin. Each entry is permission-gated individually so a
     * user only sees what they're allowed to spawn.
     *
     * Adding a new entity? Drop a row in the right $sections array and
     * make sure the matching create route exists in routes/admin.php.
     */
    $sections = [
        [
            'label' => 'Content',
            'items' => [
                [
                    'perm' => 'posts.create',
                    'route' => 'admin.posts.create',
                    'label' => 'New Post',
                    'sub' => 'Article, news, video, gallery — full editor',
                    'icon' => 'newspaper',
                    'color' => 'indigo',
                    'shortcut' => 'P',
                ],
                [
                    'perm' => 'posts.create',
                    'route' => 'admin.posts.create',
                    'route_query' => ['type' => 'news'],
                    'label' => 'New News Article',
                    'sub' => 'Hard news with breaking + scheduled support',
                    'icon' => 'megaphone',
                    'color' => 'rose',
                    'shortcut' => 'N',
                ],
                [
                    'perm' => 'pages.create',
                    'route' => 'admin.pages.create',
                    'label' => 'New Page',
                    'sub' => 'Static page (About, Contact, Privacy…)',
                    'icon' => 'file-text',
                    'color' => 'emerald',
                ],
                [
                    'perm' => 'categories.create',
                    'route' => 'admin.categories.create',
                    'label' => 'New Category',
                    'sub' => 'Top-level or nested taxonomy bucket',
                    'icon' => 'folder-tree',
                    'color' => 'amber',
                ],
                [
                    'perm' => 'tags.create',
                    'route' => 'admin.tags.index',
                    'route_query' => ['create' => 1],
                    'label' => 'New Tag',
                    'sub' => 'Add a folksonomy tag with per-language names',
                    'icon' => 'tag',
                    'color' => 'fuchsia',
                ],
                [
                    'perm' => 'media.upload',
                    'route' => 'admin.media.index',
                    'label' => 'Upload Media',
                    'sub' => 'Drop images, video or documents',
                    'icon' => 'upload-cloud',
                    'color' => 'sky',
                ],
            ],
        ],
        [
            'label' => 'AI Studio',
            'items' => [
                [
                    'perm' => 'ai.use_writer',
                    'route' => 'admin.ai.writer',
                    'label' => 'AI-Written Article',
                    'sub' => 'Generate a full draft from a topic + tone',
                    'icon' => 'sparkles',
                    'color' => 'violet',
                    'shortcut' => 'A',
                ],
                [
                    'perm' => 'ai.use_seo',
                    'route' => 'admin.ai.seo-generator',
                    'label' => 'AI SEO Meta',
                    'sub' => 'Title, description, keywords, slug',
                    'icon' => 'search',
                    'color' => 'teal',
                ],
                [
                    'perm' => 'ai.templates',
                    'route' => 'admin.ai.prompt-templates',
                    'route_query' => ['create' => 1],
                    'label' => 'New Prompt Template',
                    'sub' => 'Versioned prompt for any AI feature',
                    'icon' => 'scroll-text',
                    'color' => 'pink',
                ],
            ],
        ],
        [
            'label' => 'Growth & Ops',
            'items' => [
                [
                    'perm' => 'rss.create',
                    'route' => 'admin.imports.sources',
                    'route_query' => ['create' => 1],
                    'label' => 'New RSS Source',
                    'sub' => 'Schedule a feed for auto-import',
                    'icon' => 'rss',
                    'color' => 'orange',
                ],
                [
                    'perm' => 'ads.create',
                    'route' => 'admin.ads.index',
                    'route_query' => ['create' => 1],
                    'label' => 'New Ad Creative',
                    'sub' => 'Image or HTML creative for any zone',
                    'icon' => 'dollar-sign',
                    'color' => 'emerald',
                ],
                [
                    'perm' => 'seo.redirects',
                    'route' => 'admin.seo.redirects',
                    'route_query' => ['create' => 1],
                    'label' => 'New Redirect',
                    'sub' => '301/302 rule for an old URL',
                    'icon' => 'corner-down-right',
                    'color' => 'sky',
                ],
            ],
        ],
        [
            'label' => 'People',
            'items' => [
                [
                    'perm' => 'staff.create',
                    'route' => 'admin.staff.create',
                    'label' => 'Add Staff',
                    'sub' => 'Onboard a new employee',
                    'icon' => 'user-plus',
                    'color' => 'indigo',
                ],
                [
                    'perm' => 'departments.create',
                    'route' => 'admin.departments.index',
                    'label' => 'Add Department',
                    'sub' => 'Create a new department',
                    'icon' => 'building-2',
                    'color' => 'slate',
                ],
            ],
        ],
    ];

    $colorMap = [
        'indigo'  => 'bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-300',
        'emerald' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300',
        'amber'   => 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-300',
        'sky'     => 'bg-sky-50 text-sky-600 dark:bg-sky-500/10 dark:text-sky-300',
        'rose'    => 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-300',
        'slate'   => 'bg-slate-100 text-slate-700 dark:bg-slate-700/40 dark:text-slate-200',
        'violet'  => 'bg-violet-50 text-violet-600 dark:bg-violet-500/10 dark:text-violet-300',
        'fuchsia' => 'bg-fuchsia-50 text-fuchsia-600 dark:bg-fuchsia-500/10 dark:text-fuchsia-300',
        'teal'    => 'bg-teal-50 text-teal-600 dark:bg-teal-500/10 dark:text-teal-300',
        'pink'    => 'bg-pink-50 text-pink-600 dark:bg-pink-500/10 dark:text-pink-300',
        'orange'  => 'bg-orange-50 text-orange-600 dark:bg-orange-500/10 dark:text-orange-300',
    ];

    // Filter each section's items by permission, then drop empty sections.
    $visibleSections = collect($sections)
        ->map(function (array $section): array {
            $section['items'] = collect($section['items'])
                ->filter(fn ($i): bool => auth()->user()?->can($i['perm']))
                ->values()
                ->all();

            return $section;
        })
        ->filter(fn (array $s): bool => ! empty($s['items']))
        ->values();

    $totalVisible = $visibleSections->sum(fn ($s) => count($s['items']));

    $buildUrl = static function (array $item): string {
        $url = route($item['route']);

        if (! empty($item['route_query']) && is_array($item['route_query'])) {
            $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($item['route_query']);
        }

        return $url;
    };
@endphp

{{--
    The script that powers Quick Create is intentionally inlined BEFORE
    the modal markup. Pushing it onto @stack('scripts') (the natural
    spot) ran it AFTER Alpine had already scanned the DOM and asked for
    `quickCreatePalette()`. Alpine couldn't find it, silently failed to
    initialise the component, and x-cloak never released — so the body
    stayed empty even though the server rendered all 14 actions.
--}}
@if ($totalVisible > 0)
    <script>
        window.quickCreatePalette = () => ({
            filter: '',
            cursor: 0,
            filteredCount: 0,
            totalCount: 0,

            init() {
                this.totalCount = this.$el.querySelectorAll('[data-qc-item]').length;
                this.filteredCount = this.totalCount;

                // Recompute filtered count + cursor whenever the filter
                // text changes.
                this.$watch('filter', () => this.recomputeFiltered());

                // When the modal opens (legacy app.js toggles the `flex`
                // class), reset filter + cursor and focus the input.
                const modal = this.$el;
                new MutationObserver(() => {
                    if (modal.classList.contains('flex')) {
                        this.filter = '';
                        this.cursor = 0;
                        this.recomputeFiltered();
                        this.$nextTick(() => this.$refs.input?.focus());
                    }
                }).observe(modal, { attributes: true, attributeFilter: ['class'] });
            },

            isOpen() {
                return this.$el.classList.contains('flex');
            },

            // Decide whether an item is visible under the current filter.
            // Called directly from x-show on each item link.
            isVisible(label) {
                const f = this.filter.trim().toLowerCase();
                if (f === '') return true;
                return label.toLowerCase().includes(f);
            },

            // Decide whether a section has at least one visible item.
            // Walks the DOM rather than the data model so it stays
            // accurate even if items are toggled at runtime.
            sectionVisible(sectionEl) {
                if (! this.filter.trim()) return true;
                const items = sectionEl.querySelectorAll('[data-qc-item]');
                for (const item of items) {
                    if (this.isVisible(item.dataset.qcLabel)) return true;
                }
                return false;
            },

            visibleItems() {
                return [...this.$el.querySelectorAll('[data-qc-item]')]
                    .filter(el => this.isVisible(el.dataset.qcLabel));
            },

            recomputeFiltered() {
                const visible = this.visibleItems();
                this.filteredCount = visible.length;
                if (visible.length === 0) {
                    this.cursor = -1;
                    return;
                }
                const stillVisible = visible.some(el => parseInt(el.dataset.qcIndex, 10) === this.cursor);
                if (! stillVisible) {
                    this.cursor = parseInt(visible[0].dataset.qcIndex, 10);
                }
            },

            moveCursor(delta) {
                const visible = this.visibleItems();
                if (visible.length === 0) return;
                const currentIdx = visible.findIndex(el => parseInt(el.dataset.qcIndex, 10) === this.cursor);
                const nextIdx = (currentIdx + delta + visible.length) % visible.length;
                this.cursor = parseInt(visible[nextIdx].dataset.qcIndex, 10);
                visible[nextIdx].scrollIntoView({ block: 'nearest' });
            },

            navigateToCursor() {
                const target = this.$el.querySelector(`[data-qc-item][data-qc-index="${this.cursor}"]`);
                if (target) target.click();
            },
        });

        // Global keyboard shortcut: Shift+Ctrl/Cmd+C opens the palette.
        // Bound on window so it works from anywhere in the admin.
        document.addEventListener('keydown', (e) => {
            if (! e.shiftKey) return;
            if (! (e.ctrlKey || e.metaKey)) return;
            if (e.key.toLowerCase() !== 'c') return;

            const modal = document.querySelector('[data-modal="quick-create-modal"]');
            if (! modal) return;
            if (modal.classList.contains('flex')) return;   // already open

            e.preventDefault();
            if (typeof window.openModal === 'function') {
                window.openModal('quick-create-modal');
            } else {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        });

        // Single-letter shortcut (Shift+P, Shift+N, Shift+A) inside an
        // open palette — only fires when focus isn't in a typeable input
        // so Shift+P inside the filter box stays a literal "P".
        document.addEventListener('keydown', (e) => {
            if (! e.shiftKey) return;
            if (e.ctrlKey || e.metaKey || e.altKey) return;

            const modal = document.querySelector('[data-modal="quick-create-modal"]');
            if (! modal || ! modal.classList.contains('flex')) return;

            const active = document.activeElement;
            if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA' || active.isContentEditable)) {
                return;
            }

            const key = e.key.toUpperCase();
            modal.querySelectorAll('[data-qc-item]').forEach((el) => {
                const kbd = el.querySelector('kbd');
                if (kbd && kbd.textContent.trim().endsWith(key)) {
                    e.preventDefault();
                    el.click();
                }
            });
        });
    </script>
@endif

<div
    data-modal="quick-create-modal"
    class="fixed inset-0 z-50 hidden items-center justify-center px-4 py-6"
    @if ($totalVisible > 0) x-data="quickCreatePalette()" x-init="init()" @endif
>
    <div class="absolute inset-0 bg-slate-900/60" data-modal-close="quick-create-modal"></div>

    <div class="relative z-10 flex max-h-[85vh] w-full max-w-3xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900">
        {{-- Header --}}
        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-6 py-4 dark:border-slate-800">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-indigo-500 via-violet-500 to-fuchsia-500 text-white shadow-sm">
                    <i data-lucide="plus" class="h-5 w-5"></i>
                </span>
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-slate-100">Quick Create</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Type to filter, Up/Down to navigate, Enter to open.
                    </p>
                </div>
            </div>
            <button type="button" class="rounded-xl p-2 hover:bg-slate-100 dark:hover:bg-slate-800" data-modal-close="quick-create-modal" aria-label="Close">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        @if ($totalVisible === 0)
            <div class="p-6">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-6 py-10 text-center dark:border-slate-800 dark:bg-slate-950">
                    <i data-lucide="lock" class="mx-auto h-8 w-8 text-slate-400"></i>
                    <p class="mt-3 text-sm font-semibold">No create permissions assigned</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                        Ask an administrator to grant you create access on at least one module.
                    </p>
                </div>
            </div>
        @else
            {{-- Search filter --}}
            <div class="border-b border-slate-200 px-6 py-3 dark:border-slate-800">
                <div class="relative">
                    <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input
                        type="text"
                        x-ref="input"
                        x-model="filter"
                        @keydown.arrow-down.prevent="moveCursor(1)"
                        @keydown.arrow-up.prevent="moveCursor(-1)"
                        @keydown.enter.prevent="navigateToCursor()"
                        placeholder="What would you like to create?"
                        class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pl-9 pr-3 text-sm focus:border-indigo-300 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-800 dark:focus:bg-slate-900"
                    >
                </div>
            </div>

            {{-- Sections + grid. Items default to visible (Tailwind block).
                 Alpine then hides ones whose label doesn't match the filter
                 via `:class="isVisible(...) ? '' : 'hidden'"`. This means a
                 fresh-open modal shows every action without Alpine even
                 needing to evaluate x-show. --}}
            <div class="flex-1 overflow-y-auto p-6">
                @php($globalIndex = 0)
                <div class="space-y-6">
                    @foreach ($visibleSections as $section)
                        @php($sectionSlug = \Illuminate\Support\Str::slug($section['label']))
                        <section
                            data-qc-section="{{ $sectionSlug }}"
                            x-show="sectionVisible($el)"
                            x-bind:class="filter && filteredCount === 0 ? 'hidden' : ''"
                        >
                            <h3 class="mb-2 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">
                                {{ $section['label'] }}
                            </h3>
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach ($section['items'] as $item)
                                    @php($idx = $globalIndex++)
                                    @php($qcLabel = \Illuminate\Support\Str::lower($item['label'].' '.$item['sub']))
                                    <a
                                        href="{{ $buildUrl($item) }}"
                                        wire:navigate
                                        data-qc-item
                                        data-qc-index="{{ $idx }}"
                                        data-qc-label="{{ $qcLabel }}"
                                        x-bind:class="isVisible('{{ $qcLabel }}') ? (cursor === {{ $idx }} ? 'border-indigo-400 bg-indigo-50/60 shadow-md ring-2 ring-indigo-100 dark:border-indigo-500/60 dark:bg-indigo-500/10' : 'border-slate-200 dark:border-slate-700') : 'hidden'"
                                        @mouseenter="cursor = {{ $idx }}"
                                        class="group flex items-start gap-3 rounded-2xl border p-4 transition hover:-translate-y-0.5 hover:border-indigo-300 hover:bg-indigo-50/40 hover:shadow-md dark:hover:border-indigo-500/40 dark:hover:bg-indigo-500/5"
                                    >
                                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl {{ $colorMap[$item['color']] ?? $colorMap['slate'] }}">
                                            <i data-lucide="{{ $item['icon'] }}" class="h-5 w-5"></i>
                                        </span>
                                        <span class="min-w-0 flex-1">
                                            <span class="flex items-center gap-1.5">
                                                <span class="block truncate text-sm font-bold text-slate-800 dark:text-slate-100">{{ $item['label'] }}</span>
                                                @if (! empty($item['shortcut']))
                                                    <kbd class="rounded-md border border-slate-300 bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] font-bold text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">Shift+{{ $item['shortcut'] }}</kbd>
                                                @endif
                                            </span>
                                            <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{{ $item['sub'] }}</span>
                                        </span>
                                        <i data-lucide="arrow-up-right" class="h-4 w-4 shrink-0 text-slate-300 transition group-hover:text-indigo-500"></i>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
                </div>

                {{-- Empty-after-filter state --}}
                <div x-show="filter && filteredCount === 0" class="py-10 text-center" style="display: none;">
                    <i data-lucide="search-x" class="mx-auto h-8 w-8 text-slate-300"></i>
                    <p class="mt-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
                        Nothing matches "<span x-text="filter"></span>".
                    </p>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 bg-slate-50/60 px-6 py-3 text-xs text-slate-500 dark:border-slate-800 dark:bg-slate-950/40">
                <span class="flex items-center gap-1.5">
                    <kbd class="rounded-md border border-slate-300 bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] font-bold text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">Ctrl+Shift+C</kbd>
                    opens this menu from anywhere
                </span>
                @if (config('app.demo_mode'))
                    <span class="flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-700 dark:bg-amber-500/20 dark:text-amber-300">
                        <i data-lucide="info" class="h-3 w-3"></i> Demo mode — saves disabled
                    </span>
                @endif
                <span class="text-[10px]">{{ $totalVisible }} create {{ \Illuminate\Support\Str::plural('action', $totalVisible) }} available</span>
            </div>
        @endif
    </div>
</div>
