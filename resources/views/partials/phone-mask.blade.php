<script>
    (function () {
        // Pure JS phone-number masking — no external library.
        // Default pattern: international "+1 (XXX) XXX-XXXX" but customizable per field via:
        //   <input type="tel" data-phone-mask>                                  (uses default below)
        //   <input type="tel" data-phone-mask="+## ### ### ####">               (custom)
        //   <input type="tel" data-phone-mask="(###) ###-####">                 (US local)
        //
        // # = digit placeholder. Any other character is kept as a literal separator.

        const DEFAULT_MASK = '+# (###) ###-####';

        function applyMask(value, mask) {
            const digits = (value || '').replace(/\D/g, '');
            if (digits === '') return '';

            let out = '';
            let di = 0;

            for (let i = 0; i < mask.length && di < digits.length; i++) {
                const m = mask[i];
                if (m === '#') {
                    out += digits[di++];
                } else {
                    out += m;
                }
            }

            return out;
        }

        function getCaretAfterFormat(originalCaret, oldVal, newVal) {
            // Count digits before the caret in old value, then place caret after the same digit in new value.
            const digitsBefore = (oldVal.slice(0, originalCaret).match(/\d/g) || []).length;
            let count = 0;
            for (let i = 0; i < newVal.length; i++) {
                if (/\d/.test(newVal[i])) count++;
                if (count === digitsBefore) return i + 1;
            }
            return newVal.length;
        }

        function bindOne(input) {
            if (input.dataset.phoneMaskInit === '1') return;

            const mask = input.dataset.phoneMask && input.dataset.phoneMask.trim() !== ''
                ? input.dataset.phoneMask
                : DEFAULT_MASK;

            // Format any existing initial value.
            if (input.value) {
                input.value = applyMask(input.value, mask);
            }

            const handler = function (e) {
                const isBackspace = e && e.inputType === 'deleteContentBackward';
                const old = input.value;
                const oldCaret = input.selectionStart || 0;

                let newVal = applyMask(input.value, mask);

                // For backspace at a separator position, drop one extra digit so the user can keep deleting.
                if (isBackspace && newVal === old && newVal.length > 0) {
                    const trimmed = (input.value.replace(/\D/g, '')).slice(0, -1);
                    newVal = applyMask(trimmed, mask);
                }

                if (newVal !== old) {
                    input.value = newVal;
                    const pos = getCaretAfterFormat(oldCaret, old, newVal);
                    try { input.setSelectionRange(pos, pos); } catch (_) {}

                    // Also fire change for Livewire wire:model (input event already bubbled from typing).
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            };

            input.addEventListener('input', handler);
            input.addEventListener('blur', () => {
                input.value = applyMask(input.value, mask);
            });

            input.dataset.phoneMaskInit = '1';
        }

        function scan(root) {
            const r = root && root.querySelectorAll ? root : document;
            // Auto-mask any input[type=tel] OR any input with data-phone-mask attribute.
            r.querySelectorAll(
                'input[type="tel"]:not([data-phone-mask-init]):not([data-no-mask]),'
                + 'input[data-phone-mask]:not([data-phone-mask-init])'
            ).forEach(bindOne);
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => scan());
        } else {
            scan();
        }

        document.addEventListener('livewire:initialized', () => {
            if (window.Livewire && typeof window.Livewire.hook === 'function') {
                window.Livewire.hook('morph.added', ({ el }) => {
                    if (!el) return;
                    if (el.matches && (el.matches('input[type="tel"]') || el.matches('input[data-phone-mask]'))) bindOne(el);
                    if (el.querySelectorAll) scan(el);
                });
                window.Livewire.hook('morph.updated', ({ el }) => {
                    if (!el || !el.querySelectorAll) return;
                    el.querySelectorAll('input[type="tel"]:not([data-phone-mask-init]):not([data-no-mask]), input[data-phone-mask]:not([data-phone-mask-init])').forEach(bindOne);
                });
            }
        });

        const observer = new MutationObserver((mutations) => {
            for (const m of mutations) {
                m.addedNodes.forEach((node) => {
                    if (node.nodeType !== 1) return;
                    if (node.matches && (node.matches('input[type="tel"]') || node.matches('input[data-phone-mask]'))) bindOne(node);
                    if (node.querySelectorAll) scan(node);
                });
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    })();
</script>
