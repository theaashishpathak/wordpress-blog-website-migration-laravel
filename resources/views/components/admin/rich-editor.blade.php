@props([
    'model' => 'content',
    'placeholder' => 'Start writing your article…',
    'minHeight' => 'min-h-[400px]',
])

{{--
    News-grade rich text editor (TipTap) — drop-in replacement for the
    Summernote textarea. Bound to a Livewire property via wire:model.defer
    on the hidden textarea, so the parent component's existing save path
    keeps working without changes.

    Usage:

        <x-admin.rich-editor model="content" />

    The TipTap instance lives entirely on the client. On `onUpdate` it
    syncs HTML into the hidden textarea and dispatches an `input` event so
    wire:model picks it up. wire:ignore protects the editor's DOM from
    Livewire's morphing on parent re-renders.
--}}

<div
    wire:ignore
    x-data="tiptapEditor($refs.textarea.value, {})"
    x-init="init()"
    data-placeholder="{{ $placeholder }}"
    class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm focus-within:border-indigo-400 focus-within:ring-2 focus-within:ring-indigo-100 dark:border-slate-700 dark:bg-slate-900 dark:focus-within:ring-indigo-500/20"
>
    {{-- Toolbar — grouped buttons separated by thin dividers. --}}
    <div class="flex flex-wrap items-center gap-0.5 border-b border-slate-200 bg-slate-50/80 px-2 py-1.5 text-slate-600 dark:border-slate-800 dark:bg-slate-950/40 dark:text-slate-300">

        {{-- Block type --}}
        <button type="button" x-on:click="cmd('paragraph')" title="Paragraph"
                class="rt-btn">
            <span class="text-xs font-semibold">P</span>
        </button>
        <button type="button" x-on:click="cmd('h2')" title="Heading 2"
                x-bind:class="is('h2') && 'rt-btn-active'" class="rt-btn">
            <span class="text-xs font-bold">H2</span>
        </button>
        <button type="button" x-on:click="cmd('h3')" title="Heading 3"
                x-bind:class="is('h3') && 'rt-btn-active'" class="rt-btn">
            <span class="text-xs font-bold">H3</span>
        </button>
        <button type="button" x-on:click="cmd('h4')" title="Heading 4"
                x-bind:class="is('h4') && 'rt-btn-active'" class="rt-btn">
            <span class="text-xs font-bold">H4</span>
        </button>

        <span class="rt-divider"></span>

        {{-- Inline formatting --}}
        <button type="button" x-on:click="cmd('bold')" title="Bold (Ctrl+B)"
                x-bind:class="is('bold') && 'rt-btn-active'" class="rt-btn">
            <i data-lucide="bold" class="h-3.5 w-3.5"></i>
        </button>
        <button type="button" x-on:click="cmd('italic')" title="Italic (Ctrl+I)"
                x-bind:class="is('italic') && 'rt-btn-active'" class="rt-btn">
            <i data-lucide="italic" class="h-3.5 w-3.5"></i>
        </button>
        <button type="button" x-on:click="cmd('underline')" title="Underline (Ctrl+U)"
                x-bind:class="is('underline') && 'rt-btn-active'" class="rt-btn">
            <i data-lucide="underline" class="h-3.5 w-3.5"></i>
        </button>
        <button type="button" x-on:click="cmd('strike')" title="Strikethrough"
                x-bind:class="is('strike') && 'rt-btn-active'" class="rt-btn">
            <i data-lucide="strikethrough" class="h-3.5 w-3.5"></i>
        </button>

        <span class="rt-divider"></span>

        {{-- Lists / quote --}}
        <button type="button" x-on:click="cmd('ul')" title="Bulleted list"
                x-bind:class="is('ul') && 'rt-btn-active'" class="rt-btn">
            <i data-lucide="list" class="h-3.5 w-3.5"></i>
        </button>
        <button type="button" x-on:click="cmd('ol')" title="Numbered list"
                x-bind:class="is('ol') && 'rt-btn-active'" class="rt-btn">
            <i data-lucide="list-ordered" class="h-3.5 w-3.5"></i>
        </button>
        <button type="button" x-on:click="cmd('blockquote')" title="Quote"
                x-bind:class="is('blockquote') && 'rt-btn-active'" class="rt-btn">
            <i data-lucide="quote" class="h-3.5 w-3.5"></i>
        </button>
        <button type="button" x-on:click="cmd('codeBlock')" title="Code block"
                x-bind:class="is('codeBlock') && 'rt-btn-active'" class="rt-btn">
            <i data-lucide="code" class="h-3.5 w-3.5"></i>
        </button>

        <span class="rt-divider"></span>

        {{-- Alignment --}}
        <button type="button" x-on:click="cmd('alignLeft')" title="Align left"
                x-bind:class="is('alignLeft') && 'rt-btn-active'" class="rt-btn">
            <i data-lucide="align-left" class="h-3.5 w-3.5"></i>
        </button>
        <button type="button" x-on:click="cmd('alignCenter')" title="Center"
                x-bind:class="is('alignCenter') && 'rt-btn-active'" class="rt-btn">
            <i data-lucide="align-center" class="h-3.5 w-3.5"></i>
        </button>
        <button type="button" x-on:click="cmd('alignRight')" title="Align right"
                x-bind:class="is('alignRight') && 'rt-btn-active'" class="rt-btn">
            <i data-lucide="align-right" class="h-3.5 w-3.5"></i>
        </button>

        <span class="rt-divider"></span>

        {{-- Insert: link / image / youtube / table / hr --}}
        <button type="button" x-on:click="cmd('link')" title="Insert link"
                x-bind:class="is('link') && 'rt-btn-active'" class="rt-btn">
            <i data-lucide="link" class="h-3.5 w-3.5"></i>
        </button>
        <button type="button" x-on:click="cmd('unlink')" title="Remove link"
                class="rt-btn">
            <i data-lucide="link-2-off" class="h-3.5 w-3.5"></i>
        </button>
        <button type="button" x-on:click="cmd('image')" title="Insert image"
                class="rt-btn">
            <i data-lucide="image" class="h-3.5 w-3.5"></i>
        </button>
        <button type="button" x-on:click="cmd('youtube')" title="Embed YouTube"
                class="rt-btn">
            <i data-lucide="youtube" class="h-3.5 w-3.5"></i>
        </button>
        <button type="button" x-on:click="cmd('table')" title="Insert table"
                class="rt-btn">
            <i data-lucide="table" class="h-3.5 w-3.5"></i>
        </button>
        <button type="button" x-on:click="cmd('hr')" title="Horizontal rule"
                class="rt-btn">
            <i data-lucide="minus" class="h-3.5 w-3.5"></i>
        </button>

        <span class="rt-divider"></span>

        {{-- Utility: clear formatting / source view / undo-redo --}}
        <button type="button" x-on:click="cmd('clear')" title="Clear formatting"
                class="rt-btn">
            <i data-lucide="remove-formatting" class="h-3.5 w-3.5"></i>
        </button>
        <button type="button" x-on:click="cmd('sourceView')" title="HTML source"
                x-bind:class="sourceView && 'rt-btn-active'" class="rt-btn">
            <i data-lucide="code-2" class="h-3.5 w-3.5"></i>
        </button>

        <span class="ml-auto inline-flex items-center gap-0.5">
            <button type="button" x-on:click="cmd('undo')" title="Undo (Ctrl+Z)" class="rt-btn">
                <i data-lucide="undo-2" class="h-3.5 w-3.5"></i>
            </button>
            <button type="button" x-on:click="cmd('redo')" title="Redo (Ctrl+Y)" class="rt-btn">
                <i data-lucide="redo-2" class="h-3.5 w-3.5"></i>
            </button>
        </span>
    </div>

    {{-- TipTap mounts here. Hidden when source view is on. --}}
    <div x-show="!sourceView" x-ref="mount" class="{{ $minHeight }}"></div>

    {{-- Source view — raw HTML textarea, bound directly to the editor. --}}
    <textarea
        x-show="sourceView"
        x-cloak
        x-bind:value="rawHtml"
        x-on:input="setRawHtml($event.target.value)"
        class="block w-full {{ $minHeight }} resize-y border-0 bg-slate-950 px-4 py-3 font-mono text-xs leading-relaxed text-emerald-200 focus:outline-none focus:ring-0"
        spellcheck="false"
    ></textarea>

    {{-- Hidden textarea — the source of truth for Livewire. wire:model.defer
         keeps things calm during typing (no network on every keystroke);
         the parent component reads $content on save. --}}
    <textarea
        x-ref="textarea"
        wire:model.defer="{{ $model }}"
        hidden
        aria-hidden="true"
    ></textarea>
</div>

@once
    @push('styles')
        <style>
            .rt-btn {
                display: inline-grid;
                place-items: center;
                height: 1.75rem;
                width: 1.75rem;
                border-radius: 0.5rem;
                color: rgb(71 85 105);
                transition: background-color 100ms ease, color 100ms ease;
            }
            .rt-btn:hover {
                background-color: rgb(241 245 249);
                color: rgb(15 23 42);
            }
            .dark .rt-btn { color: rgb(203 213 225); }
            .dark .rt-btn:hover { background-color: rgb(30 41 59); color: rgb(248 250 252); }
            .rt-btn-active,
            .rt-btn-active:hover {
                background-color: rgb(224 231 255) !important;
                color: rgb(79 70 229) !important;
            }
            .dark .rt-btn-active,
            .dark .rt-btn-active:hover {
                background-color: rgba(99, 102, 241, 0.18) !important;
                color: rgb(199 210 254) !important;
            }
            .rt-divider {
                display: inline-block;
                height: 1.25rem;
                width: 1px;
                background-color: rgb(226 232 240);
                margin: 0 0.25rem;
            }
            .dark .rt-divider { background-color: rgb(51 65 85); }

            /* TipTap editing surface — sensible defaults so writing looks
               like the published article from the first keystroke. */
            .ProseMirror { outline: none; }
            .ProseMirror p.is-editor-empty:first-child::before {
                content: attr(data-placeholder);
                color: rgb(148 163 184);
                float: left;
                pointer-events: none;
                height: 0;
            }
        </style>
    @endpush
@endonce
