<div class="space-y-6">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('admin.posts.index') }}" wire:navigate class="hover:text-indigo-600">Posts</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">Create</span>
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
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                @include('livewire.admin.posts._form-fields')
            </div>

            @include('livewire.admin.posts._seo-panel')
        </div>

        {{-- Sidebar — actions + meta --}}
        <aside class="space-y-4">
            @include('livewire.admin.posts._featured-image-card')

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-100 bg-slate-50/60 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/40">
                    <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] text-slate-500">
                        <i data-lucide="upload-cloud" class="h-3.5 w-3.5"></i>
                        Publish
                    </h3>
                </div>

                <div class="space-y-2.5 p-4">
                    {{-- Primary action: Save & Submit for Review --}}
                    <button type="button" wire:click="saveAndSubmit"
                            wire:loading.attr="disabled"
                            wire:target="saveAndSubmit"
                            class="group inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:from-indigo-700 hover:to-indigo-600 hover:shadow disabled:cursor-not-allowed disabled:opacity-60">
                        <i data-lucide="send-horizontal" class="h-4 w-4 transition group-hover:translate-x-0.5" wire:loading.remove wire:target="saveAndSubmit"></i>
                        <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="saveAndSubmit"></i>
                        <span wire:loading.remove wire:target="saveAndSubmit">Submit for Review</span>
                        <span wire:loading wire:target="saveAndSubmit">Submitting…</span>
                    </button>

                    {{-- Direct publish — only if user has the permission --}}
                    @if ($this->canPublishDirectly)
                        <button type="button" wire:click="savePublish"
                                wire:loading.attr="disabled"
                                wire:target="savePublish"
                                class="group inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-emerald-500 px-4 py-3 text-sm font-bold text-white shadow-sm transition hover:from-emerald-700 hover:to-emerald-600 hover:shadow disabled:cursor-not-allowed disabled:opacity-60">
                            <i data-lucide="zap" class="h-4 w-4" wire:loading.remove wire:target="savePublish"></i>
                            <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="savePublish"></i>
                            <span wire:loading.remove wire:target="savePublish">Publish Now</span>
                            <span wire:loading wire:target="savePublish">Publishing…</span>
                        </button>
                    @endif

                    {{-- Save Draft — subdued ghost variant --}}
                    <button type="button" wire:click="saveDraft"
                            wire:loading.attr="disabled"
                            wire:target="saveDraft"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 hover:text-slate-900 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-slate-100">
                        <i data-lucide="save" class="h-3.5 w-3.5" wire:loading.remove wire:target="saveDraft"></i>
                        <i data-lucide="loader-2" class="h-3.5 w-3.5 animate-spin" wire:loading wire:target="saveDraft"></i>
                        <span wire:loading.remove wire:target="saveDraft">Save as Draft</span>
                        <span wire:loading wire:target="saveDraft">Saving…</span>
                    </button>
                </div>
            </div>

        </aside>
    </div>

    {{-- AI Assistant slide-over (opens via openAIAssistant() dispatched event) --}}
    <livewire:admin.posts.ai-assistant-drawer />

    {{-- Media picker modal (opens via openFeaturedImagePicker() dispatched event) --}}
    <livewire:admin.media.media-picker-modal />
</div>
