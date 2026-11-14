@extends('frontend.layout')

@use('App\Services\CheckoutOptions')
@use('App\Support\Storefront')

@section('title', __('Checkout'))

@section('content')
@php
    $fmt = fn ($amount) => money($amount, null, fmod((float) $amount, 1.0) != 0.0 ? 2 : 0);
    $itemsTotal = max(0, $subtotal - $discount);
    $freeShipping = $freeShippingThreshold !== null && $itemsTotal >= $freeShippingThreshold;
    $itemCount = (int) collect($cart)->sum('quantity');

    // Everything the page needs per delivery zone, pre-formatted so switching city never does money maths in JS.
    $zones = collect($deliveryOptions)->mapWithKeys(fn ($zone, $name) => [$name => [
        'label' => $name === CheckoutOptions::OTHER_ZONE ? __('Other areas') : $name,
        'etaText' => $zone['eta'] !== '' ? __('Delivery in :eta', ['eta' => $zone['eta']]) : __('Delivered by our courier partner'),
        'chargeText' => $freeShipping ? __('Free') : $fmt($zone['charge']),
        'totalText' => $fmt($itemsTotal + ($freeShipping ? 0 : $zone['charge'])),
    ]])->all();

    $selectedCity = old('city', array_key_exists((string) $prefill['city'], $zones) ? $prefill['city'] : array_key_first($zones));
    $selectedMethod = old('payment_method', in_array(old('payment_method'), $paymentMethods, true) ? old('payment_method') : $paymentMethods[0]);
    $hasSavedAddress = filled(auth()->user()?->address);
    $legal = $policyPages->keyBy('slug');
    $section = 'text-lg font-semibold tracking-tight text-slate-900';
    $checkbox = 'mt-0.5 size-[18px] shrink-0 rounded border-slate-300 accent-brand-600';
@endphp

<form id="checkout-logout" method="POST" action="{{ route('auth.logout') }}" class="hidden">@csrf</form>

<div x-data="{ city: @js($selectedCity), method: @js($selectedMethod), zones: @js($zones), summaryOpen: false, submitting: false }"
     class="border-b border-[var(--sf-border)] bg-white lg:grid lg:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]">

    {{-- Order summary: collapsible bar on mobile, sticky grey column on desktop --}}
    <aside class="border-b border-[var(--sf-border)] bg-[var(--sf-bg)] lg:col-start-2 lg:row-start-1 lg:border-b-0 lg:border-l" aria-labelledby="summary-title">
        <button type="button" @click="summaryOpen = !summaryOpen" :aria-expanded="summaryOpen.toString()" aria-controls="order-summary"
                class="flex w-full items-center justify-between gap-3 px-4 py-4 sm:px-6 lg:hidden sf-focus">
            <span class="flex items-center gap-2 text-sm font-medium text-brand-700">
                <i class="bi bi-cart3 text-base" aria-hidden="true"></i>
                <span x-text="summaryOpen ? @js(__('Hide order summary')) : @js(__('Show order summary'))">{{ __('Show order summary') }}</span>
                <i class="bi bi-chevron-down text-xs transition-transform" :class="summaryOpen && 'rotate-180'" aria-hidden="true"></i>
            </span>
            <span class="text-lg font-semibold tabular-nums text-slate-900" x-text="zones[city].totalText">{{ $zones[$selectedCity]['totalText'] }}</span>
        </button>

        <div id="order-summary" class="px-4 pb-6 max-lg:hidden sm:px-6 lg:sticky lg:top-24 lg:max-w-[480px] lg:px-10 lg:py-10" :class="summaryOpen && 'max-lg:!block'">
            <h2 id="summary-title" class="sr-only">{{ __('Order summary') }}</h2>

            <ul class="space-y-4" aria-label="{{ __('Items in your order') }}">
                @foreach($cart as $item)
                    <li class="flex items-center gap-4">
                        <div class="relative shrink-0">
                            <x-store.image :src="Storefront::imageUrl($item['image'] ?? null)" :alt="$item['name']" :width="64" :height="64"
                                           class="size-16 rounded-[var(--sf-radius)] border border-[var(--sf-border)] bg-white [&_i]:!text-2xl" img-class="p-1" />
                            <span class="absolute -right-2 -top-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-slate-600 px-1.5 text-[11px] font-semibold text-white"
                                  aria-label="{{ __('Quantity: :count', ['count' => $item['quantity']]) }}">{{ localized_number($item['quantity']) }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="line-clamp-2 text-sm font-medium leading-snug text-slate-900">{{ $item['name'] }}</p>
                            @if(!empty($item['variant_name']))
                                <p class="mt-0.5 truncate text-xs text-slate-500">{{ $item['variant_name'] }}</p>
                            @endif
                            @if($item['quantity'] > 1)
                                <p class="mt-0.5 text-xs text-slate-500 tabular-nums">{{ localized_number($item['quantity']) }} × {{ $fmt($item['price']) }}</p>
                            @endif
                        </div>
                        <p class="shrink-0 text-sm font-medium tabular-nums text-slate-900">{{ $fmt($item['price'] * $item['quantity']) }}</p>
                    </li>
                @endforeach
            </ul>
            <p class="mt-4 text-right">
                <a href="{{ route('cart.index') }}" class="rounded text-xs font-semibold text-brand-700 hover:underline sf-focus">{{ __('Edit cart') }}</a>
            </p>

            {{-- Discount code --}}
            <div class="mt-4 border-t border-[var(--sf-border)] pt-5">
                @if($coupon)
                    <div class="flex items-center justify-between gap-3 rounded-[var(--sf-radius-sm)] border border-emerald-200 bg-emerald-50 px-3 py-2.5">
                        <p class="flex min-w-0 items-center gap-2 text-sm text-emerald-800">
                            <i class="bi bi-tag-fill" aria-hidden="true"></i>
                            <span class="font-semibold uppercase tracking-wide">{{ $coupon->code }}</span>
                            <span class="truncate text-emerald-700/80">{{ $coupon->summary() }}</span>
                        </p>
                        <form method="POST" action="{{ route('cart.coupon.remove') }}">
                            @csrf
                            <input type="hidden" name="return" value="checkout">
                            <button type="submit" class="flex size-8 items-center justify-center rounded-full text-emerald-700 hover:bg-emerald-100 sf-focus">
                                <span class="sr-only">{{ __('Remove discount :code', ['code' => $coupon->code]) }}</span><i class="bi bi-x-lg text-xs" aria-hidden="true"></i>
                            </button>
                        </form>
                    </div>
                @else
                    <form method="POST" action="{{ route('cart.coupon.apply') }}" x-data="{ code: @js(old('code', '')) }" class="flex items-start gap-2">
                        @csrf
                        <input type="hidden" name="return" value="checkout">
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

            {{-- Totals --}}
            <dl class="mt-5 space-y-2.5 border-t border-[var(--sf-border)] pt-5 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-600">{{ __('Subtotal') }} · {{ trans_choice(':count item|:count items', $itemCount, ['count' => localized_number($itemCount)]) }}</dt>
                    <dd class="font-medium tabular-nums text-slate-900">{{ $fmt($subtotal) }}</dd>
                </div>
                @if($discount > 0)
                    <div class="flex justify-between gap-4">
                        <dt class="flex items-center gap-1.5 text-slate-600">{{ __('Discount') }} <span class="inline-flex items-center gap-1 rounded bg-slate-200/70 px-1.5 py-0.5 text-[11px] font-semibold uppercase text-slate-700"><i class="bi bi-tag-fill" aria-hidden="true"></i>{{ $coupon?->code }}</span></dt>
                        <dd class="font-medium tabular-nums text-emerald-700">−{{ $fmt($discount) }}</dd>
                    </div>
                @endif
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-600">{{ __('Shipping') }} <span class="text-xs text-slate-400" x-text="'(' + zones[city].label + ')'"></span></dt>
                    <dd class="font-medium tabular-nums text-slate-900" x-text="zones[city].chargeText">{{ $zones[$selectedCity]['chargeText'] }}</dd>
                </div>
                @if($freeShippingThreshold !== null && !$freeShipping)
                    @php($progress = (int) min(100, round($itemsTotal / $freeShippingThreshold * 100)))
                    <div class="rounded-[var(--sf-radius-sm)] bg-white p-3 ring-1 ring-[var(--sf-border)]">
                        <p class="flex items-center gap-1.5 text-xs text-slate-600"><i class="bi bi-truck text-brand-600" aria-hidden="true"></i> {!! __('Add :amount more for <strong>free delivery</strong>', ['amount' => e($fmt($freeShippingThreshold - $itemsTotal))]) !!}</p>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}" aria-label="{{ __('Progress to free delivery') }}">
                            <div class="h-full rounded-full bg-primary" style="width: {{ max(4, $progress) }}%"></div>
                        </div>
                    </div>
                @endif
                <div class="flex items-baseline justify-between gap-4 border-t border-[var(--sf-border)] pt-4">
                    <dt class="text-base font-semibold text-slate-900">{{ __('Total') }}</dt>
                    <dd class="flex items-baseline gap-2">
                        <span class="text-xs text-slate-500">BDT</span>
                        <span class="font-display text-2xl font-bold tabular-nums text-slate-900" x-text="zones[city].totalText">{{ $zones[$selectedCity]['totalText'] }}</span>
                    </dd>
                </div>
                @if($discount > 0)
                    <p class="flex items-center gap-1.5 text-sm font-semibold text-emerald-700"><i class="bi bi-tag-fill" aria-hidden="true"></i> {{ __('Total savings') }} {{ $fmt($discount) }}</p>
                @endif
            </dl>

            {{-- Frequently bought together --}}
            @if($upsells->isNotEmpty())
                <section class="mt-8" aria-labelledby="upsell-title">
                    <h3 id="upsell-title" class="text-sm font-semibold uppercase tracking-wide text-slate-900">{{ __('Frequently bought together') }}</h3>
                    <ul class="mt-3 space-y-2">
                        @foreach($upsells as $product)
                            @php($price = (float) ($product->discounted_price ?: $product->price))
                            <li class="flex items-center gap-3 rounded-[var(--sf-radius)] border border-[var(--sf-border)] bg-white p-2.5">
                                <x-store.image :src="Storefront::imageUrl($product->images->first()?->image)" :alt="(string) $product->name" :width="56" :height="56"
                                               class="size-14 shrink-0 rounded-[var(--sf-radius-sm)] bg-slate-50 [&_i]:!text-xl" img-class="p-1 mix-blend-multiply" />
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('products.show', $product->id) }}" class="line-clamp-2 rounded text-sm font-medium leading-snug text-slate-900 hover:text-brand-700 sf-focus">{{ $product->name }}</a>
                                    <p class="mt-0.5 text-sm tabular-nums text-slate-600">
                                        {{ $fmt($price) }}
                                        @if($price < (float) $product->price)<span class="ml-1 text-xs text-slate-400 line-through">{{ $fmt($product->price) }}</span>@endif
                                    </p>
                                </div>
                                <form method="POST" action="{{ route('cart.add') }}" class="shrink-0">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <button type="submit" class="flex h-9 items-center gap-1 rounded-full border border-slate-300 px-3 text-xs font-semibold text-slate-800 transition-colors hover:border-brand-600 hover:text-brand-700 sf-focus"
                                            aria-label="{{ __('Add :name to cart', ['name' => $product->name]) }}">
                                        {{ __('Add') }} <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <ul class="mt-8 grid grid-cols-3 gap-2 text-center text-[11px] leading-tight text-slate-600">
                <li class="flex flex-col items-center gap-1.5"><i class="bi bi-shield-lock text-lg text-brand-600" aria-hidden="true"></i>{{ __('Secure checkout') }}</li>
                <li class="flex flex-col items-center gap-1.5"><i class="bi bi-arrow-counterclockwise text-lg text-brand-600" aria-hidden="true"></i>{{ __(':days-day returns', ['days' => localized_number(config('store.return_days', 30))]) }}</li>
                <li class="flex flex-col items-center gap-1.5"><i class="bi bi-patch-check text-lg text-brand-600" aria-hidden="true"></i>{{ __('100% genuine products') }}</li>
            </ul>
        </div>
    </aside>

    {{-- Customer details, delivery and payment --}}
    <div class="lg:col-start-1 lg:row-start-1 lg:flex lg:justify-end">
        <div class="mx-auto w-full max-w-[640px] px-4 py-6 sm:px-6 lg:mx-0 lg:px-10 lg:py-10 xl:pr-14">
            <x-checkout.steps current="details" />

            @if(session('error'))
                <div class="mt-5 flex gap-3 rounded-[var(--sf-radius-sm)] border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
                    <i class="bi bi-exclamation-triangle-fill mt-0.5" aria-hidden="true"></i><p>{{ session('error') }}</p>
                </div>
            @elseif($errors->any())
                <div class="mt-5 flex gap-3 rounded-[var(--sf-radius-sm)] border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800" role="alert">
                    <i class="bi bi-exclamation-triangle-fill mt-0.5" aria-hidden="true"></i><p>{{ __('Please check the highlighted fields and try again.') }}</p>
                </div>
            @elseif(session('success'))
                <div class="mt-5 flex gap-3 rounded-[var(--sf-radius-sm)] border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800" role="status">
                    <i class="bi bi-check-circle-fill mt-0.5" aria-hidden="true"></i><p>{{ session('success') }}</p>
                </div>
            @endif
            <x-store.cart-changes :changes="$cartChanges" class="mt-5" />

            <form id="checkoutForm" action="{{ route('checkout.placeOrder') }}" method="POST" class="mt-6 space-y-9"
                  @submit="if (submitting) { $event.preventDefault(); return; } submitting = true"
                  x-init="$nextTick(() => $el.querySelector('[aria-invalid=true]')?.focus())">
                @csrf
                <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">

                {{-- Contact --}}
                <section aria-labelledby="contact-title">
                    <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
                        <h2 id="contact-title" class="{{ $section }}">{{ __('Contact') }}</h2>
                        @auth
                            <p class="text-xs text-slate-500">
                                {{ __('Logged in as :name.', ['name' => auth()->user()->name]) }}
                                <button type="submit" form="checkout-logout" class="rounded font-semibold text-brand-700 hover:underline sf-focus">{{ __('Log out') }}</button>
                            </p>
                        @endauth
                    </div>
                    <div class="space-y-3">
                        <x-checkout.field name="email" type="email" :label="__('Email')" :value="$prefill['email']" required autocomplete="email" maxlength="150" />
                        <x-checkout.field name="phone" type="tel" :label="__('Mobile number')" :value="$prefill['phone']" required autocomplete="tel" inputmode="tel" maxlength="20"
                                          :hint="__('We may call this number to confirm your order.')" />
                    </div>
                    @unless($subscribed)
                        <label class="mt-3 flex cursor-pointer items-start gap-2.5 text-sm text-slate-700">
                            <input type="checkbox" name="newsletter" value="1" @checked(old('newsletter')) class="{{ $checkbox }}">
                            {{ __('Email me with news and offers') }}
                        </label>
                    @endunless
                </section>

                {{-- Delivery --}}
                <section aria-labelledby="delivery-title">
                    <h2 id="delivery-title" class="{{ $section }} mb-3">{{ __('Delivery') }}</h2>
                    <div class="space-y-3">
                        <div class="relative flex h-[52px] flex-col justify-center rounded-[var(--sf-radius-sm)] border border-slate-200 bg-slate-50 px-3">
                            <span class="text-xs text-slate-500">{{ __('Country/region') }}</span>
                            <span class="text-sm text-slate-900">{{ __('Bangladesh') }}</span>
                            <i class="bi bi-lock absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" aria-hidden="true"></i>
                        </div>
                        <x-checkout.field name="name" :label="__('Full name')" :value="$prefill['name']" required autocomplete="name" maxlength="100" />
                        <x-checkout.field name="shipping_address" :label="__('Address (house, road, area)')" :value="$prefill['address']" required autocomplete="address-line1" maxlength="180" />
                        <x-checkout.field name="address_line2" :label="__('Apartment, floor, landmark')" optional :value="$prefill['address_line2']" autocomplete="address-line2" maxlength="70" />
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div>
                                <div class="relative">
                                    <select id="checkout-city" name="city" x-model="city" required autocomplete="address-level2"
                                            @if($errors->has('city')) aria-invalid="true" @endif
                                            @class(['block h-[52px] w-full cursor-pointer appearance-none rounded-[var(--sf-radius-sm)] border bg-white pb-1.5 pl-3 pr-9 pt-5 text-sm text-slate-900 focus:outline-none focus:ring-2',
                                                    'border-rose-500 focus:ring-rose-500/20' => $errors->has('city'),
                                                    'border-slate-300 focus:border-brand-600 focus:ring-primary/25' => !$errors->has('city')])>
                                        @foreach($zones as $name => $zone)
                                            <option value="{{ $name }}" @selected($selectedCity === $name)>{{ $zone['label'] }}</option>
                                        @endforeach
                                    </select>
                                    <label for="checkout-city" class="pointer-events-none absolute left-3 top-2 text-xs text-slate-500">{{ __('City / zone') }}</label>
                                    <i class="bi bi-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-500" aria-hidden="true"></i>
                                </div>
                                @error('city')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <x-checkout.field name="region" :label="__('Area / thana')" optional :value="$prefill['region']" autocomplete="address-level3" maxlength="100" />
                            <x-checkout.field name="postal_code" :label="__('Postcode')" optional :value="$prefill['postal_code']" autocomplete="postal-code" inputmode="numeric" maxlength="20" />
                        </div>
                        <x-checkout.field name="alt_phone" type="tel" :label="__('Alternative phone')" optional :value="$prefill['alt_phone']" autocomplete="tel" inputmode="tel" maxlength="20"
                                          :hint="__('Used only if we cannot reach your main number.')" />
                    </div>
                    <label class="mt-3 flex cursor-pointer items-start gap-2.5 text-sm text-slate-700">
                        <input type="checkbox" name="save_info" value="1" @checked(old('save_info', !$hasSavedAddress)) class="{{ $checkbox }}">
                        {{ __('Save this information for next time') }}
                    </label>
                </section>

                {{-- Delivery method --}}
                <section aria-labelledby="method-title">
                    <h2 id="method-title" class="{{ $section }} mb-3">{{ __('Delivery method') }}</h2>
                    <div class="flex items-center gap-3 rounded-[var(--sf-radius-sm)] border border-brand-600 bg-brand-50/60 px-4 py-3.5">
                        <span class="flex size-[18px] shrink-0 items-center justify-center rounded-full border-[5px] border-brand-600 bg-white" aria-hidden="true"></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-slate-900">{{ __('Standard home delivery') }}</p>
                            <p class="text-xs text-slate-500" x-text="zones[city].etaText">{{ $zones[$selectedCity]['etaText'] }}</p>
                        </div>
                        <p class="text-sm font-semibold tabular-nums text-slate-900" x-text="zones[city].chargeText">{{ $zones[$selectedCity]['chargeText'] }}</p>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">{{ __('The delivery charge updates when you change the city / zone.') }}</p>
                </section>

                {{-- Payment --}}
                <section aria-labelledby="payment-title">
                    <h2 id="payment-title" class="{{ $section }}">{{ __('Payment') }}</h2>
                    <p class="mb-3 mt-1 text-sm text-slate-500">{{ __('All transactions are secure and encrypted.') }}</p>
                    @error('payment_method')<p class="mb-2 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror

                    <div class="overflow-hidden rounded-[var(--sf-radius-sm)] border border-slate-300" role="radiogroup" aria-labelledby="payment-title">
                        @if(in_array('cod', $paymentMethods, true))
                            <div :class="method === 'cod' && 'bg-brand-50/50'">
                                <label class="flex cursor-pointer items-center gap-3 px-4 py-3.5" :class="method === 'cod' && 'ring-1 ring-inset ring-brand-600'">
                                    <input type="radio" name="payment_method" value="cod" x-model="method" @checked($selectedMethod === 'cod') class="size-[18px] shrink-0 accent-brand-600">
                                    <span class="flex-1 text-sm font-medium text-slate-900">{{ __('Cash on delivery (COD)') }}</span>
                                    <i class="bi bi-cash-coin text-xl text-slate-500" aria-hidden="true"></i>
                                </label>
                                <div x-show="method === 'cod'" @if($selectedMethod !== 'cod') style="display: none" @endif class="border-t border-slate-200 bg-slate-50 px-4 py-4 text-center text-sm text-slate-600">
                                    {{ __('Pay in cash when the courier delivers your order. Please keep the exact amount ready.') }}
                                </div>
                            </div>
                        @endif
                        @if(in_array('online', $paymentMethods, true))
                            <div class="border-slate-300 [&:not(:first-child)]:border-t" :class="method === 'online' && 'bg-brand-50/50'">
                                <label class="flex cursor-pointer items-center gap-3 px-4 py-3.5" :class="method === 'online' && 'ring-1 ring-inset ring-brand-600'">
                                    <input type="radio" name="payment_method" value="online" x-model="method" @checked($selectedMethod === 'online') class="size-[18px] shrink-0 accent-brand-600">
                                    <span class="flex-1 text-sm font-medium text-slate-900">{{ __('Pay online') }}</span>
                                    <span class="flex items-center gap-1" aria-label="bKash, Nagad, Visa, Mastercard">
                                        <span class="flex h-6 items-center rounded bg-[#e2136e] px-1.5 text-[10px] font-bold text-white">bKash</span>
                                        <span class="flex h-6 items-center rounded bg-[#ec1c24] px-1.5 text-[10px] font-bold text-white">Nagad</span>
                                        <span class="hidden h-6 items-center rounded border border-slate-200 bg-white px-1.5 text-[10px] font-extrabold italic text-[#1a1f71] sm:flex">VISA</span>
                                        <span class="hidden h-6 items-center rounded border border-slate-200 bg-white px-1 sm:flex">
                                            <svg width="22" height="14" viewBox="0 0 28 18" aria-hidden="true"><circle cx="10" cy="9" r="8" fill="#eb001b"/><circle cx="18" cy="9" r="8" fill="#f79e1b" fill-opacity=".9"/></svg>
                                        </span>
                                    </span>
                                </label>
                                <div x-show="method === 'online'" @if($selectedMethod !== 'online') style="display: none" @endif class="border-t border-slate-200 bg-slate-50 px-6 py-5 text-center">
                                    <i class="bi bi-box-arrow-up-right text-3xl text-slate-400" aria-hidden="true"></i>
                                    <p class="mx-auto mt-2 max-w-sm text-sm text-slate-600">{{ __('After clicking “Pay now”, you will be redirected to SSLCommerz to pay securely with bKash, Nagad, card or net banking.') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </section>

                {{-- Order note --}}
                <details class="group rounded-[var(--sf-radius-sm)] border border-slate-200" @if(old('order_notes') || $errors->has('order_notes')) open @endif>
                    <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 px-4 text-sm font-medium text-slate-800 sf-focus [&::-webkit-details-marker]:hidden">
                        <span class="flex items-center gap-2"><i class="bi bi-chat-left-text text-slate-500" aria-hidden="true"></i> {{ __('Add a note to your order') }} <span class="font-normal text-slate-400">({{ __('optional') }})</span></span>
                        <i class="bi bi-chevron-down text-xs text-slate-400 transition-transform group-open:rotate-180" aria-hidden="true"></i>
                    </summary>
                    <div class="px-4 pb-4">
                        <label for="order_notes" class="sr-only">{{ __('Order note') }}</label>
                        <textarea id="order_notes" name="order_notes" rows="3" maxlength="1000"
                                  class="block w-full resize-y rounded-[var(--sf-radius-sm)] border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-primary/25"
                                  placeholder="{{ __('Delivery instructions, preferred time, gift message…') }}">{{ old('order_notes') }}</textarea>
                        @error('order_notes')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </details>

                {{-- Submit --}}
                <div>
                    <div class="flex flex-col-reverse items-center gap-4 sm:flex-row sm:justify-between">
                        <a href="{{ route('cart.index') }}" class="flex min-h-11 items-center gap-1.5 rounded text-sm font-medium text-brand-700 hover:underline sf-focus">
                            <i class="bi bi-chevron-left text-xs" aria-hidden="true"></i> {{ __('Return to cart') }}
                        </a>
                        <button type="submit" :disabled="submitting"
                                class="flex h-14 w-full items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] bg-brand-600 px-8 text-base font-semibold text-white shadow-[var(--sf-shadow)] transition-colors hover:bg-brand-700 disabled:cursor-wait disabled:opacity-70 sm:w-auto sf-focus">
                            <i class="bi" :class="submitting ? 'bi-arrow-repeat animate-spin' : 'bi-lock-fill'" aria-hidden="true"></i>
                            <span x-text="submitting ? @js(__('Processing…')) : (method === 'online' ? @js(__('Pay now')) : @js(__('Complete order')))">{{ $selectedMethod === 'online' ? __('Pay now') : __('Complete order') }}</span>
                        </button>
                    </div>
                    <p class="mt-4 text-center text-xs leading-relaxed text-slate-500 sm:text-left">
                        {{ __('By placing your order, you agree to our') }}
                        @if($legal->has('terms'))<a href="{{ route('pages.show', 'terms') }}" class="text-brand-700 underline-offset-2 hover:underline">{{ __('Terms of service') }}</a>@else{{ __('Terms of service') }}@endif
                        {{ __('and') }}
                        @if($legal->has('privacy'))<a href="{{ route('pages.show', 'privacy') }}" class="text-brand-700 underline-offset-2 hover:underline">{{ __('Privacy policy') }}</a>@else{{ __('Privacy policy') }}@endif.
                    </p>
                </div>
            </form>

            @if($policyPages->isNotEmpty())
                <nav aria-label="{{ __('Store policies') }}" class="mt-10 border-t border-[var(--sf-border)] pt-4">
                    <ul class="flex flex-wrap gap-x-5 gap-y-2 text-xs">
                        @foreach($policyPages as $page)
                            <li><a href="{{ route('pages.show', $page->slug) }}" class="rounded text-brand-700 hover:underline sf-focus">{{ $page->title }}</a></li>
                        @endforeach
                    </ul>
                </nav>
            @endif
        </div>
    </div>
</div>
@endsection
