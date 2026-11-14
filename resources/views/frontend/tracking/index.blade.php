@extends('frontend.layout')

@section('title', __('Track your order'))

@section('content')
@php
    $card = 'rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white p-5 md:p-6';
@endphp

<div class="mx-auto max-w-4xl px-4 pb-12 pt-5 md:pb-16 md:pt-8">
    <nav aria-label="{{ __('Breadcrumb') }}" class="text-xs text-slate-500">
        <ol class="flex items-center gap-1.5">
            <li><a href="{{ route('home') }}" class="rounded hover:text-brand-700 sf-focus">{{ __('Home') }}</a></li>
            <li aria-hidden="true"><i class="bi bi-chevron-right text-[10px]"></i></li>
            <li aria-current="page" class="font-medium text-slate-700">{{ __('Track order') }}</li>
        </ol>
    </nav>

    <header class="mt-3 flex items-center gap-3 md:mt-4 md:gap-4">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-[var(--sf-radius)] bg-brand-50 text-xl text-brand-600 md:size-14 md:text-2xl" aria-hidden="true"><i class="bi bi-truck"></i></span>
        <div>
            <h1 class="font-display text-xl font-bold tracking-tight text-slate-900 md:text-3xl">{{ __('Track your order') }}</h1>
            <p class="mt-0.5 text-sm text-slate-500">{{ __('Enter your order number and the phone number used at checkout.') }}</p>
        </div>
    </header>

    {{-- Search --}}
    <form action="{{ route('tracking.track') }}" method="POST" class="{{ $card }} mt-6">
        @csrf
        @if(session('error'))
            <div class="mb-4 flex gap-3 rounded-[var(--sf-radius-sm)] border border-rose-200 bg-rose-50 p-3 text-sm text-rose-800" role="alert">
                <i class="bi bi-exclamation-triangle-fill mt-0.5" aria-hidden="true"></i><p>{{ session('error') }}</p>
            </div>
        @endif
        <div class="grid grid-cols-1 gap-3 md:grid-cols-[1fr_1fr_auto]">
            <x-checkout.field name="order_id" :label="__('Order number, e.g. ORD-20261113-ABC123')" :value="isset($order) ? $order->order_id : request('order_id')" required autocomplete="off" autocapitalize="characters" />
            <x-checkout.field name="phone" type="tel" :label="__('Phone number')" :value="request('phone')" required autocomplete="tel" inputmode="tel" />
            <button type="submit" class="flex h-[52px] items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] bg-brand-600 px-6 text-sm font-semibold text-white hover:bg-brand-700 sf-focus">
                <i class="bi bi-search" aria-hidden="true"></i> {{ __('Track') }}
            </button>
        </div>
    </form>

    @isset($order)
        @php
            $status = $order->statusEnum()?->customerFacing();
            $courier = $order->orderCourier?->isBooked() ? $order->orderCourier : null;
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

        {{-- Order summary --}}
        <section class="{{ $card }} mt-5" aria-labelledby="order-title">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-xs text-slate-500">{{ __('Order') }}</p>
                    <h2 id="order-title" class="font-mono text-lg font-semibold text-slate-900">#{{ $order->order_id }}</h2>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold {{ \App\Enums\OrderStatus::badge($order->status) }}">
                    <i class="bi {{ $status?->icon() ?? 'bi-clock' }}" aria-hidden="true"></i> {{ __($status?->label() ?? ucfirst($order->status)) }}
                </span>
            </div>
            <dl class="mt-5 grid grid-cols-2 gap-4 border-t border-[var(--sf-border)] pt-5 text-sm md:grid-cols-4">
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Placed on') }}</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $order->created_at->format('d M Y') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Payment') }}</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $order->payment_method === 'cod' ? __('Cash on delivery') : __('Online') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Courier') }}</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $courier?->courierLabel() ?? __('Being assigned') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">{{ __('Tracking code') }}</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">
                        @if($courier?->trackingUrl())
                            <a href="{{ $courier->trackingUrl() }}" target="_blank" rel="noopener" class="rounded font-mono text-brand-700 hover:underline sf-focus">{{ $courier->tracking_code ?: $courier->consignment_id }} <i class="bi bi-box-arrow-up-right text-xs" aria-hidden="true"></i></a>
                        @else
                            <span class="font-mono">{{ $courier?->tracking_code ?: __('Pending') }}</span>
                        @endif
                    </dd>
                </div>
            </dl>
            <x-store.order-progress :order="$order" class="mt-6 border-t border-[var(--sf-border)] pt-6" />
        </section>

        {{-- Timeline --}}
        <section class="{{ $card }} mt-5" aria-labelledby="timeline-title">
            <h2 id="timeline-title" class="text-lg font-semibold text-slate-900">{{ __('Updates') }}</h2>
            @if($timeline->isNotEmpty())
                <ol class="relative mt-5 ml-4 space-y-6 border-l border-[var(--sf-border)]">
                    @foreach($timeline as $event)
                        <li class="ml-6">
                            <span @class(['absolute -left-4 flex size-8 items-center justify-center rounded-full ring-4 ring-white',
                                          'bg-brand-600 text-white' => $loop->first,
                                          'bg-brand-50 text-brand-600' => !$loop->first]) aria-hidden="true">
                                <i class="bi {{ $event['icon'] }} text-sm"></i>
                            </span>
                            <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-0.5">
                                <p class="text-sm font-semibold text-slate-900">{{ $event['title'] }}</p>
                                <time datetime="{{ $event['at']->toIso8601String() }}" class="text-xs text-slate-500">{{ $event['at']->format('d M Y, h:i A') }}</time>
                            </div>
                            @if($event['desc'])
                                <p class="mt-0.5 text-sm text-slate-600">{{ $event['desc'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @else
                <p class="mt-3 text-sm text-slate-500">{{ __('No updates yet. We will post them here as your order moves.') }}</p>
            @endif
        </section>
    @else
        <ul class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-3">
            @foreach([
                ['bi-envelope', __('Find your order number'), __('It is in your order confirmation email and on the thank-you page.')],
                ['bi-phone', __('Use the same phone'), __('Enter the number you gave at checkout, or your alternative number.')],
                ['bi-clock-history', __('Courier updates'), __('Courier scans can take up to 24 hours to appear after dispatch.')],
            ] as [$icon, $title, $text])
                <li class="rounded-[var(--sf-radius)] border border-[var(--sf-border)] bg-white p-4">
                    <i class="bi {{ $icon }} text-xl text-brand-600" aria-hidden="true"></i>
                    <p class="mt-2 text-sm font-semibold text-slate-900">{{ $title }}</p>
                    <p class="mt-0.5 text-sm text-slate-500">{{ $text }}</p>
                </li>
            @endforeach
        </ul>
    @endisset

    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
        @auth
            <a href="{{ route('user.orders.index') }}" class="flex items-center gap-3 rounded-[var(--sf-radius)] border border-[var(--sf-border)] bg-white p-4 hover:border-brand-300 sf-focus">
                <i class="bi bi-bag-check text-xl text-brand-600" aria-hidden="true"></i>
                <span class="min-w-0 flex-1"><span class="block text-sm font-semibold text-slate-900">{{ __('My orders') }}</span><span class="block text-xs text-slate-500">{{ __('See every order on your account') }}</span></span>
                <i class="bi bi-chevron-right text-xs text-slate-400" aria-hidden="true"></i>
            </a>
        @endauth
        <a href="{{ route('contact') }}" class="flex items-center gap-3 rounded-[var(--sf-radius)] border border-[var(--sf-border)] bg-white p-4 hover:border-brand-300 sf-focus">
            <i class="bi bi-headset text-xl text-brand-600" aria-hidden="true"></i>
            <span class="min-w-0 flex-1"><span class="block text-sm font-semibold text-slate-900">{{ __('Need help?') }}</span><span class="block text-xs text-slate-500">{{ __('Our support team is here 24/7') }}</span></span>
            <i class="bi bi-chevron-right text-xs text-slate-400" aria-hidden="true"></i>
        </a>
    </div>
</div>
@endsection
