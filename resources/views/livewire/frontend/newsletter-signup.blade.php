@php
    $isInline = $variant === 'inline';
    $isFooter = $variant === 'footer';
    $isHero = $variant === 'hero';

    // Hero variant needs `relative overflow-hidden` because it renders
    // two decorative `absolute` blobs with negative offsets. Without a
    // positioned ancestor those blobs anchor to the viewport and push
    // the body horizontally, producing a page-level scrollbar.
    $containerClasses = match ($variant) {
        'hero' => 'relative overflow-hidden rounded-3xl bg-emerald-600 p-8 text-white shadow-xl',
        'footer' => 'rounded-2xl bg-slate-100 p-5 dark:bg-slate-900',
        default => 'rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900',
    };
@endphp

<div id="newsletter" class="{{ $containerClasses }}">
    {{-- Decorative blobs on hero variant --}}
    @if ($isHero)
        <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-emerald-300/15 blur-3xl"></div>
    @endif

    <div class="relative">
        @if ($submitted && $successMessage)
            {{-- Success state --}}
            <div x-data="{}" x-init="setTimeout(() => { try { window.scrollTo({ top: $el.offsetTop - 100, behavior: 'smooth' }); } catch(e) {} }, 100)"
                 class="flex flex-col items-center gap-4 text-center">
                <span class="grid h-16 w-16 place-items-center rounded-full {{ $isHero ? 'bg-white text-emerald-600' : 'bg-emerald-100 text-emerald-600 dark:bg-emerald-500/15' }} shadow-lg">
                    <i data-lucide="check-check" class="h-7 w-7"></i>
                </span>
                <div>
                    <h3 class="text-xl font-black {{ $isHero ? 'text-white' : 'text-slate-900 dark:text-slate-100' }}" style="font-family: 'Playfair Display', serif;">
                        Almost there!
                    </h3>
                    <p class="mt-2 max-w-md text-sm {{ $isHero ? 'text-white/85' : 'text-slate-600 dark:text-slate-400' }}">
                        {{ $successMessage }}
                    </p>
                </div>
            </div>
        @else
            @if ($isHero)
                {{-- Hero variant — only uses utility classes confirmed in
                     the production CSS bundle (no /opacity suffixes, no
                     hover: variants that aren't already compiled). --}}
                <div class="grid items-center gap-6 md:grid-cols-2">
                    <div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-white">
                            <i data-lucide="mail" class="h-3 w-3"></i>
                            Newsletter
                        </span>
                        <h3 class="mt-4 text-3xl font-black leading-tight text-white" style="font-family: 'Playfair Display', serif;">
                            Stay in the loop.
                        </h3>
                        <p class="mt-3 max-w-md text-sm leading-relaxed text-white opacity-90">
                            A weekly digest of our best articles — handpicked by editors. No spam, ever. Unsubscribe anytime.
                        </p>
                    </div>

                    <form wire:submit="subscribe" class="space-y-2">
                        <div class="absolute -left-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
                            <label>Don't fill this:</label>
                            <input type="text" wire:model="hp" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <input type="email" wire:model="email" required
                                   placeholder="you@example.com"
                                   class="flex-1 rounded-xl border border-emerald-500 bg-white px-4 py-3 text-sm text-slate-900 outline-none">
                            <button type="submit"
                                    wire:loading.attr="disabled" wire:target="subscribe"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-6 py-3 text-sm font-bold text-emerald-600 shadow-md">
                                <i data-lucide="send" class="h-4 w-4" wire:loading.remove wire:target="subscribe"></i>
                                <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="subscribe"></i>
                                <span wire:loading.remove wire:target="subscribe">Subscribe</span>
                                <span wire:loading wire:target="subscribe">Sending…</span>
                            </button>
                        </div>

                        @error('email') <p class="text-xs font-semibold text-white">{{ $message }}</p> @enderror
                        @if ($errorMessage)
                            <p class="text-xs font-semibold text-white">{{ $errorMessage }}</p>
                        @endif

                        <p class="pt-1 text-[11px] text-white opacity-90">
                            <i data-lucide="shield-check" class="inline h-3 w-3"></i>
                            We respect your privacy. No spam.
                        </p>
                    </form>
                </div>
            @else
                <div class="mb-3">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100"
                        style="font-family: 'Playfair Display', serif;">
                        Stay in the loop
                    </h3>
                    <p class="mt-0.5 text-xs text-slate-500">
                        Weekly digest of the best articles. No spam, ever.
                    </p>
                </div>

                <form wire:submit="subscribe" class="space-y-2">
                    <div class="absolute -left-[9999px] h-0 w-0 overflow-hidden" aria-hidden="true">
                        <label>Don't fill this:</label>
                        <input type="text" wire:model="hp" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <input type="email" wire:model="email" required
                               placeholder="you@example.com"
                               class="flex-1 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm outline-none focus:ring-2 focus:ring-slate-300 dark:border-slate-700 dark:bg-slate-950">
                        <button type="submit"
                                wire:loading.attr="disabled" wire:target="subscribe"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 disabled:opacity-60">
                            <i data-lucide="send" class="h-4 w-4" wire:loading.remove wire:target="subscribe"></i>
                            <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="subscribe"></i>
                            <span wire:loading.remove wire:target="subscribe">Subscribe</span>
                            <span wire:loading wire:target="subscribe">Sending…</span>
                        </button>
                    </div>

                    @error('email') <p class="text-xs text-rose-600">{{ $message }}</p> @enderror
                    @if ($errorMessage)
                        <p class="text-xs text-rose-600">{{ $errorMessage }}</p>
                    @endif
                </form>
            @endif
        @endif
    </div>
</div>
