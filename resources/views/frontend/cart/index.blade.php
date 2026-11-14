@extends('frontend.layout')

@use('App\Support\Storefront')

@section('title', __('Shopping cart'))

@if($cart)
    @push('head')
        {{-- Keeps the chat button above the mobile checkout bar. --}}
        <style>@media (max-width: 1023px) { :root { --sf-fab-lift: 76px; } }</style>
    @endpush
@endif

@section('content')
@php
    $fmt = fn ($amount) => money($amount, null, fmod((float) $amount, 1.0) != 0.0 ? 2 : 0);
    $itemCount = (int) collect($cart)->sum('quantity');
    $itemsTotal = max(0, $total - $discount);
    $freeShipping = $freeShippingThreshold !== null && $itemsTotal >= $freeShippingThreshold;
    $progress = $freeShippingThreshold ? (int) min(100, round($itemsTotal / $freeShippingThreshold * 100)) : 0;
@endphp

<div class="mx-auto max-w-6xl px-4 pb-28 pt-5 md:pt-8 lg:pb-16">
    <x-checkout.steps current="cart" class="mx-auto max-w-xl" />

    <div class="mt-6 flex flex-wrap items-end justify-between gap-3 md:mt-8">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">{{ __('Your cart') }}</h1>
            @if($cart)
                <p class="mt-1 text-sm text-slate-500">{{ trans_choice(':count item|:count items', $itemCount, ['count' => localized_number($itemCount)]) }}</p>
            @endif
        </div>
        @if($cart)
            <a href="{{ route('products.index') }}" class="hidden min-h-11 items-center gap-1.5 rounded text-sm font-semibold text-brand-700 hover:underline sm:flex sf-focus">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> {{ __('Continue shopping') }}
            </a>
        @endif
    </div>

    @if(session('success'))
        <div class="mt-5 flex gap-3 rounded-[var(--sf-radius-sm)] border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">
            <i class="bi bi-check-circle-fill mt-0.5" aria-hidden="true"></i><p>{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="mt-5 flex gap-3 rounded-[var(--sf-radius-sm)] border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
            <i class="bi bi-exclamation-triangle-fill mt-0.5" aria-hidden="true"></i><p>{{ session('error') }}</p>
        </div>
    @endif
    <x-store.cart-changes :changes="$cartChanges" class="mt-5" />

    @if($cart)
        <div class="mt-5 lg:grid lg:grid-cols-[minmax(0,1fr)_380px] lg:items-start lg:gap-8">
            {{-- Items --}}
            <section aria-label="{{ __('Items in your cart') }}" class="overflow-hidden rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white">
                <div class="hidden grid-cols-[minmax(0,1fr)_150px_120px] gap-4 border-b border-[var(--sf-border)] bg-slate-50 px-5 py-3 text-xs font-semibold uppercase tracking-wider text-slate-500 md:grid" aria-hidden="true">
                    <span>{{ __('Product') }}</span><span class="text-center">{{ __('Quantity') }}</span><span class="text-right">{{ __('Total') }}</span>
                </div>
                <ul class="divide-y divide-[var(--sf-border)]">
                    @foreach($cart as $id => $item)
                        @php
                            $quantity = (int) $item['quantity'];
                            $stock = isset($item['stock']) ? (int) $item['stock'] : null;
                            $productUrl = route('products.show', $item['product_id']);
                        @endphp
                        <li class="flex gap-4 p-4 md:grid md:grid-cols-[minmax(0,1fr)_150px_120px] md:items-center md:px-5">
                            <div class="flex min-w-0 flex-1 gap-4">
                                <a href="{{ $productUrl }}" class="shrink-0 rounded-[var(--sf-radius)] sf-focus" tabindex="-1" aria-hidden="true">
                                    <x-store.image :src="Storefront::imageUrl($item['image'] ?? null)" :alt="$item['name']" :width="88" :height="88"
                                                   class="size-20 rounded-[var(--sf-radius)] border border-[var(--sf-border)] bg-slate-50 md:size-[88px] [&_i]:!text-3xl" img-class="p-1.5 mix-blend-multiply" />
                                </a>
                                <div class="min-w-0 flex-1">
                                    <a href="{{ $productUrl }}" class="line-clamp-2 rounded text-sm font-medium leading-snug text-slate-900 hover:text-brand-700 sf-focus md:text-base">{{ $item['name'] }}</a>
                                    @if(!empty($item['variant_name']))
                                        <p class="mt-0.5 text-xs text-slate-500">{{ $item['variant_name'] }}</p>
                                    @endif
                                    <p class="mt-1 text-sm tabular-nums text-slate-600">{{ $fmt($item['price']) }}</p>
                                    @if($stock !== null && $stock > 0 && $stock <= 5)
                                        <p class="mt-0.5 text-xs font-semibold text-[var(--sf-warning)]">{{ __('Only :count left', ['count' => localized_number($stock)]) }}</p>
                                    @endif

                                    {{-- Mobile: quantity, remove and line total under the name --}}
                                    <div class="mt-3 flex items-center justify-between gap-3 md:hidden">
                                        @include('frontend.cart.partials.quantity', ['id' => $id, 'quantity' => $quantity, 'stock' => $stock, 'name' => $item['name']])
                                        <p class="text-sm font-semibold tabular-nums text-slate-900">{{ $fmt($item['price'] * $quantity) }}</p>
                                    </div>
                                    <form action="{{ route('cart.remove', $id) }}" method="POST" class="mt-2">
                                        @csrf
                                        <button type="submit" class="inline-flex min-h-9 items-center gap-1.5 rounded text-xs font-medium text-slate-500 hover:text-rose-600 sf-focus">
                                            <i class="bi bi-trash3" aria-hidden="true"></i> {{ __('Remove') }}<span class="sr-only">: {{ $item['name'] }}</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="hidden justify-center md:flex">
                                @include('frontend.cart.partials.quantity', ['id' => $id, 'quantity' => $quantity, 'stock' => $stock, 'name' => $item['name']])
                            </div>
                            <p class="hidden text-right font-semibold tabular-nums text-slate-900 md:block">{{ $fmt($item['price'] * $quantity) }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>

            {{-- Summary --}}
            <aside class="mt-6 lg:sticky lg:top-24 lg:mt-0" aria-labelledby="cart-summary-title">
                <div class="rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white p-5">
                    <h2 id="cart-summary-title" class="text-lg font-semibold tracking-tight text-slate-900">{{ __('Order summary') }}</h2>

                    @if($freeShippingThreshold !== null)
                        <div class="mt-4 rounded-[var(--sf-radius-sm)] bg-brand-50 p-3">
                            <p class="flex items-center gap-1.5 text-xs text-slate-700">
                                <i class="bi bi-truck text-brand-600" aria-hidden="true"></i>
                                @if($freeShipping)
                                    <strong class="text-emerald-700">{{ __('You get free delivery!') }}</strong>
                                @else
                                    {!! __('Add :amount more for <strong>free delivery</strong>', ['amount' => e($fmt($freeShippingThreshold - $itemsTotal))]) !!}
                                @endif
                            </p>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}" aria-label="{{ __('Progress to free delivery') }}">
                                <div @class(['h-full rounded-full', 'bg-emerald-500' => $freeShipping, 'bg-primary' => !$freeShipping]) style="width: {{ max(4, $progress) }}%"></div>
                            </div>
                        </div>
                    @endif

                    {{-- Discount code --}}
                    <div class="mt-4">
                        @if($coupon)
                            <div class="flex items-center justify-between gap-3 rounded-[var(--sf-radius-sm)] border border-emerald-200 bg-emerald-50 px-3 py-2.5">
                                <p class="flex min-w-0 items-center gap-2 text-sm text-emerald-800">
                                    <i class="bi bi-tag-fill" aria-hidden="true"></i>
                                    <span class="font-semibold uppercase tracking-wide">{{ $coupon->code }}</span>
                                    <span class="truncate text-emerald-700/80">{{ $coupon->summary() }}</span>
                                </p>
                                <form method="POST" action="{{ route('cart.coupon.remove') }}">
                                    @csrf
                                    <button type="submit" class="flex size-8 items-center justify-center rounded-full text-emerald-700 hover:bg-emerald-100 sf-focus">
                                        <span class="sr-only">{{ __('Remove discount :code', ['code' => $coupon->code]) }}</span><i class="bi bi-x-lg text-xs" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        @else
                            <form method="POST" action="{{ route('cart.coupon.apply') }}" x-data="{ code: @js(old('code', '')) }" class="flex items-start gap-2">
                                @csrf
                                <x-checkout.field name="code" :label="__('Discount code')" class="flex-1" x-model="code" maxlength="50" autocomplete="off" autocapitalize="characters" />
                                <button type="submit" :disabled="!code.trim()"
                                        class="h-[52px] shrink-0 rounded-[var(--sf-radius-sm)] bg-navy-900 px-5 text-sm font-semibold text-white transition-colors hover:bg-navy-800 disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-500 sf-focus">
                                    {{ __('Apply') }}
                                </button>
                            </form>
                            @if(session('coupon_error'))
                                <p class="mt-2 flex items-center gap-1 text-xs font-medium text-rose-600" role="alert"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ session('coupon_error') }}</p>
                            @endif
                        @endif
                    </div>

                    <dl class="mt-5 space-y-2.5 border-t border-[var(--sf-border)] pt-5 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-600">{{ __('Subtotal') }}</dt>
                            <dd class="font-medium tabular-nums text-slate-900">{{ $fmt($total) }}</dd>
                        </div>
                        @if($discount > 0)
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-600">{{ __('Discount') }}</dt>
                                <dd class="font-medium tabular-nums text-emerald-700">−{{ $fmt($discount) }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-600">{{ __('Shipping') }}</dt>
                            <dd class="text-slate-500">{{ $freeShipping ? __('Free') : __('Calculated at checkout') }}</dd>
                        </div>
                        <div class="flex items-baseline justify-between gap-4 border-t border-[var(--sf-border)] pt-4">
                            <dt class="text-base font-semibold text-slate-900">{{ __('Estimated total') }}</dt>
                            <dd class="font-display text-2xl font-bold tabular-nums text-slate-900">{{ $fmt($itemsTotal) }}</dd>
                        </div>
                    </dl>

                    <a href="{{ route('checkout.index') }}" class="mt-5 flex h-14 w-full items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] bg-brand-600 text-base font-semibold text-white shadow-[var(--sf-shadow)] transition-colors hover:bg-brand-700 sf-focus">
                        <i class="bi bi-lock-fill" aria-hidden="true"></i> {{ __('Checkout') }}
                    </a>
                    @guest
                        <p class="mt-2 text-center text-xs text-slate-500">{{ __('You will be asked to log in or create an account.') }}</p>
                    @endguest

                    <ul class="mt-5 flex flex-wrap items-center justify-center gap-1.5" aria-label="{{ __('Payment methods') }}">
                        <li class="flex h-6 items-center rounded bg-[#e2136e] px-1.5 text-[10px] font-bold text-white">bKash</li>
                        <li class="flex h-6 items-center rounded bg-[#ec1c24] px-1.5 text-[10px] font-bold text-white">Nagad</li>
                        <li class="flex h-6 items-center rounded border border-slate-200 bg-white px-1.5 text-[10px] font-extrabold italic text-[#1a1f71]">VISA</li>
                        <li class="flex h-6 items-center rounded border border-slate-200 bg-white px-1" aria-label="Mastercard">
                            <svg width="22" height="14" viewBox="0 0 28 18" aria-hidden="true"><circle cx="10" cy="9" r="8" fill="#eb001b"/><circle cx="18" cy="9" r="8" fill="#f79e1b" fill-opacity=".9"/></svg>
                        </li>
                        <li class="flex h-6 items-center gap-1 rounded border border-slate-200 bg-white px-1.5 text-[10px] font-semibold text-slate-700"><i class="bi bi-cash-coin" aria-hidden="true"></i> COD</li>
                    </ul>
                </div>

                <ul class="mt-4 grid grid-cols-3 gap-2 text-center text-[11px] leading-tight text-slate-600">
                    <li class="flex flex-col items-center gap-1.5"><i class="bi bi-shield-lock text-lg text-brand-600" aria-hidden="true"></i>{{ __('Secure checkout') }}</li>
                    <li class="flex flex-col items-center gap-1.5"><i class="bi bi-arrow-counterclockwise text-lg text-brand-600" aria-hidden="true"></i>{{ __(':days-day returns', ['days' => localized_number(config('store.return_days', 30))]) }}</li>
                    <li class="flex flex-col items-center gap-1.5"><i class="bi bi-patch-check text-lg text-brand-600" aria-hidden="true"></i>{{ __('100% genuine products') }}</li>
                </ul>
            </aside>
        </div>

        {{-- Mobile: checkout bar above the bottom navigation --}}
        <div class="fixed inset-x-0 bottom-[calc(var(--sf-bottom-nav-h)+var(--sf-safe-bottom))] z-[var(--sf-z-fab)] border-t border-[var(--sf-border)] bg-white/95 px-4 py-3 shadow-[0_-8px_24px_-12px_rgb(15_23_42/0.25)] backdrop-blur md:bottom-0 md:pb-[calc(0.75rem+var(--sf-safe-bottom))] lg:hidden">
            <div class="mx-auto flex max-w-6xl items-center gap-4">
                <div class="min-w-0">
                    <p class="text-xs text-slate-500">{{ __('Estimated total') }}</p>
                    <p class="font-display text-lg font-bold tabular-nums text-slate-900">{{ $fmt($itemsTotal) }}</p>
                </div>
                <a href="{{ route('checkout.index') }}" class="ml-auto flex h-12 flex-1 items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] bg-brand-600 px-6 text-sm font-semibold text-white active:bg-brand-700 sm:flex-none sf-focus">
                    <i class="bi bi-lock-fill" aria-hidden="true"></i> {{ __('Checkout') }}
                </a>
            </div>
        </div>
    @else
        <div class="mt-6 rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white px-6 py-14 text-center md:py-20">
            <span class="mx-auto flex size-20 items-center justify-center rounded-full bg-brand-50 text-4xl text-brand-600" aria-hidden="true"><i class="bi bi-bag"></i></span>
            <h2 class="mt-5 font-display text-xl font-bold text-slate-900 md:text-2xl">{{ __('Your cart is empty') }}</h2>
            <p class="mx-auto mt-2 max-w-md text-sm text-slate-500">{{ __('Looks like you have not added anything yet. Browse our latest phones, laptops and home appliances.') }}</p>
            <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ route('products.index') }}" class="flex h-12 items-center gap-2 rounded-full bg-navy-900 px-7 text-sm font-semibold text-white hover:bg-navy-800 sf-focus">{{ __('Start shopping') }} <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                <a href="{{ route('flash-deals') }}" class="flex h-12 items-center gap-2 rounded-full border border-[var(--sf-border)] px-7 text-sm font-semibold text-slate-700 hover:border-slate-300 sf-focus"><i class="bi bi-lightning-charge-fill text-sale" aria-hidden="true"></i> {{ __('See today\'s deals') }}</a>
            </div>
        </div>
    @endif

    @if($recommendations->isNotEmpty())
        <section class="mt-10 md:mt-14" aria-labelledby="recommended-title">
            <x-store.section-header :title="$cart ? __('You may also like') : __('Popular right now')" id="recommended-title" icon="bi-stars" />
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
                @foreach($recommendations as $product)
                    <x-store.product-card :product="$product" :wishlist-ids="$wishlistIds" />
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
