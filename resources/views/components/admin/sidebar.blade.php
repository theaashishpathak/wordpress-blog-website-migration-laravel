@php
    $menuBaseClass = 'sidebar-menu-item';
    $dropdownParentClass = 'sidebar-menu-item sidebar-menu-parent justify-between';
    $menuActiveClass = 'sidebar-menu-item-active';
    $menuInactiveClass = 'sidebar-menu-item-inactive';
    $menuContentClass = 'flex min-w-0 flex-1 items-center gap-2.5';
    $menuChevronClass = 'sidebar-menu-chevron';
    $subMenuWrapper = 'sidebar-submenu';
    $childMenuBaseClass = 'sidebar-submenu-item';
    $childMenuActiveClass = 'sidebar-submenu-item-active';
    $childMenuInactiveClass = 'sidebar-submenu-item-inactive';
@endphp

<aside data-sidebar
    class="fixed inset-y-0 left-0 z-40 flex w-60 -translate-x-full flex-col border-r border-white/10 bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 text-slate-100 shadow-2xl shadow-slate-950/40 transition-all duration-300 lg:sticky lg:translate-x-0">
    @php
        $logoLight = $settings->get('branding.logo');
        $logoDark = $settings->get('branding.logo_dark') ?: $logoLight;
        // $logoLightUrl = $logoLight ?
        $logoLightUrl = $logoLight ? \Illuminate\Support\Facades\Storage::disk('public')->url($logoDark) : null;

        $logoDarkUrl = $logoDark ? \Illuminate\Support\Facades\Storage::disk('public')->url($logoLight) : null;
        $companyName = $settings->get('company.name') ?: config('app.name', 'NewsPilot AI');
    @endphp
    <div class="flex h-16 items-center justify-between border-b border-white/10 px-4">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            @if ($logoLightUrl)
                <img src="{{ $logoLightUrl }}" alt="{{ $companyName }}"
                    class="h-[60px] w-[100px] rounded-xl object-contain {{ $logoDarkUrl && $logoDarkUrl !== $logoLightUrl ? 'dark:hidden' : '' }}">
                @if ($logoDarkUrl && $logoDarkUrl !== $logoLightUrl)
                    <img src="{{ $logoDarkUrl }}" alt="{{ $companyName }}"
                        class="hidden h-10 w-10 rounded-xl object-contain dark:block">
                @endif
            @else
                <span
                    class="grid h-10 w-10 place-items-center rounded-xl bg-gradient-to-br from-indigo-500 to-sky-500 text-sm font-bold text-white shadow-lg shadow-indigo-900/40">{{ strtoupper(substr($companyName, 0, 1)) }}</span>
            @endif
            {{-- <span data-sidebar-label class="text-sm font-semibold tracking-wide text-white">{{ $companyName }}</span> --}}
        </a>

        <button type="button"
            class="rounded-xl border border-white/10 bg-white/5 p-2 text-slate-300 transition hover:bg-white/10 hover:text-white lg:hidden"
            data-sidebar-close aria-label="Close sidebar">
            <i data-lucide="x" class="h-5 w-5"></i>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto px-3 py-4">
        <nav class="space-y-1.5">

            {{-- Super Admin Dashboard --}}
            @php($mySuperAdminDashboard = request()->routeIs('admin.dashboard.super'))
            <a href="{{ route('admin.dashboard.super') }}"
                class="{{ $menuBaseClass }} {{ $mySuperAdminDashboard ? $menuActiveClass : $menuInactiveClass }}">
                <i data-lucide="layout-grid" class="h-4 w-4 shrink-0"></i>
                <span data-sidebar-label class="truncate">Super Admin Dashboard</span>
            </a>

            {{-- My Dashboard (top-level, available to every authenticated user) --}}
            @php($myDashboardActive = request()->routeIs('dashboard') || request()->routeIs('dashboard.my'))
            <a href="{{ route('dashboard.my') }}"
                class="{{ $menuBaseClass }} {{ $myDashboardActive ? $menuActiveClass : $menuInactiveClass }}">
                <i data-lucide="layout-grid" class="h-4 w-4 shrink-0"></i>
                <span data-sidebar-label class="truncate">Admin Dashboard</span>
            </a>

             {{-- ============================================================
                 Content + Editorial menu groups
                 ============================================================ --}}

            {{-- Content (Posts, News, Categories, Tags, Pages, Media, RSS) --}}
            @canany(['posts.view', 'posts.view_any', 'news.view', 'categories.view', 'tags.view', 'pages.view',
                'media.view', 'rss.view'])
                @php($contentMenuActive = request()->routeIs('admin.posts.*') || request()->routeIs('admin.news.*') || request()->routeIs('admin.categories.*') || request()->routeIs('admin.tags.*') || request()->routeIs('admin.pages.*') || request()->routeIs('admin.media.*') || request()->routeIs('admin.imports.*'))
                <div>
                    <button type="button"
                        class="{{ $dropdownParentClass }} {{ $contentMenuActive ? $menuActiveClass : $menuInactiveClass }}"
                        data-sidebar-menu-toggle="content-sidebar-menu" aria-controls="content-sidebar-menu">
                        <span class="{{ $menuContentClass }}">
                            <i data-lucide="newspaper" class="h-4 w-4 shrink-0"></i>
                            <span data-sidebar-label class="truncate">Posts Management</span>
                        </span>
                        <i data-lucide="chevron-down" class="{{ $menuChevronClass }}" data-sidebar-label></i>
                    </button>

                    <div data-sidebar-submenu="content-sidebar-menu"
                        class="{{ $subMenuWrapper }} {{ $contentMenuActive ? '' : 'hidden' }}">
                        @canany(['posts.view', 'posts.view_any'])
                            <a href="{{ route('admin.posts.index') }}" wire:navigate
                                class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.posts.*') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                <span data-sidebar-label>Posts</span>
                            </a>
                        @endcanany
                        {{-- @can('news.view')
                            <a href="{{ route('admin.posts.index', ['type' => 'news']) }}" wire:navigate
                                class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.news.*') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                <span data-sidebar-label>News</span>
                            </a>
                        @endcan --}}
                        @can('categories.view')
                            <a href="{{ route('admin.categories.index') }}" wire:navigate
                                class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.categories.*') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                <span data-sidebar-label>Categories</span>
                            </a>
                        @endcan
                        @can('tags.view')
                            <a href="{{ route('admin.tags.index') }}" wire:navigate
                                class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.tags.*') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                <span data-sidebar-label>Tags</span>
                            </a>
                        @endcan
                        @can('pages.view')
                            <a href="{{ route('admin.pages.index') }}" wire:navigate
                                class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.pages.*') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                <span data-sidebar-label>Pages</span>
                            </a>
                        @endcan
                        @can('media.view')
                            <a href="{{ route('admin.media.index') }}" wire:navigate
                                class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.media.*') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                <span data-sidebar-label>Media Library</span>
                            </a>
                        @endcan
                        @can('rss.view')
                            <a href="{{ route('admin.imports.sources') }}" wire:navigate
                                class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.imports.*') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                <span data-sidebar-label>RSS Sources</span>
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany





            {{-- Team group (Staff + Departments) --}}
            @canany(['staff.view', 'departments.view'])
                @php($teamMenuActive = request()->routeIs('admin.staff.*') || request()->routeIs('admin.departments.*'))
                <div>
                    <button type="button"
                        class="{{ $dropdownParentClass }} {{ $teamMenuActive ? $menuActiveClass : $menuInactiveClass }}"
                        data-sidebar-menu-toggle="team-sidebar-menu" aria-controls="team-sidebar-menu">
                        <span class="{{ $menuContentClass }}">
                            <i data-lucide="users-round" class="h-4 w-4 shrink-0"></i>
                            <span data-sidebar-label class="truncate">Users Management</span>
                        </span>
                        <i data-lucide="chevron-down" class="{{ $menuChevronClass }}" data-sidebar-label></i>
                    </button>

                    <div data-sidebar-submenu="team-sidebar-menu"
                        class="{{ $subMenuWrapper }} {{ $teamMenuActive ? '' : 'hidden' }}">
                        @can('staff.view')
                            <a href="{{ route('admin.staff.index') }}"
                                class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.staff.*') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                <span data-sidebar-label>Staff</span>
                            </a>
                        @endcan
                        @can('departments.view')
                            <a href="{{ route('admin.departments.index') }}"
                                class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.departments.*') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                <span data-sidebar-label>Departments</span>
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany





            {{-- Analytics Dashboards group --}}
            @php($dashboardsActive = request()->routeIs('admin.dashboards.*'))
            <div>
                <button type="button"
                    class="{{ $dropdownParentClass }} {{ $dashboardsActive ? $menuActiveClass : $menuInactiveClass }}"
                    data-sidebar-menu-toggle="dashboards-sidebar-menu" aria-controls="dashboards-sidebar-menu">
                    <span class="{{ $menuContentClass }}">
                        <i data-lucide="bar-chart-3" class="h-4 w-4 shrink-0"></i>
                        <span data-sidebar-label class="truncate">Analytics</span>
                    </span>
                    <i data-lucide="chevron-down" class="{{ $menuChevronClass }}" data-sidebar-label></i>
                </button>

                <div data-sidebar-submenu="dashboards-sidebar-menu"
                    class="{{ $subMenuWrapper }} {{ $dashboardsActive ? '' : 'hidden' }}">
                    <a href="{{ route('admin.dashboards.overview') }}" wire:navigate
                        class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.dashboards.overview') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                        <span data-sidebar-label>Overview</span>
                    </a>
                    @canany(['posts.view', 'posts.view_any'])
                        <a href="{{ route('admin.dashboards.content') }}" wire:navigate
                            class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.dashboards.content') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                            <span data-sidebar-label>Content Analytics</span>
                        </a>
                    @endcanany
                    @can('seo.view')
                        <a href="{{ route('admin.dashboards.seo') }}" wire:navigate
                            class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.dashboards.seo') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                            <span data-sidebar-label>SEO Analytics</span>
                        </a>
                    @endcan
                    @canany(['ads.view', 'newsletter.view'])
                        <a href="{{ route('admin.dashboards.revenue') }}" wire:navigate
                            class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.dashboards.revenue') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                            <span data-sidebar-label>Revenue Analytics</span>
                        </a>
                    @endcanany
                    @canany(['staff.view', 'logs.login.view', 'logs.activity.view'])
                        <a href="{{ route('admin.dashboards.users') }}" wire:navigate
                            class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.dashboards.users') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                            <span data-sidebar-label>User Activity</span>
                        </a>
                    @endcanany
                    @can('ai.reports')
                        <a href="{{ route('admin.dashboards.ai') }}" wire:navigate
                            class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.dashboards.ai') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                            <span data-sidebar-label>AI Usage</span>
                        </a>
                    @endcan
                </div>
            </div>

            {{-- Notifications --}}
            @can('notifications.view')
                @php($notificationsActive = request()->routeIs('notifications.index'))
                <a href="{{ route('notifications.index') }}"
                    class="{{ $menuBaseClass }} {{ $notificationsActive ? $menuActiveClass : $menuInactiveClass }}">
                    <i data-lucide="bell" class="h-4 w-4 shrink-0"></i>
                    <span data-sidebar-label class="truncate">Notifications</span>
                </a>
            @endcan



            {{-- AI Studio --}}
            @canany(['ai.use', 'ai.use_writer', 'ai.use_seo', 'ai.settings', 'ai.reports'])
                @php($aiMenuActive = request()->routeIs('admin.ai.*'))
                <div>
                    <button type="button"
                        class="{{ $dropdownParentClass }} {{ $aiMenuActive ? $menuActiveClass : $menuInactiveClass }}"
                        data-sidebar-menu-toggle="ai-sidebar-menu" aria-controls="ai-sidebar-menu">
                        <span class="{{ $menuContentClass }}">
                            <i data-lucide="sparkles" class="h-4 w-4 shrink-0 text-violet-300"></i>
                            <span data-sidebar-label class="truncate">AI Studio</span>
                        </span>
                        <i data-lucide="chevron-down" class="{{ $menuChevronClass }}" data-sidebar-label></i>
                    </button>

                    <div data-sidebar-submenu="ai-sidebar-menu"
                        class="{{ $subMenuWrapper }} {{ $aiMenuActive ? '' : 'hidden' }}">
                        @can('ai.use_writer')
                            <a href="{{ route('admin.ai.writer') }}" wire:navigate
                                class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.ai.writer') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                <span data-sidebar-label>AI Writer</span>
                            </a>
                        @endcan
                        @can('ai.use_seo')
                            <a href="{{ route('admin.ai.seo-generator') }}" wire:navigate
                                class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.ai.seo-generator') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                <span data-sidebar-label>SEO Generator</span>
                            </a>
                        @endcan
                        @can('ai.templates')
                            <a href="{{ route('admin.ai.prompt-templates') }}" wire:navigate
                                class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.ai.prompt-templates') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                <span data-sidebar-label>Prompt Templates</span>
                            </a>
                        @endcan
                        @can('ai.reports')
                            <a href="{{ route('admin.ai.usage-reports') }}" wire:navigate
                                class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.ai.usage-reports') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                <span data-sidebar-label>Usage Reports</span>
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany

            {{-- Editorial Workflow — only users in active editorial roles
                 (review / approve / reject). Authors with read-only
                 editorial.notes / editorial.revisions see those features
                 inline on the post detail page, not as a sidebar group. --}}
            @canany(['editorial.review_queue', 'editorial.calendar', 'editorial.approve', 'comments.moderate'])
                @php($editorialMenuActive = request()->routeIs('admin.editorial.*') || request()->routeIs('admin.comments.*'))
                <div>
                    <button type="button"
                        class="{{ $dropdownParentClass }} {{ $editorialMenuActive ? $menuActiveClass : $menuInactiveClass }}"
                        data-sidebar-menu-toggle="editorial-sidebar-menu" aria-controls="editorial-sidebar-menu">
                        <span class="{{ $menuContentClass }}">
                            <i data-lucide="check-square" class="h-4 w-4 shrink-0"></i>
                            <span data-sidebar-label class="truncate">Editorial</span>
                        </span>
                        <i data-lucide="chevron-down" class="{{ $menuChevronClass }}" data-sidebar-label></i>
                    </button>

                    <div data-sidebar-submenu="editorial-sidebar-menu"
                        class="{{ $subMenuWrapper }} {{ $editorialMenuActive ? '' : 'hidden' }}">
                        @can('editorial.review_queue')
                            @php($isQueueActive = request()->routeIs('admin.editorial.queue'))
                            <a href="{{ route('admin.editorial.queue') }}" wire:navigate
                                class="{{ $childMenuBaseClass }} {{ $isQueueActive ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                <span data-sidebar-label>Review Queue</span>
                            </a>
                        @endcan
                        @can('comments.moderate')
                            <a href="{{ route('admin.comments.index') }}" wire:navigate
                                class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.comments.*') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                <span data-sidebar-label>Comments</span>
                            </a>
                        @endcan
                        @can('editorial.calendar')
                            <a href="{{ route('admin.editorial.calendar') }}" wire:navigate
                                class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.editorial.calendar') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                <span data-sidebar-label>Content Calendar</span>
                            </a>
                        @endcan
                        @can('editorial.revisions')
                            <a href="{{ route('admin.posts.index') }}" wire:navigate
                                class="{{ $childMenuBaseClass }} {{ $childMenuInactiveClass }}">
                                <span data-sidebar-label>Revisions</span>
                                <span data-sidebar-label
                                    class="ml-auto text-[10px] uppercase tracking-wider text-slate-400">per&nbsp;post</span>
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany

            {{-- SEO Tools --}}
            @canany(['seo.view', 'seo.manage', 'seo.audit', 'seo.sitemap', 'seo.redirects'])
                @php($seoActive = request()->routeIs('admin.seo.*'))
                <div>
                    <button type="button"
                        class="{{ $dropdownParentClass }} {{ $seoActive ? $menuActiveClass : $menuInactiveClass }}"
                        data-sidebar-menu-toggle="seo-sidebar-menu" aria-controls="seo-sidebar-menu">
                        <span class="{{ $menuContentClass }}">
                            <i data-lucide="search" class="h-4 w-4 shrink-0"></i>
                            <span data-sidebar-label class="truncate">SEO Tools</span>
                        </span>
                        <i data-lucide="chevron-down" class="{{ $menuChevronClass }}" data-sidebar-label></i>
                    </button>

                    <div data-sidebar-submenu="seo-sidebar-menu"
                        class="{{ $subMenuWrapper }} {{ $seoActive ? '' : 'hidden' }}">
                        @can('seo.view')
                            <a href="{{ route('admin.seo.tools') }}" wire:navigate
                                class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.seo.tools') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                <span data-sidebar-label>Overview / Audit</span>
                            </a>
                        @endcan
                        @can('seo.redirects')
                            <a href="{{ route('admin.seo.redirects') }}" wire:navigate
                                class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.seo.redirects') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                <span data-sidebar-label>Redirects</span>
                            </a>
                        @endcan
                    </div>
                </div>
            @endcanany

            {{-- Newsletter --}}
            @canany(['newsletter.view', 'subscribers.view', 'newsletter.campaigns'])
                @php($newsletterActive = request()->routeIs('admin.newsletter.*'))
                <div>
                    <a href="{{ route('admin.newsletter.subscribers') }}" wire:navigate
                        class="{{ $menuBaseClass }} {{ $newsletterActive ? $menuActiveClass : $menuInactiveClass }}">
                        <i data-lucide="send" class="h-4 w-4 shrink-0"></i>
                        <span data-sidebar-label class="truncate">Newsletter</span>
                    </a>
                </div>
            @endcanany

            {{-- Monetization --}}
            @canany(['ads.view', 'sponsored.manage', 'premium.manage'])
                @php($adsActive = request()->routeIs('admin.ads.*'))
                <div>
                    <a href="{{ route('admin.ads.index') }}" wire:navigate
                        class="{{ $menuBaseClass }} {{ $adsActive ? $menuActiveClass : $menuInactiveClass }}">
                        <i data-lucide="dollar-sign" class="h-4 w-4 shrink-0"></i>
                        <span data-sidebar-label class="truncate">Monetization</span>
                    </a>
                </div>
            @endcanany

            {{-- Languages --}}
            @canany(['languages.view', 'translations.view'])
                @php($languagesActive = request()->routeIs('admin.languages.*'))
                <div>
                    <a href="{{ route('admin.languages.index') }}" wire:navigate
                        class="{{ $menuBaseClass }} {{ $languagesActive ? $menuActiveClass : $menuInactiveClass }}">
                        <i data-lucide="globe" class="h-4 w-4 shrink-0"></i>
                        <span data-sidebar-label class="truncate">Languages</span>
                    </a>
                </div>
            @endcanany

            {{-- Settings --}}
            @canany(['settings.view', 'settings.update', 'settings.roles', 'settings.permissions', 'logs.login.view',
                'logs.activity.view'])
                @php($settingsMenuActive = request()->routeIs('settings') || request()->routeIs('settings.*') || request()->routeIs('admin.settings.*') || request()->routeIs('admin.permission-groups.index') || request()->routeIs('admin.permissions.index') || request()->routeIs('admin.roles.index') || request()->routeIs('admin.roles.create') || request()->routeIs('admin.roles.edit') || request()->routeIs('admin.assign-role.index') || request()->routeIs('admin.assign-user-permissions.index') || request()->routeIs('admin.assign-user-permissions.edit') || request()->routeIs('admin.logs.*'))
                <div>
                    <button type="button"
                        class="{{ $dropdownParentClass }} {{ $settingsMenuActive ? $menuActiveClass : $menuInactiveClass }}"
                        data-sidebar-menu-toggle="settings-sidebar-menu" aria-controls="settings-sidebar-menu">
                        <span class="{{ $menuContentClass }}">
                            <i data-lucide="settings-2" class="h-4 w-4 shrink-0"></i>
                            <span data-sidebar-label class="truncate">Settings</span>
                        </span>
                        <i data-lucide="chevron-down" class="{{ $menuChevronClass }}" data-sidebar-label></i>
                    </button>

                    <div data-sidebar-submenu="settings-sidebar-menu"
                        class="{{ $subMenuWrapper }} {{ $settingsMenuActive ? '' : 'hidden' }}">
                        @can('settings.view')
                            <a href="{{ route('admin.settings.index') }}"
                                class="{{ $childMenuBaseClass }} {{ request()->routeIs('settings') || request()->routeIs('admin.settings.*') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                <span data-sidebar-label>General Settings</span>
                            </a>
                        @endcan
                        @canany(['settings.permissions', 'settings.roles'])
                            <div data-sidebar-label
                                class="px-3 pt-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">
                                Permission Settings
                            </div>
                            @can('settings.permissions')
                                <a href="{{ route('admin.permission-groups.index') }}"
                                    class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.permission-groups.index') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                    <span data-sidebar-label>Permission Groups</span>
                                </a>
                                <a href="{{ route('admin.permissions.index') }}"
                                    class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.permissions.index') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                    <span data-sidebar-label>Permissions</span>
                                </a>
                                <a href="{{ route('admin.assign-user-permissions.index') }}"
                                    class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.assign-user-permissions.index') || request()->routeIs('admin.assign-user-permissions.edit') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                    <span data-sidebar-label>Assign Permissions</span>
                                </a>
                            @endcan
                            @can('settings.roles')
                                <a href="{{ route('admin.roles.index') }}"
                                    class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.roles.index') || request()->routeIs('admin.roles.create') || request()->routeIs('admin.roles.edit') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                    <span data-sidebar-label>Roles</span>
                                </a>
                                <a href="{{ route('admin.assign-role.index') }}"
                                    class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.assign-role.index') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                    <span data-sidebar-label>Assign Roles</span>
                                </a>
                            @endcan
                        @endcanany
                        @canany(['logs.login.view', 'logs.activity.view'])
                            <div data-sidebar-label
                                class="px-3 pt-1 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">
                                Audit Logs
                            </div>
                            @can('logs.login.view')
                                <a href="{{ route('admin.logs.login.index') }}" wire:navigate
                                    class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.logs.login.*') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                    <span data-sidebar-label>Login Logs</span>
                                </a>
                            @endcan
                            @can('logs.activity.view')
                                <a href="{{ route('admin.logs.activity.index') }}" wire:navigate
                                    class="{{ $childMenuBaseClass }} {{ request()->routeIs('admin.logs.activity.*') ? $childMenuActiveClass : $childMenuInactiveClass }}">
                                    <span data-sidebar-label>Activity Logs</span>
                                </a>
                            @endcan
                        @endcanany
                    </div>
                </div>
            @endcanany

            <a href="{{ route('profile') }}"
                class="{{ $menuBaseClass }} {{ request()->routeIs('profile') ? $menuActiveClass : $menuInactiveClass }}">
                <i data-lucide="user-round" class="h-4 w-4 shrink-0"></i>
                <span data-sidebar-label class="truncate">Profile</span>
            </a>
        </nav>
    </div>

    <div class="border-t border-white/10 p-4">
        <div class="flex items-center gap-3 rounded-2xl border border-white/10 bg-white/5 p-3">
            <img src="{{ auth()->user()?->avatarUrl() }}" alt="Avatar"
                class="h-10 w-10 rounded-full object-cover ring-2 ring-white/20">
            <div data-sidebar-label class="min-w-0">
                <div class="truncate text-sm font-semibold text-white">{{ auth()->user()?->name ?? 'Admin' }}</div>
                <div class="truncate text-xs text-slate-400">{{ auth()->user()?->email ?? 'admin@newspilot.ai' }}
                </div>
            </div>
        </div>
    </div>
</aside>

<div class="fixed inset-0 z-30 hidden bg-slate-950/70 backdrop-blur-[2px] lg:hidden" data-sidebar-backdrop></div>
