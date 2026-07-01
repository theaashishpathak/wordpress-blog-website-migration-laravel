<style>
    /* Match Summernote with the rest of the app's rounded inputs */
    .note-editor.note-frame {
        border-radius: 0.75rem !important;
        border-color: #e2e8f0 !important;
        background-color: #ffffff;
    }
    .note-editor.note-frame .note-statusbar { display: none; }
    .note-editor .note-toolbar {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        border-top-left-radius: 0.75rem;
        border-top-right-radius: 0.75rem;
    }
    .note-editor.note-frame .note-editing-area .note-editable {
        background-color: #ffffff;
        color: #0f172a;
    }
    .note-popover .popover-content,
    .note-toolbar { padding: 5px 5px 5px 5px; }

    /* Dark mode */
    .dark .note-editor.note-frame { border-color: #334155 !important; background-color: #0f172a; }
    .dark .note-editor .note-toolbar {
        background: #1e293b;
        border-bottom-color: #334155;
    }
    .dark .note-btn-group .note-btn {
        background-color: #0f172a;
        border-color: #334155;
        color: #e2e8f0;
    }
    .dark .note-btn-group .note-btn:hover { background-color: #1e293b; }
    .dark .note-editor.note-frame .note-editing-area .note-editable {
        background-color: #0f172a;
        color: #e2e8f0;
    }
    .dark .note-modal-content { background-color: #0f172a; color: #e2e8f0; border-color: #334155; }
    .dark .note-modal-content .note-modal-header { border-color: #334155; }
    .dark .note-modal-content .form-control { background-color: #1e293b; border-color: #334155; color: #e2e8f0; }
</style>

<script>
    (function () {
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.summernote === 'undefined') {
            return;
        }

        const $ = window.jQuery;

        const TOOLBAR_DEFAULT = [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link']],
            ['view', ['fullscreen', 'codeview', 'help']],
        ];

        const TOOLBAR_MINIMAL = [
            ['font', ['bold', 'italic', 'underline']],
            ['para', ['ul', 'ol']],
            ['insert', ['link']],
        ];

        function initOne(el) {
            if (el.dataset.summernoteInit === '1') return;
            const $el = $(el);
            if ($el.next('.note-editor').length > 0) return;

            const minimal = el.hasAttribute('data-minimal');
            const toolbar = minimal ? TOOLBAR_MINIMAL : TOOLBAR_DEFAULT;
            const height = parseInt(el.dataset.height || '220', 10);
            const placeholder = el.dataset.placeholder || el.getAttribute('placeholder') || 'Write here...';

            $el.summernote({
                height,
                placeholder,
                toolbar,
                disableDragAndDrop: false,
                callbacks: {
                    onChange: function (contents) {
                        // Bridge Summernote → Livewire wire:model.
                        el.value = contents;
                        el.dispatchEvent(new Event('input', { bubbles: true }));
                        el.dispatchEvent(new Event('change', { bubbles: true }));
                    },
                    onBlur: function () {
                        el.dispatchEvent(new Event('blur', { bubbles: true }));
                    },
                },
            });

            el.dataset.summernoteInit = '1';
        }

        function destroyOne(el) {
            if (el.dataset.summernoteInit !== '1') return;
            try {
                const $el = $(el);
                if ($el.next('.note-editor').length > 0) {
                    $el.summernote('destroy');
                }
            } catch (_) { /* ignore */ }
            delete el.dataset.summernoteInit;
        }

        function scan(root) {
            const r = root && root.querySelectorAll ? root : document;
            r.querySelectorAll('textarea[data-summernote]:not([data-summernote-init])').forEach(initOne);
            // Backwards-compat: legacy class-based hook.
            r.querySelectorAll('textarea.summernote:not([data-summernote-init])').forEach(initOne);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => scan());
        } else {
            scan();
        }

        document.addEventListener('livewire:initialized', () => {
            if (window.Livewire && typeof window.Livewire.hook === 'function') {
                // Destroy before morph so Livewire's diff sees the original textarea.
                window.Livewire.hook('morph', ({ el }) => {
                    if (!el || !el.querySelectorAll) return;
                    el.querySelectorAll('textarea[data-summernote-init="1"]').forEach(destroyOne);
                });
                window.Livewire.hook('morph.added', ({ el }) => {
                    if (!el) return;
                    if (el.matches && (el.matches('textarea[data-summernote]') || el.matches('textarea.summernote'))) initOne(el);
                    if (el.querySelectorAll) scan(el);
                });
                window.Livewire.hook('morph.updated', ({ el }) => {
                    if (!el || !el.querySelectorAll) return;
                    el.querySelectorAll('textarea[data-summernote]:not([data-summernote-init]), textarea.summernote:not([data-summernote-init])').forEach(initOne);
                });
            }
        });

        const observer = new MutationObserver((mutations) => {
            for (const m of mutations) {
                m.addedNodes.forEach((node) => {
                    if (node.nodeType !== 1) return;
                    if (node.matches && (node.matches('textarea[data-summernote]') || node.matches('textarea.summernote'))) initOne(node);
                    if (node.querySelectorAll) scan(node);
                });
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    })();
</script>
