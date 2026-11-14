@extends('user.layout')

@section('title', __('Track order'))

@section('account')
@php
    $card = 'rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white p-5 md:p-6';
@endphp

<div class="space-y-5">
    <header>
        <h1 class="font-display text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">{{ __('Track order') }}</h1>
        <p class="mt-1 text-sm text-slate-500">{{ __('Pick one of your orders in progress or search by order number.') }}</p>
    </header>

    <form action="{{ route('user.trackOrder.submit') }}" method="POST" class="{{ $card }}">
        @csrf
        <div class="flex flex-col gap-3 sm:flex-row">
            <x-checkout.field name="order_id" :label="__('Order number, e.g. ORD-20261113-ABC123')" :value="isset($order) ? $order->order_id : request('order_id')" class="flex-1" required autocomplete="off" autocapitalize="characters" />
            <button type="submit" class="flex h-[52px] items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] bg-brand-600 px-6 text-sm font-semibold text-white hover:bg-brand-700 sf-focus">
                <i class="bi bi-search" aria-hidden="true"></i> {{ __('Track') }}
            </button>
        </div>
    </form>

    @isset($order)
        <section class="{{ $card }}" aria-labelledby="tracked-title">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 id="tracked-title" class="font-mono text-lg font-semibold text-slate-900">#{{ $order->order_id }}</h2>
                    <p class="text-sm text-slate-500">{{ __('Placed on :date', ['date' => $order->created_at->format('d M Y')]) }}</p>
                </div>
                <a href="{{ route('user.orders.details', $order) }}" class="flex h-10 items-center gap-1.5 rounded-[var(--sf-radius-sm)] border border-slate-300 px-4 text-sm font-semibold text-slate-800 hover:border-brand-600 hover:text-brand-700 sf-focus">
                    {{ __('Full order details') }} <i class="bi bi-chevron-right text-xs" aria-hidden="true"></i>
                </a>
            </div>
            <x-store.order-progress :order="$order" />
            @if($order->orderCourier?->trackingUrl())
                <a href="{{ $order->orderCourier->trackingUrl() }}" target="_blank" rel="noopener" class="mt-5 inline-flex h-10 items-center gap-2 rounded-[var(--sf-radius-sm)] bg-slate-50 px-4 text-sm font-semibold text-brand-700 hover:bg-brand-50 sf-focus">
                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> {{ __('Track with :courier', ['courier' => $order->orderCourier->courierLabel()]) }}
                </a>
            @endif
        </section>
    @endisset

    <section aria-labelledby="active-title">
        <h2 id="active-title" class="mb-3 text-lg font-semibold text-slate-900">{{ __('Orders in progress') }}</h2>
        @if($activeOrders->isNotEmpty())
            <ul class="grid gap-3 sm:grid-cols-2">
                @foreach($activeOrders as $active)
                    @php($status = $active->statusEnum()?->customerFacing())
                    <li>
                        <form action="{{ route('user.trackOrder.submit') }}" method="POST">
                            @csrf
                            <input type="hidden" name="order_id" value="{{ $active->order_id }}">
                            <button type="submit" @class(['flex w-full items-center gap-3 rounded-[var(--sf-radius-lg)] border bg-white p-4 text-left transition-colors hover:border-brand-300 sf-focus',
                                                          'border-brand-600 ring-1 ring-brand-600' => isset($order) && $order->id === $active->id,
                                                          'border-[var(--sf-border)]' => !(isset($order) && $order->id === $active->id)])>
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600" aria-hidden="true"><i class="bi {{ $status?->icon() ?? 'bi-box' }}"></i></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block font-mono text-sm font-semibold text-slate-900">#{{ $active->order_id }}</span>
                                    <span class="block text-xs text-slate-500">{{ __($status?->label() ?? ucfirst($active->status)) }} · {{ $active->created_at->format('d M Y') }}</span>
                                </span>
                                <i class="bi bi-chevron-right text-xs text-slate-400" aria-hidden="true"></i>
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @else
            <div class="{{ $card }} text-center text-sm text-slate-500">
                {{ __('You have no orders in progress right now.') }}
                <a href="{{ route('user.orders.index') }}" class="font-semibold text-brand-700 hover:underline">{{ __('See all orders') }}</a>
            </div>
        @endif
    </section>
</div>
@endsection
