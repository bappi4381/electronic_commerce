const RECENT_KEY = 'onemall.recentSearches';
const RECENT_LIMIT = 8;
const BANGLA = /[\u0980-\u09FF]/;

const config = () => window.StoreConfig || {};

function loadRecent() {
    try {
        const list = JSON.parse(localStorage.getItem(RECENT_KEY) || '[]');
        return Array.isArray(list) ? list.filter((q) => typeof q === 'string').slice(0, RECENT_LIMIT) : [];
    } catch (e) {
        return [];
    }
}

function storeRecent(list) {
    try {
        localStorage.setItem(RECENT_KEY, JSON.stringify(list.slice(0, RECENT_LIMIT)));
    } catch (e) {
        /* storage unavailable (private mode) */
    }
}

/**
 * Auto-suggest for the header (mode "dropdown") and the mobile full-screen overlay (mode "overlay").
 * Results come from our /api/search proxy; Meilisearch is never called from the browser.
 */
export function searchBox({ mode = 'dropdown', initial = '' } = {}) {
    return {
        mode,
        query: initial,
        open: false,
        loading: false,
        composing: false,
        data: null,
        active: -1,
        recent: [],
        trending: config().search?.trending ?? [],
        controller: null,
        timer: null,
        uid: Math.random().toString(36).slice(2, 8),

        init() {
            this.recent = loadRecent();
            if (this.mode === 'overlay') {
                this.$watch('$store.ui.searchOpen', (open) => {
                    if (!open) return;
                    this.recent = loadRecent();
                    // After x-show has made the overlay visible.
                    setTimeout(() => this.$refs.input?.focus(), 50);
                    if (this.query) this.fetch();
                });
            }
        },

        /** Bangla suggests from 1 character, Latin from 2. */
        isLongEnough(q) {
            const compact = q.replace(/\s+/g, '');
            return compact.length >= (BANGLA.test(compact) ? 1 : 2);
        },

        get results() {
            return this.data && this.isLongEnough(this.query.trim()) ? this.data : null;
        },

        /** Flat list used for arrow-key navigation: products, categories, brands, then "see all". */
        get items() {
            const r = this.results;
            if (!r) return [];
            return [
                ...r.products.map((p) => ({ ...p, kind: 'product' })),
                ...r.categories.map((c) => ({ ...c, kind: 'category' })),
                ...r.brands.map((b) => ({ ...b, kind: 'brand' })),
                { kind: 'all', url: r.see_all_url },
            ];
        },

        offset(kind) {
            const r = this.results;
            if (!r) return 0;
            return { product: 0, category: r.products.length, brand: r.products.length + r.categories.length, all: r.products.length + r.categories.length + r.brands.length }[kind];
        },

        optionId(index) {
            return `search-${this.uid}-opt-${index}`;
        },

        onFocus() {
            this.open = true;
            this.recent = loadRecent();
        },

        onInput(event) {
            if (event.isComposing || this.composing) return;
            this.schedule();
        },

        onCompositionEnd() {
            this.composing = false;
            this.schedule();
        },

        schedule() {
            this.open = true;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.fetch(), 150);
        },

        async fetch() {
            const q = this.query.trim();
            this.active = -1;
            if (!this.isLongEnough(q)) {
                this.controller?.abort();
                this.loading = false;
                this.data = null;
                return;
            }

            this.controller?.abort();
            const controller = new AbortController();
            this.controller = controller;
            this.loading = true;

            try {
                const params = new URLSearchParams({ q, lang: config().locale || 'en' });
                const response = await fetch(`${config().routes.searchApi}?${params}`, {
                    signal: controller.signal,
                    headers: { Accept: 'application/json' },
                });
                if (!response.ok) return;
                const data = await response.json();
                if (q === this.query.trim()) this.data = data;
            } catch (e) {
                if (e.name !== 'AbortError') console.warn('Search failed', e);
            } finally {
                if (this.controller === controller) this.loading = false;
            }
        },

        onKeydown(event) {
            const count = this.items.length;
            if (event.key === 'ArrowDown' && count) {
                event.preventDefault();
                this.open = true;
                this.active = (this.active + 1) % count;
                this.scrollActive();
            } else if (event.key === 'ArrowUp' && count) {
                event.preventDefault();
                this.active = this.active <= 0 ? count - 1 : this.active - 1;
                this.scrollActive();
            } else if (event.key === 'Enter' && !event.isComposing) {
                event.preventDefault();
                if (this.active >= 0 && this.items[this.active]) {
                    this.go(this.items[this.active]);
                } else {
                    this.submit();
                }
            } else if (event.key === 'Escape') {
                if (this.mode === 'overlay') {
                    this.$store.ui.closeSearch();
                } else {
                    this.close();
                    event.target.blur();
                }
            }
        },

        scrollActive() {
            this.$nextTick(() => document.getElementById(this.optionId(this.active))?.scrollIntoView({ block: 'nearest' }));
        },

        go(item) {
            this.remember(this.query);
            window.location.href = item.url;
        },

        submit(text = null) {
            const q = (text ?? this.query).trim();
            if (!q) return;
            this.remember(q);
            window.location.href = `${config().routes.search}?${new URLSearchParams({ q })}`;
        },

        choose(text) {
            this.query = text;
            this.$refs.input?.focus();
            this.fetch();
        },

        clear() {
            this.query = '';
            this.data = null;
            this.active = -1;
            this.$refs.input?.focus();
        },

        close() {
            this.open = false;
            this.active = -1;
        },

        remember(q) {
            q = (q || '').trim();
            if (!q) return;
            this.recent = [q, ...loadRecent().filter((item) => item.toLowerCase() !== q.toLowerCase())].slice(0, RECENT_LIMIT);
            storeRecent(this.recent);
        },

        forget(q) {
            this.recent = this.recent.filter((item) => item !== q);
            storeRecent(this.recent);
        },

        clearRecent() {
            this.recent = [];
            storeRecent([]);
        },
    };
}
