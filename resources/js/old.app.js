const themeStorageKey = 'crm-theme';
const sidebarStorageKey = 'crm-sidebar-collapsed';

const isDesktop = () => window.innerWidth >= 1024;

const setTheme = (theme) => {
	const root = document.documentElement;
	const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
	const shouldUseDark = theme === 'dark' || (theme === 'system' && prefersDark);

	root.classList.toggle('dark', shouldUseDark);
	localStorage.setItem(themeStorageKey, theme);

	document.querySelectorAll('[data-theme-choice]').forEach((radio) => {
		radio.checked = radio.value === theme;
	});
};

const showToast = (message, type = 'success') => {
	if (!message || !window.toastr) {
		return;
	}

 const normalizedType = type === 'danger' ? 'error' : type;

	toastr.options = {
		closeButton: true,
		progressBar: true,
		positionClass: 'toast-top-right',
		timeOut: 2500,
	};

			 if (typeof toastr[normalizedType] === 'function') {
			  toastr[normalizedType](message);
	} else {
		toastr.success(message);
	}
};

const openModal = (id) => {
	const modal = document.querySelector(`[data-modal="${id}"]`);

	if (!modal) {
		return;
	}

	modal.classList.remove('hidden');
	modal.classList.add('flex');

	const panel = modal.querySelector('[data-modal-panel]');
	if (panel) {
		panel.classList.remove('translate-x-full');
	}
};

const closeModal = (id) => {
	const modal = document.querySelector(`[data-modal="${id}"]`);

	if (!modal) {
		return;
	}

	const panel = modal.querySelector('[data-modal-panel]');
	if (panel) {
		panel.classList.add('translate-x-full');
	}

	modal.classList.add('hidden');
	modal.classList.remove('flex');
};

const closeAllDropdowns = () => {
	document.querySelectorAll('[data-dropdown]').forEach((dropdown) => {
		dropdown.classList.add('hidden');
	});
};

const toggleDropdown = (id) => {
	const dropdown = document.querySelector(`[data-dropdown="${id}"]`);

	if (!dropdown) {
		return;
	}

	const wasHidden = dropdown.classList.contains('hidden');
	closeAllDropdowns();

	if (wasHidden) {
		dropdown.classList.remove('hidden');
	}
};

const updateSidebarLabels = (collapsed) => {
	document.querySelectorAll('[data-sidebar-label]').forEach((label) => {
		label.classList.toggle('hidden', collapsed);
	});
};

const applySidebarState = (collapsed) => {
	const sidebar = document.querySelector('[data-sidebar]');

	if (!sidebar) {
		return;
	}

	sidebar.classList.toggle('w-60', !collapsed);
	sidebar.classList.toggle('w-16', collapsed);
	sidebar.classList.toggle('lg:w-60', !collapsed);
	sidebar.classList.toggle('lg:w-16', collapsed);
	updateSidebarLabels(collapsed);
	localStorage.setItem(sidebarStorageKey, collapsed ? '1' : '0');
};

const toggleSidebar = () => {
	const sidebar = document.querySelector('[data-sidebar]');
	const backdrop = document.querySelector('[data-sidebar-backdrop]');

	if (!sidebar) {
		return;
	}

	if (isDesktop()) {
		const collapsed = sidebar.classList.contains('w-60');
		applySidebarState(collapsed);
		return;
	}

	const isHidden = sidebar.classList.contains('-translate-x-full');
	sidebar.classList.toggle('-translate-x-full', !isHidden);
	sidebar.classList.toggle('translate-x-0', isHidden);

	if (backdrop) {
		backdrop.classList.toggle('hidden', !isHidden);
	}
};

const closeSidebar = () => {
	const sidebar = document.querySelector('[data-sidebar]');
	const backdrop = document.querySelector('[data-sidebar-backdrop]');

	if (sidebar && !isDesktop()) {
		sidebar.classList.add('-translate-x-full');
		sidebar.classList.remove('translate-x-0');
	}

	if (backdrop) {
		backdrop.classList.add('hidden');
	}
};

const initCharts = () => {
	if (!window.Chart) {
		return;
	}

	document.querySelectorAll('[data-chart-canvas]').forEach((canvas) => {
		if (canvas.dataset.chartInitialized === '1') {
			return;
		}

		const chartConfig = canvas.dataset.chartConfig;
		if (!chartConfig) {
			return;
		}

		try {
			const parsedConfig = JSON.parse(chartConfig);
			new Chart(canvas, parsedConfig);
			canvas.dataset.chartInitialized = '1';
		} catch (error) {
			console.error('Failed to parse chart config', error);
		}
	});

	const dashboardData = window.crmDashboardData;
	const reportsData = window.crmReportsData;

	const revenueCanvas = document.getElementById('dashboardRevenueChart');
	if (revenueCanvas && dashboardData) {
		new Chart(revenueCanvas, {
			type: 'line',
			data: {
				labels: dashboardData.labels,
				datasets: [
					{
						label: 'Revenue',
						data: dashboardData.revenue,
						borderColor: '#4f46e5',
						backgroundColor: 'rgba(79, 70, 229, 0.12)',
						tension: 0.4,
						fill: true,
					},
				],
			},
			options: {
				responsive: true,
				plugins: {
					legend: {
						display: false,
					},
				},
				scales: {
					y: {
						beginAtZero: true,
					},
				},
			},
		});
	}

	const leadsCanvas = document.getElementById('dashboardLeadsChart');
	if (leadsCanvas && dashboardData) {
		new Chart(leadsCanvas, {
			type: 'bar',
			data: {
				labels: dashboardData.labels,
				datasets: [
					{
						label: 'Leads',
						data: dashboardData.leads,
						backgroundColor: '#6366f1',
					},
				],
			},
			options: {
				responsive: true,
				plugins: {
					legend: {
						display: false,
					},
				},
				scales: {
					y: {
						beginAtZero: true,
					},
				},
			},
		});
	}

	const reportsCanvas = document.getElementById('reportsRevenueChart');
	if (reportsCanvas && reportsData) {
		new Chart(reportsCanvas, {
			type: 'bar',
			data: {
				labels: reportsData.labels,
				datasets: [
					{
						label: 'Revenue',
						data: reportsData.values,
						backgroundColor: '#4f46e5',
					},
				],
			},
			options: {
				responsive: true,
				plugins: {
					legend: {
						display: false,
					},
				},
			},
		});
	}
};

const initPickers = () => {
	if (!window.flatpickr) {
		return;
	}

	document.querySelectorAll('[data-date-range]').forEach((element) => {
		flatpickr(element, {
			mode: 'range',
			dateFormat: 'Y-m-d',
		});
	});

	document.querySelectorAll('[data-flatpickr-date]').forEach((element) => {
		flatpickr(element, {
			dateFormat: 'Y-m-d',
		});
	});

	document.querySelectorAll('[data-flatpickr-datetime]').forEach((element) => {
		flatpickr(element, {
			enableTime: true,
			dateFormat: 'Y-m-d H:i',
			time_24hr: true,
		});
	});

	document.querySelectorAll('[data-flatpickr-time]').forEach((element) => {
		flatpickr(element, {
			enableTime: true,
			noCalendar: true,
			dateFormat: 'H:i',
			time_24hr: true,
		});
	});

	document.querySelectorAll('[data-flatpickr-time-range]').forEach((element) => {
		flatpickr(element, {
			enableTime: true,
			noCalendar: true,
			mode: 'range',
			dateFormat: 'H:i',
			time_24hr: true,
		});
	});

	document.querySelectorAll('[data-flatpickr-date-range]').forEach((element) => {
		flatpickr(element, {
			mode: 'range',
			dateFormat: 'Y-m-d',
		});
	});
};

const initSelect2 = () => {
	if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
		return;
	}

	window.jQuery('.js-select2').select2({
		width: '100%',
	});
};

const initSummernote = () => {
	if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.summernote) {
		return;
	}

	window.jQuery('.summernote').summernote({
		height: 220,
	});
};

const initServiceTabs = () => {
	const buttons = document.querySelectorAll('[data-service-tab-button]');
	const panels = document.querySelectorAll('[data-service-tab-panel]');

	if (!buttons.length || !panels.length) {
		return;
	}

	const activateTab = (tabKey) => {
		buttons.forEach((button) => {
			const active = button.dataset.serviceTabButton === tabKey;
			button.classList.toggle('bg-indigo-600', active);
			button.classList.toggle('text-white', active);
			button.classList.toggle('bg-slate-100', !active);
			button.classList.toggle('text-slate-600', !active);
			button.classList.toggle('dark:bg-slate-800', !active);
			button.classList.toggle('dark:text-slate-300', !active);
		});

		panels.forEach((panel) => {
			panel.classList.toggle('hidden', panel.dataset.serviceTabPanel !== tabKey);
		});
	};

	buttons.forEach((button) => {
		button.addEventListener('click', () => activateTab(button.dataset.serviceTabButton || 'overview'));
	});

	activateTab('overview');
};

const initQuickCreate = () => {
	const selector = document.querySelector('[data-quick-create-resource]');
	const form = document.querySelector('[data-quick-create-form]');

	if (!selector || !form) {
		return;
	}

	const contactAction = form.dataset.contactAction;
	const leadAction = form.dataset.leadAction;

	const updateAction = () => {
		form.action = selector.value === 'leads' ? (leadAction || form.action) : (contactAction || form.action);
	};

	selector.addEventListener('change', updateAction);
	updateAction();
};

const initThemeControls = () => {
	const savedTheme = localStorage.getItem(themeStorageKey) || 'system';
	setTheme(savedTheme);

	document.querySelectorAll('[data-theme-choice]').forEach((radio) => {
		radio.addEventListener('change', () => setTheme(radio.value));
	});
};

const initFlashToast = () => {
 document.querySelectorAll('[data-toast-message]').forEach((toastMessage) => {
  if (!toastMessage?.dataset.toastMessage) {
   return;
  }

  showToast(toastMessage.dataset.toastMessage, toastMessage.dataset.toastType || 'success');
 });
};

const initDeleteConfirm = () => {
	document.addEventListener('submit', (event) => {
		const submitter = event.submitter;

		if (!submitter || !submitter.matches('[data-delete-confirm]')) {
			return;
		}

		event.preventDefault();

		if (!window.Swal) {
			event.target.submit();
			return;
		}

		window.Swal.fire({
			title: 'Delete record?',
			text: 'This action cannot be undone.',
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#4f46e5',
			cancelButtonColor: '#64748b',
			confirmButtonText: 'Yes, delete it',
		}).then((result) => {
			if (result.isConfirmed) {
				event.target.submit();
			}
		});
	});
};

const initButtons = () => {
	document.addEventListener('click', (event) => {
		const target = event.target;

		const sidebarToggle = target.closest('[data-sidebar-toggle]');
		if (sidebarToggle) {
			event.preventDefault();
			toggleSidebar();
			return;
		}

		const sidebarClose = target.closest('[data-sidebar-close]');
		if (sidebarClose) {
			event.preventDefault();
			closeSidebar();
			return;
		}

		const modalTrigger = target.closest('[data-open-modal]');
		if (modalTrigger) {
			event.preventDefault();
			openModal(modalTrigger.dataset.openModal || '');
			return;
		}

		const modalClose = target.closest('[data-modal-close]');
		if (modalClose) {
			event.preventDefault();
			closeModal(modalClose.dataset.modalClose || '');
			return;
		}

        const dropdownToggle = target.closest('[data-dropdown-toggle]');
        if (dropdownToggle) {
            event.preventDefault();
            toggleDropdown(dropdownToggle.dataset.dropdownToggle || '');
            dropdownToggle.blur();
            return;
        }

		const saveButton = target.closest('[data-demo-save]');
		if (saveButton) {
			event.preventDefault();
			showToast('Saved successfully');
		}
	});

	document.addEventListener('click', (event) => {
		const target = event.target;

		if (!target.closest('[data-dropdown-toggle]') && !target.closest('[data-dropdown]')) {
			closeAllDropdowns();
		}

		if (!target.closest('[data-modal]') && !target.closest('[data-open-modal]')) {
			document.querySelectorAll<HTMLElement>('[data-modal]').forEach((modal) => {
				if (!modal.classList.contains('hidden') && modal.dataset.modal !== 'quick-create-modal') {
					const panel = modal.querySelector<HTMLElement>('[data-modal-panel]');
					if (panel) {
						panel.classList.add('translate-x-full');
					}
				}
			});
		}
	});

	const backdrop = document.querySelector('[data-sidebar-backdrop]');
	if (backdrop) {
		backdrop.addEventListener('click', closeSidebar);
	}
};

const initPlugins = () => {
	if (window.lucide) {
		window.lucide.createIcons();
	}

	initCharts();
	initPickers();
	initSelect2();
	initSummernote();
	initServiceTabs();
	initQuickCreate();
	initFlashToast();
};

const initSidebarState = () => {
	const saved = localStorage.getItem(sidebarStorageKey);

	if (saved === '1' && isDesktop()) {
		applySidebarState(true);
		return;
	}

	applySidebarState(false);
};

const initLivewireToastBridge = () => {
	if (!window.Livewire) {
		return;
	}

	window.Livewire.on('toast', (event) => {
		if (!event || typeof event !== 'object') {
			return;
		}

		showToast(event.message || '', event.type || 'success');
	});

	window.Livewire.on('toast.success', (event) => {
		if (!event || typeof event !== 'object') {
			return;
		}

		showToast(event.message || '', 'success');
	});

	window.Livewire.on('toast.danger', (event) => {
		if (!event || typeof event !== 'object') {
			return;
		}

		showToast(event.message || '', 'danger');
	});
};

window.addEventListener('DOMContentLoaded', () => {
	initSidebarState();
	initThemeControls();
	initButtons();
	initDeleteConfirm();
	initPlugins();
});

document.addEventListener('livewire:init', () => {
	initLivewireToastBridge();
});

window.addEventListener('resize', () => {
	const sidebar = document.querySelector('[data-sidebar]');
	if (!sidebar) {
		return;
	}

	if (isDesktop()) {
		const backdrop = document.querySelector('[data-sidebar-backdrop]');
		if (backdrop) {
			backdrop.classList.add('hidden');
		}

		sidebar.classList.remove('-translate-x-full');
		sidebar.classList.add('translate-x-0');
		initSidebarState();
	}
});

window.crmShowToast = showToast;


