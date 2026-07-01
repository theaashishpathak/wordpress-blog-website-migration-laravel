@php
    /**
     * @var array<string, array<string, mixed>> $groups
     * @var string|null $activeGroup
     */
    $activeGroup = $activeGroup ?? null;

    $colorActive = [
        'indigo'  => 'bg-indigo-50 text-indigo-700 ring-indigo-100 dark:bg-indigo-500/15 dark:text-indigo-200 dark:ring-indigo-500/30',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-100 dark:bg-emerald-500/15 dark:text-emerald-200 dark:ring-emerald-500/30',
        'violet'  => 'bg-violet-50 text-violet-700 ring-violet-100 dark:bg-violet-500/15 dark:text-violet-200 dark:ring-violet-500/30',
        'sky'     => 'bg-sky-50 text-sky-700 ring-sky-100 dark:bg-sky-500/15 dark:text-sky-200 dark:ring-sky-500/30',
        'amber'   => 'bg-amber-50 text-amber-700 ring-amber-100 dark:bg-amber-500/15 dark:text-amber-200 dark:ring-amber-500/30',
        'rose'    => 'bg-rose-50 text-rose-700 ring-rose-100 dark:bg-rose-500/15 dark:text-rose-200 dark:ring-rose-500/30',
        'cyan'    => 'bg-cyan-50 text-cyan-700 ring-cyan-100 dark:bg-cyan-500/15 dark:text-cyan-200 dark:ring-cyan-500/30',
        'slate'   => 'bg-slate-100 text-slate-800 ring-slate-200 dark:bg-slate-700/40 dark:text-slate-100 dark:ring-slate-600/40',
        'orange'  => 'bg-orange-50 text-orange-700 ring-orange-100 dark:bg-orange-500/15 dark:text-orange-200 dark:ring-orange-500/30',
        'teal'    => 'bg-teal-50 text-teal-700 ring-teal-100 dark:bg-teal-500/15 dark:text-teal-200 dark:ring-teal-500/30',
        'pink'    => 'bg-pink-50 text-pink-700 ring-pink-100 dark:bg-pink-500/15 dark:text-pink-200 dark:ring-pink-500/30',
        'red'     => 'bg-red-50 text-red-700 ring-red-100 dark:bg-red-500/15 dark:text-red-200 dark:ring-red-500/30',
    ];

    $colorIcon = [
        'indigo'  => 'bg-indigo-100 text-indigo-600 dark:bg-indigo-500/20 dark:text-indigo-300',
        'emerald' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/20 dark:text-emerald-300',
        'violet'  => 'bg-violet-100 text-violet-600 dark:bg-violet-500/20 dark:text-violet-300',
        'sky'     => 'bg-sky-100 text-sky-600 dark:bg-sky-500/20 dark:text-sky-300',
        'amber'   => 'bg-amber-100 text-amber-600 dark:bg-amber-500/20 dark:text-amber-300',
        'rose'    => 'bg-rose-100 text-rose-600 dark:bg-rose-500/20 dark:text-rose-300',
        'cyan'    => 'bg-cyan-100 text-cyan-600 dark:bg-cyan-500/20 dark:text-cyan-300',
        'slate'   => 'bg-slate-100 text-slate-600 dark:bg-slate-700/50 dark:text-slate-300',
        'orange'  => 'bg-orange-100 text-orange-600 dark:bg-orange-500/20 dark:text-orange-300',
        'teal'    => 'bg-teal-100 text-teal-600 dark:bg-teal-500/20 dark:text-teal-300',
        'pink'    => 'bg-pink-100 text-pink-600 dark:bg-pink-500/20 dark:text-pink-300',
        'red'     => 'bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-300',
    ];
@endphp

<aside class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="flex items-center gap-3 border-b border-slate-200 bg-gradient-to-br from-slate-50 to-white px-5 py-4 dark:border-slate-800 dark:from-slate-900 dark:to-slate-900">
        <span class="grid h-10 w-10 place-items-center rounded-xl bg-indigo-600 text-white shadow-sm">
            <i data-lucide="settings-2" class="h-5 w-5"></i>
        </span>
        <div>
            <h2 class="text-sm font-semibold">Settings Groups</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ count($groups) }} sections</p>
        </div>
    </div>

    <nav class="p-2" x-data>
        @foreach ($groups as $groupSlug => $group)
            @php
                $isActive = $activeGroup === $groupSlug;
                $color = $group['color'] ?? 'slate';
                $iconChip = $isActive
                    ? 'bg-white/20 text-current'
                    : ($colorIcon[$color] ?? $colorIcon['slate']);
                $row = $isActive
                    ? 'bg-indigo-600 text-white shadow-sm'
                    : 'text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800';
            @endphp

            <a
                href="{{ route('admin.settings.group', ['group' => $groupSlug]) }}"
                wire:navigate
                class="group flex items-center gap-3 rounded-xl px-3 py-2.5 transition {{ $row }}"
            >
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg transition {{ $iconChip }}">
                    <i data-lucide="{{ $group['icon'] ?? 'settings-2' }}" class="h-4 w-4"></i>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold">{{ $group['label'] }}</span>
                    <span class="block truncate text-[11px] {{ $isActive ? 'text-indigo-100' : 'text-slate-500 dark:text-slate-400' }}">
                        {{ $group['field_count'] }} field{{ $group['field_count'] === 1 ? '' : 's' }}
                    </span>
                </span>
                <i data-lucide="chevron-right" class="h-4 w-4 shrink-0 {{ $isActive ? 'text-white' : 'text-slate-300 group-hover:text-slate-500 dark:text-slate-600' }}"></i>
            </a>
        @endforeach
    </nav>

    @can('settings.update')
        {{-- Clear Cache shortcut — visible from any settings page so admins
             don't have to navigate back to the index to flush caches after
             editing a setting. Links straight to the General Settings page
             with a flag the Livewire component picks up. --}}
        <div class="border-t border-slate-200 p-3 dark:border-slate-800">
            <a href="{{ route('admin.settings.index') }}#maintenance"
               wire:navigate
               class="flex items-center justify-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs font-bold text-amber-800 transition hover:bg-amber-100 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200 dark:hover:bg-amber-500/20">
                <i data-lucide="eraser" class="h-3.5 w-3.5"></i>
                Clear Cache
            </a>
        </div>
    @endcan
</aside>
