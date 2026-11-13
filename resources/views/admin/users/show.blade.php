@extends('admin.layouts')
@section('title', $user->name)
@section('ui-v2', '1')

@section('content')
@php
    $isActive = $user->status === 'active';
    $dt = 'text-xs font-medium text-gray-500 dark:text-gray-400';
    $dd = 'mt-1 text-sm text-gray-900 dark:text-white';
@endphp

<x-admin.page-header :title="$user->name" :description="'Customer since ' . $user->created_at->format('d M Y')"
                     :breadcrumbs="['Customers' => route('users.index'), $user->name => null]">
    @can('users.edit')
        <form method="POST" action="{{ route('users.toggleStatus', $user) }}"
              data-confirm="{{ $isActive ? 'They will no longer be able to log in or place orders.' : 'They will be able to log in and order again.' }}"
              data-confirm-title="{{ $isActive ? 'Block' : 'Unblock' }} {{ $user->name }}?" data-confirm-button="{{ $isActive ? 'Block' : 'Unblock' }}">
            @csrf @method('PATCH')
            <x-admin.button type="submit" variant="secondary" :icon="$isActive ? 'bi-lock' : 'bi-unlock'">{{ $isActive ? 'Block' : 'Unblock' }}</x-admin.button>
        </form>
        <x-admin.button :href="route('users.edit', $user)" icon="bi-pencil">Edit customer</x-admin.button>
    @endcan
</x-admin.page-header>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="space-y-6">
        <x-admin.card>
            <div class="flex items-center gap-4">
                <span class="flex size-14 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xl font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300" aria-hidden="true">
                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                </span>
                <div class="min-w-0">
                    <p class="truncate font-semibold text-gray-900 dark:text-white">{{ $user->name }}</p>
                    <p class="mt-1"><x-admin.status-badge :status="$isActive ? 'active' : 'blocked'" /></p>
                </div>
            </div>
            <dl class="mt-6 space-y-4 border-t border-gray-200 pt-6 dark:border-gray-800">
                <div><dt class="{{ $dt }}">Email</dt><dd class="{{ $dd }} break-all">{{ $user->email }}</dd></div>
                <div><dt class="{{ $dt }}">Phone</dt><dd class="{{ $dd }} tabular-nums">{{ $user->phone ?? '—' }}</dd></div>
                <div><dt class="{{ $dt }}">Customer ID</dt><dd class="{{ $dd }} font-mono">#{{ $user->user_id }}</dd></div>
                <div><dt class="{{ $dt }}">Member since</dt><dd class="{{ $dd }}">{{ $user->created_at->format('d M Y') }}</dd></div>
            </dl>
        </x-admin.card>

        <x-admin.card title="Address">
            <p class="whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $user->address ?: 'No address on file.' }}</p>
            @if($user->city || $user->state || $user->postal_code || $user->country)
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ collect([$user->city, $user->state, $user->postal_code, $user->country])->filter()->implode(', ') }}</p>
            @endif
        </x-admin.card>
    </div>

    <div class="min-w-0 lg:col-span-2">
        <div class="mb-3 flex items-center justify-between gap-4">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Orders <span class="font-normal text-gray-500 dark:text-gray-400">({{ $orders->total() }})</span></h2>
            @if($orders->total())
                <x-admin.button variant="link" :href="route('users.orders', $user)" icon-right="bi-arrow-right">All orders</x-admin.button>
            @endif
        </div>
        <x-admin.data-table :paginator="$orders"
            :columns="[
                ['label' => 'Order', 'sortable' => true],
                ['label' => 'Total', 'sortable' => true, 'align' => 'right'],
                ['label' => 'Status'],
                ['label' => 'Placed', 'sortable' => true],
                ['label' => 'Actions', 'srOnly' => true, 'align' => 'right'],
            ]"
            empty-title="No orders yet" empty-description="This customer hasn't placed an order." empty-icon="bi-cart">
            @foreach($orders as $order)
                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40">
                    <td data-primary data-sort="{{ $order->order_id }}">
                        <a href="{{ route('orders.show', $order->id) }}" class="rounded font-medium text-gray-900 hover:text-brand-600 focus-ring dark:text-white dark:hover:text-brand-400">#{{ $order->order_id }}</a>
                    </td>
                    <td data-label="Total" data-sort="{{ $order->total_price }}" class="whitespace-nowrap text-right font-medium tabular-nums">Tk {{ number_format($order->total_price, 2) }}</td>
                    <td data-label="Status"><x-admin.status-badge type="order" :status="$order->status" /></td>
                    <td data-label="Placed" data-sort="{{ $order->created_at->timestamp }}" class="whitespace-nowrap">{{ $order->created_at->format('d M Y') }}</td>
                    <td class="text-right">
                        <x-admin.button variant="ghost" size="sm" :href="route('orders.show', $order->id)" icon="bi-eye" aria-label="View order #{{ $order->order_id }}" />
                    </td>
                </tr>
            @endforeach
        </x-admin.data-table>
    </div>
</div>
@endsection
