@extends('admin.layouts')
@section('title', 'Payments')
@section('ui-v2', '1')

@section('content')
@php
    $filterControl = 'h-10 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200';
@endphp

<x-admin.page-header title="Payments" :description="number_format($payments->total()) . ' ' . \Illuminate\Support\Str::plural('transaction', $payments->total()) . ' · Tk ' . number_format($totalAmount, 2) . ' in this view'">
    @foreach(['xlsx' => ['Excel', 'bi-file-earmark-excel'], 'csv' => ['CSV', 'bi-filetype-csv']] as $type => [$name, $icon])
        <x-admin.button variant="secondary" :href="route('payments.export', array_merge(['type' => $type], array_filter($filters)))" :icon="$icon">Export {{ $name }}</x-admin.button>
    @endforeach
</x-admin.page-header>

<x-admin.data-table :paginator="$payments" search="search" search-placeholder="Transaction, order ID, customer…"
    :columns="[
        ['label' => 'Transaction', 'sortable' => true],
        ['label' => 'Order', 'sortable' => true],
        ['label' => 'Customer', 'sortable' => true],
        ['label' => 'Amount', 'sortable' => true, 'align' => 'right'],
        ['label' => 'Status'],
        ['label' => 'Method'],
        ['label' => 'Date', 'sortable' => true, 'align' => 'right'],
        ['label' => 'Actions', 'srOnly' => true, 'align' => 'right'],
    ]"
    empty-title="No payments yet" empty-description="Online payments and collected cash appear here." empty-icon="bi-cash-stack">
    <x-slot:filters>
        <label for="filter-status" class="sr-only">Status</label>
        <select id="filter-status" name="status" class="{{ $filterControl }} pr-9">
            <option value="">Any status</option>
            @foreach($statuses as $status)
                <option value="{{ $status }}" @selected(($filters['status'] ?? null) === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
            @endforeach
        </select>
        <label for="filter-gateway" class="sr-only">Gateway</label>
        <select id="filter-gateway" name="gateway" class="{{ $filterControl }} pr-9">
            <option value="">Any gateway</option>
            @foreach($gateways as $value => $name)
                <option value="{{ $value }}" @selected(($filters['gateway'] ?? null) === $value)>{{ $name }}</option>
            @endforeach
        </select>
        <label for="filter-from" class="sr-only">From date</label>
        <input id="filter-from" type="date" name="from_date" value="{{ $filters['from_date'] ?? '' }}" class="{{ $filterControl }}" title="From">
        <label for="filter-to" class="sr-only">To date</label>
        <input id="filter-to" type="date" name="to_date" value="{{ $filters['to_date'] ?? '' }}" class="{{ $filterControl }}" title="To">
    </x-slot:filters>

    @foreach($payments as $payment)
        @php($paidAt = $payment->payment_date ?? $payment->created_at)
        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40">
            <td data-primary data-sort="{{ $payment->id }}">
                <div class="min-w-0">
                    <a href="{{ route('payments.show', $payment) }}" class="rounded font-medium text-gray-900 hover:text-brand-600 focus-ring dark:text-white dark:hover:text-brand-400">#{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</a>
                    <span class="block max-w-48 truncate font-mono text-xs font-normal text-gray-500 dark:text-gray-400">{{ $payment->transaction_id }}</span>
                </div>
            </td>
            <td data-label="Order" data-sort="{{ $payment->order?->order_id }}" class="whitespace-nowrap">
                @if($payment->order)
                    <a href="{{ route('orders.show', $payment->order) }}" class="rounded text-brand-600 hover:underline focus-ring dark:text-brand-400">#{{ $payment->order->order_id }}</a>
                    @if($payment->order->trashed())<x-admin.status-badge status="archived" label="Deleted" class="ml-1" />@endif
                @else
                    —
                @endif
            </td>
            <td data-label="Customer" class="max-w-48 truncate">{{ $payment->user->name ?? $payment->order?->customer_name ?? '—' }}</td>
            <td data-label="Amount" data-sort="{{ $payment->amount }}" class="whitespace-nowrap text-right font-medium text-gray-900 tabular-nums dark:text-white">Tk {{ number_format($payment->amount, 2) }}</td>
            <td data-label="Status"><x-admin.status-badge type="payment" :status="$payment->status" /></td>
            <td data-label="Method" class="whitespace-nowrap">{{ $gateways[$payment->payment_method] ?? ucfirst($payment->payment_method) }}</td>
            <td data-label="Date" data-sort="{{ $paidAt?->timestamp }}" class="whitespace-nowrap text-right text-gray-500 dark:text-gray-400">{{ $paidAt?->format('d M Y, h:i A') }}</td>
            <td class="text-right">
                <x-admin.button variant="ghost" size="sm" :href="route('payments.show', $payment)" icon="bi-eye" title="View" aria-label="View payment #{{ $payment->id }}" />
            </td>
        </tr>
    @endforeach
</x-admin.data-table>
@endsection
