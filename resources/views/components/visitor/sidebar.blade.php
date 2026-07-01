@php
    $settings = app(\App\Services\SettingService::class);
    $companyName = $settings->get('company.name') ?: config('app.name', 'NewsPilot AI');
    $logoLight = $settings->get('branding.logo');
    $logoLightUrl = $logoLight ? \Illuminate\Support\Facades\Storage::disk('public')->url($logoLight) : null;

    /**
     * Visitor portal menu — grouped sections, declared as config so each
     * phase (V2–V9) just slots its real routes in. Items marked with
     * `built` => false render a "Coming soon" badge.
     */
    $sections = [
        [
            'label' => null,
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'route' => 'visitor.dashboard', 'built' => true],
            ],
        ],
        [
            'label' => 'My Library',
            'icon' => 'library',
            'items' => [
                ['label' => 'Bookmarks',        'icon' => 'bookmark',    'route' => 'visitor.bookmarks',       'built' => true],
                ['label' => 'Reading List',     'icon' => 'clock',       'route' => 'visitor.reading-list',    'built' => true],
                ['label' => 'Reading History',  'icon' => 'history',     'route' => 'visitor.reading-history', 'built' => true],
                ['label' => 'Highlights',       'icon' => 'highlighter', 'route' => 'visitor.highlights',      'built' => true],
            ],
        ],
        [
            'label' => 'Engagement',
            'icon' => 'message-square',
            'items' => [
                ['label' => 'My Comments',      'icon' => 'message-square', 'route' => 'visitor.comments',        'built' => true],
                ['label' => 'Likes & Dislikes', 'icon' => 'thumbs-up',      'route' => 'visitor.reactions',       'built' => true],
                ['label' => 'For You',          'icon' => 'sparkles',       'route' => 'visitor.recommendations', 'built' => true],
            ],
        ],
        [
            'label' => 'Following',
            'icon' => 'users',
            'items' => [
                ['label' => 'Topics',  'icon' => 'hash',     'route' => 'visitor.following.topics',  'built' => true],
                ['label' => 'Authors', 'icon' => 'pen-tool', 'route' => 'visitor.following.authors', 'built' => true],
                ['label' => 'Readers', 'icon' => 'users',    'route' => 'visitor.following.users',   'built' => true],
            ],
        ],
        [
            'label' => null,
            'items' => [
                ['label' => 'Notifications', 'icon' => 'bell', 'route' => 'visitor.notifications', 'built' => true],
            ],
        ],
        [
            'label' => 'Email & Newsletter',
            'icon' => 'mail',
            'items' => [
                ['label' => 'Preferences',      'icon' => 'sliders', 'route' => 'visitor.email.preferences',   'built' => true],
                ['label' => 'Subscribed Lists', 'icon' => 'inbox',   'route' => 'visitor.email.subscriptions', 'built' => true],
            ],
        ],
        [
            'label' => 'Settings',
            'icon' => 'settings',
            'items' => [
                ['label' => 'My Profile',         'icon' => 'user-round',          'route' => 'visitor.settings.profile',    'built' => true],
                ['label' => 'Account & Security', 'icon' => 'shield-check',        'route' => 'visitor.settings.security',   'built' => true],
                ['label' => 'Sessions & Devices', 'icon' => 'monitor-smartphone',  'route' => 'visitor.settings.sessions',   'built' => true],
                ['label' => 'My Activity',        'icon' => 'history',             'route' => 'visitor.settings.activity',   'built' => true],
                ['label' => 'Privacy',            'icon' => 'lock',                'route' => 'visitor.settings.privacy',    'built' => true],
                ['label' => 'Appearance',         'icon' => 'palette',             'route' => 'visitor.settings.appearance', 'built' => true],
            ],
        ],
        [
            'label' => 'Data & Privacy',
            'icon' => 'database',
            'items' => [
                ['label' => 'Export My Data', 'icon' => 'download', 'route' => 'visitor.data.export', 'built' => true],
                ['label' => 'Delete Account', 'icon' => 'trash-2',  'route' => 'visitor.data.delete', 'built' => true, 'danger' => true],
            ],
        ],
    ];
@endphp

{{-- Mobile overlay --}}
<div data-visitor-overlay class="fixed inset-0 z-30 hidden bg-slate-900/50 backdrop-blur-sm lg:hidden"></div>

<aside data-visitor-sidebar
       class="fixed inset-y-0 left-0 z-40 flex w-72 -translate-x-full flex-col border-r border-slate-200 bg-white transition-transform duration-300 dark:border-slate-800 dark:bg-slate-950 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">

    {{-- Brand — flat, no gradient. The serif company name carries the editorial voice. --}}
    <div class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-slate-200 px-5 dark:border-slate-800">
        <a href="{{ route('visitor.dashboard') }}" class="flex min-w-0 items-center gap-2.5">
            @if ($logoLightUrl)
                <img src="{{ $logoLightUrl }}" alt="{{ $companyName }}" class="h-9 w-9 rounded-lg object-contain">
            @else
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900">
                    <span class="text-sm font-black" style="font-family: 'Playfair Display', serif;">{{ mb_substr($companyName, 0, 1) }}</span>
                </span>
            @endif
            <span class="flex min-w-0 flex-col leading-tight">
                <span class="truncate text-sm font-black tracking-tight text-slate-900 dark:text-slate-100" style="font-family: 'Playfair Display', serif;">
                    {{ $companyName }}
                </span>
                <span class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-500 dark:text-slate-400">
                    Reader Portal
                </span>
            </span>
        </a>

        <button type="button" data-visitor-sidebar-close
                class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-slate-500 hover:bg-slate-100 lg:hidden dark:text-slate-400 dark:hover:bg-slate-800"
                aria-label="Close sidebar">
            <i data-lucide="x" class="h-4 w-4"></i>
        </button>
    </div>

    {{-- Menu --}}
    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5 text-sm">
        @foreach ($sections as $section)
            <div class="space-y-0.5">
                @if (! empty($section['label']))
                    <p class="mb-2 px-3 text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400 dark:text-slate-500">
                        {{ $section['label'] }}
                    </p>
                @endif

                @foreach ($section['items'] as $item)
                    @php
                        $isActive = request()->routeIs($item['route']);
                        $isDanger = ! empty($item['danger']);
                        $isBuilt = ! empty($item['built']);

                        if ($isActive) {
                            $stateClasses = 'bg-slate-100 text-slate-900 dark:bg-slate-800 dark:text-slate-50';
                        } elseif ($isDanger) {
                            $stateClasses = 'text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-500/10';
                        } else {
                            $stateClasses = 'text-slate-600 hover:bg-slate-50 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-900 dark:hover:text-slate-100';
                        }
                    @endphp
                    <a href="{{ route($item['route']) }}" wire:navigate
                       class="relative flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition {{ $stateClasses }}">
                        @if ($isActive)
                            <span aria-hidden="true" class="absolute left-0 top-1/2 h-5 w-0.5 -translate-y-1/2 rounded-full bg-emerald-600 dark:bg-emerald-400"></span>
                        @endif
                        <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4 shrink-0 {{ $isActive ? 'text-emerald-700 dark:text-emerald-400' : '' }}"></i>
                        <span class="flex-1 truncate">{{ $item['label'] }}</span>
                        @if (! $isBuilt)
                            <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[8px] font-bold uppercase tracking-wider text-slate-500 dark:bg-slate-800 dark:text-slate-400">Soon</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endforeach
    </nav>

    {{-- Footer — user card + logout. Calmer, no gradient avatar. --}}
    <div class="shrink-0 border-t border-slate-200 p-3 dark:border-slate-800">
        @auth
            <div class="mb-2 flex items-center gap-3 rounded-lg px-2 py-2">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-slate-900 text-sm font-black uppercase text-white dark:bg-slate-100 dark:text-slate-900">
                    {{ mb_substr(auth()->user()->name, 0, 1) }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-bold text-slate-900 dark:text-slate-100">{{ auth()->user()->name }}</p>
                    <p class="truncate text-[11px] text-slate-500">{{ auth()->user()->email }}</p>
                </div>
            </div>
        @endauth

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 dark:border-slate-800 dark:text-slate-400 dark:hover:border-rose-500/40 dark:hover:bg-rose-500/10 dark:hover:text-rose-300">
                <i data-lucide="log-out" class="h-4 w-4"></i>
                Sign out
            </button>
        </form>
    </div>
</aside>
