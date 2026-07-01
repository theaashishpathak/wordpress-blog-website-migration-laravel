<style>
    /* Match Select2 styling with the rest of the app's rounded inputs */
    .select2-container--default .select2-selection--single,
    .select2-container--default .select2-selection--multiple {
        min-height: 46px;
        border-radius: 0.75rem !important;
        border-color: #e2e8f0 !important;
        padding: 0.25rem 0.5rem;
        background-color: #ffffff;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px;
        color: #0f172a;
        padding-left: 0.5rem;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 44px;
    }
    .select2-container--default.select2-container--focus .select2-selection--single,
    .select2-container--default.select2-container--focus .select2-selection--multiple,
    .select2-container--default.select2-container--open .select2-selection--single,
    .select2-container--default.select2-container--open .select2-selection--multiple {
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 1px rgba(99, 102, 241, .15);
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #eef2ff;
        border-color: #c7d2fe;
        color: #4338ca;
        border-radius: 0.5rem;
        padding: 2px 8px;
    }
    .select2-dropdown {
        border-radius: 0.75rem !important;
        border-color: #e2e8f0 !important;
        overflow: hidden;
    }
    .select2-results__option--highlighted[aria-selected],
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #4f46e5 !important;
    }

    /* Dark mode */
    .dark .select2-container--default .select2-selection--single,
    .dark .select2-container--default .select2-selection--multiple {
        background-color: #0f172a !important;
        border-color: #334155 !important;
    }
    .dark .select2-container--default .select2-selection--single .select2-selection__rendered,
    .dark .select2-container--default .select2-selection--multiple .select2-selection__rendered {
        color: #e2e8f0 !important;
    }
    .dark .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #64748b !important;
    }
    .dark .select2-dropdown {
        background-color: #0f172a !important;
        border-color: #334155 !important;
    }
    .dark .select2-results__option {
        color: #cbd5e1;
    }
    .dark .select2-search--dropdown .select2-search__field {
        background-color: #1e293b;
        border-color: #334155;
        color: #e2e8f0;
    }
    .dark .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #312e81;
        border-color: #4338ca;
        color: #e0e7ff;
    }
</style>

<script>
    (function () {
        if (typeof window.jQuery === 'undefined' || typeof window.jQuery.fn.select2 === 'undefined') {
            return;
        }

        const $ = window.jQuery;

        function initOne(el) {
            if (el.dataset.select2Init === '1') return;
            const $el = $(el);
            if ($el.hasClass('select2-hidden-accessible')) return;

            const config = {
                width: '100%',
                placeholder: el.dataset.placeholder || el.getAttribute('placeholder') || 'Select an option',
                allowClear: el.hasAttribute('data-allow-clear') || el.dataset.select2 === 'clearable',
                tags: el.hasAttribute('data-tags'),
                minimumResultsForSearch: el.hasAttribute('data-no-search') ? Infinity : 0,
                dropdownParent: el.closest('.modal, [data-modal]') ? $(el.closest('.modal, [data-modal]')) : $(document.body),
            };

            $el.select2(config);

            // Bridge Select2 → Livewire wire:model.
            // Livewire listens for input/change on the original select; Select2 fires change but not input.
            $el.on('change.select2-livewire', function () {
                el.dispatchEvent(new Event('input', { bubbles: true }));
            });

            el.dataset.select2Init = '1';
        }

        function destroyOne(el) {
            if (el.dataset.select2Init !== '1') return;
            try {
                const $el = $(el);
                $el.off('change.select2-livewire');
                if ($el.data('select2')) $el.select2('destroy');
            } catch (_) { /* ignore */ }
            delete el.dataset.select2Init;
        }

        function scan(root) {
            const r = root && root.querySelectorAll ? root : document;
            r.querySelectorAll('select[data-select2]:not([data-select2-init])').forEach(initOne);
        }

        // Initial scan.
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => scan());
        } else {
            scan();
        }

        // Livewire integration.
        document.addEventListener('livewire:initialized', () => {
            if (window.Livewire && typeof window.Livewire.hook === 'function') {
                // When Livewire is about to morph, destroy Select2 so the morph diff stays clean.
                window.Livewire.hook('morph', ({ el }) => {
                    if (!el || !el.querySelectorAll) return;
                    el.querySelectorAll('select[data-select2-init="1"]').forEach(destroyOne);
                });

                // After morph, re-init.
                window.Livewire.hook('morph.added', ({ el }) => {
                    if (!el) return;
                    if (el.matches && el.matches('select[data-select2]')) initOne(el);
                    if (el.querySelectorAll) scan(el);
                });

                window.Livewire.hook('morph.updated', ({ el }) => {
                    if (!el || !el.querySelectorAll) return;
                    el.querySelectorAll('select[data-select2]:not([data-select2-init])').forEach(initOne);
                });
            }
        });

        // Watch for raw JS-driven insertions (e.g. modals).
        const observer = new MutationObserver((mutations) => {
            for (const m of mutations) {
                m.addedNodes.forEach((node) => {
                    if (node.nodeType !== 1) return;
                    if (node.matches && node.matches('select[data-select2]')) initOne(node);
                    if (node.querySelectorAll) scan(node);
                });
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    })();
</script>
