@php
    $storeConfig = [
        'locale' => app()->getLocale(),
        'cart' => ['count' => $cartCount, 'totalText' => money($cartTotal)],
        'wishlistCount' => $wishlistCount,
        'search' => ['trending' => array_slice($searchTrending, 0, 8)],
        'routes' => [
            'searchApi' => route('api.search'),
            'search' => route('search.index'),
            'cart' => route('cart.index'),
            'login' => route('user.auth.login'),
            'wishlistToggle' => route('wishlist.toggle', '__ID__'),
            'newsletter' => route('newsletter.subscribe'),
        ],
        'i18n' => [
            'addedToCart' => __('Added to cart: :name'),
            'addFailed' => __('Could not add to cart.'),
            'viewCart' => __('View cart'),
            'wishlistAdded' => __('Saved to wishlist'),
            'wishlistRemoved' => __('Removed from wishlist'),
            'loginForWishlist' => __('Log in to save items to your wishlist.'),
            'login' => __('Log in'),
            'notifyMessage' => __('Please notify me when this is back in stock: :name'),
            'endsIn' => __('Ends in :d days :h hours :m minutes'),
            'error' => __('Something went wrong. Please try again.'),
            'networkError' => __('Network error. Please try again.'),
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ $storeName }} | @yield('title')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="format-detection" content="telephone=no">
    <meta name="theme-color" content="#0b1220">
    <meta name="description" content="@yield('meta_description', __('Genuine phones, laptops, home appliances and more in Bangladesh. Cash on delivery, bKash & Nagad.'))">
    @foreach(['en', 'bn'] as $alternate)
        <link rel="alternate" hreflang="{{ $alternate }}" href="{{ \App\Support\Storefront::localeUrl($alternate) }}">
    @endforeach

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@500;600;700;800&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <script>window.StoreConfig = @json($storeConfig);</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body x-data class="storefront has-bottom-nav flex min-h-screen flex-col bg-[var(--sf-bg)] font-body text-[var(--sf-text)] antialiased">
    <a href="#main" class="sr-only z-[100] rounded-lg bg-white px-4 py-2 text-sm font-semibold text-navy-900 shadow focus:not-sr-only focus:fixed focus:left-4 focus:top-4">{{ __('Skip to content') }}</a>

    @include('frontend.partials.header')

    <main id="main" tabindex="-1" class="flex-grow focus:outline-none">
        @yield('content')

        <!-- Global Wishlist Drawer -->
        <div x-data="wishlistManager" @open-wishlist.window="showWishlist = true; fetchWishlist()"
            @wishlist-changed.window="if (showWishlist) fetchWishlist()" x-cloak>
            <div x-show="showWishlist" x-transition.opacity @click="showWishlist = false"
                class="fixed inset-0 z-[var(--sf-z-drawer)] bg-navy-950/60 backdrop-blur-sm"></div>

            <aside x-show="showWishlist" x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full" @keydown.escape.window="showWishlist = false"
                class="fixed right-0 top-0 z-[var(--sf-z-drawer)] flex h-full w-full max-w-md flex-col bg-white shadow-2xl"
                role="dialog" aria-modal="true" aria-labelledby="wishlist-title">
                <div class="flex items-center justify-between border-b border-[var(--sf-border)] px-6 py-5">
                    <h2 id="wishlist-title" class="font-display text-lg font-bold text-slate-900">{{ __('My Wishlist') }}</h2>
                    <button type="button" @click="showWishlist = false" class="flex size-11 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 sf-focus">
                        <span class="sr-only">{{ __('Close') }}</span><i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-6">
                    <div x-show="loading" class="space-y-3" aria-hidden="true">
                        <div class="sf-skeleton h-20 rounded-[var(--sf-radius)]"></div>
                        <div class="sf-skeleton h-20 rounded-[var(--sf-radius)]"></div>
                    </div>
                    <div x-show="!loading" x-html="wishlistHtml"></div>
                </div>
                <div class="border-t border-[var(--sf-border)] p-6">
                    <a href="{{ route('products.index') }}" class="flex h-12 w-full items-center justify-center gap-2 rounded-full bg-navy-900 text-sm font-semibold text-white hover:bg-navy-800 sf-focus">
                        {{ __('Continue Shopping') }} <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </aside>
        </div>
    </main>

    @include('frontend.partials.footer')
    @include('frontend.partials.bottom-nav')
    @include('frontend.partials.search-overlay')
    @include('frontend.partials.toasts')

    <script src="{{ asset('frontend') }}/js/jquery-1.11.0.min.js"></script>
    <script src="{{ asset('frontend') }}/js/plugins.js"></script>
    <script src="{{ asset('frontend') }}/js/script.js"></script>

    @include('frontend.partials.chat-widget')

    @stack('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('wishlistManager', () => ({
                showWishlist: false,
                wishlistHtml: '',
                loading: false,
                fetchWishlist() {
                    this.loading = true;
                    fetch(@js(route('wishlist.fetch')))
                        .then(response => response.text())
                        .then(html => {
                            this.wishlistHtml = html;
                            this.loading = false;
                        })
                        .catch(err => {
                            console.error('Wishlist Error:', err);
                            this.wishlistHtml = '<p class="py-10 text-center text-sm text-rose-600">' + @js(__('Error loading wishlist.')) + '</p>';
                            this.loading = false;
                        });
                },
                // Used by the wishlist drawer items (wishlist-items partial).
                toggleWishlist(productId) {
                    Alpine.store('wishlist').toggle(productId).then(() => this.fetchWishlist());
                },
            }));
        });
    </script>
</body>

</html>
