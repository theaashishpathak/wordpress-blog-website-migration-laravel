@php
    $locale = app(\App\Support\LocaleResolver::class)->current();
    $tabs = [
        'all' => ['label' => 'All', 'count' => $counts['all']],
        'category' => ['label' => 'Categories', 'count' => $counts['category']],
        'tag' => ['label' => 'Tags', 'count' => $counts['tag']],
    ];
@endphp

<div class="space-y-8">
    <x-visitor.page-header
        eyebrow="Following"
        title="Followed Topics"
        description="Tags and categories you subscribe to. Toggle the bell to mute notifications.">
        <x-slot:actions>
            <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1 dark:border-slate-800 dark:bg-slate-900">
                @foreach ($tabs as $key => $tab)
                    <button type="button" wire:click="switchFilter('{{ $key }}')"
                            class="rounded-md px-3 py-1.5 text-xs font-bold transition {{ $filter === $key ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-950 dark:text-slate-100' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
                        {{ $tab['label'] }}
                        <span class="ml-1 text-slate-400">{{ $tab['count'] }}</span>
                    </button>
                @endforeach
            </div>
        </x-slot:actions>
    </x-visitor.page-header>

    @if ($this->follows->isEmpty())
        <x-visitor.empty-state
            icon="hash"
            title="No topics yet."
            description="Visit any category or tag page and tap “Follow” to subscribe.">
            <x-slot:action>
                <a href="{{ route('frontend.home', ['locale' => $locale?->code]) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                    <i data-lucide="compass" class="h-4 w-4"></i>
                    Discover topics
                </a>
            </x-slot:action>
        </x-visitor.empty-state>
    @else
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->follows as $follow)
                @php
                    $f = $follow->followable;
                    $isTag = $f && $follow->followable_type === \App\Models\Tag::class;
                    $name = $f ? ($f->translate('name') ?? ($f->name ?? '#'.$f->id)) : null;
                    $url = $f
                        ? ($isTag
                            ? route('frontend.tag', ['locale' => $locale?->code, 'tag' => $f->slug])
                            : route('frontend.category', ['locale' => $locale?->code, 'slug' => $f->translate('slug')]))
                        : null;
                @endphp
                @if (! $f)
                    @continue
                @endif

                <article class="group flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700">
                    <a href="{{ $url }}" class="flex min-w-0 flex-1 items-center gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-slate-50 text-slate-600 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                            <i data-lucide="{{ $isTag ? 'hash' : ($f->icon ?? 'folder') }}" class="h-4 w-4"></i>
                        </span>
                        <span class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">{{ $isTag ? 'Tag' : 'Category' }}</p>
                            <p class="line-clamp-1 text-sm font-bold text-slate-900 dark:text-slate-100" style="font-family: 'Playfair Display', serif;">
                                {{ $name }}
                            </p>
                        </span>
                    </a>

                    <div class="flex items-center gap-1">
                        <button type="button" wire:click="toggleNotify({{ $follow->id }})"
                                class="grid h-8 w-8 place-items-center rounded-md transition
                                       {{ $follow->notify_on_post
                                          ? 'text-emerald-700 dark:text-emerald-400'
                                          : 'text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                                title="{{ $follow->notify_on_post ? 'Notifications on' : 'Muted — click to enable' }}">
                            <i data-lucide="{{ $follow->notify_on_post ? 'bell' : 'bell-off' }}" class="h-3.5 w-3.5"></i>
                        </button>
                        <button type="button" wire:click="unfollow({{ $follow->id }})"
                                class="grid h-8 w-8 place-items-center rounded-md text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-500/10 dark:hover:text-rose-300"
                                title="Unfollow">
                            <i data-lucide="x" class="h-3.5 w-3.5"></i>
                        </button>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">{{ $this->follows->onEachSide(1)->links() }}</div>
    @endif
</div>
