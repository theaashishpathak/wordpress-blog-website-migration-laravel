<div class="space-y-8">
    <x-visitor.page-header
        eyebrow="Email &amp; Newsletter"
        title="Notification Preferences"
        description="Pick which events ping you and on which channel. In-app is the bell badge — email arrives at {{ auth()->user()->email }}.">
        <x-slot:actions>
            <button type="button" wire:click="toggleMasterEmailMute"
                    class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-xs font-bold transition
                           {{ $masterEmailMute
                              ? 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300'
                              : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800' }}">
                <i data-lucide="{{ $masterEmailMute ? 'mail-x' : 'mail-check' }}" class="h-3.5 w-3.5"></i>
                {{ $masterEmailMute ? 'Emails muted' : 'Mute all emails' }}
            </button>
        </x-slot:actions>
    </x-visitor.page-header>

    {{-- Event matrix --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <header class="grid grid-cols-[1fr_auto_auto] items-center gap-6 border-b border-slate-200 bg-slate-50 px-5 py-3 text-[10px] font-bold uppercase tracking-[0.22em] text-slate-500 dark:border-slate-800 dark:bg-slate-800/40">
            <span>Event</span>
            <span class="text-center">In-app</span>
            <span class="text-center">Email</span>
        </header>

        @foreach ($catalog as $key => $config)
            @php
                $hasInApp = in_array('in_app', $config['channels'], true);
                $hasEmail = in_array('email', $config['channels'], true);
                $inAppOn = $hasInApp ? (bool) ($prefs[$key]['in_app'] ?? false) : false;
                $emailOn = $hasEmail ? (bool) ($prefs[$key]['email'] ?? false) : false;
            @endphp
            <div class="grid grid-cols-[1fr_auto_auto] items-center gap-6 border-b border-slate-100 px-5 py-4 last:border-b-0 dark:border-slate-800">
                <div class="flex items-start gap-3">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-slate-50 text-slate-500 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700">
                        <i data-lucide="{{ $config['icon'] ?? 'bell' }}" class="h-4 w-4"></i>
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ $config['label'] }}</p>
                        <p class="mt-0.5 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ $config['description'] }}</p>
                    </div>
                </div>

                {{-- In-app toggle --}}
                <div class="flex justify-center">
                    @if ($hasInApp)
                        <button type="button" wire:click="toggle('{{ $key }}', 'in_app')"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition
                                       {{ $inAppOn ? 'bg-emerald-600' : 'bg-slate-200 dark:bg-slate-700' }}"
                                aria-label="Toggle in-app for {{ $config['label'] }}">
                            <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition
                                          {{ $inAppOn ? 'translate-x-5' : 'translate-x-0.5' }}"></span>
                        </button>
                    @else
                        <span class="text-[10px] font-bold uppercase text-slate-300 dark:text-slate-600">—</span>
                    @endif
                </div>

                {{-- Email toggle --}}
                <div class="flex justify-center">
                    @if ($hasEmail)
                        <button type="button" wire:click="toggle('{{ $key }}', 'email')"
                                wire:loading.attr="disabled"
                                @disabled($masterEmailMute)
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition
                                       {{ $emailOn && ! $masterEmailMute ? 'bg-emerald-600' : 'bg-slate-200 dark:bg-slate-700' }}
                                       {{ $masterEmailMute ? 'opacity-50 cursor-not-allowed' : '' }}"
                                aria-label="Toggle email for {{ $config['label'] }}">
                            <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition
                                          {{ $emailOn && ! $masterEmailMute ? 'translate-x-5' : 'translate-x-0.5' }}"></span>
                        </button>
                    @else
                        <span class="text-[10px] font-bold uppercase text-slate-300 dark:text-slate-600">—</span>
                    @endif
                </div>
            </div>
        @endforeach
    </section>

    <p class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs leading-relaxed text-slate-600 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-300">
        <i data-lucide="info" class="inline h-3 w-3"></i>
        Critical security emails (password resets, login alerts) are always sent regardless of your preferences here.
    </p>
</div>
