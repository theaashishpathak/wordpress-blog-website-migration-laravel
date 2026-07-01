<div class="space-y-8">
    <x-visitor.page-header
        eyebrow="Settings"
        title="Account &amp; Security"
        description="Change your password and configure two-factor authentication." />

    <x-visitor.section title="Change password" description="Choose a long passphrase — 12+ characters with a mix of letters, numbers, and symbols.">
        <form wire:submit="changePassword" class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Current password</label>
                <input type="password" wire:model="currentPassword" autocomplete="current-password"
                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950">
                @error('currentPassword') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">New password</label>
                <input type="password" wire:model="newPassword" autocomplete="new-password"
                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950">
                @error('newPassword') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Confirm new password</label>
                <input type="password" wire:model="newPasswordConfirmation" autocomplete="new-password"
                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950">
            </div>
            <div class="sm:col-span-2">
                <button type="submit"
                        wire:loading.attr="disabled" wire:target="changePassword"
                        class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 disabled:opacity-60 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                    <i data-lucide="key" class="h-4 w-4" wire:loading.remove wire:target="changePassword"></i>
                    <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="changePassword"></i>
                    <span wire:loading.remove wire:target="changePassword">Update password</span>
                    <span wire:loading wire:target="changePassword">Updating…</span>
                </button>
            </div>
        </form>
    </x-visitor.section>

    <x-visitor.section
        title="Two-factor authentication"
        description="Add a one-time code from your authenticator app on top of your password.">
        <x-slot:actions>
            @if ($twoFactorConfirmed)
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30">
                    <i data-lucide="check-circle-2" class="h-2.5 w-2.5"></i>
                    Active
                </span>
            @elseif ($twoFactorEnabled)
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-200 dark:ring-amber-500/30">
                    <i data-lucide="clock" class="h-2.5 w-2.5"></i>
                    Pending
                </span>
            @else
                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                    Off
                </span>
            @endif
        </x-slot:actions>

        <div class="flex flex-wrap items-center gap-2">
            @if (! $twoFactorEnabled)
                <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                        <i data-lucide="shield-plus" class="h-4 w-4"></i>
                        Enable 2FA
                    </button>
                </form>
            @else
                @if ($twoFactorEnabled && ! $twoFactorConfirmed)
                    <a href="{{ url('/user/two-factor-qr-code') }}" target="_blank"
                       class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                        <i data-lucide="qr-code" class="h-4 w-4"></i>
                        Show QR code
                    </a>
                @endif

                <a href="{{ url('/user/two-factor-recovery-codes') }}" target="_blank"
                   class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                    <i data-lucide="key-round" class="h-4 w-4"></i>
                    Recovery codes
                </a>

                <form method="POST" action="{{ url('/user/two-factor-authentication') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-bold text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 dark:border-slate-700 dark:text-slate-300 dark:hover:border-rose-500/40 dark:hover:bg-rose-500/10 dark:hover:text-rose-300">
                        <i data-lucide="shield-off" class="h-4 w-4"></i>
                        Disable
                    </button>
                </form>
            @endif
        </div>

        <p class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-3 text-[11px] leading-relaxed text-slate-600 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-300">
            <i data-lucide="info" class="inline h-3 w-3"></i>
            Use Google Authenticator, Authy, 1Password, or any TOTP app. Store the recovery codes somewhere safe — they unlock your account if you lose the device.
        </p>
    </x-visitor.section>
</div>
