@php
    $locale = app(\App\Support\LocaleResolver::class)->current();
    $subs = $this->subscriptions;
    $activeCount = $subs->where('status', \App\Models\NewsletterSubscriber::STATUS_CONFIRMED)->count();
@endphp

<div class="space-y-8">
    <x-visitor.page-header
        eyebrow="Email &amp; Newsletter"
        title="Subscribed Lists"
        description="Subscriptions tied to {{ auth()->user()->email }}.">
        <x-slot:actions>
            @if ($activeCount > 1)
                <button type="button" wire:click="unsubscribeAll"
                        wire:confirm="Unsubscribe from every list?"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 dark:border-slate-700 dark:text-slate-300 dark:hover:border-rose-500/40 dark:hover:bg-rose-500/10 dark:hover:text-rose-300">
                    <i data-lucide="mail-x" class="h-3.5 w-3.5"></i>
                    Unsubscribe from all
                </button>
            @endif
        </x-slot:actions>
    </x-visitor.page-header>

    @if ($subs->isEmpty())
        <x-visitor.empty-state
            icon="inbox"
            title="No newsletter subscriptions."
            description="You haven't signed up to any of our lists yet. Look for the signup box on the homepage or in the footer.">
            <x-slot:action>
                <a href="{{ route('frontend.home', ['locale' => $locale?->code]) }}#newsletter"
                   class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                    <i data-lucide="mail" class="h-4 w-4"></i>
                    Subscribe to the newsletter
                </a>
            </x-slot:action>
        </x-visitor.empty-state>
    @else
        <div class="space-y-3">
            @foreach ($subs as $sub)
                @php
                    $isActive = $sub->status === \App\Models\NewsletterSubscriber::STATUS_CONFIRMED;
                    $isUnsubscribed = $sub->status === \App\Models\NewsletterSubscriber::STATUS_UNSUBSCRIBED;

                    $statusMeta = match ($sub->status) {
                        \App\Models\NewsletterSubscriber::STATUS_CONFIRMED   => ['label' => 'Active',       'tone' => 'emerald', 'icon' => 'check-circle-2'],
                        \App\Models\NewsletterSubscriber::STATUS_PENDING     => ['label' => 'Awaiting confirmation', 'tone' => 'amber', 'icon' => 'clock'],
                        \App\Models\NewsletterSubscriber::STATUS_UNSUBSCRIBED => ['label' => 'Unsubscribed', 'tone' => 'slate', 'icon' => 'mail-x'],
                        \App\Models\NewsletterSubscriber::STATUS_BOUNCED     => ['label' => 'Bounced',      'tone' => 'rose', 'icon' => 'alert-triangle'],
                        \App\Models\NewsletterSubscriber::STATUS_COMPLAINED  => ['label' => 'Complained',   'tone' => 'rose', 'icon' => 'alert-triangle'],
                        default => ['label' => ucfirst($sub->status), 'tone' => 'slate', 'icon' => 'circle'],
                    };

                    $toneClasses = [
                        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30',
                        'amber'   => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-200 dark:ring-amber-500/30',
                        'rose'    => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/15 dark:text-rose-300 dark:ring-rose-500/30',
                        'slate'   => 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700',
                    ][$statusMeta['tone']];
                @endphp

                <article class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 transition hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700">
                    <div class="flex min-w-0 items-center gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-slate-50 text-slate-600 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700">
                            <i data-lucide="newspaper" class="h-4 w-4"></i>
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-900 dark:text-slate-100" style="font-family: 'Playfair Display', serif;">
                                NewsPilot Newsletter
                                @if ($sub->language)
                                    <span class="ml-1.5 inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $sub->language->flag_emoji ?? '🌐' }}
                                        {{ $sub->language->name }}
                                    </span>
                                @endif
                            </p>
                            <p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">
                                @if ($sub->confirmed_at)
                                    Subscribed since {{ $sub->confirmed_at->format('M j, Y') }}
                                @else
                                    Joined {{ $sub->created_at?->format('M j, Y') }}
                                @endif
                                @if ($sub->source)
                                    <span class="text-slate-300 dark:text-slate-600">·</span> via <span class="font-mono">{{ $sub->source }}</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] ring-1 {{ $toneClasses }}">
                            <i data-lucide="{{ $statusMeta['icon'] }}" class="h-2.5 w-2.5"></i>
                            {{ $statusMeta['label'] }}
                        </span>

                        @if ($isActive)
                            <button type="button" wire:click="unsubscribe({{ $sub->id }})"
                                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 dark:border-slate-700 dark:text-slate-300 dark:hover:border-rose-500/40 dark:hover:bg-rose-500/10 dark:hover:text-rose-300">
                                <i data-lucide="mail-x" class="h-3 w-3"></i>
                                Unsubscribe
                            </button>
                        @elseif ($isUnsubscribed)
                            <button type="button" wire:click="resubscribe({{ $sub->id }})"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                                <i data-lucide="mail-check" class="h-3 w-3"></i>
                                Resubscribe
                            </button>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
