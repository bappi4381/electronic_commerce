@extends('admin.layouts')
@section('title', 'Products Management')

@section('content')
<div class="space-y-6">
    {{-- Header & Search --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Products</h1>
            <p class="text-sm text-slate-500 mt-1">Manage your inventory and monitor stock levels</p>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <form action="{{ route('admin.products.index') }}" method="GET" class="relative flex-grow sm:min-w-[300px]">
                <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Search products..." 
                       class="w-full bg-white border border-slate-200 rounded-lg pl-10 pr-10 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-colors">
                @if(request('search'))
                    <a href="{{ route('admin.products.index') }}" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-red-500 transition-colors">
                        <i class="bi bi-x-circle-fill"></i>
                    </a>
                @endif
            </form>
            <a href="{{ route('admin.products.create') }}" class="bg-primary text-white px-5 py-2.5 rounded-lg font-medium text-sm hover:bg-primary-dark transition-colors flex items-center justify-center gap-2 shadow-sm whitespace-nowrap">
                <i class="bi bi-plus-lg"></i> Add Product
            </a>
        </div>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-lg flex items-center gap-3">
            <i class="bi bi-check-circle-fill text-emerald-500 text-xl"></i>
            <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Products Table --}}
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 font-semibold text-slate-600">ID</th>
                        <th class="px-6 py-4 font-semibold text-slate-600">Product Info</th>
                        <th class="px-6 py-4 font-semibold text-slate-600 text-center">Category</th>
                        <th class="px-6 py-4 font-semibold text-slate-600 text-center">Stock</th>
                        <th class="px-6 py-4 font-semibold text-slate-600 text-right">Price</th>
                        <th class="px-6 py-4 font-semibold text-slate-600 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-6 py-4 text-slate-500">
                                #{{ $product->id }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 flex-shrink-0 bg-slate-50 rounded-lg border border-slate-100 overflow-hidden flex items-center justify-center">
                                        @if($product->images->count())
                                            <img src="{{ asset('storage/' . $product->images->first()->image) }}" class="w-full h-full object-contain p-1">
                                        @else
                                            <i class="bi bi-image text-slate-300"></i>
                                        @endif
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-slate-900 line-clamp-1">{{ $product->name }}</span>
                                        <span class="text-xs text-slate-500 mt-0.5">{{ $product->brand }} {{ $product->model ? '| ' . $product->model : '' }}</span>
                                        @if($product->discount)
                                            <span class="inline-block mt-1 bg-red-50 text-red-600 text-xs font-medium px-2 py-0.5 rounded-full w-max border border-red-100">
                                                {{ $product->discount }}% OFF
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-xs font-medium text-slate-600 bg-slate-100 px-2.5 py-1 rounded-md">{{ $product->category->name ?? 'N/A' }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php $isLow = $product->stock < 5; @endphp
                                <div class="flex flex-col items-center">
                                    <span class="font-semibold {{ $isLow ? 'text-red-600' : 'text-slate-900' }}">{{ $product->stock ?? 0 }}</span>
                                    @if($isLow)
                                        <span class="text-[10px] text-red-500 font-medium">Low Stock</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex flex-col items-end">
                                    <span class="font-semibold text-slate-900">৳ {{ number_format($product->price, 0) }}</span>
                                    @if($product->discount)
                                        <span class="text-xs text-slate-400 line-through">৳ {{ number_format($product->price * (1 + $product->discount/100), 0) }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end items-center gap-2">
                                    <a href="{{ route('admin.products.show', $product) }}" class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 hover:bg-indigo-600 hover:text-white transition-colors" title="Moderate Discussions">
                                        <i class="bi bi-chat-left-text"></i>
                                    </a>
                                    <a href="{{ route('admin.products.edit', $product) }}" class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600 hover:bg-primary hover:text-white transition-colors" title="Edit Product">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg bg-red-50 flex items-center justify-center text-red-600 hover:bg-red-600 hover:text-white transition-colors" title="Delete Product">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                <i class="bi bi-box-seam text-4xl mb-3 block text-slate-300"></i>
                                <p class="font-medium text-slate-600">No products found</p>
                                <p class="text-sm mt-1">Start by adding a new product to your inventory.</p>
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
