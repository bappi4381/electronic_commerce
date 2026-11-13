@php
    $tabs = [
        ['route' => 'home', 'active' => 'home', 'label' => __('Home'), 'icon' => 'bi-house-door', 'iconActive' => 'bi-house-door-fill'],
        ['route' => 'products.index', 'active' => ['products.*', 'search.index'], 'label' => __('Shop'), 'icon' => 'bi-grid', 'iconActive' => 'bi-grid-fill'],
        ['route' => 'flash-deals', 'active' => 'flash-deals', 'label' => __('Discount'), 'icon' => 'bi-tags', 'iconActive' => 'bi-tags-fill', 'dot' => true],
        ['route' => 'cart.index', 'active' => ['cart.*', 'checkout.*'], 'label' => __('Cart'), 'icon' => 'bi-bag', 'iconActive' => 'bi-bag-fill', 'cart' => true],
        auth()->check()
            ? ['route' => 'user.dashboard', 'active' => 'user.*', 'label' => __('Account'), 'icon' => 'bi-person', 'iconActive' => 'bi-person-fill']
            : ['route' => 'user.auth.login', 'active' => ['user.auth.*'], 'label' => __('Login'), 'icon' => 'bi-person', 'iconActive' => 'bi-person-fill'],
    ];
@endphp

<nav class="fixed inset-x-0 bottom-0 z-[var(--sf-z-bottom-nav)] border-t border-[var(--sf-border)] bg-white/95 pb-safe shadow-[0_-8px_24px_-12px_rgb(15_23_42/0.18)] backdrop-blur transition-transform duration-200 md:hidden"
     :class="($store.ui.searchOpen || $store.ui.keyboardOpen) && 'translate-y-full'" aria-label="{{ __('Main') }}">
    <ul class="grid h-[var(--sf-bottom-nav-h)] grid-cols-5">
        @foreach($tabs as $tab)
            @php($current = request()->routeIs(...(array) $tab['active']))
            <li>
                <a href="{{ route($tab['route']) }}" @if($current) aria-current="page" @endif
                   @class(['relative flex h-full flex-col items-center justify-center gap-0.5 text-[11px] font-medium sf-focus', 'text-brand-700' => $current, 'text-slate-500' => !$current])>
                    @if($current)<span class="absolute inset-x-5 top-0 h-0.5 rounded-b bg-primary" aria-hidden="true"></span>@endif
                    <span class="relative">
                        <i class="bi {{ $current ? $tab['iconActive'] : $tab['icon'] }} text-[22px] leading-none" aria-hidden="true"></i>
                        @if(!empty($tab['cart']))
                            <span x-text="$store.cart.count" x-show="$store.cart.count > 0" @if(!$cartCount) style="display:none" @endif
                                  class="absolute -right-2.5 -top-1 flex min-w-[18px] items-center justify-center rounded-full bg-sale px-1 text-[10px] font-bold leading-[18px] text-white">{{ $cartCount }}</span>
                        @elseif(!empty($tab['dot']))
                            <span class="absolute -right-1 top-0 size-2 rounded-full bg-sale ring-2 ring-white" aria-hidden="true"></span>
                        @endif
                    </span>
                    {{ $tab['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
