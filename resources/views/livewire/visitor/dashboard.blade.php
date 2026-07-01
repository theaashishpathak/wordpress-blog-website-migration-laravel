@php
    $localeResolver = app(\App\Support\LocaleResolver::class);
    $currentLocale = $localeResolver->current();
    $joinedDays = $user->created_at?->diffInDays(now()) ?? 0;
    $firstName = explode(' ', (string) $user->name)[0] ?? $user->name;
@endphp

<div class="space-y-10">
    {{-- Editorial welcome lede --}}
    <section class="border-b border-slate-200 pb-8 dark:border-slate-800">
        <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.24em] text-emerald-700 dark:text-emerald-300">
            Welcome back
        </p>
        <h1 class="text-4xl font-black tracking-tight text-slate-900 md:text-5xl dark:text-slate-50"
            style="font-family: 'Playfair Display', serif;">
            Good to see you, {{ $firstName }}.
        </h1>
        <p class="mt-3 max-w-prose text-sm leading-relaxed text-slate-500 dark:text-slate-400">
            Reading with us since <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $user->created_at?->format('F Y') }}</span>
            @if ($joinedDays >= 7)
                <span class="text-slate-300 dark:text-slate-600">·</span> {{ $joinedDays }} days
            @endif
            . Pick up where you left off, or browse what's new.
        </p>

        <div class="mt-5 flex flex-wrap items-center gap-2">
            <a href="{{ route('frontend.home', ['locale' => $currentLocale?->code]) }}"
               class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                <i data-lucide="newspaper" class="h-4 w-4"></i>
                Browse latest articles
            </a>
            <a href="{{ route('visitor.recommendations') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-900">
                <i data-lucide="sparkles" class="h-4 w-4 text-emerald-600 dark:text-emerald-400"></i>
                See your For You feed
            </a>
        </div>
    </section>

    {{-- Live stat tiles — monochrome, calm. --}}
    @php
        $stats = [
            [
                'label' => 'Bookmarks',
                'icon' => 'bookmark',
                'value' => $user->bookmarks()->count(),
                'route' => route('visitor.bookmarks'),
            ],
            [
                'label' => 'Reading List',
                'icon' => 'clock',
                'value' => $user->readingListItems()->active()->count(),
                'route' => route('visitor.reading-list'),
            ],
            [
                'label' => 'Articles read',
                'icon' => 'book-open',
                'value' => $user->readingHistory()->count(),
                'route' => route('visitor.reading-history'),
            ],
            [
                'label' => 'Highlights',
                'icon' => 'highlighter',
                'value' => $user->highlights()->count(),
                'route' => route('visitor.highlights'),
            ],
            [
                'label' => 'My comments',
                'icon' => 'message-square',
                'value' => $user->comments_count ?? \App\Models\Comment::query()->where('user_id', $user->id)->count(),
                'route' => route('visitor.comments'),
            ],
            [
                'label' => 'Likes',
                'icon' => 'thumbs-up',
                'value' => $user->reactions()->where('type', 'like')->count(),
                'route' => route('visitor.reactions', ['type' => 'like']),
            ],
            [
                'label' => 'Dislikes',
                'icon' => 'thumbs-down',
                'value' => $user->reactions()->where('type', 'dislike')->count(),
                'route' => route('visitor.reactions', ['type' => 'dislike']),
            ],
            [
                'label' => 'For You',
                'icon' => 'sparkles',
                'value' => null,
                'route' => route('visitor.recommendations'),
                'is_cta' => true,
            ],
        ];
    @endphp
    <section>
        <p class="mb-4 text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">
            At a glance
        </p>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($stats as $tile)
                @php $isCta = ! empty($tile['is_cta']); @endphp
                <a href="{{ $tile['route'] }}" wire:navigate
                   class="group rounded-2xl border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700">
                    <div class="flex items-center justify-between">
                        <span class="grid h-9 w-9 place-items-center rounded-lg bg-slate-50 text-slate-600 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                            <i data-lucide="{{ $tile['icon'] }}" class="h-4 w-4 {{ $isCta ? 'text-emerald-600 dark:text-emerald-400' : '' }}"></i>
                        </span>
                        <i data-lucide="arrow-up-right" class="h-3.5 w-3.5 text-slate-300 transition group-hover:text-emerald-600 dark:text-slate-600 dark:group-hover:text-emerald-400"></i>
                    </div>
                    @if ($isCta)
                        <p class="mt-5 text-base font-black text-slate-900 dark:text-slate-50" style="font-family: 'Playfair Display', serif;">
                            Open feed
                        </p>
                    @else
                        <p class="mt-5 text-3xl font-black text-slate-900 dark:text-slate-50" style="font-family: 'Playfair Display', serif;">
                            {{ number_format((int) $tile['value']) }}
                        </p>
                    @endif
                    <p class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">{{ $tile['label'] }}</p>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Pending deletion banner --}}
    @if ($pendingDeletion ?? false)
        <section class="rounded-2xl border border-rose-200 bg-rose-50 p-5 dark:border-rose-500/40 dark:bg-rose-500/10">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-white text-rose-600 ring-1 ring-rose-200 dark:bg-rose-500/20 dark:text-rose-300 dark:ring-rose-500/30">
                        <i data-lucide="alert-triangle" class="h-4 w-4"></i>
                    </span>
                    <div>
                        <p class="text-sm font-black text-rose-900 dark:text-rose-100" style="font-family: 'Playfair Display', serif;">Account deletion is scheduled.</p>
                        <p class="mt-0.5 text-xs text-rose-700 dark:text-rose-300">Your account will be permanently deleted {{ $pendingDeletion->scheduled_for?->diffForHumans() }}.</p>
                    </div>
                </div>
                <a href="{{ route('visitor.data.delete') }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-xs font-bold text-emerald-700 ring-1 ring-emerald-200 hover:bg-emerald-50 dark:bg-slate-900 dark:text-emerald-300 dark:ring-emerald-500/30">
                    <i data-lucide="undo-2" class="h-3 w-3"></i>
                    Cancel deletion
                </a>
            </div>
        </section>
    @endif

    {{-- For You preview --}}
    @if (! empty($picks) && $picks->isNotEmpty())
        <section>
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3 border-b border-slate-200 pb-3 dark:border-slate-800">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">For you</p>
                    <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-900 dark:text-slate-100" style="font-family: 'Playfair Display', serif;">Picked from today's reading</h2>
                </div>
                <a href="{{ route('visitor.recommendations') }}"
                   class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 hover:underline dark:text-emerald-300">
                    See full feed
                    <i data-lucide="arrow-right" class="h-3 w-3"></i>
                </a>
            </div>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($picks as $pick)
                    <x-frontend.post-card :post="$pick" :showExcerpt="false" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Activity + profile two-column --}}
    <section class="grid gap-6 lg:grid-cols-[1.5fr_1fr]">
        {{-- Recent activity --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
            <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">Latest</p>
            <h2 class="mt-1 text-xl font-black tracking-tight text-slate-900 dark:text-slate-100" style="font-family: 'Playfair Display', serif;">Recent activity</h2>

            @php
                $activityList = $recentActivity ?? collect();
            @endphp
            @if ($activityList->isEmpty())
                <div class="mt-5 rounded-xl border border-dashed border-slate-200 px-6 py-10 text-center dark:border-slate-700">
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200" style="font-family: 'Playfair Display', serif;">Nothing to show yet.</p>
                    <p class="mt-1 text-xs text-slate-500">Your comments, likes, and bookmarks will gather here over time.</p>
                    <a href="{{ route('frontend.home', ['locale' => $currentLocale?->code]) }}"
                       class="mt-4 inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 hover:underline dark:text-emerald-300">
                        Start exploring
                        <i data-lucide="arrow-right" class="h-3 w-3"></i>
                    </a>
                </div>
            @else
                <ul class="mt-5 divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($activityList as $event)
                        @php
                            $post = $event['post'];
                            $t = $post?->translation()
                                ?? $post?->translations->firstWhere('language_id', $post->default_language_id)
                                ?? $post?->translations->first();
                        @endphp
                        @if ($t && ! empty($t->slug))
                            <li class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-slate-50 text-slate-500 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700">
                                    <i data-lucide="{{ $event['icon'] }}" class="h-3.5 w-3.5"></i>
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">{{ $event['label'] }}</p>
                                    <a href="{{ route('frontend.post.show', ['locale' => $currentLocale?->code, 'slug' => $t->slug]) }}"
                                       class="line-clamp-1 text-sm font-bold text-slate-900 hover:text-emerald-700 dark:text-slate-100 dark:hover:text-emerald-300"
                                       style="font-family: 'Playfair Display', serif;">
                                        {{ $t->title }}
                                    </a>
                                </div>
                                <span class="shrink-0 text-[11px] text-slate-400 dark:text-slate-500">{{ $event['occurred_at']?->diffForHumans() }}</span>
                            </li>
                        @endif
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Profile snippet + quick actions --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
            <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400 dark:text-slate-500">You</p>
            <h2 class="mt-1 text-xl font-black tracking-tight text-slate-900 dark:text-slate-100" style="font-family: 'Playfair Display', serif;">Your reader profile</h2>

            <div class="mt-5 flex items-center gap-4">
                <span class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-slate-900 text-lg font-black uppercase text-white dark:bg-slate-100 dark:text-slate-900">
                    {{ mb_substr($user->name, 0, 1) }}
                </span>
                <div class="min-w-0">
                    <p class="truncate text-base font-bold text-slate-900 dark:text-slate-100">{{ $user->name }}</p>
                    <p class="truncate text-sm text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                    <p class="mt-1.5 inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                        Visitor
                    </p>
                </div>
            </div>

            <div class="mt-6 grid gap-2 sm:grid-cols-2">
                <a href="{{ route('frontend.author', ['locale' => $currentLocale?->code, 'user' => $user->id]) }}"
                   class="inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                    <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                    Public profile
                </a>
                <a href="{{ route('visitor.settings.profile') }}" wire:navigate
                   class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white transition hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                    <i data-lucide="settings" class="h-3.5 w-3.5"></i>
                    Edit profile
                </a>
            </div>
        </div>
    </section>
</div>
