@extends('frontend.layout')

@section('title', 'Shop Electronics')

@section('content')
<!-- Breadcrumbs / Page Header -->
<div class="bg-slate-900 py-16 relative overflow-hidden">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-0 right-0 w-96 h-96 bg-primary rounded-full blur-[150px]"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 relative z-10 text-center">
        <h1 class="text-4xl lg:text-5xl font-black text-white tracking-tighter uppercase mb-4">Store Catalog</h1>
        <div class="flex items-center justify-center gap-2 text-slate-400 font-bold text-xs uppercase tracking-widest">
            <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span class="text-white">Shop</span>
        </div>
    </div>
</div>

<section class="py-20 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex flex-col lg:flex-row gap-12">
            <!-- Left Sidebar Filter -->
            <aside class="w-full lg:w-1/4">
                <div class="space-y-8 sticky top-24">
                    <!-- Search Widget -->
                    <div class="bg-white p-8 rounded-[32px] shadow-xl shadow-slate-200/50 border border-slate-100">
                        <h5 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-6 flex items-center gap-2">
                            <span class="w-2 h-2 bg-primary rounded-full"></span>
                            Quick Search
                        </h5>
                        <form action="{{ route('products.index') }}" method="GET" class="relative group">
                            @if(request('category'))
                                <input type="hidden" name="category" value="{{ request('category') }}">
                            @endif
                            <input type="text" name="search" 
                                   class="w-full bg-slate-50 border-none rounded-2xl pl-12 pr-4 py-4 text-sm font-bold focus:ring-2 focus:ring-primary transition-all outline-none" 
                                   placeholder="Search products..." value="{{ request('search') }}">
                            <i class="bi bi-search absolute left-5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                        </form>
                    </div>

                    <!-- Category Widget -->
                    <div class="bg-white p-8 rounded-[32px] shadow-xl shadow-slate-200/50 border border-slate-100">
                        <h5 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-6 flex items-center gap-2">
                            <span class="w-2 h-2 bg-primary rounded-full"></span>
                            Departments
                        </h5>
                        <ul class="space-y-2 p-0 m-0 list-none">
                            @foreach($categories as $category)
                                <li>
                                    <a href="{{ route('products.index', ['category' => $category->id]) }}" 
                                       class="flex items-center justify-between group p-4 rounded-2xl transition-all {{ request('category') == $category->id ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'hover:bg-slate-50 text-slate-600' }} no-underline">
                                        <span class="font-bold text-sm">{{ $category->name }}</span>
                                        <div class="w-6 h-6 rounded-lg bg-white/20 flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-all">
                                            <i class="bi bi-chevron-right text-[10px]"></i>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Price Filter -->
                    <div class="bg-white p-8 rounded-[32px] shadow-xl shadow-slate-200/50 border border-slate-100">
                        <h5 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-6 flex items-center gap-2">
                            <span class="w-2 h-2 bg-primary rounded-full"></span>
                            Price Range
                        </h5>
                        <form action="{{ route('products.index') }}" method="GET" class="space-y-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-black uppercase text-slate-400 ml-2">From</label>
                                    <input type="number" name="min_price" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-primary outline-none" placeholder="Min" value="{{ request('min_price') }}">
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-black uppercase text-slate-400 ml-2">To</label>
                                    <input type="number" name="max_price" class="w-full bg-slate-50 border-none rounded-xl px-4 py-3 text-sm font-bold focus:ring-2 focus:ring-primary outline-none" placeholder="Max" value="{{ request('max_price') }}">
                                </div>
                            </div>
                            <button type="submit" class="w-full py-4 bg-slate-900 hover:bg-primary text-white font-black uppercase text-xs tracking-widest rounded-xl transition-all shadow-lg active:scale-95">
                                Filter Results
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            <!-- Product Display Area -->
            <div class="w-full lg:w-3/4">
                <!-- Sorting Bar -->
                <div class="bg-white p-6 rounded-3xl shadow-lg shadow-slate-200/40 mb-10 border border-slate-100 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div class="text-slate-500 font-bold text-sm">
                        Showing <span class="text-slate-900">{{ $products->count() }}</span> of <span class="text-slate-900">{{ $products->total() }}</span> results
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-xs font-black uppercase text-slate-400 tracking-widest">Sort By:</span>
                        <form action="{{ route('products.index') }}" method="GET">
                             @if(request('category')) <input type="hidden" name="category" value="{{ request('category') }}"> @endif
                             @if(request('search')) <input type="hidden" name="search" value="{{ request('search') }}"> @endif
                            <select name="sort" class="bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-primary transition-all outline-none appearance-none cursor-pointer pr-10 relative" onchange="this.form.submit()">
                                <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest Arrivals</option>
                                <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                                <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                            </select>
                        </form>
                    </div>
                </div>
            
                <!-- Product Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                    @forelse($products as $product)
                        <div class="group bg-white rounded-[32px] border border-slate-100 p-4 hover:shadow-[0_40px_80px_-20px_rgba(0,0,0,0.1)] transition-all hover:-translate-y-2 relative overflow-hidden flex flex-col">
                            @if($product->discount)
                                <div class="absolute top-6 right-6 bg-red-500 text-white text-[10px] font-black px-3 py-1 rounded-full z-20 shadow-lg shadow-red-500/20">
                                    -{{ intval($product->discount) }}%
                                </div>
                            @endif
                            
                            <div class="aspect-square bg-slate-50 rounded-[24px] mb-6 overflow-hidden relative flex items-center justify-center p-8">
                                <img src="{{ $product->images->first() ? asset('storage/' . $product->images->first()->image) : 'https://images.unsplash.com/photo-1526733169359-81173747976e?q=80&w=1470&auto=format&fit=crop' }}" 
                                     class="max-w-full max-h-full object-contain group-hover:scale-110 transition-transform duration-700" alt="{{ $product->name }}">
                                
                                <form action="{{ route('cart.add') }}" method="POST" class="absolute bottom-0 left-0 w-full translate-y-full group-hover:translate-y-0 transition-transform duration-300 z-10 p-4">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <button type="submit" class="w-full py-4 bg-primary hover:bg-primary-dark text-white font-black uppercase text-[10px] tracking-widest rounded-xl transition-all shadow-xl shadow-primary/30 active:scale-95">
                                        Add to Cart
                                    </button>
                                </form>
                            </div>
                            
                            <div class="text-center px-2 flex-1 flex flex-col justify-between">
                                <div class="mb-4">
                                    <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-1 block">{{ $product->brand ?? 'Premium Brand' }}</span>
                                    <h3 class="text-sm font-bold text-slate-900 mb-2 group-hover:text-primary transition-colors">
                                        <a href="{{ route('products.show', $product->id) }}" class="no-underline text-inherit leading-tight block">{{ $product->name }}</a>
                                    </h3>
                                    <div class="flex justify-center items-center gap-1 text-yellow-400 text-[10px]">
                                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                                    </div>
                                </div>
                                
                                @if ($product->discount)
                                    <div class="flex flex-col items-center gap-1">
                                        <span class="text-[11px] text-slate-400 line-through font-bold">tk. {{ number_format($product->price, 2) }}</span>
                                        <span class="text-lg font-black text-red-500 tracking-tighter">tk. {{ number_format($product->discounted_price, 2) }}</span>
                                    </div>
                                @else
                                    <div class="text-lg font-black text-primary tracking-tighter">tk. {{ number_format($product->price, 2) }}</div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full py-32 text-center bg-white rounded-[40px] shadow-xl shadow-slate-200/50 border border-slate-100">
                            <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-8">
                                <i class="bi bi-search text-4xl text-slate-300"></i>
                            </div>
                            <h3 class="text-2xl font-black text-slate-900 mb-4 tracking-tighter">No Products Found</h3>
                            <p class="text-slate-500 font-bold mb-8">Try adjusting your filters or search keywords.</p>
                            <a href="{{ route('products.index') }}" class="inline-block px-8 py-4 bg-primary text-white font-black uppercase text-xs tracking-widest rounded-xl shadow-lg shadow-primary/30 no-underline">Clear All Filters</a>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination Area -->
                <div class="mt-16 flex justify-center">
                    <div class="bg-white p-4 rounded-2xl shadow-lg shadow-slate-200/50 border border-slate-100">
                        {{ $products->withQueryString()->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
