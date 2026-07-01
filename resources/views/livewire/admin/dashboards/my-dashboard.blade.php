<div class="space-y-6">
    {{-- Welcome banner --}}
    @php($me = $this->me)
    <div class="rounded-3xl bg-gradient-to-br from-indigo-600 via-violet-600 to-fuchsia-600 p-6 text-white shadow-lg">
        <div class="flex flex-col items-start gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <img src="{{ $me->avatarUrl() }}" alt="{{ $me->name }}" class="h-16 w-16 rounded-2xl object-cover ring-4 ring-white/30">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-white/70">My Dashboard</p>
                    <h1 class="mt-0.5 text-2xl font-black tracking-tight" style="font-family: 'Playfair Display', serif;">
                        Welcome back, {{ $me->name }} 👋
                    </h1>
                    <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-white/80">
                        @if ($me->employee_id)
                            <span class="inline-flex items-center rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-semibold">{{ $me->employee_id }}</span>
                        @endif
                        @if ($me->job_title) <span>{{ $me->job_title }}</span> @endif
                        @if ($me->department) <span>· {{ $me->department->name }}</span> @endif
                        @if ($me->manager) <span>· Reports to <strong class="text-white">{{ $me->manager->name }}</strong></span> @endif
                        @if ($me->hire_date) <span>· Joined {{ \Illuminate\Support\Carbon::parse($me->hire_date)->format('M j, Y') }}</span> @endif
                    </p>
                </div>
            </div>

            {{-- Quick action chips --}}
            <div class="flex flex-wrap gap-2">
                @foreach ($this->quickActions as $action)
                    <a href="{{ $action['url'] }}" wire:navigate
                       class="inline-flex items-center gap-2 rounded-xl border border-white/30 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">
                        <i data-lucide="{{ $action['icon'] }}" class="h-4 w-4"></i> {{ $action['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    @php($s = $this->myPostStats)
    {{-- Per-user post tiles (only show when the user can author) --}}
    @canany(['posts.create', 'posts.view'])
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">My drafts</span>
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-gradient-to-br from-slate-400 to-slate-600 text-white">
                        <i data-lucide="file" class="h-3.5 w-3.5"></i>
                    </span>
                </div>
                <p class="mt-1 text-2xl font-black tabular-nums">{{ number_format($s['drafts']) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Pending review</span>
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-gradient-to-br from-amber-500 to-orange-500 text-white">
                        <i data-lucide="hourglass" class="h-3.5 w-3.5"></i>
                    </span>
                </div>
                <p class="mt-1 text-2xl font-black tabular-nums">{{ number_format($s['pending']) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Scheduled</span>
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-gradient-to-br from-violet-500 to-fuchsia-500 text-white">
                        <i data-lucide="calendar-clock" class="h-3.5 w-3.5"></i>
                    </span>
                </div>
                <p class="mt-1 text-2xl font-black tabular-nums">{{ number_format($s['scheduled']) }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Published · this month</span>
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-gradient-to-br from-emerald-500 to-teal-500 text-white">
                        <i data-lucide="check-circle" class="h-3.5 w-3.5"></i>
                    </span>
                </div>
                <p class="mt-1 text-2xl font-black tabular-nums">{{ number_format($s['published_month']) }}</p>
                <p class="text-[10px] text-slate-400">{{ number_format($s['published_total']) }} all-time</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Views · 30d</span>
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-gradient-to-br from-pink-500 to-rose-500 text-white">
                        <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                    </span>
                </div>
                <p class="mt-1 text-2xl font-black tabular-nums">{{ number_format($s['views_30d']) }}</p>
                <p class="text-[10px] text-slate-400">{{ number_format($s['views_total']) }} all-time</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Comments on my posts</span>
                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-gradient-to-br from-sky-500 to-indigo-500 text-white">
                        <i data-lucide="message-circle" class="h-3.5 w-3.5"></i>
                    </span>
                </div>
                <p class="mt-1 text-2xl font-black tabular-nums">{{ number_format($s['comments_on_my_posts']) }}</p>
                @if ($s['pending_comments_on_my_posts'] > 0)
                    <p class="text-[10px] font-bold text-amber-600">{{ $s['pending_comments_on_my_posts'] }} pending review</p>
                @endif
            </div>
        </div>
    @endcanany

    {{-- Main two-column body --}}
    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        {{-- LEFT COLUMN --}}
        <div class="space-y-6">
            {{-- My recent posts (full table) --}}
            @canany(['posts.create', 'posts.view'])
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">My recent posts</h3>
                        <a href="{{ route('admin.posts.index') }}" wire:navigate class="text-xs font-semibold text-indigo-600 hover:underline">All my posts →</a>
                    </header>
                    <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($this->myRecentPosts as $post)
                            @php($status = $post->status?->value)
                            @php($statusColor = match ($status) {
                                'published' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
                                'scheduled' => 'bg-violet-100 text-violet-700 dark:bg-violet-500/20 dark:text-violet-300',
                                'pending_review' => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
                                'approved' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300',
                                'changes_requested' => 'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-300',
                                'rejected' => 'bg-rose-100 text-rose-700 dark:bg-rose-500/20 dark:text-rose-300',
                                default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                            })
                            <li class="flex items-start gap-3 px-5 py-3 hover:bg-slate-50/60 dark:hover:bg-slate-800/30">
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
                                    <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-500">
                                        <i data-lucide="eye" class="h-3 w-3"></i> {{ number_format((int) $post->view_count) }}
                                    </span>
                                @endif
                            </li>
                        @empty
                            <li class="px-5 py-10 text-center">
                                <i data-lucide="file-plus-2" class="mx-auto h-8 w-8 text-slate-300"></i>
                                <p class="mt-2 text-sm font-semibold text-slate-700 dark:text-slate-200">You haven't published anything yet.</p>
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

            {{-- Personal publishing trend --}}
            @canany(['posts.create', 'posts.view'])
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="mb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">My publishing · last 14 days</h3>
                            <p class="text-[11px] text-slate-500">Posts you've moved to Published.</p>
                        </div>
                    </div>
                    <div class="relative h-48">
                        <canvas id="my-trend-chart"></canvas>
                    </div>
                </div>
            @endcanany

            {{-- My top posts + categories side by side --}}
            <div class="grid gap-4 md:grid-cols-2">
                {{-- Top posts --}}
                @canany(['posts.create', 'posts.view'])
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">My top posts</h3>
                        </header>
                        <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($this->myTopPosts as $i => $p)
                                <li class="flex items-center gap-3 px-5 py-2.5">
                                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-gradient-to-br from-pink-500 to-rose-500 text-[10px] font-black text-white">#{{ $i + 1 }}</span>
                                    <a href="{{ route('admin.posts.edit', $p) }}" wire:navigate
                                       class="min-w-0 flex-1 truncate text-sm font-semibold text-slate-800 hover:text-indigo-600 dark:text-slate-100">
                                        {{ $p->translation()?->title ?? '#'.$p->id }}
                                    </a>
                                    <span class="font-mono text-xs font-bold tabular-nums text-pink-700 dark:text-pink-300">{{ number_format((int) $p->view_count) }}</span>
                                </li>
                            @empty
                                <li class="px-5 py-8 text-center text-xs text-slate-500">No published posts yet.</li>
                            @endforelse
                        </ul>
                    </div>
                @endcanany

                {{-- Top categories --}}
                @canany(['posts.create', 'posts.view'])
                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <header class="border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                            <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">My categories · last 90 days</h3>
                        </header>
                        <ul class="space-y-2 px-5 py-4">
                            @php($maxCat = $this->myTopCategories->max('c') ?: 1)
                            @forelse ($this->myTopCategories as $row)
                                <li>
                                    <div class="mb-0.5 flex items-center justify-between text-xs">
                                        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $row->name }}</span>
                                        <span class="font-mono text-slate-500">{{ $row->c }} posts</span>
                                    </div>
                                    <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                        <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-violet-500"
                                             style="width: {{ (int) (($row->c / $maxCat) * 100) }}%"></div>
                                    </div>
                                </li>
                            @empty
                                <li class="py-4 text-center text-xs text-slate-500">You haven't written in any category yet.</li>
                            @endforelse
                        </ul>
                    </div>
                @endcanany
            </div>

            {{-- Recent comments on my posts --}}
            @canany(['posts.create', 'posts.view'])
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                        <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Comments on my posts</h3>
                        @if ($s['pending_comments_on_my_posts'] > 0 && auth()->user()?->can('comments.moderate'))
                            <a href="{{ route('admin.comments.index') }}" wire:navigate
                               class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-amber-700 hover:bg-amber-200 dark:bg-amber-500/20 dark:text-amber-300">
                                {{ $s['pending_comments_on_my_posts'] }} pending →
                            </a>
                        @endif
                    </header>
                    <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($this->recentCommentsOnMyPosts as $comment)
                            @php($commentStatusColor = match ($comment->status) {
                                'approved' => 'bg-emerald-100 text-emerald-700',
                                'pending' => 'bg-amber-100 text-amber-700',
                                'spam' => 'bg-rose-100 text-rose-700',
                                default => 'bg-slate-100 text-slate-700',
                            })
                            <li class="flex items-start gap-3 px-5 py-3">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-gradient-to-br from-sky-500 to-indigo-500 text-[10px] font-bold uppercase text-white">
                                    {{ mb_substr($comment->authorName(), 0, 1) }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs">
                                        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $comment->authorName() }}</span>
                                        on
                                        <span class="text-slate-600 dark:text-slate-300">"{{ \Illuminate\Support\Str::limit((string) ($comment->post?->translation()?->title ?? '#'.$comment->post_id), 50) }}"</span>
                                    </p>
                                    <p class="mt-0.5 truncate text-xs text-slate-500">{{ \Illuminate\Support\Str::limit(strip_tags((string) $comment->body), 110) }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="rounded-md px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider {{ $commentStatusColor }}">
                                        {{ $comment->status }}
                                    </span>
                                    <p class="mt-0.5 text-[10px] text-slate-400">{{ $comment->created_at?->diffForHumans() }}</p>
                                </div>
                            </li>
                        @empty
                            <li class="px-5 py-8 text-center text-xs text-slate-500">No comments on your posts yet.</li>
                        @endforelse
                    </ul>
                </div>
            @endcanany
        </div>

        {{-- RIGHT COLUMN (sidebar) --}}
        <aside class="space-y-6">
            {{-- AI spend card --}}
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
                    @if ($ai['last_used_at'])
                        <p class="mt-3 text-[10px] text-violet-700/70 dark:text-violet-300/70">
                            Last used {{ $ai['last_used_at']->diffForHumans() }}
                        </p>
                    @endif
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
                        <span class="rounded-full bg-amber-200 px-2 py-0.5 text-[10px] font-bold text-amber-900 dark:bg-amber-500/30 dark:text-amber-200">{{ $pc['score'] }}%</span>
                    </div>
                    <div class="mb-3 h-2 overflow-hidden rounded-full bg-amber-200/70 dark:bg-amber-500/20">
                        <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-orange-500" style="width: {{ $pc['score'] }}%"></div>
                    </div>
                    <ul class="space-y-1.5 text-xs">
                        @foreach ($pc['missing'] as $missing)
                            <li>
                                <a href="{{ $missing['url'] }}" wire:navigate
                                   class="flex items-center gap-2 rounded-lg px-1 py-1 hover:bg-amber-100 dark:hover:bg-amber-500/20">
                                    <i data-lucide="circle-dashed" class="h-3.5 w-3.5 text-amber-700 dark:text-amber-300"></i>
                                    <span class="text-amber-900 dark:text-amber-200">{{ $missing['label'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @else
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-5 shadow-sm dark:border-emerald-500/30 dark:bg-emerald-500/10">
                    <div class="flex items-center gap-2">
                        <i data-lucide="badge-check" class="h-5 w-5 text-emerald-600 dark:text-emerald-300"></i>
                        <h3 class="text-sm font-bold text-emerald-900 dark:text-emerald-200">Profile complete</h3>
                    </div>
                    <p class="mt-1 text-xs text-emerald-800 dark:text-emerald-300">Bio, avatar, social links, contact and 2FA are all set.</p>
                </div>
            @endif

            {{-- Notifications --}}
            @php($n = $this->notificationStats)
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Notifications</h3>
                    <a href="{{ route('notifications.index') }}" wire:navigate class="text-xs font-semibold text-indigo-600 hover:underline">
                        @if ($n['unread'] > 0)
                            {{ $n['unread'] }} unread →
                        @else
                            View all →
                        @endif
                    </a>
                </header>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($n['latest'] as $notif)
                        @php($data = $notif->data ?? [])
                        <li class="flex items-start gap-3 px-5 py-3 {{ $notif->read_at ? '' : 'bg-indigo-50/40 dark:bg-indigo-500/5' }}">
                            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                <i data-lucide="{{ $data['icon'] ?? 'bell' }}" class="h-3.5 w-3.5"></i>
                            </span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ $data['url'] ?? '#' }}"
                                   class="block truncate text-xs font-semibold text-slate-700 hover:text-indigo-600 dark:text-slate-200">
                                    {{ $data['title'] ?? $data['message'] ?? 'Notification' }}
                                </a>
                                <p class="mt-0.5 text-[10px] text-slate-500">{{ $notif->created_at?->diffForHumans() }}</p>
                            </div>
                            @if ($notif->read_at === null)
                                <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-500"></span>
                            @endif
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-xs text-slate-500">
                            <i data-lucide="bell-off" class="mx-auto h-5 w-5 text-slate-300"></i>
                            <p class="mt-1.5">No notifications.</p>
                        </li>
                    @endforelse
                </ul>
            </div>

            {{-- Recent logins --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                    <h3 class="flex items-center gap-2 text-sm font-bold text-slate-800 dark:text-slate-100">
                        <i data-lucide="shield-check" class="h-4 w-4 text-emerald-500"></i> Recent sign-ins
                    </h3>
                </header>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($this->recentLogins as $login)
                        <li class="px-5 py-2.5">
                            <p class="truncate text-xs font-semibold text-slate-700 dark:text-slate-200">
                                {{ $login->browser ?? 'Unknown' }}
                                <span class="ml-1 font-mono text-[10px] font-normal text-slate-500">{{ $login->ip_address ?? '—' }}</span>
                            </p>
                            <p class="truncate text-[10px] text-slate-500">
                                @if ($login->city || $login->country) {{ trim(($login->city ?? '').', '.($login->country ?? ''), ', ') }} · @endif
                                {{ $login->login_at?->diffForHumans() ?? $login->created_at?->diffForHumans() }}
                            </p>
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-xs text-slate-500">No sign-ins recorded yet.</li>
                    @endforelse
                </ul>
            </div>

            {{-- Team mini-card (managers only) --}}
            @if ($this->teamCount > 0)
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center gap-3">
                        <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-sky-500 to-indigo-500 text-white">
                            <i data-lucide="users" class="h-4 w-4"></i>
                        </span>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Direct reports</p>
                            <p class="text-2xl font-black tabular-nums">{{ number_format($this->teamCount) }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </aside>
    </div>
</div>

@script
<script>
    /**
     * Personal publishing sparkline. Re-renders on Livewire morph so
     * deltas show up immediately when the user navigates between
     * dashboards.
     */
    const renderMyTrendChart = () => {
        const el = document.getElementById('my-trend-chart');
        if (! el || typeof Chart === 'undefined') return;

        if (window._myTrendChart) {
            window._myTrendChart.destroy();
        }

        const labels = @json($this->myPublishingChart['labels']);
        const data = @json($this->myPublishingChart['counts']);

        window._myTrendChart = new Chart(el, {
            type: 'bar',
            data: { labels, datasets: [{
                data,
                backgroundColor: 'rgba(139, 92, 246, 0.55)',
                borderColor: 'rgb(139, 92, 246)',
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
