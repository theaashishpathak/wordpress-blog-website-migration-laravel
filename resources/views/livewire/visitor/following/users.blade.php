@php
    $locale = app(\App\Support\LocaleResolver::class)->current();
    $isFollowing = $direction === 'following';
@endphp

<div class="space-y-8">
    <x-visitor.page-header
        eyebrow="Following"
        title="Readers"
        description="Other readers you follow and those who follow you.">
        <x-slot:actions>
            <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1 dark:border-slate-800 dark:bg-slate-900">
                <button type="button" wire:click="switchDirection('following')"
                        class="rounded-md px-3 py-1.5 text-xs font-bold transition {{ $isFollowing ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-950 dark:text-slate-100' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
                    Following <span class="ml-1 text-slate-400">{{ $counts['following'] }}</span>
                </button>
                <button type="button" wire:click="switchDirection('followers')"
                        class="rounded-md px-3 py-1.5 text-xs font-bold transition {{ ! $isFollowing ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-950 dark:text-slate-100' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
                    Followers <span class="ml-1 text-slate-400">{{ $counts['followers'] }}</span>
                </button>
            </div>
        </x-slot:actions>
    </x-visitor.page-header>

    @if ($this->rows->isEmpty())
        <x-visitor.empty-state
            icon="users"
            title="{{ $isFollowing ? 'Not following anyone yet.' : 'No followers yet.' }}"
            description="{{ $isFollowing
                ? 'Visit another reader\'s profile (from their comments under any article) and tap “Follow”.'
                : 'Comment, highlight, or react to articles — fellow readers may follow you back.' }}" />
    @else
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($this->rows as $row)
                @php($u = $isFollowing ? $row->followed : $row->follower)
                @continue (! $u)
                <article class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-slate-900 text-base font-black uppercase text-white dark:bg-slate-100 dark:text-slate-900">
                        {{ mb_substr($u->name, 0, 1) }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="line-clamp-1 text-sm font-bold text-slate-900 dark:text-slate-100" style="font-family: 'Playfair Display', serif;">
                            {{ $u->name }}
                        </p>
                        @if ($u->bio)
                            <p class="line-clamp-1 text-[11px] text-slate-500 dark:text-slate-400">{{ $u->bio }}</p>
                        @endif
                        <p class="mt-0.5 text-[10px] text-slate-400 dark:text-slate-500">
                            {{ $isFollowing ? 'Followed' : 'Followed you' }} {{ $row->created_at?->diffForHumans() }}
                        </p>
                    </div>

                    @if ($isFollowing)
                        <button type="button" wire:click="unfollow({{ $u->id }})"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 dark:border-slate-700 dark:text-slate-300 dark:hover:border-rose-500/40 dark:hover:bg-rose-500/10 dark:hover:text-rose-300">
                            <i data-lucide="user-minus" class="h-3 w-3"></i>
                            Unfollow
                        </button>
                    @endif
                </article>
            @endforeach
        </div>

        <div class="mt-6">{{ $this->rows->onEachSide(1)->links() }}</div>
    @endif
</div>
