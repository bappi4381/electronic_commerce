@extends('admin.layouts')
@section('title', 'Orders · ' . $user->name)
@section('ui-v2', '1')

@section('content')
<x-admin.page-header :title="'Orders by ' . $user->name" :description="$user->email . ' · ' . $orders->total() . ' ' . \Illuminate\Support\Str::plural('order', $orders->total())"
                     :breadcrumbs="['Customers' => route('users.index'), $user->name => route('users.show', $user), 'Orders' => null]" />

<x-admin.data-table :paginator="$orders"
    :columns="[
        ['label' => 'Order', 'sortable' => true],
        ['label' => 'Items', 'sortable' => true, 'align' => 'center'],
        ['label' => 'Total', 'sortable' => true, 'align' => 'right'],
        ['label' => 'Status'],
        ['label' => 'Payment'],
        ['label' => 'Placed', 'sortable' => true],
        ['label' => 'Actions', 'srOnly' => true, 'align' => 'right'],
    ]"
    empty-title="No orders yet" empty-description="This customer hasn't placed an order." empty-icon="bi-cart">
    @foreach($orders as $order)
        <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40">
            <td data-primary data-sort="{{ $order->order_id }}">
                <a href="{{ route('orders.show', $order) }}" class="rounded font-medium text-gray-900 hover:text-brand-600 focus-ring dark:text-white dark:hover:text-brand-400">#{{ $order->order_id }}</a>
            </td>
            <td data-label="Items" class="text-center tabular-nums">{{ $order->order_items_count }}</td>
            <td data-label="Total" data-sort="{{ $order->total_price }}" class="whitespace-nowrap text-right font-medium tabular-nums">Tk {{ number_format($order->total_price, 2) }}</td>
            <td data-label="Status"><x-admin.status-badge type="order" :status="$order->status" /></td>
            <td data-label="Payment"><x-admin.status-badge type="payment" :status="$order->payment_status" /></td>
            <td data-label="Placed" data-sort="{{ $order->created_at->timestamp }}" class="whitespace-nowrap">{{ $order->created_at->format('d M Y') }}</td>
            <td class="text-right">
                <x-admin.button variant="ghost" size="sm" :href="route('orders.show', $order)" icon="bi-eye" aria-label="View order #{{ $order->order_id }}" />
            </td>
        </tr>
    @endforeach
</x-admin.data-table>
@endsection
