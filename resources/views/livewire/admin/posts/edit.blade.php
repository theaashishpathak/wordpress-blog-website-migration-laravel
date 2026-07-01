<div class="space-y-6">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('admin.posts.index') }}" wire:navigate class="hover:text-indigo-600">Posts</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">
            Edit — {{ $title !== '' ? $title : ('#'.$post->id) }}
        </span>

        @php($status = $post->status)
        @php($statusColor = match ($status->value) {
            'published' => 'bg-emerald-100 text-emerald-700',
            'draft' => 'bg-slate-100 text-slate-700',
            'pending_review','in_review' => 'bg-amber-100 text-amber-700',
            'changes_requested' => 'bg-orange-100 text-orange-700',
            'approved' => 'bg-indigo-100 text-indigo-700',
            'scheduled' => 'bg-sky-100 text-sky-700',
            'rejected' => 'bg-rose-100 text-rose-700',
            'archived' => 'bg-zinc-100 text-zinc-700',
            'unpublished' => 'bg-stone-100 text-stone-700',
            default => 'bg-slate-100 text-slate-700',
        })
        <span class="ml-2 rounded-full px-2 py-0.5 text-xs font-semibold {{ $statusColor }}">
            {{ $status->label() }}
        </span>
    </nav>

    {{-- AI Assistant launch bar --}}
    @canany(['ai.use_writer', 'ai.use_seo', 'ai.use_rewrite'])
        <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-violet-200 bg-gradient-to-r from-violet-50 to-indigo-50 px-4 py-3 dark:border-violet-500/30 dark:from-violet-500/10 dark:to-indigo-500/10">
            <span class="grid h-8 w-8 place-items-center rounded-xl bg-gradient-to-br from-violet-500 to-indigo-500 text-white">
                <i data-lucide="sparkles" class="h-4 w-4"></i>
            </span>
            <p class="mr-auto text-sm font-semibold text-violet-900 dark:text-violet-200">AI Assistant</p>

            @can('ai.use_writer')
                <button type="button" wire:click="openAIAssistant('article')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-violet-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-violet-700">
                    <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                    Generate Article
                </button>
            @endcan
            @can('ai.use_seo')
                <button type="button" wire:click="openAIAssistant('seo')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                    <i data-lucide="search" class="h-3.5 w-3.5"></i>
                    Generate SEO
                </button>
            @endcan
            @can('ai.use_rewrite')
                <button type="button" wire:click="openAIAssistant('rewrite')"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-orange-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-orange-700">
                    <i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i>
                    Rewrite
                </button>
            @endcan
        </div>
    @endcanany

    <div class="grid gap-6 xl:grid-cols-[1fr_320px]">
        {{-- Form column --}}
        <div class="space-y-6">
            @include('livewire.admin.posts._translation-tabs')

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                @include('livewire.admin.posts._form-fields')
            </div>

            @include('livewire.admin.posts._seo-panel')
        </div>

        {{-- Sidebar — actions + workflow --}}
        <aside class="space-y-4">
            @include('livewire.admin.posts._featured-image-card')

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                    <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                        <i data-lucide="save" class="h-3.5 w-3.5"></i>
                        Save Changes
                    </h3>
                </div>
                <div class="p-4">
                    <button type="button" wire:click="save"
                            wire:loading.attr="disabled" wire:target="save"
                            class="group inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:from-indigo-700 hover:to-indigo-600 disabled:cursor-not-allowed disabled:opacity-60">
                        <i data-lucide="save" class="h-4 w-4" wire:loading.remove wire:target="save"></i>
                        <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="save"></i>
                        <span wire:loading.remove wire:target="save">Save Changes</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </button>
                </div>
            </div>

            {{-- Editorial workflow buttons --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Editorial Workflow</h3>

                @if ($this->canApprove || $this->canReject || $this->canRequestChanges)
                    <textarea
                        wire:model="editorialNote"
                        rows="3"
                        placeholder="Note (required for reject + request changes)…"
                        class="mb-3 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950"
                    ></textarea>
                @endif

                <div class="space-y-2">
                    @if ($this->canSubmitForReview)
                        <button type="button" wire:click="submitForReview"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                            <i data-lucide="send" class="h-3.5 w-3.5"></i>
                            Submit for Review
                        </button>
                    @endif

                    @if ($this->canApprove)
                        <button type="button" wire:click="approve"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                            <i data-lucide="check" class="h-3.5 w-3.5"></i>
                            Approve
                        </button>
                    @endif

                    @if ($this->canRequestChanges)
                        <button type="button" wire:click="requestChanges"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-orange-500 px-3 py-2 text-xs font-semibold text-white hover:bg-orange-600">
                            <i data-lucide="message-square" class="h-3.5 w-3.5"></i>
                            Request Changes
                        </button>
                    @endif

                    @if ($this->canReject)
                        <button type="button" wire:click="reject"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-rose-600 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-700">
                            <i data-lucide="x-circle" class="h-3.5 w-3.5"></i>
                            Reject
                        </button>
                    @endif

                    @if ($this->canPublish)
                        @if ($post->status->value !== 'published')
                            <button type="button" wire:click="publish"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">
                                <i data-lucide="zap" class="h-3.5 w-3.5"></i>
                                Publish
                            </button>
                        @else
                            <button type="button" wire:click="unpublish"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-stone-500 px-3 py-2 text-xs font-semibold text-white hover:bg-stone-600">
                                <i data-lucide="eye-off" class="h-3.5 w-3.5"></i>
                                Unpublish
                            </button>
                        @endif
                    @endif

                    @if ($this->canArchive && $post->status->value !== 'archived')
                        <button type="button" wire:click="archive"
                                class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-zinc-300 bg-zinc-50 px-3 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                            <i data-lucide="archive" class="h-3.5 w-3.5"></i>
                            Archive
                        </button>
                    @endif
                </div>
            </div>

            {{-- Revision badge --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-4 text-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Revisions</span>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        {{ $this->revisionCount }}
                    </span>
                </div>
                <p class="mt-2 text-xs text-slate-500">A new revision is recorded automatically every time you Save.</p>
                @can('editorial.revisions')
                    <a href="{{ route('admin.posts.revisions', $post) }}" wire:navigate
                       class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                        <i data-lucide="history" class="h-3.5 w-3.5"></i> View history
                    </a>
                @endcan
            </div>

            {{-- Editorial notes thread (read-only) --}}
            @if ($this->editorialNotes->isNotEmpty())
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Editorial Notes</h3>
                    <div class="space-y-3">
                        @foreach ($this->editorialNotes as $note)
                            @php($typeColor = match ($note->type) {
                                \App\Models\EditorialNote::TYPE_APPROVE => 'border-emerald-200 bg-emerald-50 text-emerald-800',
                                \App\Models\EditorialNote::TYPE_REJECT => 'border-rose-200 bg-rose-50 text-rose-800',
                                \App\Models\EditorialNote::TYPE_REQUEST_CHANGES => 'border-orange-200 bg-orange-50 text-orange-800',
                                \App\Models\EditorialNote::TYPE_AI_SUGGESTION => 'border-violet-200 bg-violet-50 text-violet-800',
                                default => 'border-slate-200 bg-slate-50 text-slate-700',
                            })
                            <div class="rounded-lg border px-3 py-2 text-xs {{ $typeColor }}">
                                <div class="mb-1 flex items-center justify-between text-[10px] uppercase tracking-wider">
                                    <span class="font-bold">{{ str($note->type)->replace('_', ' ')->title() }}</span>
                                    <span>{{ $note->created_at?->diffForHumans() }}</span>
                                </div>
                                <p>{{ $note->body }}</p>
                                <p class="mt-1 text-[10px] opacity-75">— {{ $note->author?->name ?? 'system' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </aside>
    </div>

    {{-- AI Assistant slide-over (opens via openAIAssistant() dispatched event) --}}
    <livewire:admin.posts.ai-assistant-drawer />

    {{-- Media picker modal (opens via openFeaturedImagePicker() dispatched event) --}}
    <livewire:admin.media.media-picker-modal />
</div>
