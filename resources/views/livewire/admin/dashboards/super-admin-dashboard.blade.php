<div class="mx-auto max-w-7xl space-y-8 px-4 py-6 sm:px-6 lg:px-8">
    @php($me = $this->me)
    @php($stats = $this->siteStats)
    @php($alerts = $this->alerts)

    {{-- ================================================================
         WELCOME BANNER
    ================================================================ --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-600 via-violet-600 to-fuchsia-600 p-8 text-white shadow-2xl">
        <div class="absolute inset-y-0 right-0 w-1/2 bg-white/10 blur-3xl"></div>
        <div class="relative grid gap-8 lg:grid-cols-[1.15fr_0.9fr] lg:items-center">
            <div class="space-y-6">
                <p class="text-xs font-semibold uppercase tracking-[0.32em] text-white/75">
                    ⚡ Command Centre
                </p>
                <div class="space-y-4">
                    <h1 class="max-w-3xl text-4xl font-black tracking-tight text-white sm:text-5xl">
                        Welcome back, {{ $me->name }} 👋
                    </h1>
                    <div class="flex flex-wrap items-center gap-2 text-xs text-white/80">
                        <span class="inline-flex items-center rounded-full bg-white/20 px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.16em]">
                            Super Admin
                        </span>
                        @if ($me->job_title)
                            <span>{{ $me->job_title }}</span>
                        @endif
                        @if ($me->department)
                            <span>· {{ $me->department->name }}</span>
                        @endif
                        <span>· {{ now()->format('l, F j, Y') }}</span>
                    </div>
                </div>
            </div>

            {{-- Quick action buttons --}}
            <div class="grid w-full gap-3 sm:grid-cols-2">
                @foreach ($this->quickActions as $action)
                    <a href="{{ $action['url'] }}" wire:navigate
                       class="inline-flex min-h-[52px] items-center justify-center gap-2 rounded-3xl border border-white/20 bg-white/10 px-4 text-sm font-semibold text-white transition duration-200 hover:-translate-y-0.5 hover:bg-white/20 hover:shadow-lg">
                        <i data-lucide="{{ $action['icon'] }}" class="h-4 w-4"></i>
                        <span>{{ $action['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ================================================================
         ALERTS & NOTIFICATIONS
    ================================================================ --}}
    @if($alerts->isNotEmpty())
        <div class="space-y-2">
            @foreach($alerts as $alert)
                <div class="rounded-2xl border-l-4 {{ match($alert->severity) {
                    'high' => 'border-rose-500 bg-rose-50/60 dark:bg-rose-500/10',
                    'medium' => 'border-amber-500 bg-amber-50/60 dark:bg-amber-500/10',
                    'low' => 'border-blue-500 bg-blue-50/60 dark:bg-blue-500/10',
                    default => 'border-slate-500 bg-slate-50/60 dark:bg-slate-500/10',
                } }} p-4 shadow-sm">
                    <div class="flex items-start gap-3">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg {{ match($alert->severity) {
                            'high' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300',
                            'medium' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
                            'low' => 'bg-blue-100 text-blue-700 dark:bg-blue-500/20 dark:text-blue-300',
                            default => 'bg-slate-100 text-slate-700 dark:bg-slate-500/20 dark:text-slate-300',
                        } }}">
                            <i data-lucide="{{ $alert->icon }}" class="h-4 w-4"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ $alert->url }}" wire:navigate
                               class="text-sm font-bold text-slate-800 hover:text-indigo-600 dark:text-slate-100">
                                {{ $alert->title }}
                            </a>
                            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $alert->message }}</p>
                        </div>
                        <a href="{{ $alert->url }}" wire:navigate
                           class="shrink-0 rounded-lg px-3 py-1.5 text-xs font-semibold {{ match($alert->severity) {
                               'high' => 'bg-rose-100 text-rose-700 hover:bg-rose-200 dark:bg-rose-500/20 dark:text-rose-300',
                               'medium' => 'bg-amber-100 text-amber-700 hover:bg-amber-200 dark:bg-amber-500/20 dark:text-amber-300',
                               'low' => 'bg-blue-100 text-blue-700 hover:bg-blue-200 dark:bg-blue-500/20 dark:text-blue-300',
                               default => 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-500/20 dark:text-slate-300',
                           } }}">
                            View →
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ================================================================
         SITE-WIDE STATISTICS
    ================================================================ --}}
    <div>
        <div class="mb-4 flex items-center gap-3">
            <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">📊 Platform Overview</p>
            <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Posts</span>
                    <span class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 text-white">
                        <i data-lucide="newspaper" class="h-4 w-4"></i>
                    </span>
                </div>
                <p class="mt-2 text-3xl font-black tabular-nums text-slate-900 dark:text-slate-100">
                    {{ number_format($stats['total_posts']) }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Published</span>
                    <span class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-500 text-white">
                        <i data-lucide="check-circle" class="h-4 w-4"></i>
                    </span>
                </div>
                <p class="mt-2 text-3xl font-black tabular-nums text-slate-900 dark:text-slate-100">
                    {{ number_format($stats['published']) }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Pending Review</span>
                    <span class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br from-amber-500 to-orange-500 text-white">
                        <i data-lucide="hourglass" class="h-4 w-4"></i>
                    </span>
                </div>
                <p class="mt-2 text-3xl font-black tabular-nums text-slate-900 dark:text-slate-100">
                    {{ number_format($stats['pending_review']) }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Scheduled</span>
                    <span class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br from-violet-500 to-purple-500 text-white">
                        <i data-lucide="calendar-clock" class="h-4 w-4"></i>
                    </span>
                </div>
                <p class="mt-2 text-3xl font-black tabular-nums text-slate-900 dark:text-slate-100">
                    {{ number_format($stats['scheduled']) }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Authors</span>
                    <span class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br from-sky-500 to-indigo-500 text-white">
                        <i data-lucide="users" class="h-4 w-4"></i>
                    </span>
                </div>
                <p class="mt-2 text-3xl font-black tabular-nums text-slate-900 dark:text-slate-100">
                    {{ number_format($stats['total_authors']) }}
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-md dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Total Views</span>
                    <span class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br from-pink-500 to-rose-500 text-white">
                        <i data-lucide="eye" class="h-4 w-4"></i>
                    </span>
                </div>
                <p class="mt-2 text-3xl font-black tabular-nums text-slate-900 dark:text-slate-100">
                    {{ number_format($stats['total_views']) }}
                </p>
            </div>
        </div>
    </div>

    {{-- ================================================================
         CONTENT PIPELINE (Full Workflow)
    ================================================================ --}}
    <div>
        <div class="mb-4 flex items-center gap-3">
            <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">📋 Content Pipeline</p>
            <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
        </div>

        <div class="grid gap-4 lg:grid-cols-4">
            {{-- Drafts --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <header class="border-b border-slate-100 bg-slate-50/60 px-4 py-3 dark:border-slate-800 dark:bg-slate-800/50">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="grid h-6 w-6 place-items-center rounded-lg bg-slate-500 text-white">
                                <i data-lucide="file" class="h-3 w-3"></i>
                            </span>
                            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">Drafts</h4>
                        </div>
                        <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold dark:bg-slate-700">{{ $this->draftPosts->count() }}</span>
                    </div>
                </header>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($this->draftPosts as $post)
                        <li class="px-4 py-2.5 hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                            <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate
                               class="block truncate text-xs font-semibold text-slate-700 hover:text-indigo-600 dark:text-slate-200">
                                {{ $post->translation()?->title ?? '#'.$post->id }}
                            </a>
                            <p class="text-[9px] text-slate-400">{{ $post->author?->name }} · {{ $post->updated_at?->diffForHumans() }}</p>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-xs text-slate-500">No drafts</li>
                    @endforelse
                </ul>
            </div>

            {{-- Pending Review --}}
            <div class="overflow-hidden rounded-2xl border border-amber-200 bg-white shadow-sm dark:border-amber-800/40 dark:bg-slate-900">
                <header class="border-b border-amber-100 bg-amber-50/60 px-4 py-3 dark:border-amber-800/30 dark:bg-amber-900/20">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="grid h-6 w-6 place-items-center rounded-lg bg-amber-500 text-white">
                                <i data-lucide="hourglass" class="h-3 w-3"></i>
                            </span>
                            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">Pending Review</h4>
                        </div>
                        <span class="rounded-full bg-amber-200 px-2 py-0.5 text-[10px] font-bold dark:bg-amber-500/30">{{ $this->pendingReviewPosts->count() }}</span>
                    </div>
                </header>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($this->pendingReviewPosts as $post)
                        <li class="px-4 py-2.5 hover:bg-amber-50/40 dark:hover:bg-amber-900/10">
                            <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate
                               class="block truncate text-xs font-semibold text-slate-700 hover:text-amber-600 dark:text-slate-200">
                                {{ $post->translation()?->title ?? '#'.$post->id }}
                            </a>
                            <p class="text-[9px] text-slate-400">{{ $post->author?->name }} · {{ $post->updated_at?->diffForHumans() }}</p>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-xs text-slate-500">All clear!</li>
                    @endforelse
                </ul>
            </div>

            {{-- Scheduled --}}
            <div class="overflow-hidden rounded-2xl border border-violet-200 bg-white shadow-sm dark:border-violet-800/40 dark:bg-slate-900">
                <header class="border-b border-violet-100 bg-violet-50/60 px-4 py-3 dark:border-violet-800/30 dark:bg-violet-900/20">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="grid h-6 w-6 place-items-center rounded-lg bg-violet-500 text-white">
                                <i data-lucide="calendar-clock" class="h-3 w-3"></i>
                            </span>
                            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">Scheduled</h4>
                        </div>
                        <span class="rounded-full bg-violet-200 px-2 py-0.5 text-[10px] font-bold dark:bg-violet-500/30">{{ $this->scheduledPosts->count() }}</span>
                    </div>
                </header>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($this->scheduledPosts as $post)
                        <li class="px-4 py-2.5 hover:bg-violet-50/40 dark:hover:bg-violet-900/10">
                            <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate
                               class="block truncate text-xs font-semibold text-slate-700 hover:text-violet-600 dark:text-slate-200">
                                {{ $post->translation()?->title ?? '#'.$post->id }}
                            </a>
                            <p class="text-[9px] text-slate-400">
                                {{ $post->author?->name }} · {{ $post->scheduled_at?->format('M j, H:i') }}
                            </p>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-xs text-slate-500">No scheduled posts</li>
                    @endforelse
                </ul>
            </div>

            {{-- Ready to Publish --}}
            <div class="overflow-hidden rounded-2xl border border-emerald-200 bg-white shadow-sm dark:border-emerald-800/40 dark:bg-slate-900">
                <header class="border-b border-emerald-100 bg-emerald-50/60 px-4 py-3 dark:border-emerald-800/30 dark:bg-emerald-900/20">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="grid h-6 w-6 place-items-center rounded-lg bg-emerald-500 text-white">
                                <i data-lucide="check-circle" class="h-3 w-3"></i>
                            </span>
                            <h4 class="text-sm font-bold text-slate-800 dark:text-slate-100">Ready to Publish</h4>
                        </div>
                        <span class="rounded-full bg-emerald-200 px-2 py-0.5 text-[10px] font-bold dark:bg-emerald-500/30">{{ $this->readyToPublishPosts->count() }}</span>
                    </div>
                </header>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($this->readyToPublishPosts as $post)
                        <li class="px-4 py-2.5 hover:bg-emerald-50/40 dark:hover:bg-emerald-900/10">
                            <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate
                               class="block truncate text-xs font-semibold text-slate-700 hover:text-emerald-600 dark:text-slate-200">
                                {{ $post->translation()?->title ?? '#'.$post->id }}
                            </a>
                            <p class="text-[9px] text-slate-400">{{ $post->author?->name }} · {{ $post->updated_at?->diffForHumans() }}</p>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-xs text-slate-500">No posts ready</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    {{-- ================================================================
         SCHEDULED PUBLISHING WIDGET
    ================================================================ --}}
    @php($schedule = $this->scheduledWidget)
    @if($schedule['upcoming']->isNotEmpty() || $schedule['today'] > 0)
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-violet-500 to-purple-500 text-white">
                        <i data-lucide="clock" class="h-4 w-4"></i>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Scheduled Publishing</h3>
                        <p class="text-[10px] text-slate-500">{{ $schedule['today'] }} post(s) scheduled for today</p>
                    </div>
                </div>
                @if($schedule['missed']->isNotEmpty())
                    <span class="rounded-full bg-rose-100 px-3 py-1 text-[10px] font-bold text-rose-700 dark:bg-rose-500/20 dark:text-rose-300">
                        ⚠️ {{ $schedule['missed']->count() }} missed
                    </span>
                @endif
            </div>

            @if($schedule['next'])
                <div class="mb-4 rounded-xl bg-gradient-to-r from-violet-50 to-purple-50 p-4 dark:from-violet-500/10 dark:to-purple-500/10">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-violet-600 dark:text-violet-400">Next publication</p>
                    <div class="mt-1 flex items-center justify-between">
                        <span class="font-bold text-slate-800 dark:text-slate-100">
                            {{ $schedule['next']->translation()?->title ?? '#'.$schedule['next']->id }}
                        </span>
                        <span class="rounded-full bg-violet-200 px-3 py-1 text-xs font-bold text-violet-700 dark:bg-violet-500/30 dark:text-violet-300">
                            {{ Carbon\Carbon::parse($schedule['next']->scheduled_at)->diffForHumans() }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500">{{ $schedule['next']->author?->name }} · {{ Carbon\Carbon::parse($schedule['next']->scheduled_at)->format('M j, Y H:i') }}</p>
                </div>
            @endif

            @if($schedule['upcoming']->isNotEmpty())
                <div>
                    <p class="mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400">Upcoming</p>
                    <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($schedule['upcoming'] as $post)
                            <li class="flex items-center justify-between py-2">
                                <div>
                                    <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate
                                       class="text-xs font-semibold text-slate-700 hover:text-violet-600 dark:text-slate-200">
                                        {{ $post->translation()?->title ?? '#'.$post->id }}
                                    </a>
                                    <p class="text-[9px] text-slate-400">{{ $post->author?->name }}</p>
                                </div>
                                <span class="text-xs font-mono text-violet-600 dark:text-violet-400">
                                    {{ Carbon\Carbon::parse($post->scheduled_at)->format('M j, H:i') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    {{-- ================================================================
         RECENT PUBLISHING ACTIVITY
    ================================================================ --}}
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Recently Published --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">🕐 Recently Published</h3>
                <a href="{{ route('admin.posts.index') }}" wire:navigate class="text-xs font-semibold text-indigo-600 hover:underline">View all →</a>
            </header>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($this->recentlyPublished as $post)
                    <li class="flex items-start gap-3 px-5 py-3 hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate
                               class="block truncate text-sm font-semibold text-slate-800 hover:text-indigo-600 dark:text-slate-100">
                                {{ $post->translation()?->title ?? '#'.$post->id }}
                            </a>
                            <p class="text-[11px] text-slate-500">
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

        {{-- Recently Updated --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">✏️ Recently Updated</h3>
                <a href="{{ route('admin.posts.index') }}" wire:navigate class="text-xs font-semibold text-indigo-600 hover:underline">View all →</a>
            </header>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($this->recentlyUpdated as $post)
                    <li class="flex items-start gap-3 px-5 py-3 hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate
                               class="block truncate text-sm font-semibold text-slate-800 hover:text-indigo-600 dark:text-slate-100">
                                {{ $post->translation()?->title ?? '#'.$post->id }}
                            </a>
                            <p class="text-[11px] text-slate-500">
                                <span class="font-medium text-slate-700 dark:text-slate-300">{{ $post->author?->name ?? '—' }}</span>
                                @if ($post->category?->translation()?->name)
                                    · {{ $post->category->translation()->name }}
                                @endif
                                · Updated {{ $post->updated_at?->diffForHumans() }}
                            </p>
                        </div>
                        <span class="rounded-md px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider {{ match($post->status?->value) {
                            'published' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
                            'scheduled' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/20 dark:text-violet-300',
                            'pending_review' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
                            'draft' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                            default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                        } }}">
                            {{ str_replace('_', ' ', $post->status?->value ?? '—') }}
                        </span>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-xs text-slate-500">No posts updated yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- ================================================================
         ACTIVITY FEED (Timeline)
    ================================================================ --}}
    <div>
        <div class="mb-4 flex items-center gap-3">
            <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">🔄 Recent Activity Feed</p>
            <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($this->activityFeed as $activity)
                    <li class="flex items-center gap-4 px-5 py-3 hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-gradient-to-br {{ $activity->color }} text-white">
                            <i data-lucide="{{ $activity->icon }}" class="h-4 w-4"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ $activity->url }}" wire:navigate
                               class="truncate text-sm font-semibold text-slate-800 hover:text-indigo-600 dark:text-slate-100">
                                @if($activity->type === 'login')
                                    {{ $activity->user }} {{ $activity->action }}
                                @else
                                    {{ $activity->title }}
                                @endif
                            </a>
                            <p class="text-[11px] text-slate-500">
                                <span class="font-medium text-slate-700 dark:text-slate-300">{{ $activity->user }}</span>
                                · {{ $activity->action }}
                                @if($activity->status && $activity->type !== 'comment')
                                    · Status: <span class="font-semibold">{{ $activity->status }}</span>
                                @endif
                                @if($activity->type === 'comment')
                                    · <span class="{{ match($activity->status) {
                                        'approved' => 'text-emerald-600',
                                        'pending' => 'text-amber-600',
                                        'spam' => 'text-rose-600',
                                        default => 'text-slate-600',
                                    } }}">{{ $activity->status ?? 'new' }}</span>
                                @endif
                                · {{ $activity->timestamp?->diffForHumans() }}
                            </p>
                        </div>
                        <span class="text-[9px] text-slate-400">{{ $activity->timestamp?->format('H:i') }}</span>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-xs text-slate-500">No recent activity.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- ================================================================
         PERFORMANCE INSIGHTS (Analytics)
    ================================================================ --}}
    <div>
        <div class="mb-4 flex items-center gap-3">
            <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">📈 Performance Insights</p>
            <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Publishing Stats --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h4 class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Publishing Stats</h4>
                @php($pubStats = $this->publishingStats)
                <div class="mt-3 space-y-2">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2 dark:border-slate-800">
                        <span class="text-sm text-slate-600 dark:text-slate-400">This Week</span>
                        <span class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ $pubStats['weekly'] }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2 dark:border-slate-800">
                        <span class="text-sm text-slate-600 dark:text-slate-400">This Month</span>
                        <span class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ $pubStats['monthly'] }}</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2 dark:border-slate-800">
                        <span class="text-sm text-slate-600 dark:text-slate-400">This Year</span>
                        <span class="text-lg font-bold text-slate-900 dark:text-slate-100">{{ $pubStats['yearly'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold text-slate-700 dark:text-slate-300">Total Published</span>
                        <span class="text-xl font-black text-emerald-600 dark:text-emerald-400">{{ number_format($pubStats['total']) }}</span>
                    </div>
                </div>
            </div>

            {{-- Most Viewed Posts --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">🔥 Most Viewed Posts</h3>
                </header>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($this->mostViewedPosts as $i => $p)
                        <li class="flex items-center gap-3 px-5 py-2.5">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-gradient-to-br from-amber-500 to-orange-500 text-[10px] font-black text-white">
                                #{{ $i + 1 }}
                            </span>
                            <a href="{{ route('admin.posts.edit', $p) }}" wire:navigate
                               class="min-w-0 flex-1 truncate text-sm font-semibold text-slate-800 hover:text-indigo-600 dark:text-slate-100">
                                {{ $p->translation()?->title ?? '#'.$p->id }}
                            </a>
                            <span class="font-mono text-xs font-bold tabular-nums text-amber-700 dark:text-amber-300">
                                {{ number_format((int) $p->view_count) }}
                            </span>
                        </li>
                    @empty
                        <li class="px-5 py-8 text-center text-xs text-slate-500">No posts yet.</li>
                    @endforelse
                </ul>
            </div>

            {{-- Top Categories --}}
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">📂 Top Categories</h3>
                </header>
                <ul class="space-y-3 px-5 py-4">
                    @php($topCats = $this->topCategories)
                    @php($maxCat = $topCats->max('post_count') ?: 1)
                    @forelse ($topCats as $cat)
                        <li>
                            <div class="mb-0.5 flex items-center justify-between text-xs">
                                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $cat->category_name }}</span>
                                <div class="flex items-center gap-3">
                                    <span class="font-mono text-slate-500">{{ $cat->post_count }} posts</span>
                                    <span class="font-mono text-xs text-slate-400">{{ number_format($cat->total_views) }} views</span>
                                </div>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-violet-500"
                                     style="width: {{ (int)(($cat->post_count / $maxCat) * 100) }}%"></div>
                            </div>
                        </li>
                    @empty
                        <li class="py-4 text-center text-xs text-slate-500">No categories yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    {{-- ================================================================
         TEAM PERFORMANCE
    ================================================================ --}}
    <div>
        <div class="mb-4 flex items-center gap-3">
            <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">👥 Team Performance</p>
            <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/60 text-[10px] font-bold uppercase tracking-wider text-slate-500 dark:border-slate-800 dark:bg-slate-800/50">
                            <th class="px-5 py-3">Author</th>
                            <th class="px-5 py-3 text-center">Published</th>
                            <th class="px-5 py-3 text-center">Total Views</th>
                            <th class="px-5 py-3 text-center">Avg Views</th>
                            <th class="px-5 py-3">First Published</th>
                            <th class="px-5 py-3">Last Activity</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($this->authorPerformance as $author)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-gradient-to-br from-sky-500 to-indigo-500 text-[10px] font-bold uppercase text-white">
                                            {{ mb_substr($author->name, 0, 1) }}
                                        </span>
                                        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $author->name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-center">
                                    <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
                                        {{ $author->published_count }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-center font-mono text-sm text-slate-600 dark:text-slate-400">
                                    {{ number_format($author->total_views) }}
                                </td>
                                <td class="px-5 py-3 text-center font-mono text-sm text-slate-600 dark:text-slate-400">
                                    {{ $author->published_count > 0 ? number_format(ceil($author->total_views / $author->published_count)) : 0 }}
                                </td>
                                <td class="px-5 py-3 text-xs text-slate-500">
                                    {{ $author->first_published_at ? Carbon\Carbon::parse($author->first_published_at)->format('M j, Y') : '—' }}
                                </td>
                                <td class="px-5 py-3 text-xs text-slate-500">
                                    {{ $author->last_published_at ? Carbon\Carbon::parse($author->last_published_at)->diffForHumans() : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-xs text-slate-500">No author data available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ================================================================
         PUBLISHING TREND CHART
    ================================================================ --}}
    @php($chart = $this->publishingChart)
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">📈 Publishing Trend · Last 30 Days</h3>
                <p class="text-[11px] text-slate-500">Posts across all statuses</p>
            </div>
            <div class="flex items-center gap-4 text-[10px]">
                <div class="flex items-center gap-1.5">
                    <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span class="text-slate-600 dark:text-slate-400">Published</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="inline-block h-2 w-2 rounded-full bg-violet-500"></span>
                    <span class="text-slate-600 dark:text-slate-400">Scheduled</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="inline-block h-2 w-2 rounded-full bg-slate-400"></span>
                    <span class="text-slate-600 dark:text-slate-400">Drafts</span>
                </div>
            </div>
        </div>
        <div class="relative h-64">
            <canvas id="publishing-chart"></canvas>
        </div>
    </div>
</div>

@script
<script>
    const renderPublishingChart = () => {
        const el = document.getElementById('publishing-chart');
        if (!el || typeof Chart === 'undefined') return;
        if (window._publishingChart) window._publishingChart.destroy();

        const labels = @json($this->publishingChart['labels']);
        const published = @json($this->publishingChart['published']);
        const scheduled = @json($this->publishingChart['scheduled']);
        const draft = @json($this->publishingChart['draft']);

        window._publishingChart = new Chart(el, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Published',
                        data: published,
                        backgroundColor: 'rgba(16, 185, 129, 0.6)',
                        borderColor: 'rgb(16, 185, 129)',
                        borderRadius: 4,
                    },
                    {
                        label: 'Scheduled',
                        data: scheduled,
                        backgroundColor: 'rgba(139, 92, 246, 0.6)',
                        borderColor: 'rgb(139, 92, 246)',
                        borderRadius: 4,
                    },
                    {
                        label: 'Drafts',
                        data: draft,
                        backgroundColor: 'rgba(148, 163, 184, 0.6)',
                        borderColor: 'rgb(148, 163, 184)',
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            maxTicksLimit: 15,
                            font: { size: 9 },
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, font: { size: 9 } },
                    },
                },
            },
        });
    };
    renderPublishingChart();
    Livewire.hook('morph.updated', () => renderPublishingChart());
</script>
@endscript
