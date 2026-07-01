<style>
    /* Match Flatpickr theming with the rest of the app's rounded inputs */
    .flatpickr-input.form-control[readonly] { background-color: inherit; }
    .flatpickr-calendar { font-family: inherit; }
    .flatpickr-calendar.dark { background: #0f172a; box-shadow: 0 10px 30px rgba(0,0,0,.5); }
    .flatpickr-calendar.dark .flatpickr-day { color: #cbd5e1; }
    .flatpickr-calendar.dark .flatpickr-day.selected { background: #4f46e5; border-color: #4f46e5; }
    .flatpickr-calendar.dark .flatpickr-day:hover { background: #1e293b; }
    .flatpickr-calendar.dark .flatpickr-weekday,
    .flatpickr-calendar.dark .flatpickr-current-month { color: #f1f5f9; }
    .flatpickr-calendar.dark .flatpickr-monthDropdown-months { background: #0f172a; color: #f1f5f9; }
</style>

<script>
    (function () {
        if (typeof window.flatpickr === 'undefined') {
            return;
        }

        // Use page-level dark class to theme the calendar.
        const isDark = () => document.documentElement.classList.contains('dark');

        const baseOptions = () => ({
            allowInput: true,
            disableMobile: true, // force consistent UI on phones (avoid native picker)
        });

        const dateOptions = () => ({
            ...baseOptions(),
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'M j, Y',
            monthSelectorType: 'dropdown',
        });

        const timeOptions = () => ({
            ...baseOptions(),
            enableTime: true,
            noCalendar: true,
            dateFormat: 'H:i',          // value sent to server (24h) — DB save format
            altInput: true,
            altFormat: 'h:i K',          // visible to user (12h with AM/PM)
            time_24hr: false,
            minuteIncrement: 1,
        });

        const dateTimeOptions = () => ({
            ...baseOptions(),
            enableTime: true,
            dateFormat: 'Y-m-d H:i',    // value sent to server (24h)
            altInput: true,
            altFormat: 'M j, Y · h:i K', // visible (12h with AM/PM)
            time_24hr: false,
            minuteIncrement: 1,
            monthSelectorType: 'dropdown',
        });

        function initOne(input) {
            if (input.dataset.flatpickrInit === '1') return;
            if (input._flatpickr) return;

            // Pick config based on input type or explicit data attribute.
            const explicit = input.dataset.flatpickr; // 'date' | 'time' | 'datetime'
            const type = explicit
                || (input.type === 'date' ? 'date'
                    : input.type === 'time' ? 'time'
                    : input.type === 'datetime-local' ? 'datetime'
                    : null);

            if (!type) return;

            let config;
            if (type === 'date') config = dateOptions();
            else if (type === 'time') config = timeOptions();
            else if (type === 'datetime') config = dateTimeOptions();
            else return;

            // Flatpickr respects min/max from native attributes.
            if (input.min) config.minDate = input.min;
            if (input.max) config.maxDate = input.max;

            // Apply dark theme to the calendar popup.
            config.onOpen = [
                function (selectedDates, dateStr, instance) {
                    instance.calendarContainer.classList.toggle('dark', isDark());
                },
            ];

            try {
                window.flatpickr(input, config);
                input.dataset.flatpickrInit = '1';
            } catch (e) {
                console.warn('Flatpickr init failed for input', input, e);
            }
        }

        function scan(root = document) {
            root.querySelectorAll(
                'input[type="date"]:not([data-flatpickr-init]),'
                + 'input[type="time"]:not([data-flatpickr-init]),'
                + 'input[type="datetime-local"]:not([data-flatpickr-init]),'
                + 'input[data-flatpickr]:not([data-flatpickr-init])'
            ).forEach(initOne);
        }

        // Initial scan once DOM is ready.
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => scan());
        } else {
            scan();
        }

        // Re-scan after Livewire morphs (form re-renders, modals open, tabs switch, etc.).
        document.addEventListener('livewire:initialized', () => {
            if (window.Livewire && typeof window.Livewire.hook === 'function') {
                window.Livewire.hook('morph.added', ({ el }) => {
                    if (!el) return;
                    if (el.matches && (el.matches('input[type="date"]')
                        || el.matches('input[type="time"]')
                        || el.matches('input[type="datetime-local"]')
                        || el.matches('input[data-flatpickr]'))) {
                        initOne(el);
                    }
                    if (el.querySelectorAll) scan(el);
                });

                window.Livewire.hook('morph.updated', () => scan());
            }
        });

        // Watch for any other dynamic insertions (e.g. modals opened by raw JS).
        const observer = new MutationObserver((mutations) => {
            for (const m of mutations) {
                m.addedNodes.forEach((node) => {
                    if (node.nodeType !== 1) return;
                    if (node.matches && (node.matches('input[type="date"]')
                        || node.matches('input[type="time"]')
                        || node.matches('input[type="datetime-local"]')
                        || node.matches('input[data-flatpickr]'))) {
                        initOne(node);
                    }
                    if (node.querySelectorAll) scan(node);
                });
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });

        // Re-theme open calendars when the user toggles dark mode.
        const themeObserver = new MutationObserver(() => {
            document.querySelectorAll('.flatpickr-calendar.open').forEach((cal) => {
                cal.classList.toggle('dark', isDark());
            });
        });
        themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    })();
</script>
