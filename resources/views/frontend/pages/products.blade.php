@extends('frontend.layout')

@section('title', 'Shop Electronics | Store Catalog')

@section('content')
<div x-data="{ mobileFiltersOpen: false }" class="relative">
    <!-- Breadcrumbs / Page Header -->
    <div class="bg-slate-900 py-12 sm:py-16 relative overflow-hidden">
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-0 right-0 w-96 h-96 bg-primary rounded-full blur-[150px]"></div>
        </div>
        <div class="max-w-7xl mx-auto px-4 relative z-10 text-center">
            <h1 class="text-3xl sm:text-5xl font-black text-white tracking-tighter uppercase mb-3">Store Catalog</h1>
            <div class="flex items-center justify-center gap-2 text-slate-400 font-bold text-xs uppercase tracking-widest">
                <a href="{{ route('home') }}" class="hover:text-primary transition-colors no-underline">Home</a>
                <i class="bi bi-chevron-right text-[10px]"></i>
                <span class="text-white">Shop</span>
            </div>
        </div>
    </div>

    <section class="pt-10 pb-44 sm:py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
                <!-- Left Sidebar Filter (Desktop Only) -->
                <aside class="hidden lg:block w-full lg:w-1/4">
                    <div class="space-y-8 sticky top-28">
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
                    <!-- Sorting & Filter Bar -->
                    <div class="bg-white p-4 sm:p-6 rounded-2xl sm:rounded-3xl shadow-lg shadow-slate-200/40 mb-6 sm:mb-10 border border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4 sm:gap-6">
                        <div class="text-slate-500 font-bold text-xs sm:text-sm text-center sm:text-left">
                            Showing <span class="text-slate-900">{{ $products->count() }}</span> of <span class="text-slate-900">{{ $products->total() }}</span> results
                        </div>
                        <div class="flex items-center justify-between sm:justify-end gap-4 w-full sm:w-auto">
                            <!-- Mobile Filter Toggle Button -->
                            <button @click="mobileFiltersOpen = true" class="lg:hidden flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-800 px-4 py-3 rounded-xl font-black uppercase text-[10px] tracking-widest transition-all">
                                <i class="bi bi-sliders text-xs"></i> Filters
                            </button>
                            
                            <div class="flex items-center gap-2 sm:gap-4 ml-auto sm:ml-0">
                                <span class="text-[10px] sm:text-xs font-black uppercase text-slate-400 tracking-widest hidden xs:inline">Sort By:</span>
                                <form action="{{ route('products.index') }}" method="GET">
                                     @if(request('category')) <input type="hidden" name="category" value="{{ request('category') }}"> @endif
                                     @if(request('search')) <input type="hidden" name="search" value="{{ request('search') }}"> @endif
                                    <select name="sort" class="bg-slate-50 border-none rounded-xl sm:rounded-2xl px-4 sm:px-6 py-3 sm:py-4 text-xs sm:text-sm font-bold focus:ring-2 focus:ring-primary transition-all outline-none appearance-none cursor-pointer pr-10 relative" onchange="this.form.submit()">
                                        <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest Arrivals</option>
                                        <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                                        <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                                    </select>
                                </form>
                            </div>
                        </div>
                    </div>
                
                    <!-- Product Grid (Optimized for 2 Columns on Mobile, 4 on Desktop) -->
                    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-6 lg:gap-8">
                        @forelse($products as $product)
                            <div class="group bg-white rounded-[20px] sm:rounded-[32px] border border-slate-100 p-3 sm:p-4 hover:shadow-[0_40px_80px_-20px_rgba(0,0,0,0.1)] transition-all lg:hover:-translate-y-2 relative overflow-hidden flex flex-col justify-between">
                                <div class="relative">
                                    @if($product->discount)
                                        <div class="absolute top-2 left-2 sm:top-4 sm:left-4 bg-red-500 text-white text-[8px] sm:text-[10px] font-black px-2 py-0.5 sm:px-3 sm:py-1 rounded-full z-20 shadow-lg shadow-red-500/20">
                                            -{{ intval($product->discount) }}%
                                        </div>
                                    @endif
                                    
                                    <div class="aspect-square bg-slate-50 rounded-[14px] sm:rounded-[24px] mb-3 sm:mb-6 overflow-hidden relative flex items-center justify-center p-4 sm:p-8">
                                        <img src="{{ $product->images->first() ? asset('storage/' . $product->images->first()->image) : 'https://images.unsplash.com/photo-1526733169359-81173747976e?q=80&w=1470&auto=format&fit=crop' }}" 
                                             class="max-w-full max-h-full object-contain lg:group-hover:scale-110 transition-transform duration-700" alt="{{ $product->name }}">
                                        
                                        <!-- Desktop Slide-up Add to Cart -->
                                        <form action="{{ route('cart.add') }}" method="POST" class="absolute bottom-0 left-0 w-full translate-y-full lg:group-hover:translate-y-0 transition-transform duration-300 z-10 p-4 hidden lg:block">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                            <button type="submit" class="w-full py-4 bg-primary hover:bg-primary-dark text-white font-black uppercase text-[10px] tracking-widest rounded-xl transition-all shadow-xl shadow-primary/30 active:scale-95">
                                                Add to Cart
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <div class="text-center px-1">
                                        <span class="text-[8px] sm:text-[10px] font-black uppercase text-slate-400 tracking-widest mb-0.5 sm:mb-1 block">{{ $product->brand ?? 'Premium Brand' }}</span>
                                        <h3 class="text-xs sm:text-sm font-bold text-slate-900 mb-1.5 sm:mb-2 lg:group-hover:text-primary transition-colors line-clamp-2">
                                            <a href="{{ route('products.show', $product->id) }}" class="no-underline text-inherit leading-tight block">{{ $product->name }}</a>
                                        </h3>
                                        <div class="flex justify-center items-center gap-0.5 text-yellow-400 text-[8px] sm:text-[10px] mb-2">
                                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-center mt-auto">
                                    @if ($product->discount)
                                        <div class="flex flex-col items-center gap-0.5 mb-2">
                                            <span class="text-[9px] sm:text-[11px] text-slate-400 line-through font-bold">tk. {{ number_format($product->price, 0) }}</span>
                                            <span class="text-sm sm:text-lg font-black text-red-500 tracking-tighter">tk. {{ number_format($product->discounted_price, 0) }}</span>
                                        </div>
                                    @else
                                        <div class="text-sm sm:text-lg font-black text-primary tracking-tighter mb-2">tk. {{ number_format($product->price, 0) }}</div>
                                    @endif

                                    <!-- Touch-Friendly Add to Cart Button (Mobile Only) -->
                                    <form action="{{ route('cart.add') }}" method="POST" class="lg:hidden w-full">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                        <button type="submit" class="w-full py-2 bg-slate-900 hover:bg-primary text-white font-black uppercase text-[8px] tracking-widest rounded-lg transition-all active:scale-95 flex items-center justify-center gap-1.5">
                                            <i class="bi bi-cart-plus text-xs"></i> Add to Cart
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full py-20 sm:py-32 text-center bg-white rounded-[24px] sm:rounded-[40px] shadow-xl shadow-slate-200/50 border border-slate-100 p-6">
                                <div class="w-16 h-16 sm:w-24 sm:h-24 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-6 sm:mb-8">
                                    <i class="bi bi-search text-2xl sm:text-4xl text-slate-300"></i>
                                </div>
                                <h3 class="text-xl sm:text-2xl font-black text-slate-900 mb-2 sm:mb-4 tracking-tighter">No Products Found</h3>
                                <p class="text-sm text-slate-500 font-bold mb-6 sm:mb-8">Try adjusting your filters or search keywords.</p>
                                <a href="{{ route('products.index') }}" class="inline-block px-6 py-3.5 sm:px-8 sm:py-4 bg-primary text-white font-black uppercase text-xs tracking-widest rounded-xl shadow-lg shadow-primary/30 no-underline">Clear All Filters</a>
                            </div>
                        @endforelse
                    </div>

                    <!-- Pagination Area -->
                    @if ($products->hasPages())
                        <div class="mt-12 sm:mt-16 flex justify-center pb-20 lg:pb-0">
                            <nav class="flex items-center gap-1.5 bg-white p-2.5 rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-100/60" role="navigation" aria-label="Pagination Navigation">
                                {{-- Previous Page Link --}}
                                @if ($products->onFirstPage())
                                    <span class="w-10 h-10 rounded-xl flex items-center justify-center text-slate-300 bg-slate-50 cursor-not-allowed">
                                        <i class="bi bi-chevron-left text-sm"></i>
                                    </span>
                                @else
                                    <a href="{{ $products->previousPageUrl() }}" class="w-10 h-10 rounded-xl flex items-center justify-center text-slate-600 hover:text-white hover:bg-primary transition-all active:scale-95 no-underline">
                                        <i class="bi bi-chevron-left text-sm"></i>
                                    </a>
                                @endif

                                {{-- Pagination Elements --}}
                                @foreach ($products->getUrlRange(max(1, $products->currentPage() - 2), min($products->lastPage(), $products->currentPage() + 2)) as $page => $url)
                                    @if ($page == $products->currentPage())
                                        <span class="w-10 h-10 rounded-xl flex items-center justify-center text-white bg-primary font-black text-xs shadow-lg shadow-primary/30">{{ $page }}</span>
                                    @else
                                        <a href="{{ $url }}" class="w-10 h-10 rounded-xl flex items-center justify-center text-slate-700 hover:text-white hover:bg-primary font-bold text-xs transition-all active:scale-95 no-underline">{{ $page }}</a>
                                    @endif
                                @endforeach

                                {{-- Next Page Link --}}
                                @if ($products->hasMorePages())
                                    <a href="{{ $products->nextPageUrl() }}" class="w-10 h-10 rounded-xl flex items-center justify-center text-slate-600 hover:text-white hover:bg-primary transition-all active:scale-95 no-underline">
                                        <i class="bi bi-chevron-right text-sm"></i>
                                    </a>
                                @else
                                    <span class="w-10 h-10 rounded-xl flex items-center justify-center text-slate-300 bg-slate-50 cursor-not-allowed">
                                        <i class="bi bi-chevron-right text-sm"></i>
                                    </span>
                                @endif
                            </nav>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <!-- Mobile Filters Drawer Overlay & Slide-out Menu -->
    <div x-show="mobileFiltersOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="mobileFiltersOpen = false"
         class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-[100] lg:hidden"
         x-cloak>
    </div>
    
    <div x-show="mobileFiltersOpen"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-300 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="-translate-x-full"
         class="fixed inset-y-0 left-0 w-full max-w-[280px] bg-white shadow-2xl z-[101] flex flex-col lg:hidden p-6 overflow-y-auto"
         x-cloak>
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-base font-black text-slate-900 uppercase italic tracking-tighter">Filter Products</h3>
            <button @click="mobileFiltersOpen = false" class="w-8 h-8 bg-slate-50 shadow-sm rounded-lg flex items-center justify-center text-slate-400 hover:text-red-500 transition-all">
                <i class="bi bi-x-lg text-sm"></i>
            </button>
        </div>
        
        <div class="space-y-6">
            <!-- Mobile Search Widget -->
            <div class="bg-slate-50 p-5 rounded-2xl border border-slate-100">
                <h5 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 bg-primary rounded-full"></span>
                    Quick Search
                </h5>
                <form action="{{ route('products.index') }}" method="GET" class="relative group">
                    @if(request('category'))
                        <input type="hidden" name="category" value="{{ request('category') }}">
                    @endif
                    <input type="text" name="search" 
                           class="w-full bg-white border border-slate-200/60 rounded-xl pl-10 pr-4 py-3 text-xs font-bold focus:ring-2 focus:ring-primary transition-all outline-none" 
                           placeholder="Search..." value="{{ request('search') }}">
                    <i class="bi bi-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                </form>
            </div>

            <!-- Mobile Category Widget -->
            <div class="bg-slate-50 p-5 rounded-2xl border border-slate-100">
                <h5 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 bg-primary rounded-full"></span>
                    Departments
                </h5>
                <ul class="space-y-1.5 p-0 m-0 list-none">
                    <li>
                        <a href="{{ route('products.index') }}" 
                           class="flex items-center justify-between group p-3 rounded-xl transition-all {{ !request('category') ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'hover:bg-white text-slate-600 bg-white/50 border border-slate-100' }} no-underline">
                            <span class="font-bold text-xs">All Products</span>
                            <i class="bi bi-chevron-right text-[8px] opacity-50"></i>
                        </a>
                    </li>
                    @foreach($categories as $category)
                        <li>
                            <a href="{{ route('products.index', ['category' => $category->id]) }}" 
                               class="flex items-center justify-between group p-3 rounded-xl transition-all {{ request('category') == $category->id ? 'bg-primary text-white shadow-lg shadow-primary/30' : 'hover:bg-white text-slate-600 bg-white/50 border border-slate-100' }} no-underline">
                                <span class="font-bold text-xs">{{ $category->name }}</span>
                                <i class="bi bi-chevron-right text-[8px] opacity-50"></i>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Mobile Price Filter -->
            <div class="bg-slate-50 p-5 rounded-2xl border border-slate-100">
                <h5 class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-4 flex items-center gap-2">
                    <span class="w-1.5 h-1.5 bg-primary rounded-full"></span>
                    Price Range
                </h5>
                <form action="{{ route('products.index') }}" method="GET" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="text-[8px] font-black uppercase text-slate-400 ml-1">From</label>
                            <input type="number" name="min_price" class="w-full bg-white border border-slate-200/60 rounded-lg px-3 py-2.5 text-xs font-bold focus:ring-2 focus:ring-primary outline-none" placeholder="Min" value="{{ request('min_price') }}">
                        </div>
                        <div class="space-y-1">
                            <label class="text-[8px] font-black uppercase text-slate-400 ml-1">To</label>
                            <input type="number" name="max_price" class="w-full bg-white border border-slate-200/60 rounded-lg px-3 py-2.5 text-xs font-bold focus:ring-2 focus:ring-primary outline-none" placeholder="Max" value="{{ request('max_price') }}">
                        </div>
                    </div>
                    <button type="submit" class="w-full py-3 bg-slate-900 hover:bg-primary text-white font-black uppercase text-[10px] tracking-widest rounded-lg transition-all active:scale-95">
                        Apply
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
