{{-- Shared comment form fields — used by the new-comment + reply forms.
     Expects on $this:
       body, guestName, guestEmail, guestWebsite, hp, submit()
--}}
<form wire:submit="submit" class="space-y-3">
    {{-- Honeypot field — hidden visually + from screen readers --}}
    <div class="absolute -left-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
        <label>Leave this empty:</label>
        <input type="text" wire:model="hp" tabindex="-1" autocomplete="off">
    </div>

    @auth
        <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-300">
            Commenting as <strong>{{ auth()->user()->name }}</strong>
        </p>
    @else
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                    Name <span class="text-rose-500">*</span>
                </label>
                <input type="text" wire:model="guestName" required maxlength="120"
                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                @error('guestName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                    Email <span class="text-rose-500">*</span>
                    <span class="text-slate-400">(never published)</span>
                </label>
                <input type="email" wire:model="guestEmail" required maxlength="255"
                       class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
                @error('guestEmail') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        </div>
        <div>
            <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">
                Website <span class="text-slate-400">(optional)</span>
            </label>
            <input type="url" wire:model="guestWebsite" maxlength="255" placeholder="https://"
                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950">
            @error('guestWebsite') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    @endauth

    <div>
        <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-300">
            Comment <span class="text-rose-500">*</span>
        </label>
        <textarea wire:model="body" rows="4" required maxlength="5000"
                  placeholder="Share your thoughts…"
                  class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
        @error('body') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <button type="submit"
            wire:loading.attr="disabled" wire:target="submit"
            class="inline-flex items-center gap-2 rounded-xl bg-slate-900 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-slate-800 disabled:opacity-60">
        <i data-lucide="send" class="h-4 w-4" wire:loading.remove wire:target="submit"></i>
        <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="submit"></i>
        <span wire:loading.remove wire:target="submit">Post comment</span>
        <span wire:loading wire:target="submit">Sending…</span>
    </button>
</form>
