@extends('user.layout')

@use('App\Support\Storefront')

@section('title', __('Order #:id', ['id' => $order->order_id]))

@section('account')
@php
    $fmt = fn ($amount) => money($amount, null, fmod((float) $amount, 1.0) != 0.0 ? 2 : 0);
    $status = $order->statusEnum()?->customerFacing();
    $paid = (bool) $order->paymentStatusEnum()?->isCollected();
    $isCod = $order->payment_method === 'cod';
    $canCancel = $status?->holdsStock() && !$paid;
    $courier = $order->orderCourier?->isBooked() ? $order->orderCourier : null;
    $card = 'rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white p-5 md:p-6';
    $label = 'text-xs font-medium uppercase tracking-wider text-slate-500';
    $units = (int) $order->orderItems->sum('quantity');
    $needsAdvance = $order->advance_amount > 0 && $isCod && !$paid && $status?->holdsStock();
    $timeline = $order->statusHistories->reject(fn ($h) => $h->isRiskReview())->map(fn ($h) => [
        'at' => $h->created_at,
        'title' => __($h->toStatus()?->label() ?? ucfirst($h->to_status)),
        'desc' => $h->note ?: __($h->toStatus()?->customerMessage() ?? ''),
        'icon' => $h->toStatus()?->icon() ?? 'bi-dot',
    ])->concat($order->courierLogs->map(fn ($log) => [
        'at' => $log->created_at,
        'title' => ($courier?->courierLabel() ?? __('Courier')) . ': ' . ucfirst(str_replace('_', ' ', $log->status_to)),
        'desc' => $log->remarks,
        'icon' => 'bi-truck',
    ]))->sortByDesc('at')->values();
@endphp

<div class="space-y-5">
    <a href="{{ route('user.orders.index') }}" class="inline-flex min-h-10 items-center gap-1.5 rounded text-sm font-medium text-slate-600 hover:text-brand-700 sf-focus">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> {{ __('Back to orders') }}
    </a>

    <header class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="font-display text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">{{ __('Order') }} <span class="font-mono">#{{ $order->order_id }}</span></h1>
                <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-xs font-semibold {{ \App\Enums\OrderStatus::badge($order->status) }}">
                    <i class="bi {{ $status?->icon() ?? 'bi-circle' }}" aria-hidden="true"></i> {{ __($status?->label() ?? ucfirst($order->status)) }}
                </span>
            </div>
            <p class="mt-1 text-sm text-slate-500">{{ __('Placed on :date', ['date' => $order->created_at->format('d M Y, h:i A')]) }} · {{ trans_choice(':count item|:count items', $units, ['count' => localized_number($units)]) }}</p>
        </div>
        <div class="flex w-full flex-wrap gap-2 sm:w-auto">
            <a href="{{ route('orders.invoice.download', $order->id) }}" class="flex h-10 flex-1 items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 hover:border-brand-600 hover:text-brand-700 sm:flex-none sf-focus">
                <i class="bi bi-download" aria-hidden="true"></i> {{ __('Invoice') }}
            </a>
            @if($canCancel)
                <form method="POST" action="{{ route('user.orders.cancel', $order) }}" class="flex-1 sm:flex-none"
                      x-data @submit="if (!confirm(@js(__('Cancel order #:id? This cannot be undone.', ['id' => $order->order_id])))) $event.preventDefault()">
                    @csrf @method('PATCH')
                    <button type="submit" class="flex h-10 w-full items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] border border-rose-200 bg-white px-4 text-sm font-semibold text-rose-700 hover:bg-rose-50 sf-focus">
                        <i class="bi bi-x-circle" aria-hidden="true"></i> {{ __('Cancel order') }}
                    </button>
                </form>
            @endif
        </div>
    </header>

    @if($needsAdvance)
        <div class="flex gap-3 rounded-[var(--sf-radius)] border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="status">
            <i class="bi bi-wallet2 mt-0.5 text-amber-600" aria-hidden="true"></i>
            <div>
                <p class="font-semibold">{{ __('Advance payment needed') }}</p>
                <p class="mt-0.5">{{ __('To confirm this cash-on-delivery order, please pay :amount in advance. Our team will call you with the payment details.', ['amount' => $fmt($order->advance_amount)]) }}</p>
            </div>
        </div>
    @endif

    {{-- Progress --}}
    <section class="{{ $card }}" aria-labelledby="progress-title">
        <div class="mb-5 flex flex-wrap items-baseline justify-between gap-2">
            <h2 id="progress-title" class="text-lg font-semibold text-slate-900">{{ __('Delivery progress') }}</h2>
            @if($deliveryEta && $status?->holdsStock())
                <p class="text-sm text-slate-500"><i class="bi bi-truck text-brand-600" aria-hidden="true"></i> {{ __('Usually delivered in :eta', ['eta' => $deliveryEta]) }}</p>
            @endif
        </div>
        <x-store.order-progress :order="$order" />
        @if($courier)
            <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-[var(--sf-radius)] bg-slate-50 px-4 py-3">
                <div class="text-sm">
                    <p class="text-slate-500">{{ __(':courier tracking code', ['courier' => $courier->courierLabel()]) }}</p>
                    <p class="font-mono font-semibold text-slate-900">{{ $courier->tracking_code ?: $courier->consignment_id }}</p>
                </div>
                @if($courier->trackingUrl())
                    <a href="{{ $courier->trackingUrl() }}" target="_blank" rel="noopener" class="flex h-10 items-center gap-2 rounded-[var(--sf-radius-sm)] bg-brand-600 px-4 text-sm font-semibold text-white hover:bg-brand-700 sf-focus">
                        {{ __('Track parcel') }} <i class="bi bi-box-arrow-up-right text-xs" aria-hidden="true"></i>
                    </a>
                @endif
            </div>
        @endif
    </section>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
        <div class="min-w-0 space-y-5">
            {{-- Items --}}
            <section class="overflow-hidden rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white" aria-labelledby="items-title">
                <h2 id="items-title" class="border-b border-[var(--sf-border)] px-5 py-4 text-lg font-semibold text-slate-900 md:px-6">{{ __('Items') }}</h2>
                <ul class="divide-y divide-[var(--sf-border)]">
                    @foreach($order->orderItems as $item)
                        @php($variantName = $item->variant?->attributeValues->map(fn ($v) => $v->attribute?->name . ': ' . $v->value)->implode(' · '))
                        <li class="flex items-center gap-4 px-5 py-4 md:px-6">
                            <x-store.image :src="Storefront::imageUrl($item->product?->images->first()?->image)" :alt="(string) ($item->product?->name ?? '')" :width="72" :height="72"
                                           class="size-[72px] shrink-0 rounded-[var(--sf-radius)] border border-[var(--sf-border)] bg-slate-50 [&_i]:!text-2xl" img-class="p-1.5 mix-blend-multiply" />
                            <div class="min-w-0 flex-1">
                                @if($item->product && !$item->product->trashed())
                                    <a href="{{ route('products.show', $item->product_id) }}" class="line-clamp-2 rounded text-sm font-medium text-slate-900 hover:text-brand-700 sf-focus">{{ $item->product->name }}</a>
                                @else
                                    <p class="text-sm font-medium text-slate-500">{{ $item->product?->name ?? __('Product no longer available') }}</p>
                                @endif
                                @if($variantName)<p class="mt-0.5 text-xs text-slate-500">{{ $variantName }}</p>@endif
                                <p class="mt-0.5 text-xs tabular-nums text-slate-500">{{ localized_number($item->quantity) }} × {{ $fmt($item->price) }}</p>
                            </div>
                            <p class="shrink-0 text-sm font-semibold tabular-nums text-slate-900">{{ $fmt($item->subtotal ?? $item->price * $item->quantity) }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>

            {{-- Timeline --}}
            <section class="{{ $card }}" aria-labelledby="timeline-title">
                <h2 id="timeline-title" class="text-lg font-semibold text-slate-900">{{ __('Updates') }}</h2>
                @if($timeline->isNotEmpty())
                    <ol class="relative ml-4 mt-5 space-y-6 border-l border-[var(--sf-border)]">
                        @foreach($timeline as $event)
                            <li class="ml-6">
                                <span @class(['absolute -left-4 flex size-8 items-center justify-center rounded-full ring-4 ring-white',
                                              'bg-brand-600 text-white' => $loop->first,
                                              'bg-brand-50 text-brand-600' => !$loop->first]) aria-hidden="true">
                                    <i class="bi {{ $event['icon'] }} text-sm"></i>
                                </span>
                                <div class="flex flex-wrap items-baseline justify-between gap-x-4">
                                    <p class="text-sm font-semibold text-slate-900">{{ $event['title'] }}</p>
                                    <time datetime="{{ $event['at']->toIso8601String() }}" class="text-xs text-slate-500">{{ $event['at']->format('d M Y, h:i A') }}</time>
                                </div>
                                @if($event['desc'])<p class="mt-0.5 text-sm text-slate-600">{{ $event['desc'] }}</p>@endif
                            </li>
                        @endforeach
                    </ol>
                @else
                    <p class="mt-3 text-sm text-slate-500">{{ __('No updates yet.') }}</p>
                @endif
            </section>
        </div>

        <div class="space-y-5">
            {{-- Summary --}}
            <section class="{{ $card }}" aria-labelledby="summary-title">
                <h2 id="summary-title" class="text-lg font-semibold text-slate-900">{{ __('Payment summary') }}</h2>
                <dl class="mt-4 space-y-2.5 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-slate-600">{{ __('Subtotal') }}</dt><dd class="tabular-nums text-slate-900">{{ $fmt($order->itemsSubtotal()) }}</dd></div>
                    @if((float) $order->discount_amount > 0)
                        <div class="flex justify-between gap-4"><dt class="text-slate-600">{{ __('Discount') }} @if($order->coupon_code)<span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-semibold uppercase text-slate-700">{{ $order->coupon_code }}</span>@endif</dt><dd class="tabular-nums text-emerald-700">−{{ $fmt($order->discount_amount) }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-4"><dt class="text-slate-600">{{ __('Shipping') }}</dt><dd class="tabular-nums text-slate-900">{{ (float) $order->delivery_charge > 0 ? $fmt($order->delivery_charge) : __('Free') }}</dd></div>
                    <div class="flex items-baseline justify-between gap-4 border-t border-[var(--sf-border)] pt-3">
                        <dt class="font-semibold text-slate-900">{{ __('Total') }}</dt>
                        <dd class="font-display text-xl font-bold tabular-nums text-slate-900">{{ $fmt($order->total_price) }}</dd>
                    </div>
                </dl>
                <div class="mt-4 flex items-center justify-between gap-3 rounded-[var(--sf-radius-sm)] bg-slate-50 px-3 py-2.5 text-sm">
                    <span class="flex items-center gap-2 text-slate-700"><i class="bi {{ $isCod ? 'bi-cash-coin' : 'bi-credit-card' }} text-slate-500" aria-hidden="true"></i>{{ $isCod ? __('Cash on delivery') : __('Online payment') }}</span>
                    <span class="rounded-full border px-2 py-0.5 text-xs font-semibold {{ \App\Enums\PaymentStatus::badge($order->payment_status) }}">{{ __($order->paymentStatusEnum()?->label() ?? ucfirst((string) $order->payment_status)) }}</span>
                </div>
            </section>

            {{-- Delivery --}}
            <section class="{{ $card }}" aria-labelledby="delivery-title">
                <h2 id="delivery-title" class="text-lg font-semibold text-slate-900">{{ __('Delivery') }}</h2>
                <dl class="mt-4 space-y-4 text-sm">
                    <div>
                        <dt class="{{ $label }}">{{ __('Recipient') }}</dt>
                        <dd class="mt-1 text-slate-800">{{ $order->customerDisplayName() }}<br><span class="tabular-nums text-slate-600">{{ $order->customerDisplayPhone() }}@if($order->alt_phone) · {{ $order->alt_phone }}@endif</span></dd>
                    </div>
                    <div>
                        <dt class="{{ $label }}">{{ __('Address') }}</dt>
                        <dd class="mt-1 text-slate-800">
                            <span class="whitespace-pre-line">{{ $order->shipping_address }}</span><br>
                            <span class="text-slate-600">{{ collect([$order->region, $order->city === \App\Services\CheckoutOptions::OTHER_ZONE ? null : $order->city])->filter()->implode(', ') }}{{ $order->postal_code ? ' - ' . $order->postal_code : '' }}</span>
                        </dd>
                    </div>
                    @if($order->order_notes)
                        <div>
                            <dt class="{{ $label }}">{{ __('Your note') }}</dt>
                            <dd class="mt-1 rounded-[var(--sf-radius-sm)] bg-slate-50 px-3 py-2 text-slate-700">{{ $order->order_notes }}</dd>
                        </div>
                    @endif
                </dl>
            </section>

            <a href="{{ route('user.messages.index') }}" class="flex items-center gap-3 rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white p-4 hover:border-brand-300 sf-focus">
                <i class="bi bi-chat-dots text-xl text-brand-600" aria-hidden="true"></i>
                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-slate-900">{{ __('Problem with this order?') }}</span>
                    <span class="block text-xs text-slate-500">{{ __('Message support and mention #:id', ['id' => $order->order_id]) }}</span>
                </span>
                <i class="bi bi-chevron-right text-xs text-slate-400" aria-hidden="true"></i>
            </a>
        </div>
    </div>
</div>
@endsection
