@extends('frontend.layout')

@section('title', 'Secure Checkout')

@section('content')
<!-- Page Header -->
<div class="bg-slate-900 py-16 relative overflow-hidden">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-0 right-0 w-96 h-96 bg-primary rounded-full blur-[150px]"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 relative z-10 text-center">
        <h1 class="text-4xl lg:text-5xl font-black text-white tracking-tighter uppercase mb-4">Secure Checkout</h1>
        <div class="flex items-center justify-center gap-2 text-slate-400 font-bold text-xs uppercase tracking-widest">
            <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <a href="{{ route('cart.index') }}" class="hover:text-primary transition-colors">Cart</a>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span class="text-white">Checkout</span>
        </div>
    </div>
</div>

<section class="py-24 bg-slate-50">
    <div class="max-w-7xl mx-auto px-4">
        @if(session('error'))
            <div class="mb-8 p-4 bg-red-50 border border-red-100 text-red-700 rounded-2xl flex items-center gap-3 animate-fade-in-down">
                <i class="bi bi-exclamation-triangle-fill text-xl"></i>
                <span class="font-bold text-sm">{{ session('error') }}</span>
            </div>
        @endif

        <form action="{{ route('checkout.placeOrder') }}" method="POST" id="checkoutForm">
            @csrf
            <div class="flex flex-col lg:flex-row gap-12">
                <!-- Shipping & Billing Details -->
                <div class="w-full lg:w-2/3 space-y-10">
                    <div class="bg-white rounded-[40px] shadow-xl shadow-slate-200/50 border border-slate-100 p-10 lg:p-12">
                        <h3 class="text-2xl font-black text-slate-900 tracking-tighter uppercase mb-10 flex items-center gap-4">
                            <span class="w-10 h-10 bg-primary/10 text-primary rounded-xl flex items-center justify-center text-lg"><i class="bi bi-geo-alt-fill"></i></span>
                            Shipping Details
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-2">
                                <label for="name" class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">Full Name</label>
                                <input type="text" name="name" id="name" value="{{ old('name', Auth::user()->name ?? '') }}" 
                                       class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none" 
                                       placeholder="Enter your full name" required>
                                @error('name') <p class="text-[10px] text-red-500 font-bold mt-1 ml-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-2">
                                <label for="email" class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">Email Address</label>
                                <input type="email" name="email" id="email" value="{{ old('email', Auth::user()->email ?? '') }}" 
                                       class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none" 
                                       placeholder="you@example.com" required>
                                @error('email') <p class="text-[10px] text-red-500 font-bold mt-1 ml-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-2">
                                <label for="phone" class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">Phone Number <span class="text-red-500">*</span></label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone', Auth::user()->phone ?? '') }}" 
                                       class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none" 
                                       placeholder="e.g., 01700000000" required>
                                @error('phone') <p class="text-[10px] text-red-500 font-bold mt-1 ml-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-2">
                                <label for="alt_phone" class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">Alt. Phone <span class="text-[8px] text-slate-300">(Optional)</span></label>
                                <input type="text" name="alt_phone" id="alt_phone" value="{{ old('alt_phone') }}" 
                                       class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none" 
                                       placeholder="Backup number for delivery">
                                @error('alt_phone') <p class="text-[10px] text-red-500 font-bold mt-1 ml-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="space-y-2 col-span-full">
                                <label for="city" class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">Delivery City <span class="text-red-500">*</span></label>
                                <select name="city" id="city" 
                                        class="w-full bg-slate-50 border-none rounded-2xl px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none appearance-none cursor-pointer" required>
                                    <option value="Dhaka" {{ old('city') == 'Dhaka' ? 'selected' : '' }}>Dhaka (Within City)</option>
                                    <option value="Other" {{ old('city') == 'Other' ? 'selected' : '' }}>Outside Dhaka</option>
                                </select>
                                @error('city') <p class="text-[10px] text-red-500 font-bold mt-1 ml-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="col-span-full space-y-2">
                                <label for="shipping_address" class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">Full Delivery Address <span class="text-red-500">*</span></label>
                                <textarea name="shipping_address" id="shipping_address" rows="2" 
                                          class="w-full bg-slate-50 border-none rounded-3xl px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none resize-none" 
                                          placeholder="Apt, Street, Area..." required>{{ old('shipping_address') }}</textarea>
                                @error('shipping_address') <p class="text-[10px] text-red-500 font-bold mt-1 ml-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="col-span-full space-y-2">
                                <label for="order_notes" class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">Order Notes <span class="text-[8px] text-slate-300">(Optional)</span></label>
                                <textarea name="order_notes" id="order_notes" rows="2" 
                                          class="w-full bg-slate-50 border-none rounded-3xl px-6 py-4 text-sm font-bold focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all outline-none resize-none" 
                                          placeholder="Special instructions, unboxing requests, or delivery preferences...">{{ old('order_notes') }}</textarea>
                                @error('order_notes') <p class="text-[10px] text-red-500 font-bold mt-1 ml-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-[40px] shadow-xl shadow-slate-200/50 border border-slate-100 p-10 lg:p-12">
                        <h3 class="text-2xl font-black text-slate-900 tracking-tighter uppercase mb-10 flex items-center gap-4">
                            <span class="w-10 h-10 bg-primary/10 text-primary rounded-xl flex items-center justify-center text-lg"><i class="bi bi-credit-card-fill"></i></span>
                            Payment Method
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <label class="relative group cursor-pointer">
                                <input type="radio" name="payment_method" value="cod" class="peer hidden" {{ old('payment_method', 'cod') == 'cod' ? 'checked' : '' }}>
                                <div class="p-8 bg-slate-50 rounded-3xl border-2 border-transparent peer-checked:border-primary peer-checked:bg-primary/5 group-hover:bg-slate-100 transition-all flex flex-col items-center text-center gap-4">
                                    <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center text-3xl text-slate-400 peer-checked:text-primary shadow-sm">
                                        <i class="bi bi-wallet2"></i>
                                    </div>
                                    <div>
                                        <h5 class="text-sm font-black text-slate-900">Cash on Delivery</h5>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Pay when you receive</p>
                                    </div>
                                </div>
                            </label>

                            <label class="relative group cursor-pointer">
                                <input type="radio" name="payment_method" value="online" class="peer hidden" {{ old('payment_method') == 'online' ? 'checked' : '' }}>
                                <div class="p-8 bg-slate-50 rounded-3xl border-2 border-transparent peer-checked:border-primary peer-checked:bg-primary/5 group-hover:bg-slate-100 transition-all flex flex-col items-center text-center gap-4">
                                    <div class="w-16 h-16 bg-white rounded-2xl flex items-center justify-center text-3xl text-slate-400 peer-checked:text-primary shadow-sm">
                                        <i class="bi bi-shield-check"></i>
                                    </div>
                                    <div>
                                        <h5 class="text-sm font-black text-slate-900">Online Payment</h5>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">SSLCommerz Secure</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Order Sidebar -->
                <div class="w-full lg:w-1/3">
                    <div class="bg-slate-900 text-white rounded-[48px] p-10 sticky top-24 shadow-2xl shadow-slate-900/50">
                        <h3 class="text-2xl font-black tracking-tighter uppercase mb-10 flex items-center justify-between">
                            Order Summary
                            <span class="text-[10px] bg-primary text-white px-3 py-1 rounded-full">{{ count(session('cart', [])) }} items</span>
                        </h3>

                        <div class="space-y-6 mb-10 max-h-[300px] overflow-y-auto pr-2 no-scrollbar">
                            @foreach(session('cart', []) as $item)
                                <div class="flex items-center gap-4 group">
                                    <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center p-2 shrink-0 border border-white/5">
                                        <img src="{{ asset('storage/' . $item['image']) }}" alt="{{ $item['name'] }}" class="max-w-full max-h-full object-contain group-hover:scale-110 transition-transform">
                                    </div>
                                    <div class="flex-grow min-w-0">
                                        <h5 class="text-xs font-bold text-white truncate leading-tight">{{ $item['name'] }}</h5>
                                        <p class="text-[10px] text-slate-400 font-black uppercase mt-1">Qty: {{ $item['quantity'] }} × {{ number_format($item['price'], 2) }}</p>
                                    </div>
                                    <div class="text-sm font-black text-primary tracking-tighter">tk. {{ number_format($item['price'] * $item['quantity'], 2) }}</div>
                                </div>
                            @endforeach
                        </div>

                        @php 
                            $cart = session()->get('cart', []);
                            $subtotal = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);
                            $selectedCity = old('city', 'Dhaka');
                            
                            $dhakaCharge = (float) \App\Models\Setting::get('shipping_charge_dhaka', 80);
                            $outsideCharge = (float) \App\Models\Setting::get('shipping_charge_outside', 110);
                            
                            $deliveryCharge = ($selectedCity == 'Dhaka') ? $dhakaCharge : $outsideCharge;
                            $grandTotal = $subtotal + $deliveryCharge;
                        @endphp

                        <div class="space-y-4 mb-10 pt-10 border-t border-white/10">
                            <div class="flex justify-between items-center text-slate-400">
                                <span class="text-[10px] font-black uppercase tracking-widest">Subtotal</span>
                                <span class="text-sm font-bold text-white">tk. {{ number_format($subtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-slate-400">
                                <span class="text-[10px] font-black uppercase tracking-widest">Delivery Charge</span>
                                <span class="text-sm font-bold text-white" id="deliveryChargeDisplay">tk. {{ number_format($deliveryCharge, 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center pt-4">
                                <span class="text-sm font-black uppercase tracking-widest text-primary">Final Total</span>
                                <span class="text-2xl font-black tracking-tighter" id="grandTotalDisplay">tk. {{ number_format($grandTotal, 2) }}</span>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-6 bg-primary hover:bg-primary-dark text-white font-black uppercase tracking-[0.2em] text-xs rounded-3xl shadow-xl shadow-primary/30 transition-all active:scale-95 group">
                            Confirm Order
                            <i class="bi bi-lock-fill ml-2 group-hover:rotate-12 transition-transform"></i>
                        </button>
                        
                        <p class="mt-8 text-[9px] text-center text-slate-500 font-bold uppercase tracking-widest px-4 leading-relaxed">
                            By placing an order, you agree to our Terms of Service and Privacy Policy.
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const citySelect = document.getElementById('city');
        const deliveryDisplay = document.getElementById('deliveryChargeDisplay');
        const totalDisplay = document.getElementById('grandTotalDisplay');
        const subtotal = {{ $subtotal }};
        const dhakaCharge = {{ $dhakaCharge }};
        const outsideCharge = {{ $outsideCharge }};

        citySelect.addEventListener('change', function() {
            let charge = this.value === 'Dhaka' ? dhakaCharge : outsideCharge;
            deliveryDisplay.textContent = 'tk. ' + charge.toLocaleString(undefined, {minimumFractionDigits: 2});
            totalDisplay.textContent = 'tk. ' + (subtotal + charge).toLocaleString(undefined, {minimumFractionDigits: 2});
            
            // Add a small animation effect
            totalDisplay.classList.add('animate-pulse', 'text-white');
            setTimeout(() => totalDisplay.classList.remove('animate-pulse', 'text-white'), 500);
        });
    });
</script>
@endsection
