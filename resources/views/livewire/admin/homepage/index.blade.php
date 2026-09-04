<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5 dark:border-slate-800">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Homepage Customizer</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Configure the hero statement, trending topics, sidebar widgets, and newsletter on your homepage.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('frontend.home') }}" target="_blank"
               class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition">
                <i data-lucide="external-link" class="h-4 w-4"></i>
                <span>View Live Home</span>
            </a>
            <button wire:click="save" wire:loading.attr="disabled" type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition disabled:opacity-50">
                <i data-lucide="check" class="h-4 w-4"></i>
                <span wire:loading.remove wire:target="save">Save Changes</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    {{-- Tabs Navigation --}}
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 overflow-x-auto scrollbar-hide">
        <button type="button" wire:click="$set('activeTab', 'hero')"
                class="inline-flex items-center gap-2 border-b-2 px-4 py-3 text-xs font-bold transition {{ $activeTab === 'hero' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
            <i data-lucide="layout-template" class="h-4 w-4"></i>
            <span>Hero & Topic Discovery</span>
        </button>
        <button type="button" wire:click="$set('activeTab', 'feed')"
                class="inline-flex items-center gap-2 border-b-2 px-4 py-3 text-xs font-bold transition {{ $activeTab === 'feed' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
            <i data-lucide="rss" class="h-4 w-4"></i>
            <span>Story Stream Feed</span>
        </button>
        <button type="button" wire:click="$set('activeTab', 'sidebar')"
                class="inline-flex items-center gap-2 border-b-2 px-4 py-3 text-xs font-bold transition {{ $activeTab === 'sidebar' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
            <i data-lucide="panel-right" class="h-4 w-4"></i>
            <span>Sidebar Widgets</span>
        </button>
        <button type="button" wire:click="$set('activeTab', 'newsletter')"
                class="inline-flex items-center gap-2 border-b-2 px-4 py-3 text-xs font-bold transition {{ $activeTab === 'newsletter' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
            <i data-lucide="mail" class="h-4 w-4"></i>
            <span>Newsletter Section</span>
        </button>
    </div>

    {{-- Form Content --}}
    <form wire:submit.prevent="save">
        {{-- TAB 1: HERO SECTION --}}
        @if ($activeTab === 'hero')
            <div class="space-y-6">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900 space-y-5">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Hero Statement</h3>
                            <p class="text-xs text-slate-500">The main heading and editorial mission displayed at the top of the homepage.</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" wire:model.defer="hero_enabled" class="peer sr-only">
                            <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full dark:bg-slate-800"></div>
                        </label>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-1 pt-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Headline Text</label>
                            <input type="text" wire:model.defer="hero_title"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Subtitle / Mission Statement</label>
                            <textarea wire:model.defer="hero_subtitle" rows="3"
                                      class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white"></textarea>
                        </div>
                    </div>
                </div>

                {{-- Trending Topics Section --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900 space-y-5">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Trending Topics Pills</h3>
                        <p class="text-xs text-slate-500">Configure the category pill badges displayed under the hero heading.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 pt-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Section Label</label>
                            <input type="text" wire:model.defer="trending_topics_label"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Categories Source</label>
                            <select wire:model.live="trending_mode"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                                <option value="auto">Automatic (Top Categories by post count)</option>
                                <option value="manual">Manual Selection</option>
                            </select>
                        </div>
                    </div>

                    @if ($trending_mode === 'manual')
                        <div class="pt-2">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-2">Select Categories to Display</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2.5 max-h-56 overflow-y-auto p-3 rounded-xl border border-slate-100 bg-slate-50 dark:border-slate-800 dark:bg-slate-950">
                                @foreach ($allCategories as $cat)
                                    <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                                        <input type="checkbox" value="{{ $cat->id }}" wire:model.defer="selected_categories"
                                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="truncate">{{ $cat->translate('name') ?? ('#' . $cat->id) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- TAB 2: STORY STREAM FEED --}}
        @if ($activeTab === 'feed')
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900 space-y-6">
                <div>
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Story Stream Settings</h3>
                    <p class="text-xs text-slate-500">Configure how articles appear in the primary magazine list.</p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2 pt-2">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Posts Per Page</label>
                        <select wire:model.defer="posts_per_page"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                            <option value="6">6 posts</option>
                            <option value="8">8 posts</option>
                            <option value="10">10 posts</option>
                            <option value="12">12 posts</option>
                            <option value="16">16 posts</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Action Button Text</label>
                        <input type="text" wire:model.defer="discover_button_text"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-4 dark:border-slate-800">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">Item Visibility Toggles</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <label class="flex items-center gap-2.5 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" wire:model.defer="show_category_badge" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Category Pill Badge</span>
                        </label>
                        <label class="flex items-center gap-2.5 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" wire:model.defer="show_featured_badge" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Featured Pill Badge</span>
                        </label>
                        <label class="flex items-center gap-2.5 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" wire:model.defer="show_author" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Author Byline</span>
                        </label>
                        <label class="flex items-center gap-2.5 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" wire:model.defer="show_date" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Publication Date</span>
                        </label>
                        <label class="flex items-center gap-2.5 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                            <input type="checkbox" wire:model.defer="show_excerpt" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Excerpt with diamond bullet</span>
                        </label>
                    </div>
                </div>
            </div>
        @endif

        {{-- TAB 3: SIDEBAR WIDGETS --}}
        @if ($activeTab === 'sidebar')
            <div class="space-y-6">
                {{-- Widget 1: ABOUT --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Widget 1: About Card</h3>
                            <p class="text-xs text-slate-500">Author or brand introduction card with avatar and social links.</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" wire:model.defer="about_enabled" class="peer sr-only">
                            <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full dark:bg-slate-800"></div>
                        </label>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 pt-1">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Widget Heading</label>
                            <input type="text" wire:model.defer="about_title"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Profile Mode</label>
                            <select wire:model.live="about_mode"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                                <option value="author">Select from Authors/Staff</option>
                                <option value="custom">Custom Profile Details</option>
                            </select>
                        </div>
                    </div>

                    @if ($about_mode === 'author')
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Author</label>
                            <select wire:model.defer="about_author_id"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                                <option value="">Auto (Lead Author with most posts)</option>
                                @foreach ($authors as $auth)
                                    <option value="{{ $auth->id }}">{{ $auth->name }} ({{ $auth->email }})</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Display Name</label>
                                <input type="text" wire:model.defer="about_custom_name"
                                       class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Role / Subtitle Badge</label>
                                <input type="text" wire:model.defer="about_custom_badge"
                                       class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Bio Description</label>
                                <textarea wire:model.defer="about_custom_bio" rows="2"
                                          class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white"></textarea>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Location</label>
                                <input type="text" wire:model.defer="about_custom_location"
                                       class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Avatar Image URL</label>
                                <input type="text" wire:model.defer="about_custom_avatar" placeholder="https://..."
                                       class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                            </div>
                        </div>
                    @endif

                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-2">Social Links</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-[11px] font-medium text-slate-500 mb-1">X / Twitter</label>
                                <input type="text" wire:model.defer="about_twitter" placeholder="#" class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-slate-500 mb-1">Facebook</label>
                                <input type="text" wire:model.defer="about_facebook" placeholder="#" class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-slate-500 mb-1">Instagram</label>
                                <input type="text" wire:model.defer="about_instagram" placeholder="#" class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-slate-500 mb-1">LinkedIn</label>
                                <input type="text" wire:model.defer="about_linkedin" placeholder="#" class="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-xs dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Widget 2: FEATURED POSTS --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Widget 2: Featured Posts Card</h3>
                            <p class="text-xs text-slate-500">Showcase a highlighted article with visual card overlay.</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" wire:model.defer="featured_enabled" class="peer sr-only">
                            <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full dark:bg-slate-800"></div>
                        </label>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 pt-1">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Widget Heading</label>
                            <input type="text" wire:model.defer="featured_title"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Post Source</label>
                            <select wire:model.live="featured_mode"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                                <option value="auto">Automatic (Most Recent Featured Post)</option>
                                <option value="manual">Pick Specific Post</option>
                            </select>
                        </div>

                        @if ($featured_mode === 'manual')
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Select Featured Post</label>
                                <select wire:model.defer="featured_post_id"
                                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 focus:border-indigo-500 focus:bg-white focus:outline-none dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                                    <option value="">-- Choose Post --</option>
                                    @foreach ($featuredPosts as $fp)
                                        <option value="{{ $fp->id }}">{{ $fp->translation()?->title ?? ('#' . $fp->id) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Widget 3 & 4: Top Categories & Popular Topics --}}
                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Widget 3: Top Categories</h3>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" wire:model.defer="categories_enabled" class="peer sr-only">
                                <div class="peer h-5 w-9 rounded-full bg-slate-200 after:absolute after:top-[2px] after:left-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full dark:bg-slate-800"></div>
                            </label>
                        </div>
                        <div class="space-y-3 pt-1">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Heading</label>
                                <input type="text" wire:model.defer="categories_title"
                                       class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Count</label>
                                <input type="number" min="1" max="15" wire:model.defer="categories_count"
                                       class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900 space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Widget 4: Popular Topics</h3>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" wire:model.defer="tags_enabled" class="peer sr-only">
                                <div class="peer h-5 w-9 rounded-full bg-slate-200 after:absolute after:top-[2px] after:left-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full dark:bg-slate-800"></div>
                            </label>
                        </div>
                        <div class="space-y-3 pt-1">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Heading</label>
                                <input type="text" wire:model.defer="tags_title"
                                       class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Tags Count</label>
                                <input type="number" min="1" max="25" wire:model.defer="tags_count"
                                       class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Widget 5: Editorial Picks --}}
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white">Widget 5: Editorial Picks</h3>
                            <p class="text-xs text-slate-500">List curated articles with external arrow indicators.</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" wire:model.defer="editorial_enabled" class="peer sr-only">
                            <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full dark:bg-slate-800"></div>
                        </label>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3 pt-1">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Widget Heading</label>
                            <input type="text" wire:model.defer="editorial_title"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Selection Mode</label>
                            <select wire:model.defer="editorial_mode"
                                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                                <option value="trending">Trending Articles</option>
                                <option value="latest">Latest Published Articles</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Picks Count</label>
                            <input type="number" min="1" max="10" wire:model.defer="editorial_count"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- TAB 4: NEWSLETTER SECTION --}}
        @if ($activeTab === 'newsletter')
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs dark:border-slate-800 dark:bg-slate-900 space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Newsletter Callout</h3>
                        <p class="text-xs text-slate-500">The centered subscription section before the footer.</p>
                    </div>
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input type="checkbox" wire:model.defer="newsletter_enabled" class="peer sr-only">
                        <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-indigo-600 peer-checked:after:translate-x-full dark:bg-slate-800"></div>
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-1 pt-1">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Section Title</label>
                        <input type="text" wire:model.defer="newsletter_title"
                               class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Subtitle / Message</label>
                        <textarea wire:model.defer="newsletter_subtitle" rows="2"
                                  class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white"></textarea>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Subscribe Button Label</label>
                            <input type="text" wire:model.defer="newsletter_button_text"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Reassurance Badge Subtext</label>
                            <input type="text" wire:model.defer="newsletter_badge_text"
                                   class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-white">
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Bottom Action Bar --}}
        <div class="pt-4 flex items-center justify-end gap-3">
            <button wire:click="save" wire:loading.attr="disabled" type="button"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition disabled:opacity-50">
                <i data-lucide="check" class="h-4 w-4"></i>
                <span wire:loading.remove wire:target="save">Save Homepage Settings</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>
    </form>
</div>
