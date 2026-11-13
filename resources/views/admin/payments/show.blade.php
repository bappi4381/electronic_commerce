@extends('admin.layouts')
@section('title', 'Payment #' . str_pad($payment->id, 6, '0', STR_PAD_LEFT))
@section('ui-v2', '1')

@section('content')
@php
    $ref = '#' . str_pad($payment->id, 6, '0', STR_PAD_LEFT);
    $dt = 'text-xs font-medium text-gray-500 dark:text-gray-400';
    $dd = 'mt-1 text-sm text-gray-900 dark:text-white';
@endphp

<x-admin.page-header :title="'Payment ' . $ref" :description="'Recorded ' . $payment->created_at->format('d M Y, h:i A')"
                     :breadcrumbs="['Payments' => route('payments.index'), $ref => null]">
    @if($payment->order)
        <x-admin.button variant="secondary" :href="route('orders.show', $payment->order)" icon="bi-receipt">View order</x-admin.button>
    @endif
</x-admin.page-header>

<div class="mx-auto grid max-w-5xl grid-cols-1 gap-6 md:grid-cols-2">
    <x-admin.card title="Transaction">
        <p class="text-3xl font-semibold tracking-tight text-gray-900 tabular-nums dark:text-white">Tk {{ number_format($payment->amount, 2) }}</p>
        <p class="mt-2"><x-admin.status-badge type="payment" :status="$payment->status" /></p>
        <dl class="mt-6 space-y-4 border-t border-gray-200 pt-6 dark:border-gray-800">
            <div><dt class="{{ $dt }}">Order</dt><dd class="{{ $dd }}">{{ $payment->order ? '#' . $payment->order->order_id : '—' }}</dd></div>
            <div><dt class="{{ $dt }}">Method</dt><dd class="{{ $dd }}">{{ ucfirst($payment->payment_method) }}</dd></div>
            <div><dt class="{{ $dt }}">Gateway transaction ID</dt><dd class="{{ $dd }} break-all font-mono">{{ $payment->transaction_id ?? '—' }}</dd></div>
        </dl>
    </x-admin.card>

    <x-admin.card title="Customer">
        <dl class="space-y-4">
            <div>
                <dt class="{{ $dt }}">Name</dt>
                <dd class="{{ $dd }} font-medium">
                    {{ $payment->user->name ?? 'Guest / unknown' }}
                    @if($payment->user && auth()->user()->can('users.view'))
                        <a href="{{ route('users.show', $payment->user) }}" class="ml-1 rounded text-xs font-normal text-brand-600 hover:underline focus-ring dark:text-brand-400">View profile</a>
                    @endif
                </dd>
            </div>
            <div><dt class="{{ $dt }}">Email</dt><dd class="{{ $dd }} break-all">{{ $payment->user->email ?? '—' }}</dd></div>
            <div><dt class="{{ $dt }}">Phone</dt><dd class="{{ $dd }} tabular-nums">{{ $payment->user->phone ?? '—' }}</dd></div>
            <div><dt class="{{ $dt }}">Timestamp</dt><dd class="{{ $dd }}">{{ $payment->created_at->format('d M Y, h:i A') }}</dd></div>
        </dl>
    </x-admin.card>
</div>
@endsection
