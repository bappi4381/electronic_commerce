@extends('admin.layouts')
@section('title', 'Create order')
@section('ui-v2', '1')

@section('content')
@php
    $initialRows = collect(old('items', [[]]))->values()->map(fn ($line, $i) => [
        'key' => $i + 1,
        'quantity' => (int) ($line['quantity'] ?? 1),
        'item' => $oldItems[$line['variant_id'] ?? 0] ?? null,
    ]);
    $control = 'block h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-900 shadow-xs placeholder:text-gray-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100';
@endphp

<x-admin.page-header title="Create order" description="Enter an order on a customer's behalf, e.g. from a phone call."
                     :breadcrumbs="['Orders' => route('orders.index'), 'Create order' => null]" />

<form method="POST" action="{{ route('orders.store') }}" class="mx-auto max-w-4xl space-y-6">
    @csrf

    <x-admin.card title="Customer" description="Pick an existing customer or enter new details.">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2" x-data="customerLookup()">
            <div class="relative md:col-span-2" @click.away="open = false">
                <x-admin.field label="Find existing customer" for="customer-search">
                    <div class="relative">
                        <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
                        <input id="customer-search" type="search" x-model.debounce.300ms="query" placeholder="Name, email or phone (at least 2 characters)" autocomplete="off"
                               class="{{ $control }} pl-9">
                    </div>
                </x-admin.field>
                <div x-show="open" x-cloak class="absolute z-30 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-800 dark:bg-gray-900">
                    <p x-show="!results.length" class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">No customers found. Fill in the details below to create one.</p>
                    <template x-for="user in results" :key="user.id">
                        <button type="button" @click="fill(user)" class="block w-full border-b border-gray-100 px-4 py-2.5 text-left last:border-0 hover:bg-gray-50 focus:bg-gray-50 focus:outline-none dark:border-gray-800 dark:hover:bg-gray-800 dark:focus:bg-gray-800">
                            <span class="block text-sm font-medium text-gray-900 dark:text-white" x-text="user.name"></span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400" x-text="[user.email, user.phone].filter(Boolean).join(' · ')"></span>
                        </button>
                    </template>
                </div>
            </div>
            <x-admin.input name="email" type="email" label="Email" required x-ref="email" placeholder="customer@example.com"
                           hint="A new email creates a customer account and sends a set-password link." />
            <x-admin.input name="name" label="Customer name" required x-ref="name" placeholder="Full name" />
            <x-admin.input name="phone" label="Phone" optional x-ref="phone" placeholder="01XXXXXXXXX" />
            <x-admin.input name="address" label="Delivery address" optional x-ref="address" placeholder="House, road, area…" />
        </div>
    </x-admin.card>

    <div x-data="orderItems(@js($initialRows))">
        <x-admin.card title="Items" description="Only variants with stock can be added.">
            <x-slot:actions>
                <span class="text-base font-semibold text-gray-900 tabular-nums dark:text-white" x-text="'Tk ' + total().toLocaleString(undefined, { minimumFractionDigits: 2 })"></span>
            </x-slot:actions>
            <div class="space-y-3">
                <template x-for="(row, index) in rows" :key="row.key">
                    <div class="flex flex-col gap-3 md:flex-row md:items-start" @variant-selected="row.item = $event.detail">
                        <x-admin.variant-picker bind-name="`items[${index}][variant_id]`" :required="true" :require-stock="true" class="md:flex-1" x-init="selected = row.item" />
                        <div class="flex items-center gap-3">
                            <label class="sr-only" :for="`qty-${row.key}`">Quantity</label>
                            <input :id="`qty-${row.key}`" type="number" :name="`items[${index}][quantity]`" x-model.number="row.quantity" min="1" :max="row.item ? Math.max(1, row.item.stock) : 999" required
                                   class="{{ $control }} w-24 text-center tabular-nums">
                            <span class="w-28 text-right text-sm font-medium text-gray-900 tabular-nums dark:text-white"
                                  x-text="row.item ? 'Tk ' + (row.item.price * (row.quantity || 0)).toLocaleString(undefined, { minimumFractionDigits: 2 }) : '—'"></span>
                            <button type="button" @click="rows.splice(index, 1)" x-show="rows.length > 1"
                                    class="flex size-10 shrink-0 items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600 focus-ring dark:hover:bg-red-500/10 dark:hover:text-red-400">
                                <span class="sr-only">Remove item</span><i class="bi bi-trash" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
            <x-admin.button variant="secondary" size="sm" icon="bi-plus-lg" class="mt-4" @click="add()">Add item</x-admin.button>
        </x-admin.card>
    </div>

    <x-admin.card title="Payment method">
        <fieldset class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <legend class="sr-only">Payment method</legend>
            @foreach(['cod' => ['Cash on delivery', 'Customer pays when the parcel arrives', 'bi-cash-stack'], 'online' => ['Online payment', 'Card or mobile banking', 'bi-credit-card']] as $value => [$title, $text, $icon])
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-4 transition-colors hover:bg-gray-50 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/60 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-brand-500/40 dark:border-gray-800 dark:hover:bg-gray-800/50 dark:has-[:checked]:border-brand-400 dark:has-[:checked]:bg-brand-500/10">
                    <input type="radio" name="payment_method" value="{{ $value }}" @checked(old('payment_method', 'cod') === $value)
                           class="mt-0.5 size-4 border-gray-300 text-brand-600 focus:ring-brand-500/40 dark:border-gray-600 dark:bg-gray-800">
                    <span>
                        <span class="flex items-center gap-2 text-sm font-medium text-gray-900 dark:text-white"><i class="bi {{ $icon }} text-gray-400" aria-hidden="true"></i> {{ $title }}</span>
                        <span class="mt-0.5 block text-sm text-gray-500 dark:text-gray-400">{{ $text }}</span>
                    </span>
                </label>
            @endforeach
        </fieldset>
    </x-admin.card>

    <div class="flex justify-end gap-2">
        <x-admin.button variant="secondary" :href="route('orders.index')">Cancel</x-admin.button>
        <x-admin.button type="submit" icon="bi-check-lg">Create order</x-admin.button>
    </div>
</form>

@push('scripts')
<script>
    function customerLookup() {
        return {
            query: '',
            results: [],
            open: false,
            init() {
                this.$watch('query', async (q) => {
                    if (q.trim().length < 2) { this.open = false; return; }
                    const res = await fetch(@js(route('admin.lookup.customers')) + '?' + new URLSearchParams({ q }), { headers: { Accept: 'application/json' } });
                    this.results = res.ok ? await res.json() : [];
                    this.open = true;
                });
            },
            fill(user) {
                this.$refs.email.value = user.email || '';
                this.$refs.name.value = user.name || '';
                this.$refs.phone.value = user.phone || '';
                this.$refs.address.value = user.address || '';
                this.query = '';
                this.open = false;
            },
        };
    }

    function orderItems(initialRows) {
        let nextKey = initialRows.length + 1;

        return {
            rows: initialRows,
            add() {
                this.rows.push({ key: nextKey++, quantity: 1, item: null });
            },
            total() {
                return this.rows.reduce((sum, row) => sum + (row.item ? row.item.price * (row.quantity || 0) : 0), 0);
            },
        };
    }
</script>
@endpush
@endsection
