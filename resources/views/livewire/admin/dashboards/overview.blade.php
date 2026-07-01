<div class="space-y-6">
    {{-- Welcome banner --}}
    <div class="rounded-3xl bg-gradient-to-br from-indigo-600 via-violet-600 to-fuchsia-600 p-6 text-white shadow-lg">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-white/70">Newsroom Overview</p>
                <h1 class="mt-1 text-2xl font-black tracking-tight" style="font-family: 'Playfair Display', serif;">
                    Welcome back, {{ auth()->user()?->name }}
                </h1>
                <p class="mt-1 text-xs text-white/80">
                    Here's the pulse of your publication right now.
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('posts.create')
                    <a href="{{ route('admin.posts.create') }}" wire:navigate
                       class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-bold text-indigo-700 shadow-md hover:bg-indigo-50">
                        <i data-lucide="plus" class="h-4 w-4"></i> New Post
                    </a>
                @endcan
                @can('ai.use_writer')
                    <a href="{{ route('admin.ai.writer') }}" wire:navigate
                       class="inline-flex items-center gap-2 rounded-xl border border-white/30 bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20">
                        <i data-lucide="sparkles" class="h-4 w-4"></i> AI Writer
                    </a>
                @endcan
            </div>
        </div>
    </div>

    {{-- Tile row --}}
    @php($t = $this->tiles)
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        @foreach ([
            ['label' => 'Published this week', 'value' => $t['published_this_week'], 'icon' => 'newspaper', 'color' => 'from-indigo-500 to-violet-500'],
            ['label' => 'Pending review', 'value' => $t['pending_review'], 'icon' => 'hourglass', 'color' => 'from-amber-500 to-orange-500', 'url' => 'admin.editorial.queue'],
            ['label' => 'Pending comments', 'value' => $t['pending_comments'], 'icon' => 'message-square', 'color' => 'from-rose-500 to-pink-500', 'url' => 'admin.comments.index'],
            ['label' => 'Unread notifications', 'value' => $t['unread_notifications'], 'icon' => 'bell', 'color' => 'from-sky-500 to-cyan-500', 'url' => 'notifications.index'],
            ['label' => 'Newsletter confirmed', 'value' => $t['newsletter_confirmed'], 'icon' => 'mail-check', 'color' => 'from-emerald-500 to-teal-500', 'url' => 'admin.newsletter.subscribers'],
            ['label' => 'Active ads', 'value' => $t['active_ads'], 'icon' => 'dollar-sign', 'color' => 'from-lime-500 to-emerald-500', 'url' => 'admin.ads.index'],
        ] as $tile)
            @php($wrapper = isset($tile['url']) ? 'a' : 'div')
            <{{ $wrapper }}
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
            </{{ $wrapper }}>
        @endforeach
    </div>

    {{-- Publishing trend chart --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-3 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Publishing trend</h3>
                <p class="text-xs text-slate-500">Posts published, last 14 days</p>
            </div>
        </div>
        <div class="relative h-64">
            <canvas id="overview-trend-chart"></canvas>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        {{-- Recent posts --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Recent posts</h3>
                <a href="{{ route('admin.posts.index') }}" wire:navigate class="text-xs font-semibold text-indigo-600 hover:underline">All posts →</a>
            </header>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($this->recentPosts as $post)
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
                    <li class="flex items-start gap-3 px-5 py-3">
                        <span class="rounded-md px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider {{ $statusColor }}">
                            {{ str_replace('_', ' ', $status ?? '—') }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate
                               class="block truncate text-sm font-semibold text-slate-800 hover:text-indigo-600 dark:text-slate-100">
                                {{ $post->translation()?->title ?? '#'.$post->id }}
                            </a>
                            <p class="mt-0.5 text-[11px] text-slate-500">
                                {{ $post->author?->name ?? '—' }}
                                @if ($post->category?->translation()?->name)
                                    · {{ $post->category->translation()->name }}
                                @endif
                                · {{ $post->updated_at?->diffForHumans() }}
                            </p>
                        </div>
                        @if ($post->view_count)
                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-slate-500">
                                <i data-lucide="eye" class="h-3 w-3"></i>
                                {{ number_format((int) $post->view_count) }}
                            </span>
                        @endif
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-xs text-slate-500">No posts yet — create your first one.</li>
                @endforelse
            </ul>
        </div>

        {{-- Trending posts --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Trending · last 30 days</h3>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">by views</span>
            </header>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($this->trendingPosts as $i => $post)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-gradient-to-br from-pink-500 to-rose-500 text-[10px] font-black text-white">
                            #{{ $i + 1 }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate
                               class="block truncate text-sm font-semibold text-slate-800 hover:text-indigo-600 dark:text-slate-100">
                                {{ $post->translation()?->title ?? '#'.$post->id }}
                            </a>
                            <p class="text-[11px] text-slate-500">
                                {{ $post->category?->translation()?->name ?? 'Uncategorised' }}
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-1 rounded-md bg-pink-50 px-2 py-0.5 text-xs font-bold text-pink-700 dark:bg-pink-500/15 dark:text-pink-300">
                            {{ number_format((int) $post->view_count) }}
                        </span>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-xs text-slate-500">No trending posts yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Recent comments --}}
    @can('comments.moderate')
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <header class="flex items-center justify-between border-b border-slate-100 px-5 py-3 dark:border-slate-800">
                <h3 class="text-sm font-bold text-slate-800 dark:text-slate-100">Recent comments</h3>
                <a href="{{ route('admin.comments.index') }}" wire:navigate class="text-xs font-semibold text-indigo-600 hover:underline">Moderate →</a>
            </header>
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse ($this->recentComments as $comment)
                    @php($statusColor = match ($comment->status) {
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
                                <span class="text-slate-600 dark:text-slate-300">
                                    "{{ \Illuminate\Support\Str::limit((string) ($comment->post?->translation()?->title ?? '#'.$comment->post_id), 50) }}"
                                </span>
                            </p>
                            <p class="mt-0.5 truncate text-xs text-slate-500">{{ \Illuminate\Support\Str::limit(strip_tags((string) $comment->body), 110) }}</p>
                        </div>
                        <div class="text-right">
                            <span class="rounded-md px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider {{ $statusColor }}">
                                {{ $comment->status }}
                            </span>
                            <p class="mt-0.5 text-[10px] text-slate-400">{{ $comment->created_at?->diffForHumans() }}</p>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-xs text-slate-500">No comments yet.</li>
                @endforelse
            </ul>
        </div>
    @endcan
</div>

@script
<script>
    /**
     * Draws the 14-day publishing sparkline using Chart.js (loaded by the
     * layout). Re-runs after every Livewire morph so the chart matches
     * the current data.
     */
    const renderOverviewTrend = () => {
        const el = document.getElementById('overview-trend-chart');
        if (! el || typeof Chart === 'undefined') return;

        // Tear down any previous instance to avoid duplicate canvas use.
        if (window._overviewTrendChart) {
            window._overviewTrendChart.destroy();
        }

        const labels = @json($this->publishingTrend['labels']);
        const data = @json($this->publishingTrend['counts']);

        window._overviewTrendChart = new Chart(el, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    data,
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: 'rgb(99, 102, 241)',
                    pointRadius: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, ticks: { stepSize: 1 } },
                },
            },
        });
    };

    renderOverviewTrend();
    Livewire.hook('morph.updated', () => renderOverviewTrend());
</script>
@endscript
