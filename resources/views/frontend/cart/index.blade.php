@extends('frontend.layout')

@section('title', 'Shopping Cart')

@section('content')
<!-- Page Header -->
<div class="bg-slate-900 py-16 relative overflow-hidden">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-0 right-0 w-96 h-96 bg-primary rounded-full blur-[150px]"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 relative z-10 text-center">
        <h1 class="text-4xl lg:text-5xl font-black text-white tracking-tighter uppercase mb-4 italic">Your Shopping Cart</h1>
        <div class="flex items-center justify-center gap-2 text-slate-400 font-bold text-xs uppercase tracking-widest">
            <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span class="text-white">Cart</span>
        </div>
    </div>
</div>

<section class="py-12 lg:py-24 bg-slate-50 min-h-[600px]">
    <div class="max-w-7xl mx-auto px-4">
        @if(session('success'))
            <div class="mb-8 p-5 bg-green-500 text-white rounded-2xl flex items-center gap-4 shadow-xl shadow-green-500/20 animate-bounce">
                <i class="bi bi-check-circle-fill text-xl"></i>
                <span class="font-black uppercase tracking-widest text-xs">{{ session('success') }}</span>
            </div>
        @endif

        @if(count($cart) > 0)
            <div class="flex flex-col lg:flex-row gap-8 lg:gap-12">
                <!-- Cart Items List -->
                <div class="w-full lg:w-2/3">
                    <div class="space-y-6">
                        @php $total = 0; @endphp
                        @foreach($cart as $id => $item)
                            @php 
                                $subtotal = $item['price'] * $item['quantity']; 
                                $total += $subtotal; 
                            @endphp
                            <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 p-6 lg:p-8 flex flex-col md:flex-row items-center gap-6 lg:gap-10 hover:shadow-xl hover:shadow-slate-200/50 transition-all group">
                                <!-- Image -->
                                <div class="w-24 h-24 lg:w-32 lg:h-32 bg-slate-50 rounded-3xl flex items-center justify-center p-4 border border-slate-100 group-hover:scale-105 transition-transform shrink-0">
                                    <img src="{{ asset('storage/' . $item['image']) }}" 
                                         alt="{{ $item['name'] }}" 
                                         class="max-w-full max-h-full object-contain">
                                </div>

                                <!-- Info -->
                                <div class="flex-1 text-center md:text-left space-y-2">
                                    <span class="text-[10px] font-black uppercase text-primary tracking-widest">Premium Collection</span>
                                    <h4 class="text-lg lg:text-xl font-black text-slate-900 group-hover:text-primary transition-colors tracking-tight leading-tight uppercase italic">{{ $item['name'] }}</h4>
                                    <p class="text-sm font-bold text-slate-400 tracking-tighter">tk. {{ number_format($item['price'], 0) }} / unit</p>
                                </div>

                                <!-- Quantity & Action -->
                                <div class="flex flex-col items-center md:items-end gap-4 shrink-0">
                                    <form action="{{ route('cart.update', $id) }}" method="POST" class="flex items-center bg-slate-50 rounded-2xl p-1 border border-slate-100">
                                        @csrf
                                        <button type="submit" name="quantity" value="{{ $item['quantity'] - 1 }}" class="w-10 h-10 flex items-center justify-center text-slate-400 hover:text-primary transition-colors {{ $item['quantity'] <= 1 ? 'opacity-20 pointer-events-none' : '' }}">
                                            <i class="bi bi-dash-lg"></i>
                                        </button>
                                        <input type="text" value="{{ $item['quantity'] }}" readonly 
                                               class="w-10 bg-transparent border-none text-center font-black text-slate-900 outline-none">
                                        <button type="submit" name="quantity" value="{{ $item['quantity'] + 1 }}" class="w-10 h-10 flex items-center justify-center text-slate-400 hover:text-primary transition-colors">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </form>

                                    <div class="flex items-center gap-6">
                                        <p class="text-lg font-black text-slate-900 tracking-tighter">tk. {{ number_format($subtotal, 0) }}</p>
                                        <form action="{{ route('cart.remove', $id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="w-10 h-10 bg-red-50 text-red-500 rounded-xl flex items-center justify-center hover:bg-red-500 hover:text-white transition-all shadow-sm active:scale-95 border border-red-100">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="mt-10 flex flex-col sm:flex-row justify-between items-center gap-6">
                        <a href="{{ route('products.index') }}" class="flex items-center gap-3 text-slate-500 hover:text-primary font-black uppercase text-[10px] tracking-widest transition-all hover:-translate-x-1">
                            <i class="bi bi-arrow-left text-lg"></i>
                            Return to Store
                        </a>
                        <div class="flex items-center gap-3 w-full sm:w-auto">
                            <input type="text" placeholder="Promo Code" disabled class="flex-1 sm:w-48 bg-white border border-slate-200 rounded-2xl px-6 py-4 text-[10px] font-black uppercase tracking-widest outline-none cursor-not-allowed opacity-50">
                            <button type="button" disabled class="bg-slate-300 text-white px-8 py-4 rounded-2xl font-black uppercase text-[10px] tracking-widest cursor-not-allowed opacity-50">Apply</button>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="w-full lg:w-1/3">
                    <div class="bg-slate-900 text-white rounded-[3rem] p-10 lg:p-12 sticky top-24 shadow-2xl shadow-slate-900/40 relative overflow-hidden">
                        <!-- Decorative bg -->
                        <div class="absolute -top-20 -right-20 w-64 h-64 bg-primary/20 rounded-full blur-[80px]"></div>
                        
                        <h3 class="text-2xl font-black tracking-tighter uppercase mb-10 relative z-10 italic">Order Review</h3>
                        
                        <div class="space-y-6 mb-10 relative z-10">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Merchandise Total</span>
                                <span class="font-bold text-lg tracking-tighter">tk. {{ number_format($total, 0) }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Shipping Estimate</span>
                                <span class="font-black text-green-400 tracking-widest text-[10px]">FREE OF CHARGE</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Tax / VAT</span>
                                <span class="font-bold text-lg tracking-tighter">tk. 0</span>
                            </div>
                            <div class="h-px bg-white/10 my-8"></div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm font-black uppercase tracking-[0.2em] text-primary">Final Payable</span>
                                <span class="text-3xl font-black tracking-tighter">tk. {{ number_format($total, 0) }}</span>
                            </div>
                        </div>

                        <div class="space-y-6 relative z-10">
                            <a href="{{ route('checkout.index') }}" class="block w-full py-6 bg-primary hover:bg-primary-dark text-white text-center font-black uppercase tracking-[0.3em] text-[10px] rounded-3xl shadow-2xl shadow-primary/30 transition-all active:scale-95 no-underline">
                                Confirm & Checkout <i class="bi bi-arrow-right-circle-fill ml-2 text-lg align-middle"></i>
                            </a>
                            <div class="flex items-center justify-center gap-6 opacity-30 mt-8 grayscale">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" class="h-4" alt="Visa">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" class="h-6" alt="Mastercard">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg" class="h-4" alt="PayPal">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="max-w-2xl mx-auto py-24 text-center bg-white rounded-[4rem] shadow-xl shadow-slate-200/50 border border-slate-100 p-12">
                <div class="w-32 h-32 bg-slate-50 rounded-3xl flex items-center justify-center mx-auto mb-10 group rotate-12">
                    <i class="bi bi-cart-x text-6xl text-slate-200 group-hover:text-primary transition-all group-hover:rotate-0"></i>
                </div>
                <h3 class="text-3xl font-black text-slate-900 mb-4 tracking-tighter uppercase italic">Cart is Currently Empty</h3>
                <p class="text-slate-500 font-bold mb-10 px-6 uppercase text-[10px] tracking-widest leading-loose">Secure your next digital upgrade today. Explore our curated collections of premium electronics.</p>
                <a href="{{ route('products.index') }}" class="inline-block px-12 py-5 bg-primary text-white font-black uppercase text-[10px] tracking-widest rounded-2xl shadow-xl shadow-primary/30 transition-all hover:-translate-y-1 active:scale-95 no-underline">
                    Begin Shopping Expedition
                </a>
            </div>
        @endif
    </div>
</section>
@endsection
