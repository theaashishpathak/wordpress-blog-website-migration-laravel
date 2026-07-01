@php
    $locale = app(\App\Support\LocaleResolver::class)->current();
    $total = $this->follows->total();
@endphp

<div class="space-y-8">
    <x-visitor.page-header
        eyebrow="Following"
        title="Followed Authors"
        description="{{ $total }} {{ \Illuminate\Support\Str::plural('writer', $total) }} you follow." />

    @if ($this->follows->isEmpty())
        <x-visitor.empty-state
            icon="pen-tool"
            title="No authors yet."
            description="Visit an author's profile page and tap “Follow” to subscribe to their work.">
            <x-slot:action>
                <a href="{{ route('frontend.home', ['locale' => $locale?->code]) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                    <i data-lucide="compass" class="h-4 w-4"></i>
                    Discover writers
                </a>
            </x-slot:action>
        </x-visitor.empty-state>
    @else
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($this->follows as $follow)
                @php($author = $follow->author)
                @continue (! $author)
                <article class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700">
                    <a href="{{ route('frontend.author', ['locale' => $locale?->code, 'user' => $author->id]) }}"
                       class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-slate-900 text-base font-black uppercase text-white dark:bg-slate-100 dark:text-slate-900">
                        {{ mb_substr($author->name, 0, 1) }}
                    </a>
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('frontend.author', ['locale' => $locale?->code, 'user' => $author->id]) }}"
                           class="line-clamp-1 text-sm font-bold text-slate-900 hover:text-emerald-700 dark:text-slate-100 dark:hover:text-emerald-300"
                           style="font-family: 'Playfair Display', serif;">
                            {{ $author->name }}
                        </a>
                        @if ($author->job_title)
                            <p class="line-clamp-1 text-[11px] text-slate-500 dark:text-slate-400">{{ $author->job_title }}</p>
                        @endif
                        <p class="mt-0.5 text-[10px] text-slate-400 dark:text-slate-500">Followed {{ $follow->created_at?->diffForHumans() }}</p>
                    </div>

                    <div class="flex items-center gap-1">
                        <button type="button" wire:click="toggleNotify({{ $follow->id }})"
                                class="grid h-8 w-8 place-items-center rounded-md transition
                                       {{ $follow->notify_on_publish
                                          ? 'text-emerald-700 dark:text-emerald-400'
                                          : 'text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800' }}"
                                title="{{ $follow->notify_on_publish ? 'Notifications on' : 'Muted — click to enable' }}">
                            <i data-lucide="{{ $follow->notify_on_publish ? 'bell' : 'bell-off' }}" class="h-3.5 w-3.5"></i>
                        </button>
                        <button type="button" wire:click="unfollow({{ $author->id }})"
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
