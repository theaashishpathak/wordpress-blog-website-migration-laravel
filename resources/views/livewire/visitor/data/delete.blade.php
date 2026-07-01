@php
    $pending = $this->pending;
@endphp

<div class="space-y-8">
    <x-visitor.page-header
        eyebrow="Data &amp; Privacy"
        title="Delete Account"
        description="Permanently remove your account and personal data. 30-day grace period to change your mind." />

    @if ($pending)
        {{-- Grace state — show countdown + cancel button --}}
        <section class="rounded-2xl border border-rose-300 bg-rose-50/60 p-6 dark:border-rose-500/40 dark:bg-rose-500/5">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <h2 class="flex items-center gap-2 text-xl font-black tracking-tight text-rose-900 dark:text-rose-100" style="font-family: 'Playfair Display', serif;">
                        <i data-lucide="alert-triangle" class="h-5 w-5"></i>
                        Account deletion is scheduled.
                    </h2>
                    <p class="mt-2 text-sm leading-relaxed text-rose-800 dark:text-rose-200">
                        Your account will be permanently deleted <strong>{{ $pending->scheduled_for?->diffForHumans() }}</strong>
                        ({{ $pending->scheduled_for?->format('M j, Y \a\t g:i A') }}).
                    </p>
                    @if ($pending->reason)
                        <p class="mt-1.5 text-[11px] text-rose-700/80 dark:text-rose-300/80">
                            Reason: <span class="font-semibold">{{ \App\Livewire\Visitor\Data\Delete::REASONS[$pending->reason] ?? $pending->reason }}</span>
                        </p>
                    @endif
                </div>

                <button type="button" wire:click="cancel"
                        class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-bold text-emerald-700 ring-1 ring-emerald-200 transition hover:bg-emerald-50 dark:bg-slate-900 dark:text-emerald-300 dark:ring-emerald-500/30">
                    <i data-lucide="undo-2" class="h-4 w-4"></i>
                    Cancel deletion
                </button>
            </div>

            <p class="mt-5 rounded-lg border border-rose-200 bg-white/70 px-3 py-2 text-[11px] leading-relaxed text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-200">
                <i data-lucide="info" class="inline h-3 w-3"></i>
                Until the scheduled date, all your data is still here. Logging in or cancelling above keeps your account active.
            </p>
        </section>

        <x-visitor.section title="Need a copy first?">
            <p class="text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                Visit <a href="{{ route('visitor.data.export') }}" class="font-bold text-emerald-700 hover:underline dark:text-emerald-300">Export My Data</a>
                before the deletion runs — we'll package everything as a downloadable ZIP.
            </p>
        </x-visitor.section>
    @else
        {{-- Pre-deletion form --}}
        <form wire:submit="submit" class="space-y-6">
            <section class="rounded-2xl border border-rose-200 bg-rose-50/60 p-6 dark:border-rose-500/30 dark:bg-rose-500/5">
                <div class="flex items-start gap-3">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-rose-100 text-rose-600 ring-1 ring-rose-200 dark:bg-rose-500/20 dark:text-rose-300 dark:ring-rose-500/30">
                        <i data-lucide="alert-triangle" class="h-4 w-4"></i>
                    </span>
                    <div>
                        <h2 class="text-lg font-black tracking-tight text-rose-900 dark:text-rose-100" style="font-family: 'Playfair Display', serif;">
                            This action cannot be undone after the grace window.
                        </h2>
                        <p class="mt-2 text-xs leading-relaxed text-rose-800/80 dark:text-rose-200/80">
                            On submit, we schedule your account for permanent deletion. You have 30 days to change your mind — just log back in and click "Cancel deletion".
                            After 30 days everything goes: profile, comments, bookmarks, reading history, follows, highlights, notification preferences. Newsletter subscriptions on your email are also unsubscribed.
                        </p>
                    </div>
                </div>
            </section>

            <x-visitor.section title="Why are you leaving?" description="Optional — but it helps us understand what to fix.">
                <div class="space-y-2">
                    @foreach ($reasons as $value => $label)
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition
                                      {{ $reason === $value
                                         ? 'border-rose-300 bg-rose-50/40 dark:border-rose-500/40 dark:bg-rose-500/10'
                                         : 'border-slate-200 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800' }}">
                            <input type="radio" wire:model="reason" value="{{ $value }}" class="h-4 w-4 text-rose-600">
                            <span class="text-sm text-slate-700 dark:text-slate-200">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>

                <div class="mt-4">
                    <label class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Anything we should know? <span class="font-normal text-slate-400">(optional)</span></label>
                    <textarea wire:model="note" rows="3" maxlength="1000"
                              placeholder="Feedback helps us improve — what could we have done better?"
                              class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-rose-400 focus:ring-1 focus:ring-rose-400 dark:border-slate-700 dark:bg-slate-950"></textarea>
                    @error('note') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </x-visitor.section>

            <x-visitor.section title="Confirm">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Type <span class="font-mono text-rose-600">DELETE</span> to confirm</label>
                        <input type="text" wire:model="confirmText"
                               class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 font-mono text-sm uppercase outline-none transition focus:border-rose-400 focus:ring-1 focus:ring-rose-400 dark:border-slate-700 dark:bg-slate-950">
                        @error('confirmText') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Current password</label>
                        <input type="password" wire:model="password" autocomplete="current-password"
                               class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-rose-400 focus:ring-1 focus:ring-rose-400 dark:border-slate-700 dark:bg-slate-950">
                        @error('password') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </x-visitor.section>

            <div class="flex flex-wrap items-center justify-end gap-2">
                <a href="{{ route('visitor.data.export') }}"
                   class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-bold text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                    <i data-lucide="download" class="h-4 w-4"></i>
                    Export my data first
                </a>
                <button type="submit"
                        wire:loading.attr="disabled" wire:target="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-rose-700 disabled:opacity-60">
                    <i data-lucide="trash-2" class="h-4 w-4" wire:loading.remove wire:target="submit"></i>
                    <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="submit"></i>
                    <span wire:loading.remove wire:target="submit">Schedule deletion</span>
                    <span wire:loading wire:target="submit">Scheduling…</span>
                </button>
            </div>
        </form>
    @endif
</div>
