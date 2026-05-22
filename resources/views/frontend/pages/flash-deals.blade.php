@extends('frontend.layout')

@section('title', 'Discount Products | Premium Electronics Store')

@section('content')
<section class="pt-24 pb-44 sm:py-24 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-16 space-y-4">
            <span class="text-red-500 font-black uppercase tracking-[0.2em] text-xs">Don't Miss Out</span>
            <h2 class="text-4xl lg:text-5xl font-black text-slate-900 tracking-tighter uppercase italic">Discount Products</h2>
            <div class="w-20 h-1.5 bg-red-500 rounded-full mx-auto"></div>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            @forelse($products as $product)
                @include('frontend.partials.product-card', ['product' => $product])
            @empty
                <div class="col-span-full py-20 text-center opacity-20">
                    <i class="bi bi-tag text-6xl"></i>
                    <p class="mt-4 font-black uppercase tracking-widest text-xs">No Discount Products Available Right Now</p>
                </div>
            @endforelse
        </div>
        
        {{-- Custom Professional Pagination --}}
        @if ($products->hasPages())
            <div class="mt-12 flex justify-center pb-20 lg:pb-0">
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
</section>
@endsection
