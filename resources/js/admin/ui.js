/**
 * Admin UI behaviour: theme, sidebar, toasts, confirm dialog, loading buttons, modals,
 * client-side table sorting, command palette, notification bell and variant picker.
 *
 * Must be imported before Alpine starts (see admin/app.js).
 */

const config = () => window.AdminConfig || {};
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

const storage = {
    get(key, fallback = null) {
        try {
            const value = localStorage.getItem(key);
            return value === null ? fallback : value;
        } catch {
            return fallback;
        }
    },
    set(key, value) {
        try {
            localStorage.setItem(key, value);
        } catch {
            /* private mode: preference is not persisted */
        }
    },
};

const focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

function trapFocus(event, container) {
    if (event.key !== 'Tab' || !container) return;
    const items = [...container.querySelectorAll(focusableSelector)].filter((el) => el.offsetParent !== null);
    if (!items.length) return;
    const first = items[0];
    const last = items[items.length - 1];
    if (event.shiftKey && document.activeElement === first) {
        last.focus();
        event.preventDefault();
    } else if (!event.shiftKey && document.activeElement === last) {
        first.focus();
        event.preventDefault();
    }
}

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    const savedTheme = storage.get('admin-theme');

    Alpine.store('theme', {
        dark: savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches),
        supported: document.documentElement.dataset.darkReady === '1',
        toggle() {
            this.dark = !this.dark;
            storage.set('admin-theme', this.dark ? 'dark' : 'light');
            if (this.supported) document.documentElement.classList.toggle('dark', this.dark);
        },
    });

    Alpine.store('sidebar', {
        collapsed: document.documentElement.classList.contains('sidebar-collapsed'),
        mobileOpen: false,
        toggle() {
            if (window.matchMedia('(min-width: 1024px)').matches) {
                this.collapsed = !this.collapsed;
                document.documentElement.classList.toggle('sidebar-collapsed', this.collapsed);
                storage.set('admin-sidebar', this.collapsed ? 'collapsed' : 'expanded');
            } else {
                this.mobileOpen = !this.mobileOpen;
            }
        },
        close() {
            this.mobileOpen = false;
        },
    });

    Alpine.store('toasts', {
        items: [],
        nextId: 1,
        push(message, type = 'success', timeout = 5000) {
            if (!message) return;
            const id = this.nextId++;
            this.items.push({ id, message, type });
            if (timeout) setTimeout(() => this.dismiss(id), timeout);
        },
        dismiss(id) {
            this.items = this.items.filter((toast) => toast.id !== id);
        },
    });

    Alpine.store('confirm', {
        open: false,
        title: '',
        message: '',
        confirmText: 'Confirm',
        cancelText: 'Cancel',
        tone: 'danger',
        resolver: null,
        returnFocus: null,
        ask(options = {}) {
            this.title = options.title || 'Are you sure?';
            this.message = options.message || '';
            this.confirmText = options.confirmText || 'Confirm';
            this.cancelText = options.cancelText || 'Cancel';
            this.tone = options.tone || 'danger';
            this.returnFocus = document.activeElement;
            this.open = true;
            return new Promise((resolve) => {
                this.resolver = resolve;
            });
        },
        settle(result) {
            this.open = false;
            this.resolver?.(result);
            this.resolver = null;
            this.returnFocus?.focus?.();
        },
    });

    Alpine.data('modal', (name, startOpen = false) => ({
        open: startOpen,
        returnFocus: null,
        init() {
            this.$watch('open', (open) => {
                document.body.classList.toggle('overflow-hidden', open);
                if (open) {
                    this.returnFocus = document.activeElement;
                    this.$nextTick(() => {
                        const panel = this.$refs.panel;
                        (panel?.querySelector('[autofocus]') || panel?.querySelector(focusableSelector))?.focus();
                    });
                } else {
                    this.returnFocus?.focus?.();
                }
            });
        },
        matches(detail) {
            return detail === name || detail?.name === name;
        },
        trap(event) {
            trapFocus(event, this.$refs.panel);
        },
    }));

    // Arrow-key navigation for <x-admin.tabs>; the selected tab lives in the parent scope.
    Alpine.data('tabList', () => ({
        move(step) {
            const tabs = [...this.$root.querySelectorAll('[role="tab"]')];
            const index = tabs.indexOf(document.activeElement);
            if (index < 0) return;
            const next = tabs[(index + step + tabs.length) % tabs.length];
            next.click();
            next.focus();
        },
    }));

    // Sorts the rows of the current page; the server still decides which rows are on the page.
    Alpine.data('dataTable', () => ({
        sortIndex: null,
        sortDir: 'asc',
        sort(index) {
            this.sortDir = this.sortIndex === index && this.sortDir === 'asc' ? 'desc' : 'asc';
            this.sortIndex = index;
            const body = this.$refs.body;
            if (!body) return;
            const rows = [...body.querySelectorAll(':scope > tr')].filter((row) => !row.querySelector('.admin-table-empty'));
            // Cells may carry data-sort; otherwise amounts like "Tk 1,200.50" or "12%" sort numerically.
            const value = (row) => {
                const cell = row.children[index];
                const raw = String(cell?.dataset.sort ?? cell?.textContent ?? '').trim();
                const numeric = raw.replace(/^(tk\.?|৳)\s*/i, '').replace(/[,%\s]/g, '');
                return numeric !== '' && !Number.isNaN(Number(numeric)) ? Number(numeric) : raw.toLowerCase();
            };
            rows.sort((a, b) => {
                const [x, y] = [value(a), value(b)];
                const result = typeof x === 'number' && typeof y === 'number' ? x - y : String(x).localeCompare(String(y), undefined, { numeric: true });
                return this.sortDir === 'asc' ? result : -result;
            });
            rows.forEach((row) => body.appendChild(row));
        },
        ariaSort(index) {
            if (this.sortIndex !== index) return 'none';
            return this.sortDir === 'asc' ? 'ascending' : 'descending';
        },
    }));

    Alpine.data('commandPalette', (data) => ({
        open: false,
        query: '',
        active: 0,
        remote: [],
        loading: false,
        requestId: 0,
        init() {
            this.$watch('query', () => {
                this.active = 0;
                this.fetchRemote();
            });
            this.$watch('open', (open) => {
                if (open) this.$nextTick(() => this.$refs.input?.focus());
                else this.query = '';
            });
        },
        onGlobalKey(event) {
            const typing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName) || event.target.isContentEditable;
            if ((event.key === 'k' && (event.metaKey || event.ctrlKey)) || (event.key === '/' && !typing)) {
                event.preventDefault();
                this.open = !this.open;
            }
        },
        get results() {
            const q = this.query.trim().toLowerCase();
            const pages = data.pages
                .filter((page) => !q || `${page.title} ${page.group}`.toLowerCase().includes(q))
                .slice(0, q ? 8 : 12)
                .map((page) => ({ ...page, kind: 'Pages' }));

            const searches = q
                ? data.searches.map((target) => ({
                    kind: 'Search',
                    title: `Search ${target.label} for “${this.query.trim()}”`,
                    icon: 'bi-search',
                    url: `${target.url}${target.url.includes('?') ? '&' : '?'}${target.param}=${encodeURIComponent(this.query.trim())}`,
                }))
                : [];

            return [...this.remote, ...pages, ...searches];
        },
        async fetchRemote() {
            const q = this.query.trim();
            const current = ++this.requestId;
            if (q.length < 2 || !data.lookups.length) {
                this.remote = [];
                return;
            }
            this.loading = true;
            const groups = await Promise.all(data.lookups.map(async (lookup) => {
                try {
                    const res = await fetch(`${lookup.url}?q=${encodeURIComponent(q)}`, { headers: { Accept: 'application/json' } });
                    if (!res.ok) return [];
                    const rows = await res.json();
                    return rows.slice(0, 5).map((row) => ({
                        kind: lookup.label,
                        icon: lookup.icon,
                        title: row.label ?? row.name,
                        subtitle: row.meta ?? row.email ?? '',
                        url: lookup.link.replace('__ID__', row.id),
                    }));
                } catch {
                    return [];
                }
            }));
            if (current !== this.requestId) return;
            this.remote = groups.flat();
            this.loading = false;
        },
        move(step) {
            const count = this.results.length;
            if (!count) return;
            this.active = (this.active + step + count) % count;
            this.$nextTick(() => this.$refs.list?.querySelector(`[data-index="${this.active}"]`)?.scrollIntoView({ block: 'nearest' }));
        },
        go(item = this.results[this.active]) {
            if (item) window.location.href = item.url;
        },
    }));
});

/* Forms (or their submit buttons) with data-confirm ask before submitting. */
document.addEventListener('submit', async (event) => {
    const form = event.target;
    const submitter = event.submitter;
    const source = submitter?.dataset.confirm !== undefined ? submitter : form;
    if (source.dataset.confirm === undefined || form.dataset.confirmed === '1') return;

    event.preventDefault();
    event.stopImmediatePropagation();

    const ok = await window.Alpine.store('confirm').ask({
        title: source.dataset.confirmTitle,
        message: source.dataset.confirm,
        confirmText: source.dataset.confirmButton,
        tone: source.dataset.confirmTone,
    });
    if (!ok) return;

    form.dataset.confirmed = '1';
    form.requestSubmit(submitter || undefined);
    setTimeout(() => delete form.dataset.confirmed, 0);
}, true);

/* Submit buttons marked data-loading show a spinner and block double submits. */
document.addEventListener('submit', (event) => {
    if (event.defaultPrevented) return;
    const form = event.target;
    const buttons = event.submitter?.hasAttribute('data-loading')
        ? [event.submitter]
        : [...form.querySelectorAll('button[type="submit"][data-loading]')];

    setTimeout(() => buttons.forEach((button) => {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.dataset.loadingActive = '1';
    }), 0);
});

/* Restore buttons when the page is shown from the back/forward cache. */
window.addEventListener('pageshow', () => {
    document.querySelectorAll('[data-loading-active]').forEach((button) => {
        button.disabled = false;
        button.removeAttribute('aria-busy');
        delete button.dataset.loadingActive;
    });
});

window.toast = (message, type = 'success') => window.Alpine?.store('toasts').push(message, type);
window.confirmAction = (options) => window.Alpine.store('confirm').ask(options);

window.adminBell = function adminBell(initialUnread) {
    return {
        open: false,
        loaded: false,
        unread: initialUnread,
        items: [],
        start() {
            this.refresh();
            setInterval(() => {
                if (!document.hidden) this.refresh();
            }, 60000);
        },
        toggle() {
            this.open = !this.open;
            if (this.open) this.refresh();
        },
        async refresh() {
            try {
                const res = await fetch(config().notificationsFeed, { headers: { Accept: 'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                this.unread = data.unread;
                this.items = data.items;
                this.loaded = true;
            } catch {
                /* offline: keep the last state */
            }
        },
        async markAllRead() {
            await fetch(config().notificationsReadAll, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() },
            });
            this.unread = 0;
            this.items = this.items.map((item) => ({ ...item, read: true }));
        },
    };
};

window.variantPicker = function variantPicker(options) {
    let requestId = 0;

    return {
        open: false,
        loading: false,
        query: '',
        results: [],
        selected: options.selected || null,
        requireStock: options.requireStock,
        init() {
            this.$watch('query', () => this.search());
        },
        async search() {
            const current = ++requestId;
            this.open = true;
            this.loading = true;
            const params = new URLSearchParams({ q: this.query });
            if (options.inactive) params.set('inactive', '1');
            try {
                const res = await fetch(`${options.url}?${params}`, { headers: { Accept: 'application/json' } });
                const data = res.ok ? await res.json() : [];
                if (current === requestId) this.results = data;
            } catch {
                if (current === requestId) this.results = [];
            }
            if (current === requestId) this.loading = false;
        },
        choose(item) {
            if (this.requireStock && item.stock <= 0) return;
            this.selected = item;
            this.open = false;
            this.query = '';
            this.$dispatch('variant-selected', item);
        },
        clear() {
            this.selected = null;
            this.$dispatch('variant-selected', null);
            this.$nextTick(() => this.$refs.search?.focus());
        },
    };
};
