import { searchBox } from './search';

const config = () => window.StoreConfig || {};
const t = (key, fallback = '') => config().i18n?.[key] ?? fallback;
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

export function registerStorefront(Alpine) {
    Alpine.store('toast', {
        items: [],
        push(message, type = 'success', action = null) {
            const id = Date.now() + Math.random();
            this.items.push({ id, message, type, action });
            setTimeout(() => this.dismiss(id), 4000);
        },
        dismiss(id) {
            this.items = this.items.filter((item) => item.id !== id);
        },
    });

    Alpine.store('cart', {
        count: config().cart?.count ?? 0,
        totalText: config().cart?.totalText ?? '',
        set({ count, total_text: totalText }) {
            if (typeof count === 'number') this.count = count;
            if (totalText) this.totalText = totalText;
        },
    });

    Alpine.store('wishlist', {
        count: config().wishlistCount ?? 0,
        async toggle(productId) {
            const response = await fetch(config().routes.wishlistToggle.replace('__ID__', productId), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json', 'Content-Type': 'application/json' },
            });
            if (response.status === 401) {
                Alpine.store('toast').push(t('loginForWishlist', 'Log in to save items.'), 'info', { label: t('login', 'Log in'), url: config().routes.login });
                return null;
            }
            if (!response.ok) {
                Alpine.store('toast').push(t('error', 'Something went wrong. Please try again.'), 'error');
                return null;
            }
            const data = await response.json();
            this.count = data.count;
            // Legacy counters and the wishlist drawer listen for these.
            document.querySelectorAll('#wishlist-counter, #wishlist-counter-mobile').forEach((el) => (el.textContent = data.count));
            window.dispatchEvent(new CustomEvent('wishlist-updated', { detail: { productId, status: data.status } }));
            window.dispatchEvent(new CustomEvent('wishlist-changed'));
            Alpine.store('toast').push(data.status === 'added' ? t('wishlistAdded', 'Saved to wishlist') : t('wishlistRemoved', 'Removed from wishlist'), 'success');
            return data.status;
        },
    });

    Alpine.store('ui', {
        scrolled: false,
        searchOpen: false,
        keyboardOpen: false,
        openSearch() {
            this.searchOpen = true;
            document.body.classList.add('sf-locked');
        },
        closeSearch() {
            this.searchOpen = false;
            document.body.classList.remove('sf-locked');
        },
    });

    // Header shrink on scroll; hide the bottom nav while the on-screen keyboard is up.
    let ticking = false;
    window.addEventListener('scroll', () => {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(() => {
            Alpine.store('ui').scrolled = window.scrollY > 24;
            ticking = false;
        });
    }, { passive: true });

    if (window.visualViewport) {
        const baseline = window.innerHeight;
        window.visualViewport.addEventListener('resize', () => {
            Alpine.store('ui').keyboardOpen = window.visualViewport.height < baseline * 0.75;
        });
    }

    Alpine.data('productCard', ({ id, inWishlist = false, name = '' }) => ({
        inWishlist,
        adding: false,
        added: false,
        init() {
            window.addEventListener('wishlist-updated', (event) => {
                if (String(event.detail?.productId) === String(id)) this.inWishlist = event.detail.status === 'added';
            });
        },
        async toggleWishlist() {
            await Alpine.store('wishlist').toggle(id);
        },
        async add(form) {
            if (this.adding) return;
            this.adding = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: new FormData(form),
                });
                const data = await response.json().catch(() => null);
                if (!response.ok || !data) {
                    Alpine.store('toast').push(data?.message || t('addFailed', 'Could not add to cart.'), 'error');
                    return;
                }
                Alpine.store('cart').set(data);
                this.added = true;
                setTimeout(() => (this.added = false), 2000);
                Alpine.store('toast').push(t('addedToCart', 'Added to cart: :name').replace(':name', name), 'success', { label: t('viewCart', 'View cart'), url: config().routes.cart });
            } catch (e) {
                form.submit();
            } finally {
                this.adding = false;
            }
        },
        notify() {
            window.dispatchEvent(new CustomEvent('open-chat', { detail: { message: t('notifyMessage', 'Please notify me when this is back in stock: :name').replace(':name', name) } }));
        },
    }));

    Alpine.data('carousel', ({ count = 1, interval = 6000 } = {}) => ({
        index: 0,
        count,
        timer: null,
        paused: false,
        startX: null,
        init() {
            this.resume();
            document.addEventListener('visibilitychange', () => (document.hidden ? this.stop() : this.resume()));
        },
        go(i) {
            this.index = (i + this.count) % this.count;
        },
        next() {
            this.go(this.index + 1);
        },
        prev() {
            this.go(this.index - 1);
        },
        stop() {
            clearInterval(this.timer);
            this.timer = null;
        },
        pause() {
            this.paused = true;
            this.stop();
        },
        resume() {
            this.paused = false;
            this.stop();
            if (this.count > 1 && !reducedMotion()) this.timer = setInterval(() => this.next(), interval);
        },
        touchStart(event) {
            this.startX = event.touches[0].clientX;
            this.stop();
        },
        touchEnd(event) {
            if (this.startX === null) return;
            const delta = event.changedTouches[0].clientX - this.startX;
            if (Math.abs(delta) > 40) delta < 0 ? this.next() : this.prev();
            this.startX = null;
            this.resume();
        },
    }));

    Alpine.data('countdown', (end) => {
        let target = end ? new Date(String(end).replace(' ', 'T')).getTime() : 0;
        // No (or a past) campaign end: run a rolling 12-hour window so the timer is never stuck at zero.
        if (!target || Number.isNaN(target) || target < Date.now()) target = Date.now() + 12 * 3600 * 1000;
        const pad = (n) => String(n).padStart(2, '0');

        return {
            d: '00', h: '00', m: '00', s: '00', label: '',
            init() {
                this.tick();
                this.timer = setInterval(() => this.tick(), 1000);
            },
            destroy() {
                clearInterval(this.timer);
            },
            tick() {
                const left = Math.max(0, target - Date.now());
                const days = Math.floor(left / 86400000);
                const hours = Math.floor((left % 86400000) / 3600000);
                const minutes = Math.floor((left % 3600000) / 60000);
                this.d = pad(days);
                this.h = pad(hours);
                this.m = pad(minutes);
                this.s = pad(Math.floor((left % 60000) / 1000));
                this.label = t('endsIn', 'Ends in :d days :h hours :m minutes').replace(':d', days).replace(':h', hours).replace(':m', minutes);
            },
        };
    });

    // Horizontal snap rows with arrow buttons (desktop) and native swipe (mobile).
    Alpine.data('scrollRow', () => ({
        atStart: true,
        atEnd: false,
        init() {
            this.$nextTick(() => this.update());
        },
        update() {
            const el = this.$refs.track;
            if (!el) return;
            this.atStart = el.scrollLeft <= 4;
            this.atEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 4;
        },
        scroll(direction) {
            const el = this.$refs.track;
            el.scrollBy({ left: direction * el.clientWidth * 0.9, behavior: reducedMotion() ? 'auto' : 'smooth' });
        },
    }));

    // "Just For You": category tabs and Load more, both served as card HTML by /load-more-products.
    Alpine.data('productFeed', ({ url, initial = 15, step = 10, hasMore = true }) => ({
        category: '',
        skip: initial,
        loading: false,
        empty: false,
        hasMore,
        async select(category) {
            if (this.category === category && !this.loading) return;
            this.category = category;
            this.skip = 0;
            await this.fetch(true);
        },
        async more() {
            await this.fetch(false);
        },
        async fetch(replace) {
            if (this.loading) return;
            this.loading = true;
            const take = replace ? initial : step;
            try {
                const params = new URLSearchParams({ skip: this.skip, take });
                if (this.category) params.set('category', this.category);
                const response = await fetch(`${url}?${params}`, { headers: { Accept: 'application/json' } });
                const data = await response.json();
                if (replace) {
                    this.$refs.grid.innerHTML = '';
                    this.empty = !(data.html || '').trim();
                }
                this.$refs.grid.insertAdjacentHTML('beforeend', data.html || '');
                this.skip += take;
                this.hasMore = !!data.has_more;
            } catch (e) {
                Alpine.store('toast').push(t('error', 'Something went wrong. Please try again.'), 'error');
            } finally {
                this.loading = false;
            }
        },
    }));

    Alpine.data('newsletter', () => ({
        email: '',
        loading: false,
        message: '',
        ok: false,
        async submit() {
            this.loading = true;
            this.message = '';
            try {
                const response = await fetch(config().routes.newsletter, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                    body: JSON.stringify({ email: this.email }),
                });
                const data = await response.json();
                this.ok = !!data.success;
                this.message = data.message || data.errors?.email?.[0] || t('error', 'Something went wrong. Please try again.');
                if (this.ok) this.email = '';
            } catch (e) {
                this.ok = false;
                this.message = t('networkError', 'Network error. Please try again.');
            } finally {
                this.loading = false;
            }
        },
    }));

    Alpine.data('searchBox', searchBox);
}
