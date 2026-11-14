@extends('user.layout')

@section('title', __('My account'))

@section('account')
@php
    $firstName = \Illuminate\Support\Str::of($user->name)->before(' ');
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? __('Good morning') : ($hour < 17 ? __('Good afternoon') : __('Good evening'));
    $card = 'rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white p-5 md:p-6';
    $statCards = [
        ['label' => __('Total orders'), 'value' => $stats['total_orders'], 'icon' => 'bi-bag', 'tone' => 'bg-brand-50 text-brand-600', 'url' => route('user.orders.index')],
        ['label' => __('In progress'), 'value' => $stats['active_orders'], 'icon' => 'bi-hourglass-split', 'tone' => 'bg-amber-50 text-amber-600', 'url' => route('user.orders.index', ['status' => 'active'])],
        ['label' => __('Delivered'), 'value' => $stats['delivered_orders'], 'icon' => 'bi-house-check', 'tone' => 'bg-emerald-50 text-emerald-600', 'url' => route('user.orders.index', ['status' => 'delivered'])],
        ['label' => __('Wishlist'), 'value' => $stats['wishlist_count'], 'icon' => 'bi-heart', 'tone' => 'bg-rose-50 text-rose-600', 'url' => route('wishlist.index')],
    ];
    $hasAddress = filled($user->address);
@endphp

<div class="space-y-6">
    <header>
        <p class="text-sm text-slate-500">{{ $greeting }},</p>
        <h1 class="font-display text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">{{ $firstName }}</h1>
    </header>

    {{-- Stats --}}
    <ul class="grid grid-cols-2 gap-3 md:grid-cols-4 md:gap-4">
        @foreach($statCards as $stat)
            <li>
                <a href="{{ $stat['url'] }}" class="flex h-full flex-col gap-3 rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white p-4 transition-shadow hover:shadow-[var(--sf-shadow-sm)] sf-focus md:p-5">
                    <span class="flex size-10 items-center justify-center rounded-full text-lg {{ $stat['tone'] }}" aria-hidden="true"><i class="bi {{ $stat['icon'] }}"></i></span>
                    <span>
                        <span class="block font-display text-2xl font-bold tabular-nums text-slate-900">{{ localized_number($stat['value']) }}</span>
                        <span class="block text-sm text-slate-500">{{ $stat['label'] }}</span>
                    </span>
                </a>
            </li>
        @endforeach
    </ul>

    {{-- Latest active order --}}
    @if($activeOrder)
        <section class="{{ $card }}" aria-labelledby="active-order-title">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 id="active-order-title" class="text-lg font-semibold text-slate-900">{{ __('Your latest order') }}</h2>
                    <p class="text-sm text-slate-500">
                        <span class="font-mono">#{{ $activeOrder->order_id }}</span> · {{ $activeOrder->created_at->format('d M Y') }} ·
                        {{ trans_choice(':count item|:count items', (int) $activeOrder->orderItems->sum('quantity'), ['count' => localized_number($activeOrder->orderItems->sum('quantity'))]) }}
                    </p>
                </div>
                <a href="{{ route('user.orders.details', $activeOrder) }}" class="flex h-10 items-center gap-1.5 rounded-[var(--sf-radius-sm)] border border-slate-300 px-4 text-sm font-semibold text-slate-800 hover:border-brand-600 hover:text-brand-700 sf-focus">
                    {{ __('Track order') }} <i class="bi bi-chevron-right text-xs" aria-hidden="true"></i>
                </a>
            </div>
            <x-store.order-progress :order="$activeOrder" />
        </section>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
        {{-- Recent orders --}}
        <section aria-labelledby="recent-title">
            <div class="mb-3 flex items-center justify-between">
                <h2 id="recent-title" class="text-lg font-semibold text-slate-900">{{ __('Recent orders') }}</h2>
                @if($recentOrders->isNotEmpty())
                    <a href="{{ route('user.orders.index') }}" class="flex min-h-10 items-center gap-1 rounded text-sm font-semibold text-brand-700 hover:underline sf-focus">{{ __('View all') }} <i class="bi bi-chevron-right text-xs" aria-hidden="true"></i></a>
                @endif
            </div>
            @forelse($recentOrders as $order)
                <div @class(['mt-3' => !$loop->first])>@include('user.orders.partials.card', ['order' => $order])</div>
            @empty
                <div class="{{ $card }} text-center">
                    <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-brand-50 text-2xl text-brand-600" aria-hidden="true"><i class="bi bi-bag"></i></span>
                    <p class="mt-3 font-semibold text-slate-900">{{ __('No orders yet') }}</p>
                    <p class="mt-1 text-sm text-slate-500">{{ __('When you place an order, you can follow it here.') }}</p>
                    <a href="{{ route('products.index') }}" class="mt-4 inline-flex h-11 items-center gap-2 rounded-full bg-navy-900 px-6 text-sm font-semibold text-white hover:bg-navy-800 sf-focus">{{ __('Start shopping') }}</a>
                </div>
            @endforelse
        </section>

        <div class="space-y-6">
            {{-- Account details --}}
            <section class="{{ $card }}" aria-labelledby="details-title">
                <div class="flex items-center justify-between">
                    <h2 id="details-title" class="font-semibold text-slate-900">{{ __('Account details') }}</h2>
                    <a href="{{ route('user.profile') }}" class="rounded text-sm font-semibold text-brand-700 hover:underline sf-focus">{{ __('Edit') }}</a>
                </div>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex gap-3">
                        <dt class="sr-only">{{ __('Email') }}</dt>
                        <i class="bi bi-envelope mt-0.5 text-slate-400" aria-hidden="true"></i>
                        <dd class="min-w-0 break-all text-slate-700">
                            {{ $user->email }}
                            @if($user->is_email_verified)
                                <span class="ml-1 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700"><i class="bi bi-patch-check-fill" aria-hidden="true"></i>{{ __('Verified') }}</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex gap-3">
                        <dt class="sr-only">{{ __('Phone') }}</dt>
                        <i class="bi bi-telephone mt-0.5 text-slate-400" aria-hidden="true"></i>
                        <dd class="text-slate-700">{{ $user->phone ?: __('No phone number added') }}</dd>
                    </div>
                    <div class="flex gap-3">
                        <dt class="sr-only">{{ __('Default address') }}</dt>
                        <i class="bi bi-geo-alt mt-0.5 text-slate-400" aria-hidden="true"></i>
                        <dd class="whitespace-pre-line text-slate-700">{{ $hasAddress ? collect([$user->address, collect([$user->state, $user->city === \App\Services\CheckoutOptions::OTHER_ZONE ? null : $user->city, $user->postal_code])->filter()->implode(', ')])->filter()->implode("\n") : __('No default address yet. It is saved when you tick "Save this information" at checkout.') }}</dd>
                    </div>
                </dl>
            </section>

            {{-- Help --}}
            <section class="rounded-[var(--sf-radius-lg)] bg-navy-900 p-5 text-white md:p-6" aria-labelledby="help-title">
                <h2 id="help-title" class="font-semibold">{{ __('Need help with an order?') }}</h2>
                <p class="mt-1 text-sm text-slate-300">{{ __('Message our support team. We usually reply within minutes.') }}</p>
                <a href="{{ route('user.messages.index') }}" class="mt-4 flex h-11 items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] bg-white text-sm font-semibold text-navy-900 hover:bg-slate-100 sf-focus">
                    <i class="bi bi-chat-dots" aria-hidden="true"></i> {{ __('Message support') }}
                </a>
            </section>
        </div>
    </div>
</div>
@endsection
