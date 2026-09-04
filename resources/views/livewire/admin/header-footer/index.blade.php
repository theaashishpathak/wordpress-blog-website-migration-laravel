<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5 dark:border-slate-800">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Header & Footer Customizer</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Configure your global website header logo, controls, action buttons, and multi-column footer.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.navigation.index') }}" wire:navigate
               class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-200 bg-indigo-50/70 px-3.5 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 dark:border-indigo-900/50 dark:bg-indigo-950/40 dark:text-indigo-300 transition">
                <i data-lucide="menu" class="h-3.5 w-3.5"></i>
                <span>Navigation Menu</span>
            </a>
            <a href="{{ route('frontend.home') }}" target="_blank"
               class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 transition">
                <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                <span>View Live Site</span>
            </a>
            <button wire:click="resetDefaults" wire:confirm="Are you sure you want to reset all Header & Footer settings to factory defaults?" type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-semibold text-slate-600 hover:bg-rose-50 hover:text-rose-600 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-rose-950/40 dark:hover:text-rose-400 transition">
                <i data-lucide="rotate-ccw" class="h-3.5 w-3.5"></i>
                <span>Reset Defaults</span>
            </button>
            <button wire:click="save" wire:loading.attr="disabled" type="button"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 transition disabled:opacity-50">
                <i data-lucide="check" class="h-4 w-4" wire:loading.remove wire:target="save"></i>
                <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="save"></i>
                <span wire:loading.remove wire:target="save">Save Changes</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </div>

    {{-- Tabs Navigation --}}
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 overflow-x-auto scrollbar-hide">
        <button type="button" wire:click="setTab('header')"
                class="inline-flex items-center gap-2 border-b-2 px-4 py-3 text-xs font-bold transition {{ $activeTab === 'header' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
            <i data-lucide="panel-top" class="h-4 w-4"></i>
            <span>Header & Navigation</span>
        </button>
        <button type="button" wire:click="setTab('footer')"
                class="inline-flex items-center gap-2 border-b-2 px-4 py-3 text-xs font-bold transition {{ $activeTab === 'footer' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
            <i data-lucide="panel-bottom" class="h-4 w-4"></i>
            <span>Footer & Columns</span>
        </button>
        <button type="button" wire:click="setTab('ticker')"
                class="inline-flex items-center gap-2 border-b-2 px-4 py-3 text-xs font-bold transition {{ $activeTab === 'ticker' ? 'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
            <i data-lucide="newspaper" class="h-4 w-4"></i>
            <span>Sticky Bottom Ticker</span>
        </button>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════════
         TAB 1: HEADER SETTINGS
    ══════════════════════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'header')
        <div class="space-y-6">
            {{-- Visual Preview Card --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-xs dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3 flex items-center gap-1.5">
                    <i data-lucide="eye" class="h-3.5 w-3.5 text-indigo-500"></i>
                    Live Header Preview
                </h3>
                <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-[#111217]">
                    <div class="flex items-center justify-between gap-4">
                        {{-- Brand --}}
                        <div class="flex items-center gap-2.5">
                            @if ($header_logo_type === 'image' && !empty($header_logo_url))
                                <img src="{{ $header_logo_url }}" alt="Logo" style="height: {{ $header_logo_height }}px" class="w-auto object-contain">
                            @else
                                <span class="grid h-7 w-7 place-items-center rounded-lg bg-slate-900 text-white dark:bg-white dark:text-slate-950">
                                    <i data-lucide="{{ $header_brand_icon ?: 'sparkles' }}" class="h-3.5 w-3.5"></i>
                                </span>
                                <span class="text-base font-black uppercase tracking-wider text-slate-900 dark:text-white" style="font-family: 'Playfair Display', serif;">
                                    {{ $header_brand_text ?: 'RUPANTRIX' }}
                                </span>
                            @endif
                        </div>

                        {{-- Dummy Menu Pill --}}
                        <div class="hidden sm:flex items-center gap-1 rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-bold text-slate-600 dark:border-[#262832] dark:bg-[#181920] dark:text-neutral-300">
                            <span class="rounded-full bg-slate-100 px-2 py-0.5 dark:bg-[#252732]">Home</span>
                            <span class="px-2 py-0.5">Categories</span>
                            <span class="px-2 py-0.5">About</span>
                            <span class="px-2 py-0.5">Contacts</span>
                        </div>

                        {{-- Controls Preview --}}
                        <div class="flex items-center gap-2">
                            @if ($header_show_search)
                                <span class="grid h-7 w-7 place-items-center rounded-full bg-white text-slate-600 dark:bg-[#181920] dark:text-neutral-300">
                                    <i data-lucide="search" class="h-3.5 w-3.5"></i>
                                </span>
                            @endif

                            @if ($header_show_theme_toggle)
                                <div class="inline-flex h-7 w-[52px] items-center justify-between rounded-full border border-slate-200 bg-slate-100 px-1 dark:border-[#2c2e3a] dark:bg-[#181920]">
                                    <i data-lucide="moon" class="h-3 w-3 text-indigo-400"></i>
                                    <i data-lucide="sun" class="h-3 w-3 text-amber-500"></i>
                                </div>
                            @endif

                            @if ($header_show_action_button)
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-900 px-3 py-1 text-[11px] font-bold text-white dark:bg-white dark:text-slate-950">
                                    <i data-lucide="{{ $header_action_icon ?: 'layout-dashboard' }}" class="h-3 w-3"></i>
                                    <span>{{ $header_action_auth_text ?: 'Dashboard' }}</span>
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Brand & Logo Settings --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-5">
                <div class="border-b border-slate-100 pb-3 dark:border-slate-800">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Brand & Logo Configuration</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Choose between styled typography or an uploaded custom logo image.</p>
                </div>

                <div class="grid gap-6 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">Logo Presentation</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border p-3 text-xs font-semibold transition {{ $header_logo_type === 'text' ? 'border-indigo-600 bg-indigo-50/50 text-indigo-700 dark:border-indigo-500 dark:bg-indigo-950/40 dark:text-indigo-300' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400' }}">
                                <input type="radio" wire:model.live="header_logo_type" value="text" class="sr-only">
                                <i data-lucide="type" class="h-4 w-4"></i>
                                <span>Text & Icon Badge</span>
                            </label>
                            <label class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border p-3 text-xs font-semibold transition {{ $header_logo_type === 'image' ? 'border-indigo-600 bg-indigo-50/50 text-indigo-700 dark:border-indigo-500 dark:bg-indigo-950/40 dark:text-indigo-300' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400' }}">
                                <input type="radio" wire:model.live="header_logo_type" value="image" class="sr-only">
                                <i data-lucide="image" class="h-4 w-4"></i>
                                <span>Custom Logo Image</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">Brand Name</label>
                        <input type="text" wire:model.live="header_brand_text" placeholder="e.g. RUPANTRIX"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:border-indigo-500 focus:outline-none">
                    </div>
                </div>

                @if ($header_logo_type === 'text')
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">Brand Icon (Lucide Icon Name)</label>
                        <div class="flex items-center gap-3">
                            <input type="text" wire:model.live="header_brand_icon" placeholder="sparkles"
                                   class="max-w-xs rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:border-indigo-500 focus:outline-none">
                            <div class="flex flex-wrap gap-1.5 text-xs">
                                @foreach (['sparkles', 'newspaper', 'globe', 'zap', 'feather', 'flame', 'compass'] as $suggested)
                                    <button type="button" wire:click="$set('header_brand_icon', '{{ $suggested }}')"
                                            class="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-medium text-slate-600 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-600 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400 transition">
                                        {{ $suggested }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <div class="grid gap-6 md:grid-cols-3">
                        <div class="md:col-span-2">
                            <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">Logo Image URL</label>
                            <input type="text" wire:model.live="header_logo_url" placeholder="https://example.com/logo.svg or /storage/logo.png"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-mono dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:border-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-xs font-semibold text-slate-700 dark:text-slate-300">Display Height (px): {{ $header_logo_height }}px</label>
                            <input type="range" wire:model.live="header_logo_height" min="20" max="64" step="2"
                                   class="w-full accent-indigo-600">
                        </div>
                    </div>
                @endif
            </div>

            {{-- Controls & Features --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-4">
                <div class="border-b border-slate-100 pb-3 dark:border-slate-800">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Header Controls & Navigation</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Toggle header elements and behavioral features.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <label class="flex items-center justify-between rounded-xl border border-slate-200 p-3.5 dark:border-slate-800 dark:bg-slate-950 cursor-pointer">
                        <div>
                            <span class="block text-xs font-semibold text-slate-800 dark:text-slate-200">Sticky Header</span>
                            <span class="block text-[11px] text-slate-500 dark:text-slate-400">Fixed top navigation while scrolling</span>
                        </div>
                        <input type="checkbox" wire:model.live="header_sticky" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    </label>

                    <label class="flex items-center justify-between rounded-xl border border-slate-200 p-3.5 dark:border-slate-800 dark:bg-slate-950 cursor-pointer">
                        <div>
                            <span class="block text-xs font-semibold text-slate-800 dark:text-slate-200">Search Modal</span>
                            <span class="block text-[11px] text-slate-500 dark:text-slate-400">Quick search button in header</span>
                        </div>
                        <input type="checkbox" wire:model.live="header_show_search" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    </label>

                    <label class="flex items-center justify-between rounded-xl border border-slate-200 p-3.5 dark:border-slate-800 dark:bg-slate-950 cursor-pointer">
                        <div>
                            <span class="block text-xs font-semibold text-slate-800 dark:text-slate-200">Theme Switcher</span>
                            <span class="block text-[11px] text-slate-500 dark:text-slate-400">Dual-icon Light / Dark toggle pill</span>
                        </div>
                        <input type="checkbox" wire:model.live="header_show_theme_toggle" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    </label>
                </div>
            </div>

            {{-- Primary Action Button --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 dark:text-white">Primary Action CTA Button</h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Configure the highlighted pill button on the far right of the header.</p>
                    </div>
                    <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-200 cursor-pointer">
                        <input type="checkbox" wire:model.live="header_show_action_button" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span>Enable Action Button</span>
                    </label>
                </div>

                @if ($header_show_action_button)
                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Button Icon</label>
                            <input type="text" wire:model.live="header_action_icon" placeholder="layout-dashboard"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:border-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Logged-In Text</label>
                            <input type="text" wire:model.live="header_action_auth_text" placeholder="Dashboard"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:border-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Logged-In URL</label>
                            <input type="text" wire:model.live="header_action_auth_url" placeholder="/admin"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-mono dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:border-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Guest Visitor Text</label>
                            <input type="text" wire:model.live="header_action_guest_text" placeholder="Sign in"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:border-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Guest Visitor URL</label>
                            <input type="text" wire:model.live="header_action_guest_url" placeholder="/login"
                                   class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-mono dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:border-indigo-500 focus:outline-none">
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════════
         TAB 2: FOOTER SETTINGS
    ══════════════════════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'footer')
        <div class="space-y-6">
            {{-- Brand & About Bio --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-5">
                <div class="border-b border-slate-100 pb-3 dark:border-slate-800">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Footer Brand & Description</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Content displayed in the main column on the left of the footer.</p>
                </div>

                <div class="grid gap-5 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Footer Brand Title</label>
                        <input type="text" wire:model.live="footer_brand_text" placeholder="RUPANTRIX"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-semibold dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:border-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Footer Brand Icon</label>
                        <input type="text" wire:model.live="footer_brand_icon" placeholder="sparkles"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:border-indigo-500 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Footer Tagline / Bio</label>
                    <textarea wire:model.live="footer_description" rows="3"
                              class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs leading-relaxed dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:border-indigo-500 focus:outline-none"
                              placeholder="Welcome to ultimate source for fresh perspectives..."></textarea>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Copyright Text</label>
                    <input type="text" wire:model.live="footer_copyright" placeholder="© {year} — {siteName}. All Rights Reserved."
                           class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:border-indigo-500 focus:outline-none">
                    <p class="mt-1 text-[11px] text-slate-400">Tip: use tokens <code class="font-mono text-indigo-500">{year}</code> and <code class="font-mono text-indigo-500">{siteName}</code> to auto-populate dynamically.</p>
                </div>
            </div>

            {{-- Social Media Links --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-5">
                <div class="border-b border-slate-100 pb-3 dark:border-slate-800">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Social Media Links</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Leave any blank to hide that platform's icon from the footer.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label class="mb-1 flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                            <i data-lucide="facebook" class="h-3.5 w-3.5 text-blue-600"></i>
                            <span>Facebook URL</span>
                        </label>
                        <input type="text" wire:model.live="footer_social_facebook" placeholder="https://facebook.com/yourpage"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-mono dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:border-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                            <i data-lucide="twitter" class="h-3.5 w-3.5 text-sky-500"></i>
                            <span>Twitter / X URL</span>
                        </label>
                        <input type="text" wire:model.live="footer_social_twitter" placeholder="https://x.com/yourhandle"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-mono dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:border-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                            <i data-lucide="instagram" class="h-3.5 w-3.5 text-pink-500"></i>
                            <span>Instagram URL</span>
                        </label>
                        <input type="text" wire:model.live="footer_social_instagram" placeholder="https://instagram.com/yourprofile"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-mono dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:border-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                            <i data-lucide="linkedin" class="h-3.5 w-3.5 text-blue-700"></i>
                            <span>LinkedIn URL</span>
                        </label>
                        <input type="text" wire:model.live="footer_social_linkedin" placeholder="https://linkedin.com/company/yourorg"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-mono dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:border-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                            <i data-lucide="youtube" class="h-3.5 w-3.5 text-red-600"></i>
                            <span>YouTube URL</span>
                        </label>
                        <input type="text" wire:model.live="footer_social_youtube" placeholder="https://youtube.com/@channel"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-mono dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:border-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="mb-1 flex items-center gap-1.5 text-xs font-semibold text-slate-700 dark:text-slate-300">
                            <i data-lucide="github" class="h-3.5 w-3.5 text-slate-800 dark:text-white"></i>
                            <span>GitHub URL</span>
                        </label>
                        <input type="text" wire:model.live="footer_social_github" placeholder="https://github.com/org"
                               class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-mono dark:border-slate-700 dark:bg-slate-950 dark:text-white focus:border-indigo-500 focus:outline-none">
                    </div>
                </div>
            </div>

            {{-- 3 Navigation Columns --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-5">
                <div class="border-b border-slate-100 pb-3 dark:border-slate-800">
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Footer Columns Configuration</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Headings and feed counts for the three navigation columns on the right side of the footer.</p>
                </div>

                <div class="grid gap-6 md:grid-cols-3">
                    {{-- Column 1 --}}
                    <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800 dark:bg-slate-950 space-y-3">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-500">Column 1</span>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Header Title</label>
                            <input type="text" wire:model.live="footer_col1_title" placeholder="HOMEPAGES"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-white focus:border-indigo-500 focus:outline-none">
                        </div>
                        <p class="text-[11px] text-slate-400">Shows curated editorial home feeds and layout choices.</p>
                    </div>

                    {{-- Column 2 --}}
                    <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800 dark:bg-slate-950 space-y-3">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-500">Column 2 (Categories)</span>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Header Title</label>
                            <input type="text" wire:model.live="footer_col2_title" placeholder="CATEGORIES"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-white focus:border-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Items to Show: {{ $footer_col2_count }}</label>
                            <input type="range" wire:model.live="footer_col2_count" min="3" max="10" step="1"
                                   class="w-full accent-indigo-600">
                        </div>
                    </div>

                    {{-- Column 3 --}}
                    <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800 dark:bg-slate-950 space-y-3">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-500">Column 3 (Pages)</span>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Header Title</label>
                            <input type="text" wire:model.live="footer_col3_title" placeholder="PAGES"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-900 dark:text-white focus:border-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Items to Show: {{ $footer_col3_count }}</label>
                            <input type="range" wire:model.live="footer_col3_count" min="3" max="12" step="1"
                                   class="w-full accent-indigo-600">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ══════════════════════════════════════════════════════════════════════════
         TAB 3: STICKY BOTTOM TICKER
    ══════════════════════════════════════════════════════════════════════════ --}}
    @if ($activeTab === 'ticker')
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-xs dark:border-slate-800 dark:bg-slate-900 space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3 dark:border-slate-800">
                <div>
                    <h2 class="text-sm font-bold text-slate-900 dark:text-white">Sticky Bottom Story Ticker Bar</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Fixed horizontal scrolling story bar at the very bottom of the screen.</p>
                </div>
                <label class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-200 cursor-pointer">
                    <input type="checkbox" wire:model.live="footer_ticker_enabled" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    <span>Enable Sticky Ticker</span>
                </label>
            </div>

            @if ($footer_ticker_enabled)
                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800 dark:bg-slate-950 space-y-3">
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Max Stories in Ticker: {{ $footer_ticker_count }}</label>
                        <input type="range" wire:model.live="footer_ticker_count" min="3" max="20" step="1"
                               class="w-full accent-indigo-600">
                        <p class="text-[11px] text-slate-400">Select how many recent published stories appear horizontally in the ticker.</p>
                    </div>

                    <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800 dark:bg-slate-950 flex items-center justify-between">
                        <div>
                            <span class="block text-xs font-semibold text-slate-800 dark:text-slate-200">Scroll to Top Button</span>
                            <span class="block text-[11px] text-slate-500 dark:text-slate-400">Smooth chevron button on the far right of the ticker bar</span>
                        </div>
                        <input type="checkbox" wire:model.live="footer_show_back_to_top" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
