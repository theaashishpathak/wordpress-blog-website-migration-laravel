@php
    $requests = $this->requests;
    $hasInFlight = $requests->whereIn('status', [
        \App\Models\DataExportRequest::STATUS_PENDING,
        \App\Models\DataExportRequest::STATUS_PROCESSING,
    ])->isNotEmpty();

    $statusMeta = [
        \App\Models\DataExportRequest::STATUS_PENDING    => ['label' => 'Pending',    'icon' => 'clock',         'tone' => 'amber'],
        \App\Models\DataExportRequest::STATUS_PROCESSING => ['label' => 'Processing', 'icon' => 'loader',        'tone' => 'sky'],
        \App\Models\DataExportRequest::STATUS_READY      => ['label' => 'Ready',      'icon' => 'check-circle-2','tone' => 'emerald'],
        \App\Models\DataExportRequest::STATUS_FAILED     => ['label' => 'Failed',     'icon' => 'alert-triangle','tone' => 'rose'],
        \App\Models\DataExportRequest::STATUS_EXPIRED    => ['label' => 'Expired',    'icon' => 'archive',       'tone' => 'slate'],
    ];
    $tone = [
        'amber'   => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-200 dark:ring-amber-500/30',
        'sky'     => 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-500/15 dark:text-sky-300 dark:ring-sky-500/30',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-300 dark:ring-emerald-500/30',
        'rose'    => 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-500/15 dark:text-rose-300 dark:ring-rose-500/30',
        'slate'   => 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700',
    ];
@endphp

<div class="space-y-8">
    <x-visitor.page-header
        eyebrow="Data &amp; Privacy"
        title="Export My Data"
        description="Download a ZIP archive containing everything we hold about your account — profile, comments, bookmarks, reactions, follows, and more. GDPR-grade portability." />

    {{-- Request box --}}
    <section class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-6 dark:border-emerald-500/30 dark:bg-emerald-500/5">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="min-w-0 flex-1">
                <h2 class="text-xl font-black tracking-tight text-emerald-900 dark:text-emerald-100" style="font-family: 'Playfair Display', serif;">
                    Request a new archive
                </h2>
                <p class="mt-1.5 max-w-prose text-xs leading-relaxed text-emerald-800/80 dark:text-emerald-200/80">
                    We package everything as JSON inside a ZIP. Larger accounts may take a minute. Download links expire after seven days.
                </p>
            </div>
            <button type="button" wire:click="requestExport"
                    wire:loading.attr="disabled" wire:target="requestExport"
                    @disabled($hasInFlight)
                    class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 disabled:opacity-60 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                <i data-lucide="package" class="h-4 w-4" wire:loading.remove wire:target="requestExport"></i>
                <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="requestExport"></i>
                @if ($hasInFlight)
                    <span>Export in progress…</span>
                @else
                    <span wire:loading.remove wire:target="requestExport">Request export</span>
                    <span wire:loading wire:target="requestExport">Queuing…</span>
                @endif
            </button>
        </div>
    </section>

    {{-- History --}}
    <x-visitor.section title="Recent requests">
        @if ($requests->isEmpty())
            <p class="text-sm text-slate-500 dark:text-slate-400">No exports requested yet.</p>
        @else
            <div class="space-y-3">
                @foreach ($requests as $r)
                    @php($meta = $statusMeta[$r->status] ?? $statusMeta[\App\Models\DataExportRequest::STATUS_PENDING])
                    <article class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-slate-300 dark:border-slate-800 dark:hover:border-slate-700">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                                <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] ring-1 {{ $tone[$meta['tone']] }}">
                                    <i data-lucide="{{ $meta['icon'] }}" class="h-2.5 w-2.5"></i>
                                    {{ $meta['label'] }}
                                </span>
                                <span>Requested {{ $r->created_at?->diffForHumans() }}</span>
                                @if ($r->expires_at)
                                    <span class="text-slate-300 dark:text-slate-600">·</span>
                                    <span>Expires {{ $r->expires_at->diffForHumans() }}</span>
                                @endif
                                @if ($r->file_size_bytes)
                                    <span class="text-slate-300 dark:text-slate-600">·</span>
                                    <span>{{ number_format($r->file_size_bytes / 1024, 1) }} KB</span>
                                @endif
                            </div>
                            @if ($r->error)
                                <p class="mt-1.5 text-[11px] text-rose-600 dark:text-rose-300">{{ $r->error }}</p>
                            @endif
                        </div>

                        @if ($r->isReady())
                            <a href="{{ route('visitor.data.export.download', $r) }}"
                               class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                                <i data-lucide="download" class="h-3 w-3"></i>
                                Download ZIP
                            </a>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </x-visitor.section>

    <p class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs leading-relaxed text-slate-600 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-300">
        <i data-lucide="info" class="inline h-3 w-3"></i>
        Each archive is private to you. The download link is auth-gated to your account — no signed URL sharing.
    </p>
</div>
