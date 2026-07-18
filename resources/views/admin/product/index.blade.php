@extends('admin.layouts')
@section('title', 'Products Management')

@section('content')
<div class="space-y-6">

    {{-- Header & Search --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Products</h1>
            <p class="text-sm text-slate-500 mt-1">Manage your inventory, variants, and monitor stock levels.</p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <form action="{{ route('admin.products.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3 flex-grow lg:min-w-[520px]">
                <div class="relative flex-grow">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search by name, brand, model..."
                        class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-colors">
                </div>
                <select name="stock_status" onchange="this.form.submit()"
                    class="bg-white border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 outline-none cursor-pointer">
                    <option value="">All Inventory</option>
                    <option value="low" {{ request('stock_status') == 'low' ? 'selected' : '' }}>⚠️ Low Stock</option>
                    <option value="out" {{ request('stock_status') == 'out' ? 'selected' : '' }}>🚫 Out of Stock</option>
                </select>
            </form>

            <a href="{{ route('admin.products.create') }}"
                class="bg-primary text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-primary-dark transition-colors flex items-center justify-center gap-2 shadow-sm whitespace-nowrap">
                <i class="bi bi-plus-lg"></i> Add Product
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-2xl flex items-center gap-3">
            <i class="bi bi-check-circle-fill text-emerald-500 text-xl"></i>
            <p class="text-sm font-semibold text-emerald-800">{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-100 p-4 rounded-2xl flex items-center gap-3">
            <i class="bi bi-exclamation-circle-fill text-red-500 text-xl"></i>
            <p class="text-sm font-semibold text-red-800">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Low Stock Warning Banner --}}
    @php
        $lowStockCount = \App\Models\Product::whereHas('variants', fn($q) => $q->where('stock', '>', 0)->whereColumn('stock', '<=', 'products.low_stock_threshold'))->count();
    @endphp
    @if($lowStockCount > 0 && !request('stock_status'))
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 flex items-center gap-3">
            <i class="bi bi-exclamation-triangle-fill text-amber-500 text-xl flex-shrink-0"></i>
            <p class="text-sm font-semibold text-amber-800">
                ⚠️ <strong>{{ $lowStockCount }}</strong> product(s) have low stock variants!
                <a href="{{ route('admin.products.index', ['stock_status' => 'low']) }}" class="underline hover:text-amber-900 ml-1">View them →</a>
            </p>
        </div>
    @endif

    {{-- Products Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider">Product</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider text-center">Category</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider text-center">Variants / Stock</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider text-right">Base Price</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                    @php
                        $totalStock  = $product->variants->sum('stock');
                        $isLow       = $totalStock > 0 && $totalStock <= ($product->low_stock_threshold ?? 5);
                        $isOut       = $totalStock <= 0;
                        $variantCount = $product->variants->count();
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 flex-shrink-0 bg-slate-50 rounded-xl border border-slate-100 overflow-hidden flex items-center justify-center">
                                    @if($product->images->count())
                                        <img src="{{ asset('storage/' . $product->images->first()->image) }}" class="w-full h-full object-cover">
                                    @else
                                        <i class="bi bi-image text-slate-300 text-xl"></i>
                                    @endif
                                </div>
                                <div>
                                    <span class="font-bold text-slate-900 line-clamp-1">
                                        {{ is_array($product->name) ? ($product->name['en'] ?? '') : $product->name }}
                                    </span>
                                    <span class="text-xs text-slate-400 mt-0.5 block">
                                        {{ $product->product_id }}
                                        {{ $product->brand ? '· ' . $product->brand : '' }}
                                    </span>
                                    <div class="flex gap-1 mt-1 flex-wrap">
                                        @if($product->is_flash_deal)
                                            <span class="bg-slate-900 text-white text-[9px] font-black px-2 py-0.5 rounded-md uppercase tracking-tighter">⚡ Flash</span>
                                        @endif

                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-xs font-semibold text-slate-600 bg-slate-100 px-2.5 py-1 rounded-lg">
                                {{ $product->category ? (is_array($product->category->name) ? ($product->category->name['en'] ?? 'N/A') : $product->category->name) : 'N/A' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex flex-col items-center gap-1">
                                @if($variantCount === 0)
                                    <span class="text-xs text-slate-400 italic">No variants</span>
                                @else
                                    <span class="text-xs font-bold text-slate-500">{{ $variantCount }} variant{{ $variantCount > 1 ? 's' : '' }}</span>
                                    <span class="font-black text-base {{ $isOut ? 'text-red-600' : ($isLow ? 'text-amber-600' : 'text-emerald-600') }}">
                                        {{ $totalStock }}
                                    </span>
                                    @if($isOut)
                                        <span class="text-[10px] font-bold text-red-500 bg-red-50 px-2 py-0.5 rounded-full">Out of Stock</span>
                                    @elseif($isLow)
                                        <span class="text-[10px] font-bold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">Low Stock</span>
                                    @endif
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex flex-col items-end">
                                <span class="font-bold text-slate-900">৳{{ number_format($product->discounted_price ?? $product->price, 0) }}</span>
                                @if($product->discount)
                                    <span class="text-xs text-slate-400 line-through">৳{{ number_format($product->price, 0) }}</span>
                                    <span class="text-[10px] text-red-500 font-bold">{{ $product->discount }}% OFF</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end items-center gap-2">
                                <a href="{{ route('admin.products.show', $product) }}"
                                    class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 hover:bg-indigo-600 hover:text-white transition-colors" title="View Comments">
                                    <i class="bi bi-chat-left-text"></i>
                                </a>
                                <a href="{{ route('admin.products.edit', $product) }}"
                                    class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600 hover:bg-primary hover:text-white transition-colors" title="Edit Product">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                                    onsubmit="return confirm('Delete this product and all its variants?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center text-red-600 hover:bg-red-600 hover:text-white transition-colors" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center text-slate-500">
                            <i class="bi bi-box-seam text-5xl block mb-3 text-slate-200"></i>
                            <p class="font-bold text-slate-600">No products found</p>
                            <p class="text-sm mt-1">Start by adding your first product.</p>
                            <a href="{{ route('admin.products.create') }}"
                                class="inline-flex items-center gap-2 mt-4 bg-primary text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-primary-dark transition-all">
                                <i class="bi bi-plus-lg"></i> Add First Product
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($products->hasPages())
        <div class="flex justify-end">
            {{ $products->links() }}
        </div>
    @endif

</div>
@endsection
