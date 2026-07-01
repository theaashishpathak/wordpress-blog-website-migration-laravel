<style>
    /* Custom file upload styling — covers any <input type="file"> in the app. */
    .x-file-wrap { position: relative; display: flex; flex-direction: column; gap: 0.5rem; }

    .x-file-zone {
        position: relative;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.875rem 1rem;
        border: 1px dashed #cbd5e1;
        background-color: #f8fafc;
        border-radius: 0.75rem;
        cursor: pointer;
        transition: border-color .15s, background-color .15s, transform .15s;
    }
    .x-file-zone:hover {
        border-color: #6366f1;
        background-color: #eef2ff;
    }
    .x-file-zone.is-dragover {
        border-color: #4f46e5;
        background-color: #eef2ff;
        transform: scale(1.01);
    }
    .x-file-zone.is-uploading { border-color: #6366f1; }

    .x-file-icon {
        display: grid;
        place-items: center;
        width: 2.5rem; height: 2.5rem;
        border-radius: 0.625rem;
        background: #e0e7ff;
        color: #4338ca;
        flex-shrink: 0;
    }
    .x-file-meta { min-width: 0; flex: 1 1 auto; }
    .x-file-meta .x-file-title {
        font-size: .875rem; font-weight: 600; color: #0f172a;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .x-file-meta .x-file-sub { font-size: .75rem; color: #64748b; margin-top: 2px; }
    .x-file-actions { display: flex; gap: 0.375rem; flex-shrink: 0; }
    .x-file-actions button {
        font-size: .75rem; font-weight: 600; padding: .35rem .6rem;
        border-radius: .5rem; background: #ffffff; border: 1px solid #e2e8f0;
        color: #334155; cursor: pointer;
    }
    .x-file-actions button.danger { color: #b91c1c; border-color: #fecaca; background: #fef2f2; }
    .x-file-actions button:hover { background: #f1f5f9; }

    /* Native input is hidden but kept in DOM for Livewire wire:model binding. */
    .x-file-native {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .x-file-progress {
        position: absolute;
        left: 0; right: 0; bottom: 0;
        height: 3px;
        background: rgba(99, 102, 241, .12);
        border-radius: 0 0 0.75rem 0.75rem;
        overflow: hidden;
        opacity: 0;
        transition: opacity .15s;
    }
    .x-file-progress.is-visible { opacity: 1; }
    .x-file-progress > .bar {
        height: 100%;
        width: 0%;
        background: linear-gradient(90deg, #4f46e5, #06b6d4);
        transition: width .2s ease;
    }

    .x-file-error {
        font-size: .75rem; color: #b91c1c; padding: .25rem .25rem 0;
    }

    /* Dark mode */
    .dark .x-file-zone { background-color: #0f172a; border-color: #334155; }
    .dark .x-file-zone:hover { background-color: #1e1b4b; border-color: #6366f1; }
    .dark .x-file-icon { background: #312e81; color: #c7d2fe; }
    .dark .x-file-meta .x-file-title { color: #e2e8f0; }
    .dark .x-file-meta .x-file-sub { color: #94a3b8; }
    .dark .x-file-actions button { background: #1e293b; border-color: #334155; color: #cbd5e1; }
    .dark .x-file-actions button:hover { background: #0f172a; }
    .dark .x-file-actions button.danger { color: #fca5a5; background: rgba(239,68,68,.1); border-color: rgba(248,113,113,.3); }
</style>

<script>
    (function () {
        const ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05L12.25 20.24a6 6 0 0 1-8.49-8.49l8.49-8.48a4 4 0 0 1 5.66 5.65l-8.49 8.49a2 2 0 0 1-2.83-2.83l7.07-7.07"/></svg>';

        function fmtBytes(b) {
            if (!b && b !== 0) return '';
            if (b < 1024) return b + ' B';
            if (b < 1024*1024) return (b/1024).toFixed(1) + ' KB';
            if (b < 1024*1024*1024) return (b/1024/1024).toFixed(1) + ' MB';
            return (b/1024/1024/1024).toFixed(2) + ' GB';
        }

        function enhance(input) {
            if (input.dataset.fileEnhanced === '1') return;
            if (input.closest('.x-file-wrap')) { input.dataset.fileEnhanced = '1'; return; }
            if (input.hasAttribute('data-no-enhance')) return;

            // Build wrapper.
            const wrap = document.createElement('div');
            wrap.className = 'x-file-wrap';

            const zone = document.createElement('label');
            zone.className = 'x-file-zone';

            const icon = document.createElement('span');
            icon.className = 'x-file-icon';
            icon.innerHTML = ICON;

            const meta = document.createElement('div');
            meta.className = 'x-file-meta';
            meta.innerHTML = '<div class="x-file-title">Choose file or drag & drop</div>'
                + '<div class="x-file-sub">' + (input.accept ? 'Accepts: ' + input.accept : 'Any file type') + '</div>';

            const actions = document.createElement('div');
            actions.className = 'x-file-actions';
            const replaceBtn = document.createElement('button');
            replaceBtn.type = 'button';
            replaceBtn.textContent = 'Browse';
            replaceBtn.addEventListener('click', (e) => { e.preventDefault(); input.click(); });
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.textContent = 'Remove';
            removeBtn.className = 'danger';
            removeBtn.style.display = 'none';
            removeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                input.value = '';
                input.dispatchEvent(new Event('change', { bubbles: true }));
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
            actions.appendChild(replaceBtn);
            actions.appendChild(removeBtn);

            const progress = document.createElement('div');
            progress.className = 'x-file-progress';
            progress.innerHTML = '<div class="bar"></div>';

            const error = document.createElement('div');
            error.className = 'x-file-error';
            error.style.display = 'none';

            zone.appendChild(icon);
            zone.appendChild(meta);
            zone.appendChild(actions);
            zone.appendChild(progress);

            // Move input INSIDE the label so clicking the zone opens the picker.
            input.parentNode.insertBefore(wrap, input);
            wrap.appendChild(zone);
            wrap.appendChild(error);
            zone.appendChild(input); // keep native input for wire:model
            input.classList.add('x-file-native');
            input.dataset.fileEnhanced = '1';

            const titleEl = meta.querySelector('.x-file-title');
            const subEl = meta.querySelector('.x-file-sub');
            const bar = progress.querySelector('.bar');

            function paint() {
                const f = input.files && input.files[0];
                if (f) {
                    titleEl.textContent = f.name;
                    subEl.textContent = fmtBytes(f.size) + (f.type ? ' · ' + f.type : '');
                    removeBtn.style.display = '';
                } else {
                    titleEl.textContent = 'Choose file or drag & drop';
                    subEl.textContent = input.accept ? 'Accepts: ' + input.accept : 'Any file type';
                    removeBtn.style.display = 'none';
                }
                error.style.display = 'none';
                error.textContent = '';
            }

            input.addEventListener('change', paint);
            paint();

            // Drag-and-drop.
            ['dragenter','dragover'].forEach(ev => zone.addEventListener(ev, e => {
                e.preventDefault(); e.stopPropagation();
                zone.classList.add('is-dragover');
            }));
            ['dragleave','drop'].forEach(ev => zone.addEventListener(ev, e => {
                e.preventDefault(); e.stopPropagation();
                zone.classList.remove('is-dragover');
            }));
            zone.addEventListener('drop', e => {
                if (!e.dataTransfer || !e.dataTransfer.files || !e.dataTransfer.files.length) return;
                input.files = e.dataTransfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });

            // Livewire upload progress events.
            // Livewire fires these on the input element itself.
            input.addEventListener('livewire-upload-start', () => {
                zone.classList.add('is-uploading');
                progress.classList.add('is-visible');
                bar.style.width = '5%';
                error.style.display = 'none';
            });
            input.addEventListener('livewire-upload-progress', (e) => {
                const pct = (e.detail && typeof e.detail.progress === 'number') ? e.detail.progress : 50;
                bar.style.width = pct + '%';
            });
            input.addEventListener('livewire-upload-finish', () => {
                bar.style.width = '100%';
                setTimeout(() => {
                    zone.classList.remove('is-uploading');
                    progress.classList.remove('is-visible');
                    bar.style.width = '0%';
                }, 400);
            });
            input.addEventListener('livewire-upload-error', (e) => {
                zone.classList.remove('is-uploading');
                progress.classList.remove('is-visible');
                bar.style.width = '0%';
                error.style.display = '';
                error.textContent = (e.detail && e.detail.message) || 'Upload failed.';
            });
        }

        function scan(root) {
            const r = root && root.querySelectorAll ? root : document;
            r.querySelectorAll('input[type="file"]:not([data-file-enhanced]):not([data-no-enhance])').forEach(enhance);
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
                    if (el.matches && el.matches('input[type="file"]')) enhance(el);
                    if (el.querySelectorAll) scan(el);
                });
                window.Livewire.hook('morph.updated', ({ el }) => {
                    if (!el || !el.querySelectorAll) return;
                    el.querySelectorAll('input[type="file"]:not([data-file-enhanced])').forEach(enhance);
                });
            }
        });

        const observer = new MutationObserver((mutations) => {
            for (const m of mutations) {
                m.addedNodes.forEach((node) => {
                    if (node.nodeType !== 1) return;
                    if (node.matches && node.matches('input[type="file"]')) enhance(node);
                    if (node.querySelectorAll) scan(node);
                });
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    })();
</script>
