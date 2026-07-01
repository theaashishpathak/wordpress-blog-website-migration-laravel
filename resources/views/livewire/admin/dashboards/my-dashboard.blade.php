<div class="space-y-6">
    @php($me = $this->me)
    @php($isAdmin = $me->hasRole('super_admin') || $me->hasRole('admin'))

    {{-- ================================================================
         WELCOME BANNER
    ================================================================ --}}
    <div class="rounded-3xl bg-gradient-to-br from-indigo-600 via-violet-600 to-fuchsia-600 p-6 text-white shadow-lg">
        <div class="flex flex-col items-start gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <img src="{{ $me->avatarUrl() }}" alt="{{ $me->name }}"
                     class="h-16 w-16 rounded-2xl object-cover ring-4 ring-white/30">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-white/70">
                        {{ $isAdmin ? 'Super Admin · Command Centre' : 'My Dashboard' }}
                    </p>
                    <h1 class="mt-0.5 text-2xl font-black tracking-tight" style="font-family:'Playfair Display',serif;">
                        Welcome back, {{ $me->name }} 👋
                    </h1>
                    <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-white/80">
                        @if ($me->job_title) <span>{{ $me->job_title }}</span> @endif
                        @if ($me->department) <span>· {{ $me->department->name }}</span> @endif
                        @if ($me->manager) <span>· Reports to <strong class="text-white">{{ $me->manager->name }}</strong></span> @endif
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach ($this->quickActions as $action)
                    <a href="{{ $action['url'] }}" wire:navigate
                       class="inline-flex items-center gap-2 rounded-xl border border-white/30 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">
                        <i data-lucide="{{ $action['icon'] }}" class="h-4 w-4"></i>
                        {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ================================================================
         SUPER ADMIN SECTION — only visible to admins
    ================================================================ --}}
    @if ($isAdmin)

        {{-- Site-wide stat tiles --}}
        @php($st = $this->siteTiles)
        <div>
            <p class="mb-3 text-[10px] font-bold uppercase tracking-widest text-slate-400">Site Overview</p>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                @foreach ([
                    ['label' => 'Total Published',   'value' => $st['total_published'],  'icon' => 'newspaper',      'color' => 'from-indigo-500 to-violet-500'],
                    ['label' => 'Published Today',   'value' => $st['published_today'],   'icon' => 'zap',            'color' => 'from-emerald-500 to-teal-500'],
                    ['label' => 'Pending Review',    'value' => $st['pending_review'],    'icon' => 'hourglass',      'color' => 'from-amber-500 to-orange-500', 'url' => 'admin.editorial.queue'],
                    ['label' => 'Scheduled',         'value' => $st['scheduled'],         'icon' => 'calendar-clock', 'color' => 'from-violet-500 to-purple-500'],
                    ['label' => 'Drafts',            'value' => $st['drafts'],            'icon' => 'file-text',      'color' => 'from-slate-400 to-slate-500'],
                    ['label' => 'Pending Comments',  'value' => $st['pending_comments'],  'icon' => 'message-square', 'color' => 'from-rose-500 to-pink-500',    'url' => 'admin.comments.index'],
                ] as $tile)
                    @php($tag = isset($tile['url']) ? 'a' : 'div')
                    <{{ $tag }}
                        @if(isset($tile['url'])) href="{{ route($tile['url']) }}" wire:navigate @endif
                        class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ $tile['label'] }}</span>
                            <span class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br {{ $tile['color'] }} text-white">
                                <i data-lucide="{{ $tile['icon'] }}" class="h-4 w-4"></i>
                            </span>
                        </div>
                        <p class="mt-2 text-3xl font-black tabular-nums text-slate-900 dark:text-slate-100">
                            {{ number_format($tile['value']) }}
                        </p>
                    </{{ $tag }}>
                @endforeach
            </div>
        </div>

        {{-- Pending Review Queue --}}
        <div class="overflow-hidden rounded-2xl border border-amber-200 bg-white shadow-sm dark:border-amber-800/40 dark:bg-slate-900">
            <header class="flex items-center justify-between border-b border-amber-100 bg-amber-50 px-5 py-3 dark:border-amber-800/30 dark:bg-amber-900/20">
                <div class="flex items-center gap-2">
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-amber-500 text-white">
                        <i data-lucide="hourglass" class="h-4 w-4"></i>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Pending Review Queue</h3>
                        <p class="text-[10px] text-amber-600 dark:text-amber-400">Posts awaiting approval before going live</p>
                    </div>
                </div>
                @can('posts.review')
                    <a href="{{ route('admin.editorial.queue') }}" wire:navigate
                       class="inline-flex items-center gap-1 rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-bold text-white hover:bg-amber-600">
                        Review All →
                    </a>
                @endcan
            </header>
            @if ($this->pendingReviewQueue->isEmpty())
                <div class="flex flex-col items-center justify-center gap-2 py-10 text-slate-400">
                    <i data-lucide="check-circle-2" class="h-8 w-8 text-emerald-400"></i>
                    <p class="text-sm font-medium">All clear — no posts pending review.</p>
                </div>
            @else
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($this->pendingReviewQueue as $post)
                        <li class="flex items-center gap-3 px-5 py-3 hover:bg-amber-50/40 dark:hover:bg-amber-900/10">
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate
                                   class="block truncate text-sm font-semibold text-slate-800 hover:text-amber-600 dark:text-slate-100">
                                    {{ $post->translation()?->title ?? '#'.$post->id }}
                                </a>
                                <p class="mt-0.5 text-[11px] text-slate-500">
                                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ $post->author?->name ?? '—' }}</span>
                                    @if ($post->category?->translation()?->name)
                                        · {{ $post->category->translation()->name }}
                                    @endif
                                    · submitted {{ $post->updated_at?->diffForHumans() }}
                                </p>
                            </div>
                            @php($isInReview = $post->status?->value === 'in_review')
                            <span class="shrink-0 rounded-md px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider {{ $isInReview ? 'bg-indigo-100 text-indigo-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ str_replace('_', ' ', $post->status?->value ?? '—') }}
                            </span>
                            <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate
                               class="shrink-0 rounded-lg border border-amber-300 px-2.5 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-100 dark:border-amber-700 dark:text-amber-400">
                                Review
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Scheduled + Recently Published --}}
        <div class="grid gap-4 lg:grid-cols-2">

            {{-- Scheduled --}}
            <div class="overflow-hidden rounded-2xl border border-violet-200 bg-white shadow-sm dark:border-violet-800/40 dark:bg-slate-900">
                <header class="flex items-center justify-between border-b border-violet-100 bg-violet-50/60 px-5 py-3 dark:border-violet-800/30 dark:bg-violet-900/20">
                    <div class="flex items-center gap-2">
                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-violet-500 text-white">
                            <i data-lucide="calendar-clock" class="h-4 w-4"></i>
                        </span>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Scheduled to Publish</h3>
                    </div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-violet-500">soonest first</span>
                </header>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($this->scheduledPosts as $post)
                        <li class="flex items-start gap-3 px-5 py-3">
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate
                                   class="block truncate text-sm font-semibold text-slate-800 hover:text-violet-600 dark:text-slate-100">
                                    {{ $post->translation()?->title ?? '#'.$post->id }}
                                </a>
                                <p class="mt-0.5 text-[11px] text-slate-500">
                                    {{ $post->author?->name ?? '—' }} · {{ $post->category?->translation()?->name ?? 'No category' }}
                                </p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-[11px] font-bold text-violet-600 dark:text-violet-400">{{ $post->scheduled_at?->format('M j') }}</p>
                                <p class="text-[10px] text-slate-400">{{ $post->scheduled_at?->format('H:i') }}</p>
                            </div>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-xs text-slate-500">No posts scheduled.</li>
                    @endforelse
                </ul>
            </div>

            {{-- Recently Published (all authors) --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Recently Published</h3>
                    <a href="{{ route('admin.posts.index') }}" wire:navigate class="text-xs font-semibold text-indigo-600 hover:underline">All posts →</a>
                </header>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($this->recentlyPublished as $post)
                        <li class="flex items-start gap-3 px-5 py-3">
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate
                                   class="block truncate text-sm font-semibold text-slate-800 hover:text-indigo-600 dark:text-slate-100">
                                    {{ $post->translation()?->title ?? '#'.$post->id }}
                                </a>
                                <p class="mt-0.5 text-[11px] text-slate-500">
                                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ $post->author?->name ?? '—' }}</span>
                                    @if ($post->category?->translation()?->name)
                                        · {{ $post->category->translation()->name }}
                                    @endif
                                    · {{ $post->published_at?->diffForHumans() }}
                                </p>
                            </div>
                            @if ($post->view_count)
                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-500">
                                    <i data-lucide="eye" class="h-3 w-3"></i> {{ number_format((int) $post->view_count) }}
                                </span>
                            @endif
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-xs text-slate-500">No posts published yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- Homepage Featured Content Snapshot --}}
        @php($featured = $this->featuredContent)
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="border-b border-slate-100 bg-slate-50 px-5 py-3 dark:border-slate-800 dark:bg-slate-800/50">
                <div class="flex items-center gap-2">
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-gradient-to-br from-pink-500 to-rose-500 text-white">
                        <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Homepage Featured Content</h3>
                        <p class="text-[10px] text-slate-500">What's currently surfaced on the front page</p>
                    </div>
                </div>
            </header>
            <div class="grid divide-y divide-slate-100 sm:grid-cols-2 sm:divide-x sm:divide-y-0 dark:divide-slate-800">
                @foreach ([
                    ['key' => 'breaking',     'label' => '🔴 Breaking',      'badge' => 'bg-rose-100 text-rose-700',    'hover' => 'hover:text-rose-600'],
                    ['key' => 'featured',     'label' => '⭐ Featured',       'badge' => 'bg-indigo-100 text-indigo-700','hover' => 'hover:text-indigo-600'],
                    ['key' => 'trending',     'label' => '📈 Trending',       'badge' => 'bg-orange-100 text-orange-700','hover' => 'hover:text-orange-600'],
                    ['key' => 'editors_pick', 'label' => "✍️ Editor's Pick", 'badge' => 'bg-emerald-100 text-emerald-700','hover' => 'hover:text-emerald-600'],
                ] as $section)
                    <div class="p-5">
                        <div class="mb-3 flex items-center gap-2">
                            <span class="rounded-md px-2 py-0.5 text-[10px] font-black uppercase tracking-wider {{ $section['badge'] }}">
                                {{ $section['label'] }}
                            </span>
                            <span class="text-[10px] text-slate-400">{{ $featured[$section['key']]->count() }} posts</span>
                        </div>
                        @forelse ($featured[$section['key']] as $post)
                            <div class="mb-2">
                                <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate
                                   class="block truncate text-xs font-semibold text-slate-700 dark:text-slate-200 {{ $section['hover'] }}">
                                    {{ $post->translation()?->title ?? '#'.$post->id }}
                                </a>
                                <p class="text-[10px] text-slate-400">
                                    {{ $post->author?->name }}
                                    @if ($post->view_count) · {{ number_format((int)$post->view_count) }} views @endif
                                </p>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400">None set.</p>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Team Activity --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-gradient-to-br from-sky-500 to-indigo-500 text-white">
                        <i data-lucide="users" class="h-4 w-4"></i>
                    </span>
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Team Activity</h3>
                </div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">last 7 days</span>
            </header>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($this->authorActivity as $author)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-gradient-to-br from-sky-500 to-indigo-500 text-[10px] font-bold uppercase text-white">
                            {{ mb_substr($author->name, 0, 1) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $author->name }}</p>
                            <p class="text-[11px] text-slate-400">
                                Last published {{ \Carbon\Carbon::parse($author->last_published_at)->diffForHumans() }}
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-black text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">
                            {{ $author->published_count }} post{{ $author->published_count !== 1 ? 's' : '' }}
                        </span>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-xs text-slate-500">No team activity in the last 7 days.</li>
                @endforelse
            </ul>
        </div>

        {{-- Divider before personal section --}}
        <div class="flex items-center gap-3">
            <div class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></div>
            <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400">My Personal Stats</span>
            <div class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></div>
        </div>

    @endif

    {{-- ================================================================
         PER-USER SECTION (visible to everyone, including admins)
    ================================================================ --}}
    @php($s = $this->myPostStats)

    @canany(['posts.create', 'posts.view'])
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
            @foreach ([
                ['label' => 'My Drafts',           'value' => $s['drafts'],           'sub' => null,                                    'icon' => 'file',         'color' => 'from-slate-400 to-slate-600'],
                ['label' => 'Pending Review',       'value' => $s['pending'],          'sub' => null,                                    'icon' => 'hourglass',    'color' => 'from-amber-500 to-orange-500'],
                ['label' => 'Scheduled',            'value' => $s['scheduled'],        'sub' => null,                                    'icon' => 'calendar-clock','color' => 'from-violet-500 to-fuchsia-500'],
                ['label' => 'Published This Month', 'value' => $s['published_month'],  'sub' => number_format($s['published_total']).' all-time','icon' => 'check-circle','color' => 'from-emerald-500 to-teal-500'],
                ['label' => 'Views · 30d',          'value' => $s['views_30d'],        'sub' => number_format($s['views_total']).' all-time','icon' => 'eye',       'color' => 'from-pink-500 to-rose-500'],
                ['label' => 'Comments on My Posts', 'value' => $s['comments_on_my_posts'],'sub' => $s['pending_comments_on_my_posts'] > 0 ? $s['pending_comments_on_my_posts'].' pending' : null,'icon' => 'message-circle','color' => 'from-sky-500 to-indigo-500'],
            ] as $tile)
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ $tile['label'] }}</span>
                        <span class="grid h-7 w-7 place-items-center rounded-lg bg-gradient-to-br {{ $tile['color'] }} text-white">
                            <i data-lucide="{{ $tile['icon'] }}" class="h-3.5 w-3.5"></i>
                        </span>
                    </div>
                    <p class="mt-1 text-2xl font-black tabular-nums">{{ number_format($tile['value']) }}</p>
                    @if ($tile['sub'])
                        <p class="text-[10px] {{ str_contains($tile['sub'], 'pending') ? 'font-bold text-amber-600' : 'text-slate-400' }}">
                            {{ $tile['sub'] }}
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    @endcanany

    <div class="grid gap-6 lg:grid-cols-[1fr_300px]">
        {{-- LEFT --}}
        <div class="space-y-6">

            {{-- My recent posts --}}
            @canany(['posts.create', 'posts.view'])
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">My Recent Posts</h3>
                        <a href="{{ route('admin.posts.index') }}" wire:navigate class="text-xs font-semibold text-indigo-600 hover:underline">All my posts →</a>
                    </header>
                    <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($this->myRecentPosts as $post)
                            @php($status = $post->status?->value)
                            @php($statusColor = match ($status) {
                                'published'         => 'bg-emerald-100 text-emerald-700',
                                'scheduled'         => 'bg-violet-100 text-violet-700',
                                'pending_review'    => 'bg-amber-100 text-amber-700',
                                'in_review'         => 'bg-indigo-100 text-indigo-700',
                                'approved'          => 'bg-sky-100 text-sky-700',
                                'changes_requested' => 'bg-orange-100 text-orange-700',
                                'rejected'          => 'bg-rose-100 text-rose-700',
                                default             => 'bg-slate-100 text-slate-600',
                            })
                            <li class="flex items-start gap-3 px-5 py-3 hover:bg-slate-50/60">
                                <span class="rounded-md px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider {{ $statusColor }}">
                                    {{ str_replace('_', ' ', $status ?? '—') }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate
                                       class="block truncate text-sm font-semibold text-slate-800 hover:text-indigo-600 dark:text-slate-100">
                                        {{ $post->translation()?->title ?? '#'.$post->id }}
                                    </a>
                                    <p class="text-[11px] text-slate-500">
                                        {{ $post->category?->translation()?->name ?? 'Uncategorised' }}
                                        · {{ $post->updated_at?->diffForHumans() }}
                                    </p>
                                </div>
                                @if ($post->view_count)
                                    <span class="inline-flex items-center gap-1 text-[11px] text-slate-500">
                                        <i data-lucide="eye" class="h-3 w-3"></i> {{ number_format((int) $post->view_count) }}
                                    </span>
                                @endif
                            </li>
                        @empty
                            <li class="px-5 py-10 text-center">
                                <i data-lucide="file-plus-2" class="mx-auto h-8 w-8 text-slate-300"></i>
                                <p class="mt-2 text-sm font-semibold text-slate-600">No posts yet.</p>
                                @can('posts.create')
                                    <a href="{{ route('admin.posts.create') }}" wire:navigate
                                       class="mt-3 inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                                        <i data-lucide="plus" class="h-3.5 w-3.5"></i> Create your first post
                                    </a>
                                @endcan
                            </li>
                        @endforelse
                    </ul>
                </div>
            @endcanany

            {{-- My publishing trend --}}
            @canany(['posts.create', 'posts.view'])
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="mb-3">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">My publishing · last 14 days</h3>
                        <p class="text-[11px] text-slate-500">Posts you've moved to Published.</p>
                    </div>
                    <div class="relative h-48">
                        <canvas id="my-trend-chart"></canvas>
                    </div>
                </div>
            @endcanany

            {{-- My top posts + categories --}}
            <div class="grid gap-4 md:grid-cols-2">
                @canany(['posts.create', 'posts.view'])
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">My Top Posts</h3>
                        </header>
                        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($this->myTopPosts as $i => $p)
                                <li class="flex items-center gap-3 px-5 py-2.5">
                                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-gradient-to-br from-pink-500 to-rose-500 text-[10px] font-black text-white">
                                        #{{ $i + 1 }}
                                    </span>
                                    <a href="{{ route('admin.posts.edit', $p) }}" wire:navigate
                                       class="min-w-0 flex-1 truncate text-sm font-semibold text-slate-800 hover:text-indigo-600 dark:text-slate-100">
                                        {{ $p->translation()?->title ?? '#'.$p->id }}
                                    </a>
                                    <span class="font-mono text-xs font-bold tabular-nums text-pink-700">{{ number_format((int) $p->view_count) }}</span>
                                </li>
                            @empty
                                <li class="px-5 py-8 text-center text-xs text-slate-500">No published posts yet.</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">My Categories · 90 days</h3>
                        </header>
                        <ul class="space-y-2 px-5 py-4">
                            @php($maxCat = $this->myTopCategories->max('c') ?: 1)
                            @forelse ($this->myTopCategories as $row)
                                <li>
                                    <div class="mb-0.5 flex items-center justify-between text-xs">
                                        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $row->name }}</span>
                                        <span class="font-mono text-slate-500">{{ $row->c }}</span>
                                    </div>
                                    <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                        <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-violet-500"
                                             style="width: {{ (int)(($row->c / $maxCat) * 100) }}%"></div>
                                    </div>
                                </li>
                            @empty
                                <li class="py-4 text-center text-xs text-slate-500">No category data yet.</li>
                            @endforelse
                        </ul>
                    </div>
                @endcanany
            </div>

        </div>

        {{-- RIGHT SIDEBAR --}}
        <aside class="space-y-6">

            {{-- AI spend --}}
            @can('ai.use')
                @php($ai = $this->myAiSpend)
                <div class="rounded-2xl border border-violet-200 bg-gradient-to-br from-violet-50 to-fuchsia-50 p-5 shadow-sm dark:border-violet-500/30 dark:from-violet-500/10 dark:to-fuchsia-500/10">
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="flex items-center gap-2 text-sm font-bold text-violet-900 dark:text-violet-200">
                            <i data-lucide="sparkles" class="h-4 w-4"></i> My AI · this month
                        </h3>
                        @can('ai.reports')
                            <a href="{{ route('admin.ai.usage-reports') }}" wire:navigate class="text-[10px] font-bold uppercase tracking-wider text-violet-700 hover:underline dark:text-violet-300">Report →</a>
                        @endcan
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-violet-700/70">Calls</p>
                            <p class="text-xl font-black tabular-nums text-violet-900 dark:text-violet-200">{{ number_format($ai['calls']) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-violet-700/70">Tokens</p>
                            <p class="text-xl font-black tabular-nums text-violet-900 dark:text-violet-200">{{ number_format($ai['tokens']) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-violet-700/70">Cost</p>
                            <p class="text-xl font-black tabular-nums text-violet-900 dark:text-violet-200">${{ number_format($ai['cost'], 4) }}</p>
                        </div>
                    </div>
                </div>
            @endcan

            {{-- Profile completeness --}}
            @php($pc = $this->profileCompleteness)
            @if ($pc['score'] < 100)
                <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-5 shadow-sm dark:border-amber-500/30 dark:bg-amber-500/10">
                    <div class="mb-3 flex items-center justify-between">
                        <h3 class="flex items-center gap-2 text-sm font-bold text-amber-900 dark:text-amber-200">
                            <i data-lucide="user-cog" class="h-4 w-4"></i> Finish your profile
                        </h3>
                        <span class="rounded-full bg-amber-200 px-2 py-0.5 text-[10px] font-bold text-amber-900">{{ $pc['score'] }}%</span>
                    </div>
                    <div class="mb-3 h-2 overflow-hidden rounded-full bg-amber-200/70">
                        <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-orange-500" style="width: {{ $pc['score'] }}%"></div>
                    </div>
                    <ul class="space-y-1.5 text-xs">
                        @foreach ($pc['missing'] as $item)
                            <li>
                                <a href="{{ $item['url'] }}" wire:navigate
                                   class="flex items-center gap-2 rounded-lg px-1 py-1 hover:bg-amber-100">
                                    <i data-lucide="circle-dashed" class="h-3.5 w-3.5 text-amber-700"></i>
                                    <span class="text-amber-900">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Notifications --}}
            @php($n = $this->notificationStats)
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Notifications</h3>
                    <a href="{{ route('notifications.index') }}" wire:navigate class="text-xs font-semibold text-indigo-600 hover:underline">
                        {{ $n['unread'] > 0 ? $n['unread'].' unread →' : 'View all →' }}
                    </a>
                </header>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($n['latest'] as $notif)
                        @php($data = $notif->data ?? [])
                        <li class="flex items-start gap-3 px-5 py-3 {{ $notif->read_at ? '' : 'bg-indigo-50/40' }}">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-600">
                                <i data-lucide="{{ $data['icon'] ?? 'bell' }}" class="h-3.5 w-3.5"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ $data['url'] ?? '#' }}"
                                   class="block truncate text-xs font-semibold text-slate-700 hover:text-indigo-600">
                                    {{ $data['title'] ?? $data['message'] ?? 'Notification' }}
                                </a>
                                <p class="mt-0.5 text-[10px] text-slate-500">{{ $notif->created_at?->diffForHumans() }}</p>
                            </div>
                            @if ($notif->read_at === null)
                                <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-500"></span>
                            @endif
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-xs text-slate-500">No notifications.</li>
                    @endforelse
                </ul>
            </div>

            {{-- Recent logins --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                    <h3 class="flex items-center gap-2 text-sm font-bold text-slate-800 dark:text-slate-100">
                        <i data-lucide="shield-check" class="h-4 w-4 text-emerald-500"></i> Recent Sign-ins
                    </h3>
                </header>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($this->recentLogins as $login)
                        <li class="px-5 py-2.5">
                            <p class="truncate text-xs font-semibold text-slate-700 dark:text-slate-200">
                                {{ $login->browser ?? 'Unknown' }}
                                <span class="ml-1 font-mono text-[10px] font-normal text-slate-500">{{ $login->ip_address ?? '—' }}</span>
                            </p>
                            <p class="text-[10px] text-slate-500">
                                {{ $login->login_at?->diffForHumans() ?? $login->created_at?->diffForHumans() }}
                            </p>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-xs text-slate-500">No sign-ins recorded yet.</li>
                    @endforelse
                </ul>
            </div>

        </aside>
    </div>
</div>

@script
<script>
    const renderMyTrendChart = () => {
        const el = document.getElementById('my-trend-chart');
        if (!el || typeof Chart === 'undefined') return;
        if (window._myTrendChart) window._myTrendChart.destroy();

        const labels = @json($this->myPublishingChart['labels']);
        const data   = @json($this->myPublishingChart['counts']);

        window._myTrendChart = new Chart(el, {
            type: 'bar',
            data: { labels, datasets: [{ data,
                backgroundColor: 'rgba(139,92,246,0.55)',
                borderColor: 'rgb(139,92,246)',
                borderRadius: 4,
            }] },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                },
            },
        });
    };
    renderMyTrendChart();
    Livewire.hook('morph.updated', () => renderMyTrendChart());
</script>
@endscript
