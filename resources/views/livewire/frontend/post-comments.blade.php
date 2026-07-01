<div class="space-y-6">
    <h2 class="flex items-center gap-2 text-xl font-black tracking-tight" style="font-family: 'Playfair Display', serif;">
        <i data-lucide="message-square" class="h-5 w-5 text-emerald-700"></i>
        Comments
        @if ($this->totalCount > 0)
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                {{ $this->totalCount }}
            </span>
        @endif
    </h2>

    {{-- Thread --}}
    @if ($this->thread->isEmpty())
        <p class="rounded-2xl border-2 border-dashed border-slate-200 p-8 text-center text-sm text-slate-400 dark:border-slate-700">
            No comments yet — be the first to start the conversation.
        </p>
    @else
        <div class="space-y-5">
            @foreach ($this->thread as $comment)
                <article wire:key="comment-{{ $comment->id }}" class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <header class="mb-2 flex items-center gap-3">
                        @if ($url = $comment->avatarUrl())
                            <img src="{{ $url }}" alt="" class="h-9 w-9 rounded-full border border-slate-200">
                        @else
                            <span class="grid h-9 w-9 place-items-center rounded-full bg-slate-900 text-xs font-bold uppercase text-white">
                                {{ mb_substr($comment->authorName(), 0, 1) }}
                            </span>
                        @endif
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                                {{ $comment->authorName() }}
                            </p>
                            <p class="text-[11px] text-slate-500">
                                {{ $comment->created_at?->diffForHumans() }}
                            </p>
                        </div>
                        <button type="button" wire:click="startReply({{ $comment->id }})"
                                class="text-xs font-semibold text-emerald-700 hover:underline">
                            Reply
                        </button>
                    </header>

                    <div class="ml-12 text-sm text-slate-700 dark:text-slate-200">
                        {{ $comment->body }}
                    </div>

                    {{-- Replies --}}
                    @if ($comment->replies->isNotEmpty())
                        <div class="mt-4 ml-12 space-y-3 border-l-2 border-slate-100 pl-4 dark:border-slate-800">
                            @foreach ($comment->replies as $reply)
                                <div wire:key="reply-{{ $reply->id }}">
                                    <header class="mb-1 flex items-center gap-2">
                                        @if ($url = $reply->avatarUrl())
                                            <img src="{{ $url }}" alt="" class="h-6 w-6 rounded-full border border-slate-200">
                                        @else
                                            <span class="grid h-6 w-6 place-items-center rounded-full bg-slate-900 text-[10px] font-bold uppercase text-white">
                                                {{ mb_substr($reply->authorName(), 0, 1) }}
                                            </span>
                                        @endif
                                        <p class="text-xs font-semibold text-slate-800 dark:text-slate-100">
                                            {{ $reply->authorName() }}
                                        </p>
                                        <p class="text-[10px] text-slate-500">
                                            {{ $reply->created_at?->diffForHumans() }}
                                        </p>
                                    </header>
                                    <div class="ml-8 text-xs text-slate-700 dark:text-slate-300">
                                        {{ $reply->body }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Inline reply form --}}
                    @if ($replyTo === $comment->id)
                        <div class="mt-4 ml-12 rounded-xl border border-slate-300 bg-emerald-50/60 p-3 dark:border-slate-700 dark:bg-emerald-500/10">
                            <p class="mb-2 text-xs font-semibold text-emerald-800 dark:text-emerald-300">
                                Replying to {{ $comment->authorName() }}
                                <button type="button" wire:click="cancelReply" class="ml-2 text-emerald-700 hover:underline">cancel</button>
                            </p>
                            @include('livewire.frontend._comment-form-fields')
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    @endif

    {{-- Status messages --}}
    @if ($submitted && $message)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
            <i data-lucide="check-circle" class="inline h-4 w-4"></i> {{ $message }}
        </div>
    @endif
    @if ($error)
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300">
            {{ $error }}
        </div>
    @endif

    {{-- Top-level new comment form --}}
    @if ($replyTo === null)
        <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <h3 class="mb-3 text-sm font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                Leave a comment
            </h3>
            @include('livewire.frontend._comment-form-fields')
        </div>
    @endif
</div>
