@extends('frontend.layout')

@section('title', 'Flash Deals | Premium Electronics Store')

@section('content')
<section class="py-24 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center mb-16 space-y-4">
            <span class="text-red-500 font-black uppercase tracking-[0.2em] text-xs">Don't Miss Out</span>
            <h2 class="text-4xl lg:text-5xl font-black text-slate-900 tracking-tighter uppercase italic">Flash Deals</h2>
            <div class="w-20 h-1.5 bg-red-500 rounded-full mx-auto"></div>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            @forelse($products as $product)
                @include('frontend.partials.product-card', ['product' => $product])
            @empty
                <div class="col-span-full py-20 text-center opacity-20">
                    <i class="bi bi-tag text-6xl"></i>
                    <p class="mt-4 font-black uppercase tracking-widest text-xs">No Flash Deals Available Right Now</p>
                </div>
            @endforelse
        </div>
        
        <div class="mt-12 flex justify-center">
            {{ $products->links() }}
        </div>
    </div>
</section>
@endsection
