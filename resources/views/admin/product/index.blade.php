@extends('admin.layouts')
@section('title', 'Products')
@section('ui-v2', '1')

@section('content')
@php
    $lowStockCount = \App\Models\Product::whereHas('variants', fn ($q) => $q->where('stock', '>', 0)->whereColumn('stock', '<=', 'products.low_stock_threshold'))->count();
    $statuses = ['' => 'All products', 'low' => 'Low stock', 'out' => 'Out of stock', 'archived' => 'Archived'];
@endphp

<x-admin.page-header title="Products" description="Your catalog, variants and stock levels.">
    @can('products.create')
        <x-admin.button variant="secondary" :href="route('admin.products.import')" icon="bi-cloud-upload">Import</x-admin.button>
        <x-admin.button :href="route('admin.products.create')" icon="bi-plus-lg">Add product</x-admin.button>
    @endcan
</x-admin.page-header>

@if($lowStockCount && !$stockStatus)
    <x-admin.alert tone="warning" class="mb-6">
        {{ $lowStockCount }} {{ \Illuminate\Support\Str::plural('product', $lowStockCount) }} {{ $lowStockCount === 1 ? 'has' : 'have' }} variants running low.
        <a href="{{ route('admin.products.index', ['stock_status' => 'low']) }}" class="rounded font-medium underline focus-ring">Review them</a>
    </x-admin.alert>
@endif

<x-admin.data-table :paginator="$products" search="search" search-placeholder="Search name, brand or model…"
    :columns="[
        ['label' => 'Product', 'sortable' => true],
        ['label' => 'Category', 'sortable' => true],
        ['label' => 'Stock', 'sortable' => true],
        ['label' => 'Price', 'sortable' => true, 'align' => 'right'],
        ['label' => 'Actions', 'srOnly' => true, 'align' => 'right'],
    ]"
    :empty-title="$archived ? 'No archived products' : 'No products yet'"
    :empty-description="$archived ? 'Archived products appear here and can be restored.' : 'Add your first product to start selling.'"
    empty-icon="bi-box-seam">
    <x-slot:filters>
        <label for="filter-status" class="sr-only">Stock status</label>
        <select id="filter-status" name="stock_status" onchange="this.form.requestSubmit()"
                class="h-10 rounded-lg border border-gray-300 bg-white pl-3 pr-9 text-sm text-gray-700 shadow-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
            @foreach($statuses as $value => $label)
                <option value="{{ $value }}" @selected((string) $stockStatus === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <label for="filter-brand" class="sr-only">Brand</label>
        <select id="filter-brand" name="brand" onchange="this.form.requestSubmit()"
                class="h-10 rounded-lg border border-gray-300 bg-white pl-3 pr-9 text-sm text-gray-700 shadow-xs focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
            <option value="">All brands</option>
            @foreach($brands as $brand)
                <option value="{{ $brand->id }}" @selected($brandId == $brand->id)>{{ $brand->name }}</option>
            @endforeach
        </select>
    </x-slot:filters>

    @foreach($products as $product)
        @php
            $totalStock = $product->variants->sum('stock');
            $variantCount = $product->variants->count();
            $lowVariants = $product->variants->filter(fn ($v) => $v->stock <= ($product->low_stock_threshold ?? 5));
            $outVariants = $product->variants->filter(fn ($v) => $v->stock <= 0);
            $image = $product->images->first();
            $name = $product->getTranslation('name', 'en');
            $salePrice = $product->discounted_price ?: $product->price;
        @endphp
        <tr @class(['transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/40', 'opacity-70' => $product->trashed()])>
            <td data-primary data-sort="{{ $name }}" class="md:w-full md:max-w-0">
                <div class="flex items-center gap-3">
                    <div class="flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800">
                        @if($image)
                            <img src="{{ asset('storage/' . $image->image) }}" alt="" class="size-full object-cover" loading="lazy">
                        @else
                            <i class="bi bi-image text-gray-300 dark:text-gray-600" aria-hidden="true"></i>
                        @endif
                    </div>
                    <div class="min-w-0">
                        @if($product->trashed())
                            <span class="block truncate font-medium text-gray-900 dark:text-white">{{ $name }}</span>
                        @else
                            <a href="{{ auth()->user()->can('products.edit') ? route('admin.products.edit', $product) : route('admin.products.show', $product) }}" class="block truncate rounded font-medium text-gray-900 hover:text-brand-600 focus-ring dark:text-white dark:hover:text-brand-400">{{ $name }}</a>
                        @endif
                        <span class="block truncate text-xs font-normal text-gray-500 dark:text-gray-400">
                            {{ $product->product_id }}@if($product->brand) · {{ $product->brand }}@endif
                        </span>
                        <span class="mt-1 flex flex-wrap gap-1">
                            @if($product->trashed())<x-admin.status-badge status="archived" />@endif
                            @if($product->is_flash_deal)<x-admin.status-badge tone="indigo" label="Flash deal" :dot="false" />@endif
                            @if($product->rating_count)<x-admin.status-badge tone="gray" :label="number_format($product->rating_avg, 1) . ' ★'" :dot="false" />@endif
                        </span>
                    </div>
                </div>
            </td>
            <td data-label="Category" class="whitespace-nowrap">{{ $product->category?->getTranslation('name', 'en') ?? '—' }}</td>
            <td data-label="Stock" data-sort="{{ $totalStock }}">
                @if($variantCount === 0)
                    <span class="text-sm text-gray-400 dark:text-gray-500">Not tracked</span>
                @else
                    <div class="flex flex-col items-end gap-1 md:items-start">
                        <span class="font-medium text-gray-900 tabular-nums dark:text-white">{{ number_format($totalStock) }} <span class="font-normal text-gray-500 dark:text-gray-400">in {{ $variantCount }} {{ \Illuminate\Support\Str::plural('variant', $variantCount) }}</span></span>
                        @if($outVariants->count() === $variantCount)
                            <x-admin.status-badge status="out of stock" />
                        @elseif($outVariants->count())
                            <x-admin.status-badge tone="red" :label="$outVariants->count() . ' out of stock'" />
                        @elseif($lowVariants->count())
                            <x-admin.status-badge status="low stock" :label="$lowVariants->count() . ' low'" />
                        @else
                            <x-admin.status-badge status="in stock" />
                        @endif
                    </div>
                @endif
            </td>
            <td data-label="Price" data-sort="{{ $salePrice }}" class="whitespace-nowrap text-right">
                <span class="font-medium text-gray-900 tabular-nums dark:text-white">Tk {{ number_format($salePrice, 2) }}</span>
                @if($product->discount > 0)
                    <span class="block text-xs text-gray-500 dark:text-gray-400"><span class="line-through">Tk {{ number_format($product->price, 2) }}</span> · {{ rtrim(rtrim(number_format($product->discount, 2), '0'), '.') }}% off</span>
                @endif
            </td>
            <td class="whitespace-nowrap text-right">
                <div class="inline-flex items-center gap-1">
                    @if($product->trashed())
                        @can('products.edit')
                            <form method="POST" action="{{ route('admin.products.restore', $product->id) }}">
                                @csrf @method('PATCH')
                                <x-admin.button type="submit" variant="secondary" size="sm" icon="bi-arrow-counterclockwise">Restore</x-admin.button>
                            </form>
                        @endcan
                    @else
                        <x-admin.button variant="ghost" size="sm" :href="route('admin.products.show', $product)" icon="bi-chat-left-text" title="Reviews" aria-label="Reviews for {{ $name }}" />
                        @can('products.edit')
                            <x-admin.button variant="ghost" size="sm" :href="route('admin.products.edit', $product)" icon="bi-pencil" title="Edit" aria-label="Edit {{ $name }}" />
                        @endcan
                        @can('products.delete')
                            <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                                  data-confirm="It will be hidden from the store. Images, variants and stock history are kept, and you can restore it from the Archived filter."
                                  data-confirm-title="Archive {{ $name }}?" data-confirm-button="Archive">
                                @csrf @method('DELETE')
                                <x-admin.button type="submit" variant="ghost" size="sm" icon="bi-archive" class="text-red-600 dark:text-red-400" :loading="false" title="Archive" aria-label="Archive {{ $name }}" />
                            </form>
                        @endcan
                    @endif
                </div>
            </td>
        </tr>
    @endforeach
</x-admin.data-table>
@endsection
