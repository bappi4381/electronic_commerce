@extends('admin.layouts')
@section('title', 'Stock movements')
@section('ui-v2', '1')

@section('content')
@php
    $typeTones = ['adjustment' => 'blue', 'correction' => 'indigo', 'return' => 'orange', 'sale' => 'green', 'order' => 'green', 'other' => 'gray'];
@endphp

<x-admin.page-header title="Stock movements" description="Every stock change, and manual adjustments for stock takes or corrections."
                     :breadcrumbs="['Products' => route('admin.products.index'), 'Stock movements' => null]" />

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    @can('products.edit')
        <x-admin.card title="Adjust stock" class="self-start"
                      x-data="{ stock: {{ \Illuminate\Support\Js::from($selectedVariant['stock'] ?? null) }}, change: {{ \Illuminate\Support\Js::from(old('change', '')) }} }">
            <form method="POST" action="{{ route('admin.products.stock_movements.store') }}" class="space-y-4"
                  @variant-selected="stock = $event.detail ? $event.detail.stock : null">
                @csrf
                <x-admin.field label="Variant" error="variant_id" required>
                    <x-admin.variant-picker name="variant_id" :required="true" :inactive="true" :selected="$selectedVariant" placeholder="Search product, code or SKU…" />
                </x-admin.field>

                <x-admin.input name="change" type="number" label="Change" required step="1" placeholder="e.g. 10 or -5" x-model.number="change"
                               hint="Use a negative number to decrease stock."
                               x-effect="$el.setCustomValidity(stock !== null && stock + (parseInt(change) || 0) < 0 ? 'This adjustment would make stock negative.' : '')" />

                <div class="grid grid-cols-3 gap-2 rounded-lg bg-gray-50 p-3 text-center text-sm dark:bg-gray-800/60" x-show="stock !== null" x-cloak>
                    <div><p class="text-xs text-gray-500 dark:text-gray-400">Current</p><p class="font-medium tabular-nums" x-text="stock"></p></div>
                    <div><p class="text-xs text-gray-500 dark:text-gray-400">Change</p><p class="font-medium tabular-nums" :class="(parseInt(change) || 0) < 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400'" x-text="((parseInt(change) || 0) > 0 ? '+' : '') + (parseInt(change) || 0)"></p></div>
                    <div><p class="text-xs text-gray-500 dark:text-gray-400">New</p><p class="font-semibold tabular-nums" :class="stock + (parseInt(change) || 0) < 0 && 'text-red-600 dark:text-red-400'" x-text="stock + (parseInt(change) || 0)"></p></div>
                </div>

                <x-admin.input name="reason" label="Reason" optional placeholder="e.g. Stock take correction" />
                <x-admin.select name="type" label="Type" :options="['adjustment' => 'Adjustment', 'correction' => 'Correction', 'return' => 'Return', 'other' => 'Other']" value="adjustment" />
                <x-admin.button type="submit" icon="bi-check-lg" class="w-full">Apply adjustment</x-admin.button>
            </form>
        </x-admin.card>
    @endcan

    <div @class(['min-w-0', 'lg:col-span-2' => auth()->user()->can('products.edit'), 'lg:col-span-3' => !auth()->user()->can('products.edit')])>
        <div class="mb-3 flex items-center justify-between gap-4">
            <h2 class="min-w-0 truncate text-base font-semibold text-gray-900 dark:text-white">{{ $filterVariant ? 'Movements for ' . $filterVariant['label'] : 'Recent movements' }}</h2>
            @if($filterVariant)
                <x-admin.button variant="link" :href="route('admin.products.stock_movements.index')" icon="bi-x-lg">Show all</x-admin.button>
            @endif
        </div>
        <x-admin.data-table :paginator="$movements->withQueryString()"
            :columns="[
                ['label' => 'Product', 'sortable' => true],
                ['label' => 'Change', 'sortable' => true, 'align' => 'right'],
                ['label' => 'Type'],
                ['label' => 'Reason'],
                ['label' => 'By'],
                ['label' => 'When', 'sortable' => true, 'align' => 'right'],
            ]"
            empty-title="No stock movements yet" empty-description="Sales, returns and manual adjustments are logged here." empty-icon="bi-arrow-left-right">
            @foreach($movements as $m)
                @php($productName = optional($m->variant?->product)->getTranslation('name', 'en') ?? 'Deleted product')
                <tr class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40">
                    <td data-primary data-sort="{{ $productName }}">
                        <div class="min-w-0">
                            <a href="{{ route('admin.products.stock_movements.index', ['variant' => $m->variant_id]) }}" class="block truncate rounded font-medium text-gray-900 hover:text-brand-600 focus-ring dark:text-white dark:hover:text-brand-400">{{ $productName }}</a>
                            <span class="block truncate font-mono text-xs font-normal text-gray-500 dark:text-gray-400">{{ $m->variant?->sku ?? '—' }}</span>
                        </div>
                    </td>
                    <td data-label="Change" data-sort="{{ $m->change }}" @class(['whitespace-nowrap text-right font-semibold tabular-nums', 'text-green-600 dark:text-green-400' => $m->change > 0, 'text-red-600 dark:text-red-400' => $m->change < 0])>
                        {{ $m->change > 0 ? '+' . $m->change : $m->change }}
                    </td>
                    <td data-label="Type"><x-admin.status-badge :tone="$typeTones[$m->type] ?? 'gray'" :status="$m->type" :dot="false" /></td>
                    <td data-label="Reason" class="max-w-xs truncate">{{ $m->reason ?: '—' }}</td>
                    <td data-label="By" class="whitespace-nowrap">{{ $m->admin->name ?? 'System' }}</td>
                    <td data-label="When" data-sort="{{ $m->created_at->timestamp }}" class="whitespace-nowrap text-right text-gray-500 dark:text-gray-400">
                        <time datetime="{{ $m->created_at->toIso8601String() }}" title="{{ $m->created_at->format('d M Y, H:i') }}">{{ $m->created_at->format('d M Y, H:i') }}</time>
                    </td>
                </tr>
            @endforeach
        </x-admin.data-table>
    </div>
</div>
@endsection
