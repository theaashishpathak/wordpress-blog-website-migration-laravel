@php
    $locale = app(\App\Support\LocaleResolver::class)->current();
    $total = $this->highlights->total();
@endphp

<div class="space-y-8">
    <x-visitor.page-header
        eyebrow="My Library"
        title="Highlights"
        description="{{ $total }} {{ \Illuminate\Support\Str::plural('passage', $total) }} you've marked as worth remembering. Add notes to give your future self some context." />

    @if ($this->highlights->isEmpty())
        <x-visitor.empty-state
            icon="highlighter"
            title="No highlights yet."
            description="Select any text inside an article and a small popover will let you save the passage here.">
            <x-slot:action>
                <a href="{{ route('frontend.home', ['locale' => $locale?->code]) }}"
                   class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                    <i data-lucide="newspaper" class="h-4 w-4"></i>
                    Find something to highlight
                </a>
            </x-slot:action>
        </x-visitor.empty-state>
    @else
        <div class="space-y-5">
            @foreach ($this->highlights as $h)
                @php
                    $post = $h->post;
                    $t = $post?->translation() ?? $post?->translations->firstWhere('language_id', $post->default_language_id) ?? $post?->translations->first();
                @endphp
                <article class="rounded-2xl border border-slate-200 bg-white p-6 transition hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700">
                    {{-- Article header --}}
                    @if ($post && $t)
                        <div class="mb-4 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                            @if ($post->category)
                                <a href="{{ route('frontend.category', ['locale' => $locale?->code, 'slug' => $post->category->translate('slug')]) }}"
                                   class="font-bold uppercase tracking-[0.18em] text-slate-600 hover:text-emerald-700 hover:underline dark:text-slate-400 dark:hover:text-emerald-300">
                                    {{ $post->category->translate('name') }}
                                </a>
                                <span class="text-slate-300 dark:text-slate-600">·</span>
                            @endif
                            <a href="{{ route('frontend.post.show', ['locale' => $locale?->code, 'slug' => $t->slug]) }}"
                               class="line-clamp-1 font-semibold text-slate-700 hover:text-emerald-700 dark:text-slate-300 dark:hover:text-emerald-300">
                                {{ $t->title }}
                            </a>
                            <span class="text-slate-300 dark:text-slate-600">·</span>
                            <span>{{ $h->created_at?->diffForHumans() }}</span>
                        </div>
                    @endif

                    {{-- Pull-quote — editorial style. --}}
                    <blockquote class="relative border-l-2 border-emerald-600 pl-5 dark:border-emerald-500">
                        <p class="text-lg leading-relaxed text-slate-800 dark:text-slate-100"
                           style="font-family: 'Playfair Display', serif;">
                            &ldquo;{{ $h->selected_text }}&rdquo;
                        </p>
                    </blockquote>

                    {{-- Note --}}
                    <div class="mt-4">
                        @if ($editingId === $h->id)
                            <div class="space-y-2">
                                <textarea wire:model="editingNote" rows="3" placeholder="Add a personal note…"
                                          class="w-full rounded-lg border border-slate-200 bg-white p-3 text-sm outline-none transition focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950"></textarea>
                                <div class="flex flex-wrap items-center gap-2">
                                    <button type="button" wire:click="saveNote"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-bold text-white shadow-sm hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                                        <i data-lucide="save" class="h-3 w-3"></i>
                                        Save note
                                    </button>
                                    <button type="button" wire:click="cancelEditingNote"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        @elseif ($h->note)
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-800/50">
                                <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400 dark:text-slate-500">My note</p>
                                <p class="mt-1 text-sm leading-relaxed text-slate-700 dark:text-slate-200">{{ $h->note }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- Footer actions --}}
                    @if ($editingId !== $h->id)
                        <div class="mt-4 flex items-center justify-between gap-2">
                            <button type="button" wire:click="startEditingNote({{ $h->id }})"
                                    class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 hover:underline dark:text-emerald-300">
                                <i data-lucide="{{ $h->note ? 'edit-3' : 'message-square-plus' }}" class="h-3 w-3"></i>
                                {{ $h->note ? 'Edit note' : 'Add note' }}
                            </button>

                            <button type="button" wire:click="delete({{ $h->id }})"
                                    wire:confirm="Delete this highlight permanently?"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 transition hover:text-rose-600 dark:text-slate-400 dark:hover:text-rose-300">
                                <i data-lucide="trash-2" class="h-3 w-3"></i>
                                Delete
                            </button>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>

        <div class="mt-6">{{ $this->highlights->onEachSide(1)->links() }}</div>
    @endif
</div>
