<!DOCTYPE html>
<html lang="en">

<head>
    <title>ONEMALL | @yield('title')</title>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="author" content="ONEMALL">
    <meta name="keywords" content="electronics, gadgets, smartphones, laptops">
    <meta name="description" content="Premium Electronics and Gadget Store">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    {{-- Alpine.js is loaded via Vite bundle (app.js) - do NOT load CDN version --}}

    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Hide scrollbar for Chrome, Safari and Opera */
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }

        /* Hide scrollbar for IE, Edge and Firefox */
        .no-scrollbar {
            -ms-overflow-style: none;
            /* IE and Edge */
            scrollbar-width: none;
            /* Firefox */
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-50 font-sans text-slate-900 flex flex-col min-h-screen">

    <div id="header-wrap">
        <!-- Top Bar -->
        <div class="hidden md:block bg-slate-950 py-2.5 text-[11px] text-white/70 border-b border-white/5">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-6">
                        <div class="flex items-center gap-1.5 cursor-pointer hover:text-primary transition-colors">
                            <img src="https://flagcdn.com/w20/gb.png" width="16" alt="English" class="rounded-sm">
                            <span class="font-bold uppercase tracking-widest">English</span>
                            <i class="bi bi-chevron-down text-[8px]"></i>
                        </div>
                        <div
                            class="flex items-center gap-1.5 cursor-pointer hover:text-primary transition-colors border-l border-white/10 pl-6">
                            <span class="font-bold uppercase tracking-widest">USD</span>
                            <i class="bi bi-chevron-down text-[8px]"></i>
                        </div>
                    </div>
                    <div class="flex items-center gap-8">
                        @auth
                            <a href="{{ route('user.dashboard') }}"
                                class="flex items-center gap-2 hover:text-primary transition-colors uppercase font-black tracking-widest">
                                <i class="bi bi-person-check-fill text-sm text-primary"></i>
                                Hi, {{ explode(' ', Auth::user()->name)[0] }}
                            </a>
                            <a href="{{ route('auth.logout') }}"
                                onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                                class="flex items-center gap-2 hover:text-red-500 transition-colors uppercase font-black tracking-widest text-slate-400">
                                <i class="bi bi-box-arrow-right text-sm"></i> Logout
                            </a>
                            <form id="logout-form" action="{{ route('auth.logout') }}" method="POST" class="hidden">
                                @csrf
                            </form>
                        @else
                            <a href="{{ route('user.auth.login') }}"
                                class="flex items-center gap-2 hover:text-primary transition-colors uppercase font-black tracking-widest"><i
                                    class="bi bi-person text-sm"></i> Account</a>
                            <a href="{{ route('user.auth.login') }}"
                                class="flex items-center gap-2 hover:text-primary transition-colors uppercase font-black tracking-widest"><i
                                    class="bi bi-lock text-sm"></i> Login</a>
                        @endauth
                        <a href="#" x-data @click.prevent="$dispatch('open-wishlist')"
                            class="flex items-center gap-2 hover:text-primary transition-colors uppercase font-black tracking-widest relative group">
                            <i class="bi bi-heart text-sm"></i>
                            <span>Wishlist</span>
                            <span id="wishlist-counter"
                                class="absolute -top-2 -right-3 bg-primary text-white text-[8px] w-4 h-4 rounded-full flex items-center justify-center border border-slate-950 group-hover:scale-110 transition-transform">
                                {{ Auth::check() ? \App\Models\Wishlist::where('user_id', Auth::id())->count() : 0 }}
                            </span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Header -->
        <header class="bg-slate-900 py-4 lg:py-6 text-white sticky top-0 z-[80]">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex flex-col lg:flex-row items-center justify-between gap-4 lg:gap-8">
                    <div class="w-full lg:w-1/4 flex items-center justify-between">
                        <a href="{{ route('home') }}"
                            class="flex items-center gap-3 lg:gap-4 group no-underline text-white">
                            @if(\App\Models\Setting::get('logo'))
                                <img src="{{ asset('storage/' . \App\Models\Setting::get('logo')) }}"
                                    alt="{{ \App\Models\Setting::get('site_name', 'ONEMALL') }}"
                                    class="h-8 lg:h-14 group-hover:scale-110 transition-transform">
                            @else
                                <div
                                    class="bg-primary text-white w-8 h-8 lg:w-14 lg:h-14 rounded-lg lg:rounded-2xl flex items-center justify-center text-lg lg:text-3xl shadow-xl shadow-primary/20 group-hover:scale-110 transition-transform">
                                    <i class="bi bi-lightning-charge-fill"></i>
                                </div>
                            @endif
                            <div class="flex flex-col">
                                <h1
                                    class="m-0 text-lg lg:text-3xl font-black tracking-tighter leading-none uppercase italic">
                                    {{ \App\Models\Setting::get('site_name', 'ONEMALL') }}</h1>
                                <span
                                    class="text-[6px] lg:text-[9px] text-primary-light font-black tracking-[0.3em] uppercase mt-0.5 lg:mt-1">Digital
                                    Universe</span>
                            </div>
                        </a>
                        <div class="lg:hidden flex items-center gap-5">
                            <a href="#" x-data @click.prevent="$dispatch('open-wishlist')"
                                class="relative text-white hover:text-primary transition-colors group">
                                <i class="bi bi-heart text-[22px]"></i>
                                <span id="wishlist-counter-mobile"
                                    class="absolute -top-1 -right-2 bg-primary text-white text-[8px] font-black w-4 h-4 rounded-full flex items-center justify-center shadow-lg border border-slate-900 group-hover:scale-110 transition-transform">
                                    {{ Auth::check() ? \App\Models\Wishlist::where('user_id', Auth::id())->count() : 0 }}
                                </span>
                            </a>
                            @php
                                $cart = session()->get('cart', []);
                                $cartCount = collect($cart)->sum('quantity');
                            @endphp
                            <a href="{{ route('cart.index') }}"
                                class="relative text-white hover:text-primary transition-colors">
                                <i class="bi bi-cart2 text-2xl"></i>
                                @if($cartCount > 0)
                                    <span
                                        class="absolute -top-1 -right-2 bg-primary text-white text-[8px] font-black w-4 h-4 rounded-full flex items-center justify-center shadow-lg">{{ $cartCount }}</span>
                                @endif
                            </a>
                        </div>
                    </div>

                    <!-- Mobile Search Bar (Inside Header) -->
                    <div class="w-full lg:hidden pb-1">
                        <form action="{{ route('products.index') }}" method="GET" class="relative">
                            <input type="text" name="search"
                                class="w-full bg-white rounded-2xl py-3 pl-12 pr-4 text-[11px] font-bold shadow-sm outline-none text-slate-700"
                                placeholder="Search for gadgets, brands...">
                            <i class="bi bi-search absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                        </form>
                    </div>
                    <div class="w-full lg:w-1/2 hidden md:block">
                        <form action="{{ route('products.index') }}" method="GET"
                            class="flex flex-col sm:flex-row bg-white rounded-xl lg:rounded-2xl overflow-hidden shadow-2xl min-h-[56px] group">
                            <select name="category"
                                class="bg-slate-50 px-6 lg:px-8 py-3 sm:py-0 text-slate-900 font-black text-[10px] lg:text-xs uppercase border-b sm:border-b-0 sm:border-r border-slate-100 outline-none appearance-none cursor-pointer hover:bg-slate-100 transition-colors tracking-widest">
                                <option value="">All Categories</option>
                                @foreach(\App\Models\Category::where('type', 'product')->get() as $cat)
                                    <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}</option>
                                @endforeach
                            </select>
                            <div class="flex flex-1">
                                <input type="text" name="search"
                                    class="flex-1 px-6 py-4 sm:py-0 text-slate-900 text-sm font-bold outline-none placeholder-slate-400"
                                    placeholder="What are you looking for?">
                                <button type="submit"
                                    class="bg-primary hover:bg-primary-dark text-white px-6 lg:px-10 transition-all flex items-center gap-2 active:scale-95">
                                    <i class="bi bi-search text-lg"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="w-full lg:w-1/4 hidden lg:flex justify-end items-center gap-6">
                        @php
                            $cartTotal = collect($cart)->sum(function ($item) {
                                return $item['price'] * $item['quantity'];
                            });
                        @endphp
                        <a href="{{ route('cart.index') }}"
                            class="flex items-center gap-5 group no-underline text-white">
                            <div
                                class="relative bg-white/5 w-14 h-14 rounded-2xl flex items-center justify-center text-2xl border border-white/10 shadow-lg group-hover:bg-primary group-hover:border-primary transition-all group-hover:rotate-6">
                                <i class="bi bi-cart-dash"></i>
                                <span
                                    class="absolute -top-2 -right-2 bg-primary text-white text-[10px] font-black w-6 h-6 rounded-full flex items-center justify-center shadow-xl border-2 border-slate-900 group-hover:bg-white group-hover:text-primary transition-colors">{{ $cartCount }}</span>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest mb-1">Your
                                    Cart</span>
                                <span class="font-black text-lg tracking-tighter">{{ number_format($cartTotal, 0) }}
                                    Tk</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Navigation Bar -->
        <nav class="bg-slate-800 text-white border-t border-white/5 hidden lg:block">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex items-center justify-between">
                    <div x-data="{ open: false }" @mouseleave="open = false" class="relative">
                        <div @mouseenter="open = true"
                            class="bg-primary px-10 py-5 font-black text-xs uppercase tracking-[0.2em] flex items-center gap-4 cursor-pointer hover:bg-primary-dark transition-all">
                            <i class="bi bi-grid-3x3-gap-fill text-lg"></i>
                            Shop By Department
                            <i class="bi bi-chevron-down text-[10px] ml-4 opacity-50"
                                :class="open ? 'rotate-180' : ''"></i>
                        </div>
                        <!-- Dynamic Categories Dropdown -->
                        <div x-show="open" x-transition x-cloak
                            class="absolute top-full left-0 w-64 bg-white shadow-2xl rounded-b-3xl border border-slate-100 py-4 z-[100]">
                            @foreach(\App\Models\Category::where('type', 'product')->get() as $cat)
                                <a href="{{ route('products.index', ['category' => $cat->id]) }}"
                                    class="flex items-center justify-between px-8 py-3 text-slate-700 hover:text-primary hover:bg-slate-50 font-bold text-xs uppercase tracking-widest no-underline transition-all">
                                    {{ $cat->name }}
                                    <i class="bi bi-chevron-right text-[8px] opacity-30"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                    <ul class="hidden lg:flex list-none m-0 p-0 gap-10">
                        <li><a href="{{ route('home') }}"
                                class="text-white no-underline font-black text-xs uppercase tracking-widest py-5 block hover:text-primary transition-all relative group {{ request()->routeIs('home') ? 'text-primary' : '' }}">
                                Home
                                <span
                                    class="absolute bottom-0 left-0 w-0 h-1 bg-primary group-hover:w-full transition-all {{ request()->routeIs('home') ? 'w-full' : '' }}"></span>
                            </a></li>
                        <li><a href="{{ route('products.index') }}"
                                class="text-white no-underline font-black text-xs uppercase tracking-widest py-5 block hover:text-primary transition-all relative group {{ request()->routeIs('products.*') ? 'text-primary' : '' }}">
                                Products
                                <span
                                    class="absolute bottom-0 left-0 w-0 h-1 bg-primary group-hover:w-full transition-all {{ request()->routeIs('products.*') ? 'w-full' : '' }}"></span>
                            </a></li>
                        <li><a href="{{ route('flash-deals') }}"
                                class="text-white no-underline font-black text-xs uppercase tracking-widest py-5 block hover:text-primary transition-all relative group {{ request()->routeIs('flash-deals') ? 'text-primary' : '' }}">
                                Discount
                                <span
                                    class="absolute bottom-0 left-0 w-0 h-1 bg-primary group-hover:w-full transition-all {{ request()->routeIs('flash-deals') ? 'w-full' : '' }}"></span>
                            </a></li>
                        <li><a href="{{ route('frontend.articles') }}"
                                class="text-white no-underline font-black text-xs uppercase tracking-widest py-5 block hover:text-primary transition-all relative group {{ request()->routeIs('frontend.articles*') ? 'text-primary' : '' }}">
                                Tech Blog
                                <span
                                    class="absolute bottom-0 left-0 w-0 h-1 bg-primary group-hover:w-full transition-all {{ request()->routeIs('frontend.articles*') ? 'w-full' : '' }}"></span>
                            </a></li>
                        <li><a href="{{ route('contact') }}"
                                class="text-white no-underline font-black text-xs uppercase tracking-widest py-5 block hover:text-primary transition-all relative group {{ request()->routeIs('contact') ? 'text-primary' : '' }}">
                                Contact
                                <span
                                    class="absolute bottom-0 left-0 w-0 h-1 bg-primary group-hover:w-full transition-all {{ request()->routeIs('contact') ? 'w-full' : '' }}"></span>
                            </a></li>
                    </ul>
                    <a href="{{ route('flash-deals') }}"
                        class="flex items-center gap-3 text-primary-light font-black text-xs uppercase tracking-widest no-underline group relative">
                        <span
                            class="bg-red-500 text-white text-[8px] px-2 py-0.5 rounded-sm absolute -top-5 right-0 uppercase animate-bounce">Limited</span>
                        Discount Products <i class="bi bi-gift-fill group-hover:rotate-12 transition-transform"></i>
                    </a>
                </div>
            </div>
        </nav>
    </div>

    <main class="flex-grow">
        @yield('content')

        <!-- Global Wishlist Modal -->
        <div x-data="wishlistManager" @open-wishlist.window="showWishlist = true; fetchWishlist()"
            @wishlist-changed.window="fetchWishlist()" x-cloak>

            <!-- Backdrop -->
            <div x-show="showWishlist" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" @click="showWishlist = false"
                class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-[100]">
            </div>

            <!-- Modal Content (Side Panel) -->
            <div x-show="showWishlist" x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition ease-in duration-300 transform" x-transition:leave-start="translate-x-0"
                x-transition:leave-end="translate-x-full"
                class="fixed right-0 top-0 h-full w-full max-w-md bg-white shadow-2xl z-[101] flex flex-col">

                <!-- Header -->
                <div class="p-8 border-b border-slate-100 flex items-center justify-between bg-slate-50">
                    <div class="space-y-1">
                        <h3 class="text-xl font-black text-slate-900 uppercase italic tracking-tighter">My Wishlist</h3>
                        <p class="text-[10px] font-black text-primary uppercase tracking-[0.2em]">Personal Collection
                        </p>
                    </div>
                    <button @click="showWishlist = false"
                        class="w-10 h-10 bg-white shadow-md rounded-xl flex items-center justify-center text-slate-400 hover:text-red-500 transition-all active:scale-90 border border-slate-100">
                        <i class="bi bi-x-lg text-lg"></i>
                    </button>
                </div>

                <!-- Content Area -->
                <div class="flex-1 overflow-y-auto p-8 custom-scrollbar">
                    <div x-show="loading" class="flex flex-col items-center justify-center h-40 space-y-4">
                        <div class="w-10 h-10 border-4 border-primary/20 border-t-primary rounded-full animate-spin">
                        </div>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest animate-pulse">
                            Synchronizing...</p>
                    </div>
                    <div x-show="!loading" x-html="wishlistHtml"></div>
                </div>

                <!-- Footer -->
                <div class="p-8 border-t border-slate-100 bg-slate-50">
                    <a href="{{ route('products.index') }}"
                        class="w-full bg-slate-900 text-white py-5 rounded-2xl font-black text-[10px] uppercase tracking-[0.3em] flex items-center justify-center gap-3 hover:bg-primary transition-all shadow-xl shadow-slate-900/10 active:scale-95">
                        Continue Shopping <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-slate-950 text-white pt-24 pb-12 hidden md:block">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-20">
                <!-- Brand Info -->
                <div class="space-y-8">
                    @if(\App\Models\Setting::get('logo'))
                        <img src="{{ asset('storage/' . \App\Models\Setting::get('logo')) }}"
                            alt="{{ \App\Models\Setting::get('site_name', 'ONEMALL') }}" class="h-10">
                    @else
                        <div class="bg-primary text-white w-12 h-12 rounded-xl flex items-center justify-center text-2xl">
                            <i class="bi bi-lightning-charge-fill"></i>
                        </div>
                    @endif
                    <h1 class="m-0 text-2xl font-black tracking-tighter leading-none uppercase italic">
                        {{ \App\Models\Setting::get('site_name', 'ONEMALL') }}</h1>
                    </a>
                    <p class="text-slate-400 text-sm leading-relaxed font-medium">
                        {{ \App\Models\Setting::get('footer_text', 'Your ultimate destination for the latest technology and gadgets. We bring the digital future to your doorstep with authentic products and premium service.') }}
                    </p>
                    <div class="flex gap-4">
                        @if(\App\Models\Setting::get('facebook_url'))
                            <a href="{{ \App\Models\Setting::get('facebook_url') }}" target="_blank"
                                class="w-10 h-10 bg-white/5 rounded-lg flex items-center justify-center hover:bg-primary transition-all text-xl"><i
                                    class="bi bi-facebook"></i></a>
                        @endif
                        @if(\App\Models\Setting::get('twitter_url'))
                            <a href="{{ \App\Models\Setting::get('twitter_url') }}" target="_blank"
                                class="w-10 h-10 bg-white/5 rounded-lg flex items-center justify-center hover:bg-primary transition-all text-xl"><i
                                    class="bi bi-twitter-x"></i></a>
                        @endif
                        @if(\App\Models\Setting::get('instagram_url'))
                            <a href="{{ \App\Models\Setting::get('instagram_url') }}" target="_blank"
                                class="w-10 h-10 bg-white/5 rounded-lg flex items-center justify-center hover:bg-primary transition-all text-xl"><i
                                    class="bi bi-instagram"></i></a>
                        @endif
                    </div>
                </div>

                <!-- Useful Links -->
                <div>
                    <h5 class="text-sm font-black uppercase tracking-[0.2em] mb-8 text-white">Explore Store</h5>
                    <ul class="space-y-4 p-0 m-0 list-none">
                        <li><a href="{{ route('home') }}"
                                class="text-slate-400 hover:text-primary no-underline text-sm font-bold transition-colors">Home
                                Page</a></li>
                        <li><a href="{{ route('products.index') }}"
                                class="text-slate-400 hover:text-primary no-underline text-sm font-bold transition-colors">All
                                Products</a></li>
                        <li><a href="#"
                                class="text-slate-400 hover:text-primary no-underline text-sm font-bold transition-colors">Featured
                                Tech</a></li>
                        <li><a href="{{ route('flash-deals') }}"
                                class="text-slate-400 hover:text-primary no-underline text-sm font-bold transition-colors">Latest
                                Deals</a></li>
                        <li><a href="#"
                                class="text-slate-400 hover:text-primary no-underline text-sm font-bold transition-colors">Gift
                                Cards</a></li>
                    </ul>
                </div>

                <!-- Support Links -->
                <div>
                    <h5 class="text-sm font-black uppercase tracking-[0.2em] mb-8 text-white">Customer Support</h5>
                    <ul class="space-y-4 p-0 m-0 list-none">
                        <li><a href="#"
                                class="text-slate-400 hover:text-primary no-underline text-sm font-bold transition-colors">Track
                                My Order</a></li>
                        <li><a href="#"
                                class="text-slate-400 hover:text-primary no-underline text-sm font-bold transition-colors">Returns
                                & Refunds</a></li>
                        <li><a href="#"
                                class="text-slate-400 hover:text-primary no-underline text-sm font-bold transition-colors">Shipping
                                Policy</a></li>
                        <li><a href="#"
                                class="text-slate-400 hover:text-primary no-underline text-sm font-bold transition-colors">Privacy
                                Policy</a></li>
                        <li><a href="{{ route('contact') }}"
                                class="text-slate-400 hover:text-primary no-underline text-sm font-bold transition-colors">Contact
                                Us</a></li>
                    </ul>
                </div>

                <!-- Contact Info -->
                <div>
                    <h5 class="text-sm font-black uppercase tracking-[0.2em] mb-8 text-white">Get In Touch</h5>
                    <div class="space-y-6">
                        <div class="flex items-start gap-4">
                            <i class="bi bi-geo-alt text-primary text-xl"></i>
                            <p class="text-slate-400 text-sm font-bold leading-tight">
                                {!! nl2br(e(\App\Models\Setting::get('contact_address', "123 Tech Avenue, Silicon Valley,\nCalifornia, USA"))) !!}
                            </p>
                        </div>
                        <div class="flex items-center gap-4">
                            <i class="bi bi-telephone text-primary text-xl"></i>
                            <p class="text-slate-400 text-sm font-bold">
                                {{ \App\Models\Setting::get('contact_phone', '+1 (555) 123-4567') }}</p>
                        </div>
                        <div class="flex items-center gap-4">
                            <i class="bi bi-envelope text-primary text-xl"></i>
                            <p class="text-slate-400 text-sm font-bold">{{ \App\Models\Setting::get('contact_email',
                                'support@onemall.tech') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="pt-8 border-t border-white/5 flex flex-col md:flex-row justify-between items-center gap-6">
                <p class="text-slate-500 text-[10px] font-black uppercase tracking-widest">
                    © {{ date('Y') }} {{ \App\Models\Setting::get('site_name', 'ONEMALL') }}. ALL RIGHTS RESERVED.
                    DESIGNED FOR THE FUTURE.
                </p>
                <div class="flex items-center gap-4 opacity-50">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" class="h-4"
                        alt="Visa">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" class="h-6"
                        alt="Mastercard">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg" class="h-4" alt="PayPal">
                </div>
            </div>
        </div>
    </footer>

    <script src="{{ asset('frontend') }}/js/jquery-1.11.0.min.js"></script>
    <script src="{{ asset('frontend') }}/js/plugins.js"></script>
    <script src="{{ asset('frontend') }}/js/script.js"></script>
    <!-- Global Live Chat Widget -->
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
                    fetch('{{ route('wishlist.fetch') }}')
                        .then(response => response.text())
                        .then(html => {
                            this.wishlistHtml = html;
                            this.loading = false;
                        })
                        .catch(err => {
                            console.error('Wishlist Error:', err);
                            this.wishlistHtml = '<p class="text-center py-10 text-red-500 font-bold uppercase text-[10px] tracking-widest italic">Error loading wishlist.</p>';
                            this.loading = false;
                        });
                },
                toggleWishlist(productId) {
                    // Using a dynamic route replacement for the toggle action
                    let url = '{{ route('wishlist.toggle', ':id') }}'.replace(':id', productId);
                    fetch(url, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        }
                    })
                        .then(res => res.json())
                        .then(data => {
                            const counter = document.getElementById('wishlist-counter');
                            if (counter) counter.innerText = data.count;
                            const counterMobile = document.getElementById('wishlist-counter-mobile');
                            if (counterMobile) counterMobile.innerText = data.count;
                            this.fetchWishlist();
                            window.dispatchEvent(new CustomEvent('wishlist-updated', { detail: { productId: productId, status: data.status } }));
                        })
                        .catch(err => console.error('Toggle Error:', err));
                }
            }))
        })
    </script>

    <!-- Bottom Nav (Mobile) -->
    <div
        class="md:hidden fixed bottom-0 left-0 w-full bg-white border-t border-slate-100 z-[90] flex justify-around items-center py-2.5 shadow-[0_-10px_40px_rgba(0,0,0,0.05)] pb-safe">
        <a href="{{ route('home') }}"
            class="flex flex-col items-center gap-1 {{ request()->routeIs('home') ? 'text-primary' : 'text-slate-400 hover:text-primary transition-colors' }}">
            <i class="bi {{ request()->routeIs('home') ? 'bi-house-door-fill' : 'bi-house-door' }} text-[22px]"></i>
            <span class="text-[8px] font-black uppercase tracking-widest">Home</span>
        </a>
        <a href="{{ route('products.index') }}"
            class="flex flex-col items-center gap-1 {{ request()->routeIs('products.*') ? 'text-primary' : 'text-slate-400 hover:text-primary transition-colors' }}">
            <i class="bi {{ request()->routeIs('products.*') ? 'bi-grid-fill' : 'bi-grid' }} text-[22px]"></i>
            <span class="text-[8px] font-black uppercase tracking-widest">Shop</span>
        </a>
        <a href="{{ route('flash-deals') }}"
            class="flex flex-col items-center gap-1 {{ request()->routeIs('flash-deals') ? 'text-primary' : 'text-slate-400 hover:text-primary transition-colors' }} relative">
            <div class="absolute top-0 right-0 bg-red-500 w-1.5 h-1.5 rounded-full animate-ping"></div>
            <div class="absolute top-0 right-0 bg-red-500 w-1.5 h-1.5 rounded-full"></div>
            <i class="bi {{ request()->routeIs('flash-deals') ? 'bi-tags-fill' : 'bi-tags' }} text-[22px]"></i>
            <span class="text-[8px] font-black uppercase tracking-widest">Discount</span>
        </a>
        <a href="{{ route('cart.index') }}"
            class="flex flex-col items-center gap-1 {{ request()->routeIs('cart.*') ? 'text-primary' : 'text-slate-400 hover:text-primary transition-colors' }} relative">
            @php
                $cartCount = collect(session()->get('cart', []))->sum('quantity');
            @endphp
            @if($cartCount > 0)
                <span
                    class="absolute -top-1 -right-2 bg-primary text-white text-[8px] font-black w-[18px] h-[18px] rounded-full flex items-center justify-center">{{ $cartCount }}</span>
            @endif
            <i class="bi {{ request()->routeIs('cart.*') ? 'bi-cart-fill' : 'bi-cart' }} text-[22px]"></i>
            <span class="text-[8px] font-black uppercase tracking-widest">Cart</span>
        </a>
        @auth
            <a href="{{ route('user.dashboard') }}"
                class="flex flex-col items-center gap-1 {{ request()->routeIs('user.*') ? 'text-primary' : 'text-slate-400 hover:text-primary transition-colors' }}">
                <i class="bi {{ request()->routeIs('user.*') ? 'bi-person-fill' : 'bi-person' }} text-[22px]"></i>
                <span class="text-[8px] font-black uppercase tracking-widest">Profile</span>
            </a>
        @else
            <a href="{{ route('user.auth.login') }}"
                class="flex flex-col items-center gap-1 {{ request()->routeIs('user.auth.*') ? 'text-primary' : 'text-slate-400 hover:text-primary transition-colors' }}">
                <i class="bi {{ request()->routeIs('user.auth.*') ? 'bi-person-fill' : 'bi-person' }} text-[22px]"></i>
                <span class="text-[8px] font-black uppercase tracking-widest">Login</span>
            </a>
        @endauth
    </div>
</body>

</html>