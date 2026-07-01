<div class="space-y-6">
    {{-- Welcome strip --}}
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-gradient-to-r from-indigo-600 via-violet-600 to-fuchsia-600 p-6 text-white shadow-lg">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-white/70">Author Portal</p>
            <h1 class="mt-1 text-2xl font-black tracking-tight" style="font-family: 'Playfair Display', serif;">
                Welcome back, {{ $this->user->name }}
            </h1>
            <p class="mt-1 text-xs text-white/80">
                You have <strong>{{ $this->counts['draft'] }}</strong> draft(s) and <strong>{{ $this->counts['pending'] }}</strong> awaiting review.
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('posts.create')
                <a href="{{ route('admin.posts.create') }}" wire:navigate
                   class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-bold text-indigo-700 shadow-md hover:bg-indigo-50">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    New Post
                </a>
            @endcan
            <a href="{{ route('admin.posts.index') }}" wire:navigate
               class="inline-flex items-center gap-2 rounded-xl border border-white/30 bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20">
                <i data-lucide="files" class="h-4 w-4"></i>
                My Posts
            </a>
            <a href="{{ route('author.profile') }}" wire:navigate
               class="inline-flex items-center gap-2 rounded-xl border border-white/30 bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20">
                <i data-lucide="user-cog" class="h-4 w-4"></i>
                Profile
            </a>
        </div>
    </div>

    {{-- Stat tiles --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php
            $tiles = [
                ['label' => 'Drafts', 'value' => $this->counts['draft'], 'icon' => 'file', 'color' => 'slate'],
                ['label' => 'Pending Review', 'value' => $this->counts['pending'], 'icon' => 'hourglass', 'color' => 'amber'],
                ['label' => 'Published', 'value' => $this->counts['published'], 'icon' => 'check-circle', 'color' => 'emerald'],
                ['label' => 'Total Views', 'value' => number_format($this->totalViews), 'icon' => 'eye', 'color' => 'indigo'],
            ];
        @endphp

        @foreach ($tiles as $tile)
            @php
                $colorClass = match ($tile['color']) {
                    'amber' => 'from-amber-500 to-orange-500',
                    'emerald' => 'from-emerald-500 to-teal-500',
                    'indigo' => 'from-indigo-500 to-violet-500',
                    default => 'from-slate-400 to-slate-500',
                };
            @endphp
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $tile['label'] }}</p>
                    <span class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br {{ $colorClass }} text-white">
                        <i data-lucide="{{ $tile['icon'] }}" class="h-4 w-4"></i>
                    </span>
                </div>
                <p class="mt-3 text-3xl font-black text-slate-900 dark:text-slate-100">{{ $tile['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
        {{-- Recent posts --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">Recent Posts</h2>
                <a href="{{ route('admin.posts.index') }}" wire:navigate class="text-xs font-semibold text-indigo-600 hover:underline">See all →</a>
            </div>

            @if ($this->recentPosts->isEmpty())
                <div class="rounded-xl border-2 border-dashed border-slate-200 p-8 text-center text-xs text-slate-400 dark:border-slate-700">
                    No posts yet. Create your first article.
                </div>
            @else
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($this->recentPosts as $post)
                        @php
                            $t = $post->translation();
                            $statusColor = match ($post->status->value) {
                                'published' => 'bg-emerald-100 text-emerald-700',
                                'pending_review','in_review' => 'bg-amber-100 text-amber-700',
                                'changes_requested' => 'bg-orange-100 text-orange-700',
                                'rejected' => 'bg-rose-100 text-rose-700',
                                'approved','scheduled' => 'bg-indigo-100 text-indigo-700',
                                default => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <li class="flex items-center gap-3 py-3">
                            <a href="{{ route('admin.posts.edit', $post) }}" wire:navigate class="flex-1 truncate">
                                <p class="truncate text-sm font-semibold text-slate-800 hover:text-indigo-600 dark:text-slate-100">
                                    {{ $t?->title ?? '#'.$post->id }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ $post->category?->translate('name') ?? 'Uncategorised' }} · {{ $post->updated_at?->diffForHumans() }}
                                </p>
                            </a>
                            <span class="rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $statusColor }}">
                                {{ $post->status->label() }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- AI usage + Actionable notes --}}
        <div class="space-y-4">
            <div class="rounded-2xl border border-violet-200 bg-gradient-to-br from-violet-50 to-fuchsia-50 p-5 shadow-sm dark:border-violet-500/30 dark:from-violet-500/10 dark:to-fuchsia-500/10">
                <div class="mb-3 flex items-center gap-2">
                    <span class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-500 text-white">
                        <i data-lucide="sparkles" class="h-4 w-4"></i>
                    </span>
                    <h3 class="text-sm font-bold text-violet-900 dark:text-violet-200">AI usage this month</h3>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-violet-700/70">Calls</p>
                        <p class="text-xl font-black text-violet-900 dark:text-violet-200">{{ $this->aiUsageThisMonth['calls'] }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-violet-700/70">Tokens</p>
                        <p class="text-xl font-black text-violet-900 dark:text-violet-200">{{ number_format($this->aiUsageThisMonth['tokens']) }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-violet-700/70">Cost</p>
                        <p class="text-xl font-black text-violet-900 dark:text-violet-200">${{ number_format($this->aiUsageThisMonth['cost'], 4) }}</p>
                    </div>
                </div>
            </div>

            @if ($this->actionableNotes->isNotEmpty())
                <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-5 shadow-sm dark:border-amber-500/30 dark:bg-amber-500/10">
                    <h3 class="mb-3 text-sm font-bold text-amber-900 dark:text-amber-200">Needs your attention</h3>
                    <ul class="space-y-2">
                        @foreach ($this->actionableNotes as $note)
                            @php($t = $note->post?->translation())
                            <li class="rounded-lg bg-white/70 p-2 text-xs dark:bg-slate-900/40">
                                <a href="{{ $note->post ? route('admin.posts.edit', $note->post) : '#' }}" wire:navigate
                                   class="font-semibold text-amber-900 hover:underline dark:text-amber-200">
                                    {{ $t?->title ?? '#'.$note->post?->id }}
                                </a>
                                <p class="mt-0.5 italic text-amber-800/80 dark:text-amber-300/80">"{{ \Illuminate\Support\Str::limit($note->body, 120) }}"</p>
                                <p class="mt-0.5 text-[10px] text-amber-700/70 dark:text-amber-300/60">
                                    — {{ $note->author?->name ?? 'system' }} · {{ $note->created_at?->diffForHumans() }}
                                </p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
