@extends('user.layout')

@section('title', 'Order Details')

@section('content')
<div class="max-w-6xl mx-auto space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-6">
            <a href="{{ route('user.orders.index') }}" class="w-12 h-12 bg-white border border-slate-100 rounded-2xl flex items-center justify-center text-slate-400 hover:text-primary hover:border-primary/20 transition-all shadow-sm group">
                <i class="bi bi-arrow-left group-hover:-translate-x-1 transition-transform"></i>
            </a>
            <div>
                <h2 class="text-3xl font-black text-slate-900 tracking-tighter uppercase">Order #{{ $order->order_id }}</h2>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Placed on {{ $order->created_at->format('d M, Y \a\t h:i A') }}</p>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            @php
                $statusStyles = match($order->status) {
                    'pending' => 'bg-amber-50 text-amber-600 border-amber-100',
                    'processing' => 'bg-blue-50 text-blue-600 border-blue-100',
                    'completed' => 'bg-green-50 text-green-600 border-green-100',
                    'cancelled' => 'bg-rose-50 text-rose-600 border-rose-100',
                    default => 'bg-slate-50 text-slate-600 border-slate-100',
                };
            @endphp
            <span class="px-6 py-2.5 rounded-2xl border text-[10px] font-black uppercase tracking-widest {{ $statusStyles }} shadow-sm">
                {{ $order->status }}
            </span>
            <a href="{{ route('orders.invoice.download', $order->id) }}" target="_blank" class="w-12 h-12 bg-white border border-slate-100 rounded-2xl flex items-center justify-center text-slate-400 hover:text-primary hover:border-primary/20 transition-all shadow-sm">
                <i class="bi bi-printer"></i>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Main Content: Items List -->
        <div class="lg:col-span-8 space-y-6">
            <div class="card border-0 shadow-sm overflow-hidden bg-white">
                <div class="px-8 py-6 border-b border-slate-50 flex items-center justify-between">
                    <h3 class="text-sm font-black text-slate-900 tracking-widest uppercase flex items-center gap-3">
                        <i class="bi bi-box-seam text-primary"></i>
                        Ordered Items
                    </h3>
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $order->orderItems->count() }} Items</span>
                </div>
                
                @if(isset($order->orderItems) && $order->orderItems->count() > 0)
                    <div class="divide-y divide-slate-50">
                        @foreach($order->orderItems as $item)
                            <div class="px-8 py-8 flex items-center gap-8 group hover:bg-slate-50/30 transition-colors">
                                <!-- Product Image placeholder -->
                                <div class="w-20 h-20 bg-slate-50 rounded-2xl flex-shrink-0 flex items-center justify-center border border-slate-100 overflow-hidden group-hover:scale-105 transition-transform">
                                    @if(isset($item->product->thumbnail))
                                        <img src="{{ asset($item->product->thumbnail) }}" class="w-full h-full object-cover">
                                    @else
                                        <i class="bi bi-image text-slate-200 text-2xl"></i>
                                    @endif
                                </div>
                                
                                <div class="flex-grow space-y-1">
                                    <h4 class="text-sm font-black text-slate-900 tracking-tighter uppercase group-hover:text-primary transition-colors leading-tight">
                                        {{ $item->product->name ?? 'N/A' }}
                                    </h4>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                        Qty: <span class="text-slate-900">{{ $item->quantity }}</span>
                                    </p>
                                </div>
                                
                                <div class="text-right">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Price</p>
                                    <p class="text-sm font-black text-slate-900">tk. {{ number_format($item->price * $item->quantity, 2) }}</p>
                                    @if($item->quantity > 1)
                                        <p class="text-[9px] font-medium text-slate-400">tk. {{ number_format($item->price, 2) }} / unit</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-16 text-center text-slate-400 font-bold text-xs uppercase tracking-widest">
                        No items found for this order.
                    </div>
                @endif
            </div>

            <!-- Delivery Info Card -->
            <div class="card border-0 shadow-sm p-10 lg:p-12 bg-white relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-[100px] -z-10"></div>
                <h4 class="text-lg font-black text-slate-900 tracking-tighter uppercase mb-8 flex items-center gap-4">
                    <span class="w-10 h-10 bg-slate-100 text-primary rounded-xl flex items-center justify-center shadow-inner">
                        <i class="bi bi-truck"></i>
                    </span>
                    Shipping Information
                </h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                    <div class="space-y-4">
                        <p class="text-[9px] font-black uppercase text-slate-400 tracking-widest">Delivery Address</p>
                        <div class="bg-slate-50/50 p-6 rounded-2xl border border-slate-100">
                            <p class="text-xs font-black text-slate-900 uppercase mb-2">{{ $order->user->name ?? 'Customer' }}</p>
                            <p class="text-xs font-medium text-slate-500 leading-relaxed">{{ $order->shipping_address }}</p>
                            <p class="text-xs font-black text-slate-900 mt-2">{{ $order->city }}, {{ $order->region }}</p>
                        </div>
                    </div>
                    <div class="space-y-4">
                        <p class="text-[9px] font-black uppercase text-slate-400 tracking-widest">Payment Details</p>
                        <div class="bg-slate-50/50 p-6 rounded-2xl border border-slate-100 space-y-4">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-sm">
                                    <i class="bi bi-credit-card text-primary"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-900 uppercase tracking-tighter">{{ $order->payment_method }}</p>
                                    <p class="text-[9px] font-bold text-green-500 uppercase tracking-widest">Paid Successfully</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar: Order Summary -->
        <div class="lg:col-span-4 space-y-8">
            <div class="card border-0 shadow-xl p-10 lg:p-12 bg-slate-900 text-white relative overflow-hidden group">
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-primary/20 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
                
                <div class="relative z-10 space-y-10">
                    <h4 class="text-lg font-black tracking-tighter uppercase border-b border-white/10 pb-6 flex items-center justify-between">
                        Order Summary
                        <i class="bi bi-receipt text-primary"></i>
                    </h4>

                    <div class="space-y-5">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-black uppercase text-slate-500 tracking-widest">Subtotal</span>
                            <span class="text-sm font-bold">tk. {{ number_format($order->total_price - ($order->delivery_charge ?? 0), 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-black uppercase text-slate-500 tracking-widest">Delivery Fee</span>
                            <span class="text-sm font-bold text-green-400">tk. {{ number_format($order->delivery_charge ?? 0, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-black uppercase text-slate-500 tracking-widest">Tax (VAT 0%)</span>
                            <span class="text-sm font-bold">tk. 0.00</span>
                        </div>
                        <div class="pt-6 border-t border-white/10 flex justify-between items-end">
                            <span class="text-[11px] font-black uppercase text-primary tracking-[0.2em]">Grand Total</span>
                            <span class="text-3xl font-black tracking-tighter text-white">tk. {{ number_format($order->total_price, 2) }}</span>
                        </div>
                    </div>

                    <div class="pt-6">
                        <a href="{{ route('user.orders.track', ['order_id' => $order->order_id]) }}" class="w-full py-5 bg-primary hover:bg-primary-dark text-white font-black uppercase tracking-[0.2em] text-[10px] rounded-2xl shadow-2xl shadow-primary/30 transition-all flex items-center justify-center gap-3 active:scale-95 group/btn">
                            Track Order Status
                            <i class="bi bi-geo-alt group-hover/btn:animate-bounce"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm p-8 bg-white space-y-6">
                <h4 class="text-[10px] font-black text-slate-900 tracking-widest uppercase">Need Assistance?</h4>
                <div class="space-y-4">
                    <a href="#" class="flex items-center gap-4 group">
                        <div class="w-10 h-10 bg-slate-50 text-slate-400 rounded-xl flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-all">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                        <span class="text-[10px] font-black text-slate-600 uppercase tracking-widest">Live Chat Support</span>
                    </a>
                    <a href="{{ route('orders.invoice.download', $order->id) }}" target="_blank" class="flex items-center gap-4 group">
                        <div class="w-10 h-10 bg-slate-50 text-slate-400 rounded-xl flex items-center justify-center group-hover:bg-primary group-hover:text-white transition-all">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </div>
                        <span class="text-[10px] font-black text-slate-600 uppercase tracking-widest">Download Invoice</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
