@extends('frontend.layout')

@use('App\Support\Storefront')

@section('title', __('Order confirmed'))

@section('content')
@php
    $fmt = fn ($amount) => money($amount, null, fmod((float) $amount, 1.0) != 0.0 ? 2 : 0);
    $paid = (bool) $order->paymentStatusEnum()?->isCollected();
    $isCod = $order->payment_method === 'cod';
    $firstName = \Illuminate\Support\Str::of($order->customerDisplayName())->before(' ');
    $phone = $order->customerDisplayPhone();
    $email = $order->customerDisplayEmail();
    $subtotal = $order->itemsSubtotal();
    $discount = (float) $order->discount_amount;
    $itemCount = (int) $order->orderItems->sum('quantity');
    $owner = auth()->id() === $order->user_id;
    $label = 'text-xs font-medium uppercase tracking-wider text-slate-500';
    $card = 'rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white p-5 md:p-6';
@endphp

<div class="mx-auto max-w-6xl px-4 pb-12 pt-5 md:pb-16 md:pt-8">
    <x-checkout.steps current="done" class="mx-auto max-w-xl" />

    <div class="mt-8 lg:grid lg:grid-cols-[minmax(0,1fr)_400px] lg:items-start lg:gap-8">
        <div class="min-w-0 space-y-5">
            {{-- Thank-you header --}}
            <header class="flex items-center gap-4">
                <span class="flex size-14 shrink-0 items-center justify-center rounded-full border-2 border-emerald-500 text-2xl text-emerald-600 md:size-16 md:text-3xl" aria-hidden="true">
                    <i class="bi bi-check-lg"></i>
                </span>
                <div class="min-w-0" x-data="{ copied: false }">
                    <p class="flex items-center gap-1.5 text-sm text-slate-500">
                        {{ __('Order') }} <span class="font-mono font-semibold text-slate-700">#{{ $order->order_id }}</span>
                        <button type="button" @click="navigator.clipboard?.writeText(@js($order->order_id)); copied = true; setTimeout(() => copied = false, 2000)"
                                class="flex size-8 items-center justify-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700 sf-focus">
                            <span class="sr-only">{{ __('Copy order number') }}</span>
                            <i class="bi" :class="copied ? 'bi-check2 text-emerald-600' : 'bi-copy'" aria-hidden="true"></i>
                        </button>
                        <span x-show="copied" x-cloak x-transition.opacity class="text-xs font-medium text-emerald-600" role="status">{{ __('Copied') }}</span>
                    </p>
                    <h1 class="font-display text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">{{ __('Thank you, :name!', ['name' => $firstName]) }}</h1>
                </div>
            </header>

            {{-- Confirmation message --}}
            <section class="{{ $card }}" aria-labelledby="confirmed-title">
                <h2 id="confirmed-title" class="text-lg font-semibold text-slate-900">
                    {{ $paid ? __('Your payment was received') : __('Your order is confirmed') }}
                </h2>
                <p class="mt-1.5 text-sm leading-relaxed text-slate-600">
                    @if($isCod)
                        {!! __('Our team may call :phone to confirm the order. Please keep :amount ready to pay the courier in cash.', ['phone' => '<strong class="text-slate-900">' . e($phone) . '</strong>', 'amount' => '<strong class="text-slate-900">' . e($fmt($order->total_price)) . '</strong>']) !!}
                    @elseif($paid)
                        {{ __('We have received your online payment. Your order is now being prepared.') }}
                    @else
                        {{ __('We are waiting for the payment gateway to confirm your payment. This page will show it once confirmed.') }}
                    @endif
                </p>
                @if($email)
                    <p class="mt-3 flex items-center gap-2 rounded-[var(--sf-radius-sm)] bg-slate-50 px-3 py-2.5 text-sm text-slate-600">
                        <i class="bi bi-envelope-check text-brand-600" aria-hidden="true"></i>
                        <span>{!! __('A confirmation has been sent to :email.', ['email' => '<strong class="text-slate-900">' . e($email) . '</strong>']) !!}</span>
                    </p>
                @endif
            </section>

            {{-- Delivery progress --}}
            <section class="{{ $card }}" aria-labelledby="progress-title">
                <div class="mb-5 flex flex-wrap items-baseline justify-between gap-2">
                    <h2 id="progress-title" class="text-lg font-semibold text-slate-900">{{ __('Order status') }}</h2>
                    @if($deliveryEta)
                        <p class="text-sm text-slate-500"><i class="bi bi-truck text-brand-600" aria-hidden="true"></i> {{ __('Estimated delivery: :eta', ['eta' => $deliveryEta]) }}</p>
                    @endif
                </div>
                <x-store.order-progress :order="$order" />
            </section>

            {{-- Customer information --}}
            <section class="{{ $card }}" aria-labelledby="info-title">
                <h2 id="info-title" class="text-lg font-semibold text-slate-900">{{ __('Customer information') }}</h2>
                <dl class="mt-4 grid grid-cols-1 gap-x-8 gap-y-5 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="{{ $label }}">{{ __('Contact') }}</dt>
                        <dd class="mt-1.5 space-y-0.5 text-slate-800">
                            <p class="font-medium">{{ $order->customerDisplayName() }}</p>
                            @if($email)<p class="break-all text-slate-600">{{ $email }}</p>@endif
                            @if($phone)<p class="tabular-nums text-slate-600">{{ $phone }}@if($order->alt_phone) · {{ $order->alt_phone }}@endif</p>@endif
                        </dd>
                    </div>
                    <div>
                        <dt class="{{ $label }}">{{ __('Payment method') }}</dt>
                        <dd class="mt-1.5 flex flex-wrap items-center gap-2 text-slate-800">
                            <i class="bi {{ $isCod ? 'bi-cash-coin' : 'bi-credit-card' }} text-slate-500" aria-hidden="true"></i>
                            {{ $isCod ? __('Cash on delivery (COD)') : __('Online payment (SSLCommerz)') }}
                            <span @class(['rounded-full px-2 py-0.5 text-xs font-semibold',
                                          'bg-emerald-50 text-emerald-700' => $paid,
                                          'bg-amber-50 text-amber-700' => !$paid])>
                                {{ $paid ? __('Paid') : ($isCod ? __('Pay on delivery') : __('Awaiting payment')) }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="{{ $label }}">{{ __('Shipping address') }}</dt>
                        <dd class="mt-1.5 text-slate-800">
                            <p class="whitespace-pre-line">{{ $order->shipping_address }}</p>
                            <p class="text-slate-600">{{ collect([$order->region, $order->city === \App\Services\CheckoutOptions::OTHER_ZONE ? null : $order->city])->filter()->implode(', ') }}{{ $order->postal_code ? ' - ' . $order->postal_code : '' }}</p>
                            <p class="text-slate-600">{{ __('Bangladesh') }}</p>
                        </dd>
                    </div>
                    <div>
                        <dt class="{{ $label }}">{{ __('Delivery method') }}</dt>
                        <dd class="mt-1.5 text-slate-800">
                            <p>{{ __('Standard home delivery') }}</p>
                            <p class="text-slate-600">{{ (float) $order->delivery_charge > 0 ? $fmt($order->delivery_charge) : __('Free') }}@if($deliveryEta) · {{ $deliveryEta }}@endif</p>
                        </dd>
                    </div>
                    @if($order->order_notes)
                        <div class="sm:col-span-2">
                            <dt class="{{ $label }}">{{ __('Order note') }}</dt>
                            <dd class="mt-1.5 rounded-[var(--sf-radius-sm)] bg-slate-50 px-3 py-2.5 text-slate-700">{{ $order->order_notes }}</dd>
                        </div>
                    @endif
                </dl>
            </section>

            {{-- Actions --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                <a href="{{ route('products.index') }}" class="flex h-12 items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] bg-brand-600 px-6 text-sm font-semibold text-white hover:bg-brand-700 sf-focus">
                    {{ __('Continue shopping') }} <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
                @if($owner)
                    <a href="{{ route('user.orders.details', $order) }}" class="flex h-12 items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] border border-slate-300 bg-white px-6 text-sm font-semibold text-slate-800 hover:border-brand-600 hover:text-brand-700 sf-focus">
                        <i class="bi bi-receipt" aria-hidden="true"></i> {{ __('View order') }}
                    </a>
                    <a href="{{ route('orders.invoice.download', $order->id) }}" class="flex h-12 items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] px-4 text-sm font-semibold text-slate-600 hover:bg-white hover:text-slate-900 sf-focus">
                        <i class="bi bi-download" aria-hidden="true"></i> {{ __('Download invoice') }}
                    </a>
                @else
                    <a href="{{ route('tracking.index') }}" class="flex h-12 items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] border border-slate-300 bg-white px-6 text-sm font-semibold text-slate-800 hover:border-brand-600 hover:text-brand-700 sf-focus">
                        <i class="bi bi-truck" aria-hidden="true"></i> {{ __('Track order') }}
                    </a>
                @endif
            </div>
            <p class="text-sm text-slate-500">
                {{ __('Need help with this order?') }}
                <a href="{{ route('contact') }}" class="font-semibold text-brand-700 hover:underline">{{ __('Contact us') }}</a>
            </p>
        </div>

        {{-- Order summary --}}
        <aside class="mt-8 lg:sticky lg:top-24 lg:mt-0" aria-labelledby="success-summary-title">
            <div class="rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white p-5 md:p-6">
                <h2 id="success-summary-title" class="text-lg font-semibold text-slate-900">{{ __('Order summary') }}</h2>
                <ul class="mt-4 space-y-4">
                    @foreach($order->orderItems as $item)
                        @php($variantName = $item->variant?->attributeValues->map(fn ($v) => $v->attribute?->name . ': ' . $v->value)->implode(' · '))
                        <li class="flex items-center gap-4">
                            <div class="relative shrink-0">
                                <x-store.image :src="Storefront::imageUrl($item->product?->images->first()?->image)" :alt="(string) ($item->product?->name ?? __('Product'))" :width="64" :height="64"
                                               class="size-16 rounded-[var(--sf-radius)] border border-[var(--sf-border)] bg-slate-50 [&_i]:!text-2xl" img-class="p-1 mix-blend-multiply" />
                                <span class="absolute -right-2 -top-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-slate-600 px-1.5 text-[11px] font-semibold text-white">{{ localized_number($item->quantity) }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="line-clamp-2 text-sm font-medium leading-snug text-slate-900">{{ $item->product?->name ?? __('Product no longer available') }}</p>
                                @if($variantName)<p class="mt-0.5 truncate text-xs text-slate-500">{{ $variantName }}</p>@endif
                            </div>
                            <p class="shrink-0 text-sm font-medium tabular-nums text-slate-900">{{ $fmt($item->subtotal) }}</p>
                        </li>
                    @endforeach
                </ul>

                <dl class="mt-5 space-y-2.5 border-t border-[var(--sf-border)] pt-5 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-600">{{ __('Subtotal') }} · {{ trans_choice(':count item|:count items', $itemCount, ['count' => localized_number($itemCount)]) }}</dt>
                        <dd class="font-medium tabular-nums text-slate-900">{{ $fmt($subtotal) }}</dd>
                    </div>
                    @if($discount > 0)
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-600">{{ __('Discount') }} @if($order->coupon_code)<span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-semibold uppercase text-slate-700">{{ $order->coupon_code }}</span>@endif</dt>
                            <dd class="font-medium tabular-nums text-emerald-700">−{{ $fmt($discount) }}</dd>
                        </div>
                    @endif
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-600">{{ __('Shipping') }}</dt>
                        <dd class="font-medium tabular-nums text-slate-900">{{ (float) $order->delivery_charge > 0 ? $fmt($order->delivery_charge) : __('Free') }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4 border-t border-[var(--sf-border)] pt-4">
                        <dt class="text-base font-semibold text-slate-900">{{ __('Total') }}</dt>
                        <dd class="flex items-baseline gap-2">
                            <span class="text-xs text-slate-500">BDT</span>
                            <span class="font-display text-2xl font-bold tabular-nums text-slate-900">{{ $fmt($order->total_price) }}</span>
                        </dd>
                    </div>
                </dl>
            </div>
        </aside>
    </div>
</div>
@endsection
