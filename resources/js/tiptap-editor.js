/*
|--------------------------------------------------------------------------
| TipTap rich-text editor — Alpine wrapper
|--------------------------------------------------------------------------
| Single Alpine factory `tiptapEditor(initial)` that mounts a TipTap
| instance into the element, mirrors changes into a hidden textarea so
| Livewire's wire:model picks them up, and exposes simple `cmd(name, ...)`
| / `is(name)` helpers the toolbar buttons call.
|
| Usage in Blade:
|
|     <x-admin.rich-editor wire:model="content" />
|
| The component renders:
|
|     <div x-data="tiptapEditor($refs.textarea.value)" x-init="init()">
|         <div class="toolbar">...</div>
|         <div x-ref="mount"></div>            ← TipTap mounts here
|         <textarea x-ref="textarea" wire:model.defer="content" hidden></textarea>
|     </div>
|
| The textarea stays the source of truth for the server: Livewire reads
| from it via wire:model. The Alpine instance keeps it in sync after every
| edit via the `onUpdate` hook.
*/

import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';
import Typography from '@tiptap/extension-typography';
import Underline from '@tiptap/extension-underline';
import TextAlign from '@tiptap/extension-text-align';
import Table from '@tiptap/extension-table';
import TableRow from '@tiptap/extension-table-row';
import TableHeader from '@tiptap/extension-table-header';
import TableCell from '@tiptap/extension-table-cell';
import Youtube from '@tiptap/extension-youtube';

window.tiptapEditor = function tiptapEditor(initial = '', opts = {}) {
    return {
        editor: null,
        // Reactive flags powering the toolbar's active states.
        states: {
            bold: false, italic: false, underline: false, strike: false,
            h2: false, h3: false, h4: false,
            ul: false, ol: false, blockquote: false, code: false, codeBlock: false,
            link: false,
            alignLeft: false, alignCenter: false, alignRight: false,
        },
        sourceView: false,

        init() {
            const placeholder = this.$el.dataset.placeholder || 'Start writing your article…';

            this.editor = new Editor({
                element: this.$refs.mount,
                extensions: [
                    StarterKit.configure({
                        // We bring our own Link extension so we can configure target/rel.
                        link: false,
                        // Headings: news editors typically use h2/h3/h4 only — h1 is reserved
                        // for the post title rendered on the frontend page.
                        heading: { levels: [2, 3, 4] },
                    }),
                    Underline,
                    Link.configure({
                        openOnClick: false,
                        autolink: true,
                        HTMLAttributes: {
                            rel: 'noopener noreferrer nofollow',
                            target: '_blank',
                        },
                    }),
                    Image.configure({
                        inline: false,
                        allowBase64: false,
                        HTMLAttributes: { class: 'rounded-xl mx-auto' },
                    }),
                    Placeholder.configure({ placeholder }),
                    Typography,
                    TextAlign.configure({ types: ['heading', 'paragraph'] }),
                    Table.configure({ resizable: true }),
                    TableRow, TableHeader, TableCell,
                    Youtube.configure({ width: 640, height: 360, controls: true }),
                ],
                content: initial || '',
                editorProps: {
                    attributes: {
                        // Tailwind prose for nice in-editor preview.
                        class: 'prose prose-lg max-w-none focus:outline-none min-h-[400px] px-4 py-3 dark:prose-invert',
                        spellcheck: 'true',
                    },
                    // Paste handler — strip Office/Word junk while keeping
                    // basic structure (headings, lists, links, images).
                    // TipTap already does most of this via prosemirror-paste-rules
                    // but we explicitly normalise smart quotes / NBSP that
                    // confuse downstream sanitizers.
                    transformPastedHTML: (html) => sanitizePastedHtml(html),
                },
                onUpdate: ({ editor }) => {
                    const html = editor.getHTML();
                    this.syncToTextarea(html);
                },
                onCreate: () => this.refreshStates(),
                onSelectionUpdate: () => this.refreshStates(),
                onTransaction: () => this.refreshStates(),
            });

            // If Livewire pushes a new value into the textarea (e.g. AI
            // assistant writes a draft) reflect it back into the editor.
            this.$watch('$refs.textarea.value', (value) => {
                if (! this.editor) return;
                if (value === this.editor.getHTML()) return;
                this.editor.commands.setContent(value || '', false);
            });

            // Same idea but for a custom event the Livewire AI drawer
            // dispatches — `editor:set-content`.
            window.addEventListener('editor:set-content', (event) => {
                if (! this.editor) return;
                const next = event?.detail?.content ?? '';
                this.editor.commands.setContent(next, true);
                this.syncToTextarea(next);
            });
        },

        // Push the latest HTML into the hidden textarea AND dispatch a
        // native `input` event so Livewire's wire:model picks it up.
        syncToTextarea(html) {
            const ta = this.$refs.textarea;
            if (! ta) return;
            ta.value = html;
            ta.dispatchEvent(new Event('input', { bubbles: true }));
        },

        refreshStates() {
            if (! this.editor) return;
            const e = this.editor;
            this.states = {
                bold: e.isActive('bold'),
                italic: e.isActive('italic'),
                underline: e.isActive('underline'),
                strike: e.isActive('strike'),
                h2: e.isActive('heading', { level: 2 }),
                h3: e.isActive('heading', { level: 3 }),
                h4: e.isActive('heading', { level: 4 }),
                ul: e.isActive('bulletList'),
                ol: e.isActive('orderedList'),
                blockquote: e.isActive('blockquote'),
                code: e.isActive('code'),
                codeBlock: e.isActive('codeBlock'),
                link: e.isActive('link'),
                alignLeft: e.isActive({ textAlign: 'left' }),
                alignCenter: e.isActive({ textAlign: 'center' }),
                alignRight: e.isActive({ textAlign: 'right' }),
            };
        },

        // ── Toolbar handlers ───────────────────────────────────────────────

        cmd(name, payload) {
            if (! this.editor) return;
            const chain = this.editor.chain().focus();
            switch (name) {
                case 'bold': chain.toggleBold().run(); break;
                case 'italic': chain.toggleItalic().run(); break;
                case 'underline': chain.toggleUnderline().run(); break;
                case 'strike': chain.toggleStrike().run(); break;
                case 'h2': chain.toggleHeading({ level: 2 }).run(); break;
                case 'h3': chain.toggleHeading({ level: 3 }).run(); break;
                case 'h4': chain.toggleHeading({ level: 4 }).run(); break;
                case 'paragraph': chain.setParagraph().run(); break;
                case 'ul': chain.toggleBulletList().run(); break;
                case 'ol': chain.toggleOrderedList().run(); break;
                case 'blockquote': chain.toggleBlockquote().run(); break;
                case 'codeBlock': chain.toggleCodeBlock().run(); break;
                case 'hr': chain.setHorizontalRule().run(); break;
                case 'clear': chain.clearNodes().unsetAllMarks().run(); break;
                case 'undo': chain.undo().run(); break;
                case 'redo': chain.redo().run(); break;
                case 'alignLeft': chain.setTextAlign('left').run(); break;
                case 'alignCenter': chain.setTextAlign('center').run(); break;
                case 'alignRight': chain.setTextAlign('right').run(); break;
                case 'link': this.promptLink(); break;
                case 'unlink': chain.unsetLink().run(); break;
                case 'image': this.promptImage(payload); break;
                case 'youtube': this.promptYoutube(); break;
                case 'table': chain.insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(); break;
                case 'sourceView': this.toggleSourceView(); break;
            }
        },

        promptLink() {
            const previousUrl = this.editor.getAttributes('link').href;
            const url = window.prompt('Link URL', previousUrl || 'https://');
            if (url === null) return;
            if (url === '') {
                this.editor.chain().focus().extendMarkRange('link').unsetLink().run();
                return;
            }
            this.editor.chain().focus().extendMarkRange('link').setLink({ href: url }).run();
        },

        promptImage(presetUrl) {
            // If a URL is passed in (e.g. from the media picker) use it
            // directly; otherwise ask the user.
            let url = presetUrl;
            if (! url) {
                url = window.prompt('Image URL', 'https://');
                if (url === null || url === '') return;
            }
            this.editor.chain().focus().setImage({ src: url, alt: '' }).run();
        },

        promptYoutube() {
            const url = window.prompt('YouTube URL');
            if (! url) return;
            this.editor.commands.setYoutubeVideo({ src: url });
        },

        toggleSourceView() {
            this.sourceView = ! this.sourceView;
        },

        // ── Source-view textarea (raw HTML editor) ──────────────────────────

        get rawHtml() {
            return this.editor ? this.editor.getHTML() : '';
        },

        setRawHtml(value) {
            if (! this.editor) return;
            this.editor.commands.setContent(value || '', true);
            this.syncToTextarea(value || '');
        },

        is(name) {
            return !! this.states[name];
        },
    };
};

/**
 * Light defensive pre-pass on pasted HTML. ProseMirror's schema does most
 * of the heavy filtering at insertion time, so we only need to:
 *   1. Strip Microsoft Office "mso-" classes / comments that confuse it.
 *   2. Replace non-breaking spaces with regular spaces (Word/Google Docs).
 *   3. Drop empty <span> wrappers that Word emits.
 * Server-side HTMLPurifier still runs on save — this is just UX polish so
 * the editor doesn't show garbage right after a paste.
 */
function sanitizePastedHtml(html) {
    if (! html) return html;
    return html
        // Word conditional comments
        .replace(/<!--\[if[^\]]*]>[\s\S]*?<!\[endif]-->/gi, '')
        // mso-* classes and inline styles
        .replace(/\sclass="?Mso[^"\s>]*"?/gi, '')
        .replace(/\smso-[^:]+:[^;"]+;?/gi, '')
        // NBSP -> normal space
        .replace(/ /g, ' ')
        // Drop empty spans / fonts
        .replace(/<(span|font)[^>]*>(\s*)<\/\1>/gi, '$2');
}
