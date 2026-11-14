@extends('user.layout')

@section('title', __('My orders'))

@section('account')
@php
    $tabs = [
        'all' => __('All'),
        'active' => __('In progress'),
        'delivered' => __('Delivered'),
        'closed' => __('Cancelled & returned'),
    ];
@endphp

<div class="space-y-5">
    <header class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">{{ __('My orders') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('Follow your deliveries, download invoices or cancel an order that has not shipped yet.') }}</p>
        </div>
    </header>

    <nav aria-label="{{ __('Filter orders') }}">
        <ul class="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4 pb-1 md:mx-0 md:px-0">
            @foreach($tabs as $key => $label)
                @php($current = $tab === $key)
                <li class="shrink-0">
                    <a href="{{ route('user.orders.index', $key === 'all' ? [] : ['status' => $key]) }}" @if($current) aria-current="page" @endif
                       @class(['flex min-h-10 items-center gap-2 rounded-full border px-4 text-sm font-medium transition-colors sf-focus',
                               'border-navy-900 bg-navy-900 text-white' => $current,
                               'border-[var(--sf-border)] bg-white text-slate-700 hover:border-slate-300' => !$current])>
                        {{ $label }}
                        <span @class(['rounded-full px-1.5 text-xs tabular-nums', 'bg-white/20 text-white' => $current, 'bg-slate-100 text-slate-500' => !$current])>{{ localized_number($counts[$key]) }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    @forelse($orders as $order)
        @include('user.orders.partials.card', ['order' => $order])
    @empty
        <div class="rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white px-6 py-12 text-center">
            <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-brand-50 text-3xl text-brand-600" aria-hidden="true"><i class="bi bi-bag"></i></span>
            <h2 class="mt-4 text-lg font-semibold text-slate-900">{{ $tab === 'all' ? __('No orders yet') : __('No orders here') }}</h2>
            <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">
                {{ $tab === 'all' ? __('When you place an order, it will appear here so you can follow it.') : __('Try another tab to see the rest of your orders.') }}
            </p>
            <a href="{{ $tab === 'all' ? route('products.index') : route('user.orders.index') }}" class="mt-5 inline-flex h-11 items-center gap-2 rounded-full bg-navy-900 px-6 text-sm font-semibold text-white hover:bg-navy-800 sf-focus">
                {{ $tab === 'all' ? __('Start shopping') : __('Show all orders') }}
            </a>
        </div>
    @endforelse

    <x-store.pagination :paginator="$orders" class="pt-3" />
</div>
@endsection
