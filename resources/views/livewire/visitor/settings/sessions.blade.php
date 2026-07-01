<div class="space-y-8">
    <x-visitor.page-header
        eyebrow="Settings"
        title="Sessions &amp; Devices"
        description="Every browser currently signed into your account. Sign out remotely if you don't recognise one." />

    @if ($driver !== 'database')
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-500/30 dark:bg-amber-500/10">
            <p class="flex items-center gap-2 text-sm font-bold text-amber-800 dark:text-amber-200">
                <i data-lucide="info" class="h-4 w-4"></i>
                Session list unavailable on the <span class="font-mono">{{ $driver }}</span> driver
            </p>
            <p class="mt-1.5 text-xs leading-relaxed text-amber-700 dark:text-amber-300/80">
                Switch <span class="font-mono">SESSION_DRIVER=database</span> in your environment to see every active session here. Until then, the "sign out other devices" form below still works.
            </p>
        </div>
    @elseif ($this->sessions->isEmpty())
        <x-visitor.empty-state
            icon="monitor-off"
            title="No active sessions."
            description="When you sign in from a new device, it will appear here." />
    @else
        <div class="space-y-3">
            @foreach ($this->sessions as $s)
                <article class="flex flex-wrap items-center gap-3 rounded-2xl border p-4 transition
                                {{ $s->is_current
                                   ? 'border-emerald-300 bg-emerald-50/50 dark:border-emerald-500/40 dark:bg-emerald-500/5'
                                   : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700' }}">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-slate-50 text-slate-600 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                        <i data-lucide="{{ $s->is_mobile ? 'smartphone' : 'monitor' }}" class="h-4 w-4"></i>
                    </span>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-bold text-slate-900 dark:text-slate-100">
                                {{ $s->browser }} on {{ $s->platform }}
                            </p>
                            @if ($s->is_current)
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">
                                    <i data-lucide="check" class="h-2.5 w-2.5"></i>
                                    This device
                                </span>
                            @endif
                        </div>
                        <p class="mt-0.5 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                            <span class="font-mono">{{ $s->ip ?? 'unknown ip' }}</span>
                            <span class="text-slate-300 dark:text-slate-600">·</span>
                            <span>Last active {{ $s->last_activity->diffForHumans() }}</span>
                        </p>
                    </div>

                    @unless ($s->is_current)
                        <button type="button" wire:click="revoke('{{ $s->id }}')"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 dark:border-slate-700 dark:text-slate-300 dark:hover:border-rose-500/40 dark:hover:bg-rose-500/10 dark:hover:text-rose-300">
                            <i data-lucide="log-out" class="h-3.5 w-3.5"></i>
                            Sign out
                        </button>
                    @endunless
                </article>
            @endforeach
        </div>
    @endif

    <x-visitor.section title="Sign out everywhere else" description="Keeps this browser logged in and signs out every other session.">
        <form wire:submit="logoutOthers" class="flex flex-wrap items-end gap-3">
            <div class="min-w-[240px] flex-1">
                <label class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Confirm password</label>
                <input type="password" wire:model="password" autocomplete="current-password"
                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950">
                @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <button type="submit"
                    wire:loading.attr="disabled" wire:target="logoutOthers"
                    class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-slate-800 disabled:opacity-60 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                <i data-lucide="log-out" class="h-4 w-4"></i>
                Sign out other devices
            </button>
        </form>
    </x-visitor.section>
</div>
