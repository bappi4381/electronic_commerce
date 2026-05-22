@extends('frontend.layout')

@section('title', 'Order Confirmed')

@section('content')
<!-- Page Header -->
<div class="bg-slate-900 py-16 relative overflow-hidden">
    <div class="absolute inset-0 opacity-20">
        <div class="absolute top-0 right-0 w-96 h-96 bg-primary rounded-full blur-[150px]"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 relative z-10 text-center">
        <h1 class="text-4xl lg:text-5xl font-black text-white tracking-tighter uppercase mb-4">Order Confirmed</h1>
        <div class="flex items-center justify-center gap-2 text-slate-400 font-bold text-xs uppercase tracking-widest">
            <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
            <i class="bi bi-chevron-right text-[10px]"></i>
            <span class="text-white">Success</span>
        </div>
    </div>
</div>

<section class="pt-24 pb-44 bg-slate-50">
    <div class="max-w-5xl mx-auto px-4">
        <!-- Success Banner Card -->
        <div class="bg-white rounded-[40px] shadow-xl shadow-slate-200/50 border border-slate-100 p-8 lg:p-12 text-center mb-12 relative overflow-hidden">
            <!-- Decorative light glow -->
            <div class="absolute -top-24 -left-24 w-48 h-48 bg-emerald-500/10 rounded-full blur-[80px]"></div>
            <div class="absolute -bottom-24 -right-24 w-48 h-48 bg-primary/10 rounded-full blur-[80px]"></div>

            <!-- Success Icon with animation -->
            <div class="inline-flex items-center justify-center w-24 h-24 bg-emerald-50 rounded-full mb-6 border border-emerald-100 shadow-inner group">
                <i class="bi bi-check-circle-fill text-5xl text-emerald-500 animate-pulse"></i>
            </div>
            
            <h2 class="text-3xl lg:text-4xl font-black text-slate-900 tracking-tighter uppercase mb-3">Order Placed Successfully!</h2>
            <p class="text-slate-500 text-sm max-w-xl mx-auto mb-8">
                Thank you for your order. We've sent a confirmation email to <span class="font-bold text-slate-700">{{ $order->user->email }}</span> with all details and your invoice receipt.
            </p>
            
            <!-- Order Info Badges -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 p-6 bg-slate-50 rounded-3xl border border-slate-100 max-w-3xl mx-auto">
                <div class="text-center md:border-r border-slate-200/60 last:border-0 py-2">
                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest block mb-1">Order ID</span>
                    <span class="text-xs font-black text-slate-900 uppercase tracking-tighter flex items-center justify-center gap-1.5">
                        {{ $order->order_id }}
                        <button onclick="navigator.clipboard.writeText('{{ $order->order_id }}'); alert('Order ID copied to clipboard!')" class="text-slate-400 hover:text-primary transition-colors text-[10px]" title="Copy Order ID">
                            <i class="bi bi-copy"></i>
                        </button>
                    </span>
                </div>
                <div class="text-center md:border-r border-slate-200/60 last:border-0 py-2">
                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest block mb-1">Date</span>
                    <span class="text-xs font-bold text-slate-900">{{ $order->created_at->format('M d, Y') }}</span>
                </div>
                <div class="text-center md:border-r border-slate-200/60 last:border-0 py-2">
                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest block mb-1">Total Amount</span>
                    <span class="text-xs font-black text-primary">{{ number_format($order->total_price, 2) }} Tk</span>
                </div>
                <div class="text-center py-2">
                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest block mb-1">Payment Method</span>
                    <span class="text-xs font-bold text-slate-900 uppercase tracking-tighter">{{ $order->payment_method === 'cod' ? 'COD' : 'Online Pay' }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Info Block: Delivery Info & Purchased Items -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Delivery Info Card -->
                <div class="bg-white rounded-[32px] shadow-xl shadow-slate-200/50 border border-slate-100 p-8 relative overflow-hidden">
                    <h3 class="text-lg font-black text-slate-900 tracking-tighter uppercase mb-6 flex items-center gap-3">
                        <span class="w-8 h-8 bg-primary/10 text-primary rounded-lg flex items-center justify-center text-sm"><i class="bi bi-geo-alt-fill"></i></span>
                        Delivery Details
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
                        <div>
                            <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest block mb-1">Recipient Name</span>
                            <span class="font-bold text-slate-800">{{ $order->user->name }}</span>
                        </div>
                        <div>
                            <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest block mb-1">Phone Number</span>
                            <span class="font-bold text-slate-800">{{ $order->user->phone }}</span>
                        </div>
                        @if($order->alt_phone)
                        <div>
                            <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest block mb-1">Alternate Phone</span>
                            <span class="font-bold text-slate-800">{{ $order->alt_phone }}</span>
                        </div>
                        @endif
                        <div class="sm:col-span-2">
                            <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest block mb-1">Shipping Address</span>
                            <span class="font-semibold text-slate-700 leading-relaxed">{{ $order->shipping_address }}, {{ $order->city }}</span>
                        </div>
                        @if($order->order_notes)
                        <div class="sm:col-span-2">
                            <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest block mb-1">Order Notes</span>
                            <span class="text-xs text-slate-500 italic leading-relaxed bg-slate-50 p-4 rounded-2xl block border border-slate-100">{{ $order->order_notes }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Purchased Items Card -->
                <div class="bg-white rounded-[32px] shadow-xl shadow-slate-200/50 border border-slate-100 p-8">
                    <h3 class="text-lg font-black text-slate-900 tracking-tighter uppercase mb-6 flex items-center gap-3">
                        <span class="w-8 h-8 bg-primary/10 text-primary rounded-lg flex items-center justify-center text-sm"><i class="bi bi-basket3-fill"></i></span>
                        Order Items
                    </h3>
                    <div class="divide-y divide-slate-100">
                        @foreach($order->orderItems as $item)
                            <div class="flex items-center gap-4 py-5 first:pt-0 last:pb-0 group">
                                <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center p-2 border border-slate-100 shrink-0">
                                    <img src="{{ $item->product && $item->product->images->first() ? asset('storage/' . $item->product->images->first()->image) : 'https://images.unsplash.com/photo-1526733169359-81173747976e?q=80&w=1470&auto=format&fit=crop' }}" 
                                         alt="{{ $item->product->name ?? 'Product' }}" class="max-w-full max-h-full object-contain group-hover:scale-110 transition-transform duration-300">
                                </div>
                                <div class="flex-grow min-w-0">
                                    <h5 class="text-xs font-bold text-slate-800 truncate leading-snug group-hover:text-primary transition-colors">
                                        {{ $item->product->name ?? 'Unknown Product' }}
                                    </h5>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">
                                        @if($item->product && $item->product->brand)
                                            Brand: {{ $item->product->brand }}
                                        @endif
                                        @if($item->product && $item->product->storage)
                                            | {{ $item->product->storage }}
                                        @endif
                                    </p>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="text-xs font-black text-slate-900">{{ number_format($item->price, 2) }} Tk</div>
                                    <div class="text-[10px] text-slate-400 font-semibold uppercase mt-0.5">Qty: {{ $item->quantity }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Small Totals Section -->
                    <div class="mt-6 pt-6 border-t border-slate-100 space-y-3">
                        <div class="flex justify-between items-center text-xs text-slate-400 font-medium">
                            <span>Subtotal</span>
                            <span class="font-bold text-slate-700">{{ number_format($order->total_price - $order->delivery_charge, 2) }} Tk</span>
                        </div>
                        <div class="flex justify-between items-center text-xs text-slate-400 font-medium">
                            <span>Delivery Charge</span>
                            <span class="font-bold text-slate-700">{{ number_format($order->delivery_charge, 2) }} Tk</span>
                        </div>
                        <div class="flex justify-between items-center pt-3 border-t border-dashed border-slate-200">
                            <span class="text-sm font-black text-slate-900 uppercase tracking-tighter">Grand Total</span>
                            <span class="text-lg font-black text-primary tracking-tighter">{{ number_format($order->total_price, 2) }} Tk</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Info Block: Stepper & Navigation -->
            <div class="space-y-8">
                <!-- Stepper Progress Tracker Card -->
                <div class="bg-white rounded-[32px] shadow-xl shadow-slate-200/50 border border-slate-100 p-8">
                    <h3 class="text-lg font-black text-slate-900 tracking-tighter uppercase mb-6 flex items-center gap-3">
                        <span class="w-8 h-8 bg-primary/10 text-primary rounded-lg flex items-center justify-center text-sm"><i class="bi bi-truck-flatbed"></i></span>
                        Order Status
                    </h3>
                    
                    <!-- Stepper Timeline -->
                    <div class="space-y-6 py-2">
                        <!-- Step 1: Placed -->
                        <div class="flex items-start gap-4">
                            <div class="flex flex-col items-center shrink-0">
                                <div class="w-8 h-8 rounded-full bg-emerald-500 ring-4 ring-emerald-50 flex items-center justify-center text-white">
                                    <i class="bi bi-check text-base"></i>
                                </div>
                                <div class="w-0.5 h-10 bg-emerald-200 mt-2"></div>
                            </div>
                            <div class="pt-1">
                                <h4 class="text-xs font-black text-slate-900 uppercase tracking-tight">Order Placed</h4>
                                <p class="text-[10px] text-slate-400 font-medium mt-0.5">We've received your order.</p>
                            </div>
                        </div>

                        <!-- Step 2: Processing -->
                        @php
                            $isProcessing = in_array($order->status, ['processing', 'shipped', 'completed', 'delivered']);
                        @endphp
                        <div class="flex items-start gap-4">
                            <div class="flex flex-col items-center shrink-0">
                                <div class="w-8 h-8 rounded-full {{ $isProcessing ? 'bg-primary ring-4 ring-primary-light/30 text-white' : 'bg-slate-200 text-slate-400' }} flex items-center justify-center">
                                    @if($isProcessing)
                                        <i class="bi bi-check text-base"></i>
                                    @else
                                        <span class="text-xs font-bold">2</span>
                                    @endif
                                </div>
                                <div class="w-0.5 h-10 {{ $isProcessing ? 'bg-primary/30' : 'bg-slate-200' }} mt-2"></div>
                            </div>
                            <div class="pt-1">
                                <h4 class="text-xs font-black {{ $isProcessing ? 'text-slate-900' : 'text-slate-400' }} uppercase tracking-tight">Processing</h4>
                                <p class="text-[10px] text-slate-400 font-medium mt-0.5">Quality check and packaging.</p>
                            </div>
                        </div>

                        <!-- Step 3: Shipped -->
                        @php
                            $isShipped = in_array($order->status, ['shipped', 'completed', 'delivered']);
                        @endphp
                        <div class="flex items-start gap-4">
                            <div class="flex flex-col items-center shrink-0">
                                <div class="w-8 h-8 rounded-full {{ $isShipped ? 'bg-primary ring-4 ring-primary-light/30 text-white' : 'bg-slate-200 text-slate-400' }} flex items-center justify-center">
                                    @if($isShipped)
                                        <i class="bi bi-check text-base"></i>
                                    @else
                                        <span class="text-xs font-bold">3</span>
                                    @endif
                                </div>
                                <div class="w-0.5 h-10 {{ $isShipped ? 'bg-primary/30' : 'bg-slate-200' }} mt-2"></div>
                            </div>
                            <div class="pt-1">
                                <h4 class="text-xs font-black {{ $isShipped ? 'text-slate-900' : 'text-slate-400' }} uppercase tracking-tight">Shipped</h4>
                                <p class="text-[10px] text-slate-400 font-medium mt-0.5">On the way to your door.</p>
                            </div>
                        </div>

                        <!-- Step 4: Delivered -->
                        @php
                            $isDelivered = in_array($order->status, ['completed', 'delivered']);
                        @endphp
                        <div class="flex items-start gap-4">
                            <div class="flex flex-col items-center shrink-0">
                                <div class="w-8 h-8 rounded-full {{ $isDelivered ? 'bg-emerald-500 ring-4 ring-emerald-50 text-white' : 'bg-slate-200 text-slate-400' }} flex items-center justify-center">
                                    @if($isDelivered)
                                        <i class="bi bi-check text-base"></i>
                                    @else
                                        <span class="text-xs font-bold">4</span>
                                    @endif
                                </div>
                            </div>
                            <div class="pt-1">
                                <h4 class="text-xs font-black {{ $isDelivered ? 'text-slate-900' : 'text-slate-400' }} uppercase tracking-tight">Delivered</h4>
                                <p class="text-[10px] text-slate-400 font-medium mt-0.5">Enjoy your tech products!</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Status Card -->
                <div class="bg-white rounded-[32px] shadow-xl shadow-slate-200/50 border border-slate-100 p-8 text-center">
                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest block mb-2">Payment Status</span>
                    @if($order->payment_status === 'paid')
                        <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-emerald-50 text-emerald-600 text-xs font-black uppercase tracking-wider border border-emerald-100">
                            <i class="bi bi-shield-fill-check"></i> Paid
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-amber-50 text-amber-600 text-xs font-black uppercase tracking-wider border border-amber-100">
                            <i class="bi bi-hourglass-split"></i> Pending Payment
                        </span>
                    @endif
                    
                    <p class="text-[10px] text-slate-400 font-semibold leading-relaxed mt-4 uppercase">
                        @if($order->payment_method === 'cod')
                            Please prepare <strong>{{ number_format($order->total_price, 2) }} Tk</strong> in cash for the courier when the package is delivered.
                        @else
                            Your transaction has been securely processed online. Thank you!
                        @endif
                    </p>
                </div>

                <!-- Sleek Action Buttons -->
                <div class="space-y-4">
                    <a href="{{ route('products.index') }}" class="w-full bg-slate-900 hover:bg-primary text-white py-5 rounded-2xl font-black text-xs uppercase tracking-[0.25em] flex items-center justify-center gap-2 transition-all shadow-xl shadow-slate-900/10 active:scale-95 group">
                        <i class="bi bi-arrow-left group-hover:-translate-x-1 transition-transform"></i>
                        Continue Shopping
                    </a>

                    @auth
                        <a href="{{ route('user.orders.index') }}" class="w-full bg-white hover:bg-slate-50 text-slate-900 py-5 rounded-2xl border-2 border-slate-900/10 font-black text-xs uppercase tracking-[0.25em] flex items-center justify-center gap-2 transition-all active:scale-95">
                            <i class="bi bi-truck"></i>
                            Track My Order
                        </a>
                    @else
                        <a href="{{ route('user.orders.track') }}" class="w-full bg-white hover:bg-slate-50 text-slate-900 py-5 rounded-2xl border-2 border-slate-900/10 font-black text-xs uppercase tracking-[0.25em] flex items-center justify-center gap-2 transition-all active:scale-95">
                            <i class="bi bi-truck"></i>
                            Track My Order
                        </a>
                    @endauth

                    <a href="{{ route('orders.invoice.download', $order->id) }}" target="_blank" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 py-4 rounded-xl font-bold text-xs uppercase tracking-widest flex items-center justify-center gap-2 transition-all active:scale-95">
                        <i class="bi bi-printer"></i>
                        Print Invoice
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection