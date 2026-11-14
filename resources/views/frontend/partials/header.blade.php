@use('App\Support\Storefront')

@php
    $locale = app()->getLocale();
    $navLinks = [
        ['route' => 'home', 'label' => __('Home'), 'active' => 'home'],
        ['route' => 'products.index', 'label' => __('Products'), 'active' => 'products.*'],
        ['route' => 'flash-deals', 'label' => __('Deals'), 'active' => 'flash-deals'],
        ['route' => 'frontend.articles', 'label' => __('Tech Blog'), 'active' => 'frontend.articles*'],
        ['route' => 'contact', 'label' => __('Contact'), 'active' => 'contact'],
    ];
    $firstDepartment = $storeDepartments[0]['id'] ?? null;
@endphp

{{-- Utility bar (desktop) --}}
<div class="hidden bg-navy-950 text-xs text-slate-300 lg:block">
    <div class="mx-auto flex h-9 max-w-7xl items-center justify-between gap-6 px-4">
        <div class="flex items-center gap-5">
            <nav aria-label="{{ __('Language') }}" class="flex items-center rounded-full bg-white/5 p-0.5">
                @foreach(['en' => 'EN', 'bn' => 'বাংলা'] as $code => $label)
                    <a href="{{ Storefront::localeUrl($code) }}" hreflang="{{ $code }}" lang="{{ $code }}" @if($locale === $code) aria-current="true" @endif
                       @class(['rounded-full px-2.5 py-1 font-semibold transition-colors sf-focus', 'bg-white text-navy-900' => $locale === $code, 'hover:text-white' => $locale !== $code])>{{ $label }}</a>
                @endforeach
            </nav>
            @if($storeHotline)
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $storeHotline) }}" class="flex items-center gap-1.5 hover:text-white sf-focus rounded">
                    <i class="bi bi-telephone text-primary" aria-hidden="true"></i> {{ __('Hotline') }}: {{ $storeHotline }}
                </a>
            @endif
        </div>
        <div class="flex items-center gap-5">
            <a href="{{ route('tracking.index') }}" class="flex items-center gap-1.5 hover:text-white sf-focus rounded"><i class="bi bi-truck" aria-hidden="true"></i> {{ __('Track order') }}</a>
            <a href="{{ route('wishlist.index') }}" @click.prevent="$dispatch('open-wishlist')" class="flex items-center gap-1.5 hover:text-white sf-focus rounded">
                <i class="bi bi-heart" aria-hidden="true"></i> {{ __('Wishlist') }} (<span id="wishlist-counter" x-text="$store.wishlist.count">{{ $wishlistCount }}</span>)
            </a>
            @auth
                <a href="{{ route('user.dashboard') }}" class="flex items-center gap-1.5 hover:text-white sf-focus rounded"><i class="bi bi-person-check" aria-hidden="true"></i> {{ __('Hi, :name', ['name' => explode(' ', Auth::user()->name)[0]]) }}</a>
                <form method="POST" action="{{ route('auth.logout') }}" id="logout-form">
                    @csrf
                    <button type="submit" class="flex items-center gap-1.5 hover:text-white sf-focus rounded"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> {{ __('Logout') }}</button>
                </form>
            @else
                <a href="{{ route('user.auth.login') }}" class="flex items-center gap-1.5 hover:text-white sf-focus rounded"><i class="bi bi-person" aria-hidden="true"></i> {{ __('Account') }}</a>
                <a href="{{ route('user.auth.login') }}" class="flex items-center gap-1.5 font-semibold text-white hover:text-primary sf-focus rounded"><i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> {{ __('Login') }}</a>
            @endauth
        </div>
    </div>
</div>

{{-- Main header: sticky on every screen size so search is always reachable --}}
<header class="sticky top-0 z-[var(--sf-z-header)] bg-navy-900 text-white shadow-[0_8px_24px_-12px_rgb(0_0_0/0.6)] transition-[padding] duration-200"
        :class="$store.ui.scrolled ? 'lg:py-2' : 'lg:py-3.5'">
    <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-x-3 gap-y-2.5 px-4 pb-3 pt-2.5 lg:flex-nowrap lg:gap-8 lg:py-0">
        <a href="{{ route('home') }}" class="flex min-h-11 min-w-0 flex-1 items-center gap-2.5 rounded sf-focus lg:flex-none lg:shrink-0" aria-label="{{ $storeName }} – {{ __('Home') }}">
            @if($storeLogo)
                <img src="{{ $storeLogo }}" alt="" class="h-8 w-auto transition-all lg:h-11" :class="$store.ui.scrolled && 'lg:!h-9'">
            @else
                <span class="flex size-9 items-center justify-center rounded-[var(--sf-radius-sm)] bg-primary text-lg lg:size-11 lg:text-2xl" aria-hidden="true"><i class="bi bi-lightning-charge-fill"></i></span>
            @endif
            <span class="flex min-w-0 flex-col leading-none">
                <span class="truncate font-display text-base font-extrabold uppercase tracking-tight sm:text-lg lg:text-2xl">{{ $storeName }}</span>
                <span class="mt-0.5 text-[9px] font-semibold uppercase tracking-[0.25em] text-primary-light lg:text-[10px]">{{ __('Digital Universe') }}</span>
            </span>
        </a>

        {{-- Mobile actions --}}
        <div class="ml-auto flex shrink-0 items-center gap-0.5 lg:hidden">
            <a href="{{ Storefront::localeUrl($locale === 'en' ? 'bn' : 'en') }}" hreflang="{{ $locale === 'en' ? 'bn' : 'en' }}"
               class="flex h-11 min-w-11 items-center justify-center rounded-full px-2 text-xs font-bold text-white/90 ring-1 ring-white/15 sf-focus"
               aria-label="{{ $locale === 'en' ? 'বাংলায় দেখুন' : 'View in English' }}">{{ $locale === 'en' ? 'বাং' : 'EN' }}</a>
            <a href="{{ route('wishlist.index') }}" @click.prevent="$dispatch('open-wishlist')" class="relative flex size-11 items-center justify-center rounded-full sf-focus" aria-label="{{ __('Wishlist') }}">
                <i class="bi bi-heart text-xl" aria-hidden="true"></i>
                <span id="wishlist-counter-mobile" x-text="$store.wishlist.count" x-show="$store.wishlist.count > 0" @if(!$wishlistCount) style="display:none" @endif
                      class="absolute right-0.5 top-1 flex min-w-[18px] items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold leading-[18px]">{{ $wishlistCount }}</span>
            </a>
            <a href="{{ route('cart.index') }}" class="relative flex size-11 items-center justify-center rounded-full sf-focus" aria-label="{{ __('Cart') }}">
                <i class="bi bi-bag text-xl" aria-hidden="true"></i>
                <span x-text="$store.cart.count" x-show="$store.cart.count > 0" @if(!$cartCount) style="display:none" @endif
                      class="absolute right-0.5 top-1 flex min-w-[18px] items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold leading-[18px]">{{ $cartCount }}</span>
            </a>
        </div>

        {{-- Mobile search trigger: opens the full-screen search --}}
        <button type="button" @click="$store.ui.openSearch()" class="flex h-11 w-full items-center gap-3 rounded-full bg-white px-4 text-left text-sm text-slate-500 lg:hidden sf-focus"
                aria-haspopup="dialog" aria-label="{{ __('Search products') }}">
            <i class="bi bi-search text-slate-400" aria-hidden="true"></i>
            <span class="truncate">{{ __('Search phones, rice cookers, t-shirts…') }}</span>
        </button>

        {{-- Desktop search with suggestion dropdown --}}
        <div class="relative hidden flex-1 lg:block" x-data="searchBox({ mode: 'dropdown', initial: @js(request()->routeIs('search.index') ? (string) request('q') : '') })"
             @click.outside="close()" @keydown.escape.window="close()">
            <form action="{{ route('search.index') }}" method="GET" role="search" @submit.prevent="submit()" class="flex h-12 overflow-hidden rounded-full bg-white shadow-lg ring-2 ring-transparent transition focus-within:ring-primary">
                <label for="desktop-search" class="sr-only">{{ __('Search products') }}</label>
                <input id="desktop-search" x-ref="input" type="search" name="q" x-model="query" autocomplete="off" spellcheck="false" enterkeyhint="search"
                       placeholder="{{ __('Search phones, rice cookers, t-shirts…') }}"
                       role="combobox" aria-autocomplete="list" :aria-expanded="open.toString()" :aria-controls="`search-${uid}-list`"
                       :aria-activedescendant="active >= 0 ? optionId(active) : null"
                       @focus="onFocus()" @input="onInput($event)" @keydown="onKeydown($event)"
                       @compositionstart="composing = true" @compositionend="onCompositionEnd()"
                       class="min-w-0 flex-1 border-0 bg-transparent px-6 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0">
                <span class="flex items-center pr-2 text-slate-400" x-show="loading" x-cloak aria-hidden="true"><i class="bi bi-arrow-repeat animate-spin"></i></span>
                <button type="submit" class="flex items-center gap-2 bg-primary px-7 text-sm font-semibold text-white transition-colors hover:bg-primary-dark sf-focus">
                    <i class="bi bi-search" aria-hidden="true"></i><span>{{ __('Search') }}</span>
                </button>
            </form>
            <div x-show="open" x-cloak x-transition.opacity.duration.150ms
                 class="absolute inset-x-0 top-full z-[var(--sf-z-dropdown)] mt-2 max-h-[70vh] overflow-y-auto rounded-[var(--sf-radius-lg)] bg-white shadow-[var(--sf-shadow-lg)] ring-1 ring-black/5">
                @include('frontend.partials.search-panel', ['mode' => 'dropdown'])
            </div>
        </div>

        {{-- Desktop wishlist + cart --}}
        <div class="hidden shrink-0 items-center gap-2 lg:flex">
            <a href="{{ route('wishlist.index') }}" @click.prevent="$dispatch('open-wishlist')" class="relative flex size-12 items-center justify-center rounded-full ring-1 ring-white/10 transition hover:bg-white/10 sf-focus" aria-label="{{ __('Wishlist') }}">
                <i class="bi bi-heart text-xl" aria-hidden="true"></i>
                <span x-text="$store.wishlist.count" class="absolute -right-0.5 -top-0.5 flex min-w-5 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-bold leading-5">{{ $wishlistCount }}</span>
            </a>
            <a href="{{ route('cart.index') }}" class="group flex items-center gap-3 rounded-full py-1 pl-1 pr-4 ring-1 ring-white/10 transition hover:bg-white/10 sf-focus">
                <span class="relative flex size-10 items-center justify-center rounded-full bg-primary text-xl" aria-hidden="true">
                    <i class="bi bi-bag"></i>
                    <span x-text="$store.cart.count" class="absolute -right-1 -top-1 flex min-w-5 items-center justify-center rounded-full bg-white px-1 text-[10px] font-bold leading-5 text-navy-900">{{ $cartCount }}</span>
                </span>
                <span class="flex flex-col leading-tight">
                    <span class="text-[11px] text-slate-400">{{ __('Your Cart') }}</span>
                    <span class="text-sm font-bold tabular-nums" x-text="$store.cart.totalText">{{ money($cartTotal) }}</span>
                </span>
            </a>
        </div>
    </div>
</header>

{{-- Navigation row with mega menu (desktop) --}}
<nav class="hidden border-t border-white/5 bg-navy-800 text-white lg:block" aria-label="{{ __('Main') }}">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-4">
        <div class="relative" x-data="{ open: false, active: @js($firstDepartment) }"
             @mouseenter="open = true" @mouseleave="open = false" @keydown.escape="open = false; $refs.trigger.focus()" @focusout="$el.contains($event.relatedTarget) || (open = false)">
            <button type="button" x-ref="trigger" @click="open = !open" :aria-expanded="open.toString()" aria-controls="mega-menu" aria-expanded="false"
                    class="flex h-12 items-center gap-3 bg-primary px-6 text-sm font-semibold transition-colors hover:bg-primary-dark sf-focus">
                <i class="bi bi-grid-3x3-gap-fill" aria-hidden="true"></i> {{ __('Shop by Department') }}
                <i class="bi bi-chevron-down text-xs transition-transform" :class="open && 'rotate-180'" aria-hidden="true"></i>
            </button>

            <div id="mega-menu" x-show="open" x-cloak x-transition.opacity.duration.150ms
                 class="absolute left-0 top-full z-[var(--sf-z-dropdown)] flex w-[860px] overflow-hidden rounded-b-[var(--sf-radius-lg)] bg-white text-slate-800 shadow-[var(--sf-shadow-lg)] ring-1 ring-black/5">
                <ul class="max-h-[460px] w-60 shrink-0 overflow-y-auto border-r border-[var(--sf-border)] bg-slate-50 py-2">
                    @foreach($storeDepartments as $department)
                        <li>
                            <a href="{{ route('products.index', ['category' => $department['id']]) }}"
                               @mouseenter="active = {{ $department['id'] }}" @focus="active = {{ $department['id'] }}"
                               class="flex items-center justify-between gap-2 px-4 py-2.5 text-sm font-medium transition-colors sf-focus"
                               :class="active === {{ $department['id'] }} ? 'bg-white text-brand-700 shadow-[inset_3px_0_0_var(--sf-accent)]' : 'text-slate-700 hover:bg-white'">
                                <span class="flex items-center gap-2.5"><i class="bi {{ $department['icon'] }} text-base text-slate-400" aria-hidden="true"></i>{{ $department['name'] }}</span>
                                @if($department['children'])<i class="bi bi-chevron-right text-[10px] text-slate-400" aria-hidden="true"></i>@endif
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="max-h-[460px] flex-1 overflow-y-auto p-6">
                    @foreach($storeDepartments as $department)
                        <div x-show="active === {{ $department['id'] }}" @if(!$loop->first) x-cloak @endif class="flex gap-6">
                            <div class="min-w-0 flex-1">
                                <div class="mb-4 flex items-baseline justify-between gap-4 border-b border-[var(--sf-border)] pb-3">
                                    <p class="text-base font-bold text-slate-900">{{ $department['name'] }}</p>
                                    <a href="{{ route('products.index', ['category' => $department['id']]) }}" class="text-xs font-semibold text-[var(--sf-accent-strong)] hover:underline">{{ __('View all (:count)', ['count' => localized_number($department['count'])]) }}</a>
                                </div>
                                @if($department['children'])
                                    <ul class="grid grid-cols-2 gap-1">
                                        @foreach($department['children'] as $child)
                                            <li>
                                                <a href="{{ route('products.index', ['category' => $child['id']]) }}" class="flex min-h-11 items-center gap-3 rounded-[var(--sf-radius-sm)] px-3 text-sm text-slate-700 hover:bg-slate-50 hover:text-brand-700 sf-focus">
                                                    <span class="flex size-8 items-center justify-center rounded-[var(--sf-radius-sm)] bg-slate-100 text-slate-500" aria-hidden="true"><i class="bi {{ $child['icon'] ?? $department['icon'] }}"></i></span>
                                                    {{ $child['name'] }}
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-sm text-slate-500">{{ __('Browse every product in :name.', ['name' => $department['name']]) }}</p>
                                @endif
                            </div>
                            @if($department['brands'] || $department['image'])
                                <aside class="w-48 shrink-0 space-y-3">
                                    @if($department['image'])
                                        <x-store.image :src="$department['image']" :alt="$department['name']" fit="cover" :icon="$department['icon']" :width="192" :height="120" class="h-28 rounded-[var(--sf-radius)] bg-slate-100" />
                                    @endif
                                    @if($department['brands'])
                                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Top brands') }}</p>
                                        <ul class="grid grid-cols-2 gap-2">
                                            @foreach($department['brands'] as $brand)
                                                <li>
                                                    <a href="{{ route('products.index', ['brand' => $brand['slug']]) }}" class="flex h-11 items-center justify-center rounded-[var(--sf-radius-sm)] border border-[var(--sf-border)] px-2 text-xs font-semibold text-slate-700 hover:border-brand-300 sf-focus">
                                                        @if($brand['logo'])<img src="{{ $brand['logo'] }}" alt="{{ $brand['name'] }}" class="max-h-7 max-w-full object-contain" loading="lazy">@else{{ $brand['name'] }}@endif
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </aside>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <ul class="flex flex-1 items-center gap-1">
            @foreach($navLinks as $link)
                @php($current = request()->routeIs($link['active']))
                <li>
                    <a href="{{ route($link['route']) }}" @if($current) aria-current="page" @endif
                       @class(['relative flex h-12 items-center px-4 text-sm font-medium transition-colors sf-focus', 'text-white after:absolute after:inset-x-4 after:bottom-0 after:h-0.5 after:bg-primary' => $current, 'text-slate-300 hover:text-white' => !$current])>{{ $link['label'] }}</a>
                </li>
            @endforeach
        </ul>

        <a href="{{ route('flash-deals') }}" class="flex h-8 items-center gap-2 rounded-full bg-sale px-4 text-xs font-bold text-white shadow-lg shadow-sale/30 hover:brightness-110 sf-focus">
            <i class="bi bi-lightning-charge-fill" aria-hidden="true"></i> {{ __('Deals') }}
        </a>
    </div>
</nav>
