@extends('frontend.layout')

@use('App\Support\Storefront')

@php
    $states = [
        'failed' => [
            'icon' => 'bi-x-lg', 'tone' => 'border-rose-500 text-rose-600',
            'title' => __('Payment failed'),
            'text' => __('Your payment could not be completed, so the order was not placed. If money was deducted, it is returned to your account automatically within 7 working days.'),
        ],
        'cancelled' => [
            'icon' => 'bi-slash-circle', 'tone' => 'border-amber-500 text-amber-600',
            'title' => __('Payment cancelled'),
            'text' => __('You cancelled the payment, so the order was not placed and no money was taken.'),
        ],
        'pending' => [
            'icon' => 'bi-hourglass-split', 'tone' => 'border-sky-500 text-sky-600',
            'title' => __('We are confirming your payment'),
            'text' => __('The payment gateway has not confirmed your payment yet. This usually takes a few minutes. Please do not pay again; we will email you as soon as it is confirmed.'),
        ],
    ];
    $state = $states[$result];
    $fmt = fn ($amount) => money($amount, null, fmod((float) $amount, 1.0) != 0.0 ? 2 : 0);
    $owner = auth()->id() === $order->user_id;
    $canRetry = $result !== 'pending';
@endphp

@section('title', $state['title'])

@section('content')
<div class="mx-auto max-w-2xl px-4 pb-12 pt-5 md:pb-16 md:pt-8">
    <x-checkout.steps current="details" class="mx-auto max-w-xl" />

    <div class="mt-8 rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white p-6 text-center md:p-10">
        <span class="mx-auto flex size-16 items-center justify-center rounded-full border-2 text-3xl {{ $state['tone'] }}" aria-hidden="true"><i class="bi {{ $state['icon'] }}"></i></span>
        <h1 class="mt-5 font-display text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">{{ $state['title'] }}</h1>
        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-slate-600">{{ $state['text'] }}</p>

        <dl class="mx-auto mt-6 grid max-w-md grid-cols-2 divide-x divide-[var(--sf-border)] rounded-[var(--sf-radius)] bg-slate-50 py-3 text-sm">
            <div>
                <dt class="text-xs text-slate-500">{{ __('Order') }}</dt>
                <dd class="mt-0.5 font-mono font-semibold text-slate-900">#{{ $order->order_id }}</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500">{{ __('Amount') }}</dt>
                <dd class="mt-0.5 font-semibold tabular-nums text-slate-900">{{ $fmt($order->total_price) }}</dd>
            </div>
        </dl>

        <ul class="mx-auto mt-5 flex max-w-md flex-wrap justify-center gap-2" aria-label="{{ __('Items in this order') }}">
            @foreach($order->orderItems->take(6) as $item)
                <li class="relative" title="{{ $item->product?->name }}">
                    <x-store.image :src="Storefront::imageUrl($item->product?->images->first()?->image)" :alt="(string) ($item->product?->name ?? '')" :width="56" :height="56"
                                   class="size-14 rounded-[var(--sf-radius-sm)] border border-[var(--sf-border)] bg-white [&_i]:!text-xl" img-class="p-1" />
                    <span class="absolute -right-1.5 -top-1.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-slate-600 px-1 text-[11px] font-semibold text-white">{{ localized_number($item->quantity) }}</span>
                </li>
            @endforeach
        </ul>

        <div class="mt-8 flex flex-col items-stretch justify-center gap-3 sm:flex-row">
            @if($canRetry)
                @if($owner)
                    <form method="POST" action="{{ route('orders.reorder', $order) }}">
                        @csrf
                        <button type="submit" class="flex h-12 w-full items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] bg-brand-600 px-6 text-sm font-semibold text-white hover:bg-brand-700 sf-focus">
                            <i class="bi bi-arrow-repeat" aria-hidden="true"></i> {{ __('Restore cart & try again') }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('user.auth.login') }}" class="flex h-12 items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] bg-brand-600 px-6 text-sm font-semibold text-white hover:bg-brand-700 sf-focus">
                        <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i> {{ __('Log in to try again') }}
                    </a>
                @endif
            @elseif($owner)
                <a href="{{ route('user.orders.details', $order) }}" class="flex h-12 items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] bg-brand-600 px-6 text-sm font-semibold text-white hover:bg-brand-700 sf-focus">
                    <i class="bi bi-receipt" aria-hidden="true"></i> {{ __('View order status') }}
                </a>
            @endif
            <a href="{{ route('products.index') }}" class="flex h-12 items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] border border-slate-300 bg-white px-6 text-sm font-semibold text-slate-800 hover:border-brand-600 hover:text-brand-700 sf-focus">
                {{ __('Continue shopping') }}
            </a>
        </div>
        @if($canRetry)
            <p class="mt-4 text-xs text-slate-500">{{ __('Tip: you can also choose Cash on delivery at checkout.') }}</p>
        @endif
    </div>

    <p class="mt-6 text-center text-sm text-slate-500">
        {{ __('Need help?') }}
        <a href="{{ route('contact') }}" class="font-semibold text-brand-700 hover:underline">{{ __('Contact support') }}</a>
        {{ __('and mention order :id.', ['id' => '#' . $order->order_id]) }}
    </p>
</div>
@endsection
