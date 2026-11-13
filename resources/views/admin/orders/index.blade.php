@extends('admin.layouts')
@section('title', 'Orders')
@section('ui-v2', '1')

@use('App\Enums\OrderStatus')
@use('App\Enums\PaymentStatus')

@section('content')
@php
    $filterControl = 'h-10 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-700 shadow-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200';
    $canEdit = auth()->user()->can('orders.edit');
@endphp

<div x-data="{ selected: [], all: false }">
    <x-admin.page-header title="Orders" :description="number_format($orders->total()) . ' ' . \Illuminate\Support\Str::plural('order', $orders->total()) . ' match the current filters.'">
        @can('orders.create')
            <x-admin.button :href="route('orders.create')" icon="bi-plus-lg">Create order</x-admin.button>
        @endcan
    </x-admin.page-header>

    <nav aria-label="Filter by status" class="scrollbar-thin mb-4 flex gap-2 overflow-x-auto pb-1">
        @php($allActive = empty($filters['status']))
        <a href="{{ route('orders.index', request()->except('status', 'page')) }}" @if($allActive) aria-current="page" @endif
           @class(['inline-flex shrink-0 items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium focus-ring',
                   'border-gray-900 bg-gray-900 text-white dark:border-white dark:bg-white dark:text-gray-900' => $allActive,
                   'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:text-gray-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:text-white' => !$allActive])>
            All <span class="tabular-nums opacity-60">{{ $statusCounts->sum() }}</span>
        </a>
        @foreach(OrderStatus::cases() as $status)
            @php($active = ($filters['status'] ?? null) === $status->value)
            <a href="{{ route('orders.index', array_merge(request()->except('page'), ['status' => $status->value])) }}" @if($active) aria-current="page" @endif
               @class(['inline-flex shrink-0 items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium focus-ring',
                       'border-gray-900 bg-gray-900 text-white dark:border-white dark:bg-white dark:text-gray-900' => $active,
                       'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:text-gray-900 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:text-white' => !$active])>
                {{ $status->label() }} <span class="tabular-nums opacity-60">{{ $statusCounts[$status->value] ?? 0 }}</span>
            </a>
        @endforeach
    </nav>

    @if(session('bulk_failures'))
        <x-admin.alert tone="warning" title="Some orders were skipped" class="mb-4">
            <ul class="list-inside list-disc space-y-0.5">
                @foreach(session('bulk_failures') as $orderRef => $reason)
                    <li>#{{ $orderRef }}: {{ $reason }}</li>
                @endforeach
            </ul>
        </x-admin.alert>
    @endif

    @if($canEdit)
        <form method="POST" action="{{ route('orders.bulkStatus') }}" x-show="selected.length" x-cloak
              class="sticky top-20 z-30 mb-4 flex flex-col gap-3 rounded-xl bg-gray-900 p-3 text-white shadow-lg md:flex-row md:items-center dark:bg-gray-800 dark:ring-1 dark:ring-gray-700">
            @csrf
            <template x-for="id in selected" :key="id"><input type="hidden" name="order_ids[]" :value="id"></template>
            <span class="px-1 text-sm font-medium"><span x-text="selected.length"></span> selected</span>
            <label for="bulk-status" class="sr-only">New status</label>
            <select id="bulk-status" name="status" required class="h-9 rounded-lg border border-white/20 bg-white/10 px-3 text-sm text-white focus:outline-none focus:ring-2 focus:ring-white/40">
                <option value="" class="text-gray-900">Move to status…</option>
                @foreach(OrderStatus::options() as $value => $label)
                    <option value="{{ $value }}" class="text-gray-900">{{ $label }}</option>
                @endforeach
            </select>
            <label for="bulk-note" class="sr-only">Note</label>
            <input id="bulk-note" type="text" name="note" maxlength="255" placeholder="Note for the status history (optional)"
                   class="h-9 flex-1 rounded-lg border border-white/20 bg-white/10 px-3 text-sm text-white placeholder:text-white/50 focus:outline-none focus:ring-2 focus:ring-white/40">
            <div class="flex gap-2">
                <x-admin.button type="submit" size="sm">Update</x-admin.button>
                <button type="button" @click="selected = []; all = false" class="h-8 rounded-lg px-3 text-xs font-medium text-white/80 hover:bg-white/10 focus-ring">Clear</button>
            </div>
        </form>
    @endif

    <x-admin.data-table :paginator="$orders" search="q" search-placeholder="Order ID, customer, email or phone…" :preserve="['status']"
        :columns="array_values(array_filter([
            $canEdit ? ['label' => 'Select', 'srOnly' => true, 'class' => 'w-10'] : null,
            ['label' => 'Order', 'sortable' => true],
            ['label' => 'Customer', 'sortable' => true],
            ['label' => 'Total', 'sortable' => true, 'align' => 'right'],
            ['label' => 'Status'],
            ['label' => 'Payment'],
            ['label' => 'Placed', 'sortable' => true],
            ['label' => 'Actions', 'srOnly' => true, 'align' => 'right'],
        ]))"
        empty-title="No orders yet" empty-description="Orders from the store and manual orders appear here." empty-icon="bi-receipt">
        <x-slot:filters>
            <label for="filter-payment" class="sr-only">Payment status</label>
            <select id="filter-payment" name="payment_status" class="{{ $filterControl }} pr-9">
                <option value="">Any payment</option>
                @foreach(PaymentStatus::options() as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['payment_status'] ?? null) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <label for="filter-from" class="sr-only">From date</label>
            <input id="filter-from" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="{{ $filterControl }}" title="From">
            <label for="filter-to" class="sr-only">To date</label>
            <input id="filter-to" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="{{ $filterControl }}" title="To">
            <label for="filter-per-page" class="sr-only">Rows per page</label>
            <select id="filter-per-page" name="per_page" onchange="this.form.requestSubmit()" class="{{ $filterControl }} pr-9">
                @foreach([10, 25, 50, 100] as $n)
                    <option value="{{ $n }}" @selected(($filters['per_page'] ?? 25) == $n)>{{ $n }} / page</option>
                @endforeach
            </select>
        </x-slot:filters>
        @if($canEdit && $orders->count())
            <x-slot:toolbar>
                <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                    <input type="checkbox" x-model="all" @change="selected = all ? @js($orders->pluck('id')->map(fn ($id) => (string) $id)) : []"
                           class="size-4 rounded border-gray-300 text-brand-600 focus:ring-2 focus:ring-brand-500/40 dark:border-gray-600 dark:bg-gray-800">
                    Select all on this page
                </label>
            </x-slot:toolbar>
        @endif

        @foreach($orders as $order)
            <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40" :class="selected.includes('{{ $order->id }}') && 'bg-brand-50/60 dark:bg-brand-500/5'">
                @if($canEdit)
                    <td class="w-10">
                        <label class="sr-only" for="select-{{ $order->id }}">Select order #{{ $order->order_id }}</label>
                        <input id="select-{{ $order->id }}" type="checkbox" value="{{ $order->id }}" x-model="selected"
                               class="size-4 rounded border-gray-300 text-brand-600 focus:ring-2 focus:ring-brand-500/40 dark:border-gray-600 dark:bg-gray-800">
                    </td>
                @endif
                <td data-primary data-sort="{{ $order->order_id }}">
                    <div class="min-w-0">
                        <a href="{{ route('orders.show', $order) }}" class="rounded font-medium text-gray-900 hover:text-brand-600 focus-ring dark:text-white dark:hover:text-brand-400">#{{ $order->order_id }}</a>
                        @if($order->orderCourier)
                            <span class="mt-0.5 flex items-center gap-1 truncate text-xs font-normal text-gray-500 dark:text-gray-400">
                                <i class="bi bi-truck" aria-hidden="true"></i> {{ $order->orderCourier->courierLabel() }} · {{ $order->orderCourier->tracking_code ?: $order->orderCourier->consignment_id }}
                            </span>
                        @endif
                    </div>
                </td>
                <td data-label="Customer" data-sort="{{ $order->customerDisplayName() }}">
                    <div class="min-w-0 text-right md:text-left">
                        <span class="block truncate font-medium text-gray-900 dark:text-white">{{ $order->customerDisplayName() }}</span>
                        <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ $order->customerDisplayPhone() ?? $order->customerDisplayEmail() ?? '—' }}</span>
                    </div>
                </td>
                <td data-label="Total" data-sort="{{ $order->total_price }}" class="whitespace-nowrap text-right">
                    <div>
                        <span class="font-medium text-gray-900 tabular-nums dark:text-white">Tk {{ number_format($order->total_price, 2) }}</span>
                        @if((float) $order->discount_amount > 0)
                            <span class="block text-xs text-green-600 dark:text-green-400">{{ $order->coupon_code }} −{{ number_format($order->discount_amount, 0) }}</span>
                        @endif
                    </div>
                </td>
                <td data-label="Status"><x-admin.status-badge type="order" :status="$order->status" /></td>
                <td data-label="Payment">
                    <div class="flex flex-col items-end gap-1 md:items-start">
                        <x-admin.status-badge type="payment" :status="$order->payment_status" />
                        @if($order->payment_method)<span class="text-xs uppercase text-gray-500 dark:text-gray-400">{{ $order->payment_method }}</span>@endif
                    </div>
                </td>
                <td data-label="Placed" data-sort="{{ $order->created_at->timestamp }}" class="whitespace-nowrap">
                    <div class="text-right md:text-left">
                        <span class="block text-gray-700 dark:text-gray-300">{{ $order->created_at->format('d M Y') }}</span>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $order->created_at->format('h:i A') }}</span>
                    </div>
                </td>
                <td class="whitespace-nowrap text-right">
                    <div class="inline-flex items-center gap-1">
                        <x-admin.button variant="ghost" size="sm" :href="route('orders.show', $order)" icon="bi-eye" title="View" aria-label="View order #{{ $order->order_id }}" />
                        @can('orders.delete')
                            <form method="POST" action="{{ route('orders.destroy', $order) }}"
                                  data-confirm="The order moves to the trash. Stock still in the warehouse is released back to inventory."
                                  data-confirm-title="Delete order #{{ $order->order_id }}?" data-confirm-button="Delete">
                                @csrf @method('DELETE')
                                <x-admin.button type="submit" variant="ghost" size="sm" icon="bi-trash" :loading="false" class="text-red-600 dark:text-red-400" title="Delete" aria-label="Delete order #{{ $order->order_id }}" />
                            </form>
                        @endcan
                    </div>
                </td>
            </tr>
        @endforeach
    </x-admin.data-table>
</div>
@endsection
