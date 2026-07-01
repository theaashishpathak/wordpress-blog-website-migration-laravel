@php
    $events = $this->events;
    $tabs = [
        'all' => 'All',
        'logins' => 'Sign-ins',
        'account' => 'Account',
    ];
@endphp

<div class="space-y-8">
    <x-visitor.page-header
        eyebrow="Settings"
        title="My Activity"
        description="A unified security timeline — every sign-in, account change, and security event tied to your account.">
        <x-slot:actions>
            <div class="inline-flex rounded-lg border border-slate-200 bg-slate-50 p-1 dark:border-slate-800 dark:bg-slate-900">
                @foreach ($tabs as $key => $label)
                    <button type="button" wire:click="setFilter('{{ $key }}')"
                            class="rounded-md px-3 py-1.5 text-xs font-bold transition {{ $filter === $key ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-950 dark:text-slate-100' : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </x-slot:actions>
    </x-visitor.page-header>

    @if ($events->isEmpty())
        <x-visitor.empty-state
            icon="clock"
            title="Nothing to show yet."
            description="Sign-ins, password changes, and other security events will gather here over time." />
    @else
        <div class="rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($events as $event)
                    @php
                        $iconMap = [
                            'login_success'                 => ['icon' => 'log-in',          'tone' => 'emerald'],
                            'login_failed'                  => ['icon' => 'shield-alert',    'tone' => 'rose'],
                            'login_logout'                  => ['icon' => 'log-out',         'tone' => 'slate'],
                            'password_updated'              => ['icon' => 'key',             'tone' => 'amber'],
                            'profile_updated'               => ['icon' => 'user-round',      'tone' => 'sky'],
                            'avatar_updated'                => ['icon' => 'image',           'tone' => 'sky'],
                            'sessions_revoked_all'          => ['icon' => 'shield-off',      'tone' => 'amber'],
                            'session_revoked'               => ['icon' => 'monitor-off',     'tone' => 'amber'],
                            'account_deletion_requested'    => ['icon' => 'alert-triangle',  'tone' => 'rose'],
                            'account_deletion_cancelled'    => ['icon' => 'undo-2',          'tone' => 'emerald'],
                            'data_export_requested'         => ['icon' => 'download',        'tone' => 'sky'],
                            'privacy_updated'               => ['icon' => 'lock',            'tone' => 'slate'],
                        ];
                        $iconInfo = $iconMap[$event->event] ?? ['icon' => 'circle', 'tone' => 'slate'];
                        $toneMap = [
                            'emerald' => 'bg-emerald-50 text-emerald-600 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30',
                            'rose'    => 'bg-rose-50 text-rose-600 ring-rose-200 dark:bg-rose-500/15 dark:text-rose-300 dark:ring-rose-500/30',
                            'amber'   => 'bg-amber-50 text-amber-600 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-300 dark:ring-amber-500/30',
                            'sky'     => 'bg-sky-50 text-sky-600 ring-sky-200 dark:bg-sky-500/15 dark:text-sky-300 dark:ring-sky-500/30',
                            'slate'   => 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700',
                        ];
                        $toneClasses = $toneMap[$iconInfo['tone']];
                    @endphp
                    <li class="flex items-start gap-3 px-5 py-4">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg ring-1 {{ $toneClasses }}">
                            <i data-lucide="{{ $iconInfo['icon'] }}" class="h-4 w-4"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <p class="text-sm font-bold text-slate-900 dark:text-slate-100">
                                    {{ $event->description }}
                                </p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">
                                    {{ $event->when?->diffForHumans() }}
                                    <span class="mx-1 text-slate-300 dark:text-slate-600">·</span>
                                    {{ $event->when?->format('d M Y · h:i A') }}
                                </p>
                            </div>
                            <p class="mt-0.5 flex flex-wrap items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                                @if ($event->ip)
                                    <span class="inline-flex items-center gap-1 font-mono">
                                        <i data-lucide="map-pin" class="h-3 w-3"></i> {{ $event->ip }}
                                    </span>
                                @endif
                                @if ($event->browser)
                                    <span class="inline-flex items-center gap-1">
                                        <i data-lucide="globe" class="h-3 w-3"></i> {{ $event->browser }}
                                    </span>
                                @endif
                                @if ($event->country)
                                    <span>{{ $event->country }}</span>
                                @endif
                            </p>
                            @if ($event->kind === 'account' && ! empty($event->meta))
                                <details class="mt-1.5 text-[11px] text-slate-500">
                                    <summary class="cursor-pointer hover:text-slate-700 dark:hover:text-slate-300">Details</summary>
                                    <pre class="mt-1 max-w-xl overflow-x-auto rounded-lg bg-slate-50 p-2 text-[10px] leading-relaxed text-slate-700 dark:bg-slate-800 dark:text-slate-200">{{ json_encode($event->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </details>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>

            @if ($events->count() >= $limit)
                <div class="border-t border-slate-200 px-5 py-3 text-center dark:border-slate-800">
                    <button type="button" wire:click="showMore"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 hover:underline dark:text-emerald-300">
                        Show older events
                        <i data-lucide="arrow-down" class="h-3 w-3"></i>
                    </button>
                </div>
            @endif
        </div>

        <p class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs leading-relaxed text-slate-600 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-300">
            <i data-lucide="info" class="inline h-3 w-3"></i>
            Don't recognise an event? Head to <a href="{{ route('visitor.settings.sessions') }}" class="font-bold text-emerald-700 hover:underline dark:text-emerald-300">Sessions &amp; Devices</a> to sign out remote sessions, or <a href="{{ route('visitor.settings.security') }}" class="font-bold text-emerald-700 hover:underline dark:text-emerald-300">Account &amp; Security</a> to change your password.
        </p>
    @endif
</div>
