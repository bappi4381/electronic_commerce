@extends('admin.layouts')
@section('title', 'Edit order #' . $order->order_id)
@section('ui-v2', '1')

@section('content')
@php
    $control = 'block h-10 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-900 shadow-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100';
    $lockReason = $order->paymentStatusEnum()?->isCollected()
        ? strtolower($order->paymentStatusEnum()->label())
        : strtolower($order->statusEnum()?->label() ?? $order->status);
@endphp

<x-admin.page-header :title="'Edit order #' . $order->order_id" :description="$order->user->name ?? 'Guest order'"
                     :breadcrumbs="['Orders' => route('orders.index'), '#' . $order->order_id => route('orders.show', $order), 'Edit' => null]" />

<form method="POST" action="{{ route('orders.update', $order) }}" class="mx-auto max-w-5xl space-y-6">
    @csrf @method('PUT')

    @unless($itemsEditable)
        <x-admin.alert tone="warning" icon="bi-lock-fill">
            Items and charges are locked because this order is {{ $lockReason }}. You can still update the delivery details and notes.
        </x-admin.alert>
    @endunless

    <x-admin.card title="Delivery details">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <x-admin.input name="customer_name" label="Recipient name" maxlength="100" :value="$order->customerDisplayName()" />
            <x-admin.input name="phone" label="Phone" maxlength="20" :value="$order->customerDisplayPhone()" />
            <div class="md:col-span-2">
                <x-admin.textarea name="shipping_address" label="Shipping address" rows="2" maxlength="255" :value="$order->shipping_address" />
            </div>
            <x-admin.input name="city" label="City / zone" maxlength="100" :value="$order->city" />
            <x-admin.input name="region" label="Area / thana" optional maxlength="100" :value="$order->region" />
            <x-admin.input name="postal_code" label="Postcode" optional maxlength="20" :value="$order->postal_code" />
            <x-admin.input name="alt_phone" label="Alternative phone" optional maxlength="20" :value="$order->alt_phone" />
            <x-admin.input name="delivery_charge" type="number" label="Delivery charge" prefix="Tk" step="0.01" min="0"
                           :value="$order->delivery_charge" :disabled="!$itemsEditable" />
            <div class="md:col-span-2">
                <x-admin.textarea name="order_notes" label="Order notes" optional rows="3" maxlength="1000" :value="$order->order_notes" />
            </div>
        </div>
    </x-admin.card>

    <x-admin.card title="Items" :description="$itemsEditable ? 'Set a quantity to 0 to remove the line.' : null" :padding="false">
        <div class="overflow-x-auto">
            <table class="admin-table min-w-full text-sm [&_td]:px-6 [&_td]:py-3 [&_td]:align-middle [&_td]:text-gray-700 dark:[&_td]:text-gray-300">
                <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800/40">
                    <tr class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        <th scope="col" class="px-6 py-3 text-left">Product</th>
                        <th scope="col" class="px-6 py-3 text-left">SKU</th>
                        <th scope="col" class="px-6 py-3 text-right">Unit price</th>
                        <th scope="col" class="px-6 py-3 text-center">Qty</th>
                        <th scope="col" class="px-6 py-3 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach($order->orderItems as $item)
                        <tr>
                            <td data-primary class="font-medium text-gray-900 dark:text-white">{{ $item->product?->name ?? 'Deleted product' }}</td>
                            <td data-label="SKU" class="font-mono text-xs">{{ $item->variant?->sku ?? '—' }}</td>
                            <td data-label="Unit price" class="text-right tabular-nums">Tk {{ number_format($item->price, 2) }}</td>
                            <td data-label="Qty" class="text-center">
                                @if($itemsEditable)
                                    <label class="sr-only" for="qty-{{ $item->id }}">Quantity for {{ $item->product?->name }}</label>
                                    <input id="qty-{{ $item->id }}" type="number" name="items[{{ $item->id }}][quantity]" min="0" max="999"
                                           value="{{ old('items.' . $item->id . '.quantity', $item->quantity) }}" class="{{ $control }} w-20 text-center tabular-nums">
                                @else
                                    <span class="tabular-nums">{{ $item->quantity }}</span>
                                @endif
                            </td>
                            <td data-label="Subtotal" class="text-right font-medium text-gray-900 tabular-nums dark:text-white">Tk {{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($itemsEditable)
            <div class="space-y-3 border-t border-gray-200 p-6 dark:border-gray-800" x-data="{ rows: [] }">
                <template x-for="(row, index) in rows" :key="row">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start">
                        <x-admin.variant-picker bind-name="`new_items[${row}][variant_id]`" :required="true" :require-stock="true" class="md:flex-1" />
                        <div class="flex items-center gap-3">
                            <label class="sr-only" :for="`new-qty-${row}`">Quantity</label>
                            <input :id="`new-qty-${row}`" type="number" :name="`new_items[${row}][quantity]`" min="1" max="999" value="1" required
                                   class="{{ $control }} w-24 text-center tabular-nums">
                            <button type="button" @click="rows.splice(index, 1)"
                                    class="flex size-10 shrink-0 items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600 focus-ring dark:hover:bg-red-500/10 dark:hover:text-red-400">
                                <span class="sr-only">Remove item</span><i class="bi bi-trash" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </template>
                <x-admin.button variant="secondary" size="sm" icon="bi-plus-lg" @click="rows.push(Date.now())">Add item</x-admin.button>
            </div>
        @endif
    </x-admin.card>

    <div class="flex justify-end gap-2">
        <x-admin.button variant="secondary" :href="route('orders.show', $order)">Cancel</x-admin.button>
        <x-admin.button type="submit" icon="bi-check-lg">Save changes</x-admin.button>
    </div>
</form>
@endsection
