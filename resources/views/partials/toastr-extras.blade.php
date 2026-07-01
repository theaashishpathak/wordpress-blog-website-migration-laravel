<style>
    /* Toastr dark-mode polish + match the app's rounded styling. */
    #toast-container > div {
        border-radius: 0.75rem !important;
        opacity: 0.97 !important;
        padding: 14px 18px 14px 50px !important;
        font-family: inherit;
    }
    #toast-container > .toast-success { background-color: #059669 !important; }
    #toast-container > .toast-error,
    #toast-container > .toast-danger { background-color: #dc2626 !important; }
    #toast-container > .toast-info { background-color: #2563eb !important; }
    #toast-container > .toast-warning { background-color: #d97706 !important; }
    .toast-progress { background: rgba(255,255,255,.35) !important; height: 3px !important; }

    .dark #toast-container > .toast-success { background-color: #047857 !important; }
    .dark #toast-container > .toast-error,
    .dark #toast-container > .toast-danger { background-color: #b91c1c !important; }
</style>

<script>
    (function () {
        // The app already wires basic Toastr in resources/js/app.js (showToast, initFlashToast,
        // and Livewire bridges for `toast`, `toast.success`, `toast.danger`).
        // This partial extends it with:
        //   - Re-scan after Livewire morph so flash divs added by component renders fire too
        //   - `toast.info` / `toast.warning` Livewire events
        //   - `toast` browser CustomEvent for pure JS dispatching

        function fire(message, type) {
            if (typeof window.crmShowToast === 'function') {
                window.crmShowToast(message, type || 'success');
            } else if (window.toastr) {
                const fn = type === 'danger' ? 'error' : type;
                if (typeof window.toastr[fn] === 'function') {
                    window.toastr[fn](message);
                } else {
                    window.toastr.success(message);
                }
            }
        }

        function scanFlashes(root) {
            const r = root && root.querySelectorAll ? root : document;
            r.querySelectorAll('[data-toast-message]:not([data-toast-fired])').forEach((el) => {
                const message = el.dataset.toastMessage;
                if (!message) return;
                fire(message, el.dataset.toastType || 'success');
                el.dataset.toastFired = '1';
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => scanFlashes());
        } else {
            scanFlashes();
        }

        document.addEventListener('livewire:initialized', () => {
            if (!window.Livewire) return;

            window.Livewire.on('toast.info', (event) => {
                const e = Array.isArray(event) ? event[0] : event;
                if (e && typeof e === 'object') fire(e.message || '', 'info');
            });
            window.Livewire.on('toast.warning', (event) => {
                const e = Array.isArray(event) ? event[0] : event;
                if (e && typeof e === 'object') fire(e.message || '', 'warning');
            });

            if (typeof window.Livewire.hook === 'function') {
                window.Livewire.hook('morph.added', ({ el }) => {
                    if (!el) return;
                    if (el.matches && el.matches('[data-toast-message]')) {
                        const message = el.dataset.toastMessage;
                        if (message && el.dataset.toastFired !== '1') {
                            fire(message, el.dataset.toastType || 'success');
                            el.dataset.toastFired = '1';
                        }
                    }
                    if (el.querySelectorAll) scanFlashes(el);
                });
                window.Livewire.hook('morph.updated', () => scanFlashes());
            }
        });

        // Pure JS API: window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Saved', type: 'success' } }))
        window.addEventListener('toast', (e) => {
            if (!e.detail) return;
            fire(e.detail.message || '', e.detail.type || 'success');
        });
    })();
</script>
