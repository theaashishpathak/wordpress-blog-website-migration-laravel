@php($title = 'General Settings')
@php($totalFields = collect($groups)->sum('field_count'))

<div class="space-y-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-300">System</p>
            <h1 class="mt-1 text-2xl font-bold">General Settings</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Configure system-wide CRM and ERP behavior across {{ count($groups) }} grouped sections.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                <i data-lucide="layers" class="h-3.5 w-3.5"></i>
                {{ count($groups) }} groups
            </span>
            <span class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                <i data-lucide="hash" class="h-3.5 w-3.5"></i>
                {{ $totalFields }} keys
            </span>

            @if(auth()->user()?->can('settings.update'))
                <button type="button"
                        wire:click="clearCache('all')"
                        wire:loading.attr="disabled" wire:target="clearCache"
                        wire:confirm="Clear ALL caches? Users may see slower next-load while caches warm up."
                        class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-800 shadow-sm transition hover:bg-amber-100 disabled:opacity-60 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200 dark:hover:bg-amber-500/20">
                    <i data-lucide="eraser" class="h-4 w-4" wire:loading.remove wire:target="clearCache"></i>
                    <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="clearCache"></i>
                    <span wire:loading.remove wire:target="clearCache">Clear Cache</span>
                    <span wire:loading wire:target="clearCache">Clearing…</span>
                </button>
            @endif

            <a href="{{ route('admin.settings.group', ['group' => 'company-settings']) }}" class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                <i data-lucide="building-2" class="h-4 w-4"></i>
                Open Company Settings
            </a>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[300px_minmax(0,1fr)]">
        @include('admin.settings._sidebar', ['groups' => $groups, 'activeGroup' => null])

        <div class="space-y-6">
            <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-indigo-500 to-violet-600 p-6 text-white shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-100">Overview</p>
                        <h2 class="mt-1 text-xl font-bold">All settings cached for fast access</h2>
                        <p class="mt-1 max-w-lg text-sm text-indigo-100/90">Pick a group from the sidebar or any card below. Values save instantly and are read from a centralized cache across the CRM.</p>
                    </div>
                    <div class="flex items-center gap-2 rounded-xl bg-white/10 px-4 py-3 backdrop-blur">
                        <i data-lucide="zap" class="h-5 w-5 text-amber-200"></i>
                        <span class="text-sm font-semibold">Live, cached, central</span>
                    </div>
                </div>
            </div>

            @if(auth()->user()?->can('settings.update'))
                <section id="maintenance" class="scroll-mt-20 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <header class="flex items-center justify-between gap-3 border-b border-slate-200 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                        <div class="flex items-center gap-3">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                                <i data-lucide="wrench" class="h-4 w-4"></i>
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">System Maintenance</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">Flush a specific cache layer or all of them at once.</p>
                            </div>
                        </div>
                        <span class="hidden text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400 sm:inline">Admin only</span>
                    </header>

                    <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($cacheTargets as $t)
                            <button type="button"
                                    wire:click="clearCache('{{ $t['key'] }}')"
                                    wire:loading.attr="disabled" wire:target="clearCache"
                                    class="group flex items-start gap-3 rounded-xl border border-slate-200 bg-white p-3 text-left transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:bg-slate-950 dark:hover:border-amber-500/40">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-amber-50 text-amber-600 group-hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-300">
                                    <i data-lucide="{{ $t['icon'] }}" class="h-4 w-4"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block text-sm font-bold text-slate-800 dark:text-slate-100">{{ $t['label'] }}</span>
                                    <span class="mt-0.5 block text-[11px] leading-relaxed text-slate-500 dark:text-slate-400">{{ $t['sub'] }}</span>
                                </span>
                                <i data-lucide="eraser" class="h-3.5 w-3.5 shrink-0 text-slate-300 group-hover:text-amber-500"></i>
                            </button>
                        @endforeach

                        <button type="button"
                                wire:click="clearCache('all')"
                                wire:loading.attr="disabled" wire:target="clearCache"
                                wire:confirm="Clear ALL caches? Users may see slower next-load while caches warm up."
                                class="group flex items-start gap-3 rounded-xl border border-amber-300 bg-gradient-to-br from-amber-50 to-orange-50 p-3 text-left transition hover:-translate-y-0.5 hover:from-amber-100 hover:shadow-sm disabled:cursor-not-allowed disabled:opacity-60 dark:border-amber-500/40 dark:from-amber-500/10 dark:to-orange-500/10">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-amber-500 text-white shadow-sm">
                                <i data-lucide="flame" class="h-4 w-4" wire:loading.remove wire:target="clearCache"></i>
                                <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="clearCache"></i>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-bold text-amber-900 dark:text-amber-200">Clear Everything</span>
                                <span class="mt-0.5 block text-[11px] leading-relaxed text-amber-700/80 dark:text-amber-300/80">Flush every cache layer above in one shot.</span>
                            </span>
                            <i data-lucide="arrow-right" class="h-3.5 w-3.5 shrink-0 text-amber-500"></i>
                        </button>
                    </div>
                </section>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($groups as $groupSlug => $group)
                    @php($iconClass = $colorIconLg[$group['color'] ?? 'slate'] ?? $colorIconLg['slate'])
                    <a
                        href="{{ route('admin.settings.group', ['group' => $groupSlug]) }}"
                        wire:navigate
                        class="group flex items-start gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-500/40"
                    >
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl {{ $iconClass }}">
                            <i data-lucide="{{ $group['icon'] ?? 'settings-2' }}" class="h-5 w-5"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">{{ $group['label'] }}</h3>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $group['field_count'] }}
                                </span>
                            </div>
                            <p class="mt-1 line-clamp-2 text-xs text-slate-500 dark:text-slate-400">{{ $group['description'] }}</p>
                            <div class="mt-3 inline-flex items-center gap-1 text-xs font-semibold text-indigo-600 transition group-hover:gap-2 dark:text-indigo-300">
                                Configure
                                <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
