@extends('frontend.layout')
@section('title', 'My Wishlist')

@section('content')
<div class="bg-white pt-20 pb-44 min-h-[60vh]">
    <div class="max-w-7xl mx-auto px-4">
        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-16">
            <div class="space-y-2 text-center md:text-left">
                <span class="text-primary font-black uppercase tracking-[0.2em] text-xs">Personal Collection</span>
                <h1 class="text-4xl lg:text-5xl font-black text-slate-900 tracking-tighter uppercase italic">My Wishlist</h1>
                <div class="w-20 h-1.5 bg-primary rounded-full mx-auto md:mx-0"></div>
            </div>
            <a href="{{ route('products.index') }}" class="px-8 py-4 bg-slate-900 text-white font-black uppercase text-[10px] tracking-widest rounded-xl hover:bg-primary transition-all shadow-xl shadow-slate-900/10 active:scale-95 text-center">
                Continue Shopping
            </a>
        </div>

        @if($wishlistItems->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($wishlistItems as $item)
                    @php $product = $item->product; @endphp
                    <div class="group relative bg-white border border-slate-100 rounded-[40px] p-8 hover:shadow-[0_40px_80px_-20px_rgba(0,0,0,0.1)] transition-all duration-700 hover:-translate-y-2">
                        {{-- Delete Btn --}}
                        <form action="{{ route('wishlist.remove', $item->id) }}" method="POST" class="absolute top-8 right-8 z-20">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-10 h-10 bg-red-50 text-red-500 rounded-full flex items-center justify-center hover:bg-red-500 hover:text-white transition-all shadow-sm">
                                <i class="bi bi-x-lg text-sm"></i>
                            </button>
                        </form>

                        {{-- Product Image --}}
                        <div class="relative bg-slate-50 rounded-[32px] aspect-square mb-8 overflow-hidden flex items-center justify-center p-12 group-hover:bg-slate-100/50 transition-colors">
                            <img src="{{ $product->images->first() ? asset('storage/' . $product->images->first()->image) : 'https://images.unsplash.com/photo-1526733169359-81173747976e?q=80&w=1470&auto=format&fit=crop' }}" 
                                 alt="{{ $product->name }}" 
                                 class="max-w-full max-h-full object-contain group-hover:scale-110 transition-transform duration-700">
                        </div>

                        {{-- Info --}}
                        <div class="text-center space-y-4">
                            <span class="text-[10px] font-black uppercase text-slate-400 tracking-[0.2em]">{{ $product->brand ?? 'Premium Edition' }}</span>
                            <h3 class="text-lg font-black text-slate-900 leading-tight group-hover:text-primary transition-colors">
                                <a href="{{ route('products.show', $product->id) }}" class="no-underline text-inherit">{{ $product->name }}</a>
                            </h3>
                            
                            <div class="flex items-center justify-center gap-4">
                                <div class="text-2xl font-black text-slate-900 tracking-tighter italic">tk.{{ number_format($product->discounted_price, 0) }}</div>
                                @if($product->discount > 0)
                                    <span class="text-xs text-slate-400 line-through font-bold">tk.{{ number_format($product->price, 0) }}</span>
                                @endif
                            </div>

                            <div class="pt-4 flex flex-col gap-3">
                                <form action="{{ route('cart.add') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                    <button type="submit" class="w-full bg-primary text-white py-5 rounded-2xl font-black text-[10px] uppercase tracking-[0.2em] shadow-xl shadow-primary/20 hover:bg-slate-900 transition-all active:scale-95 flex items-center justify-center gap-3">
                                        <i class="bi bi-bag-plus-fill text-sm"></i> Move To Cart
                                    </button>
                                </form>
                                <a href="{{ route('products.show', $product->id) }}" class="w-full py-4 bg-slate-50 text-slate-400 font-black text-[10px] uppercase tracking-widest rounded-xl hover:bg-slate-100 hover:text-slate-900 transition-all text-center">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="py-20 text-center space-y-8">
                <div class="w-32 h-32 bg-slate-50 rounded-[3rem] flex items-center justify-center text-slate-200 mx-auto text-5xl shadow-inner">
                    <i class="bi bi-heart"></i>
                </div>
                <div class="space-y-2">
                    <h2 class="text-2xl font-black text-slate-900 uppercase tracking-tight">Your wishlist is empty</h2>
                    <p class="text-slate-400 font-medium italic">Save items you love here and they'll be waiting for you.</p>
                </div>
                <div class="pt-6">
                    <a href="{{ route('products.index') }}" class="px-10 py-5 bg-primary text-white font-black uppercase text-xs tracking-widest rounded-2xl shadow-2xl shadow-primary/30 hover:scale-105 active:scale-95 transition-all inline-block">
                        Start Adding Items
                    </a>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
