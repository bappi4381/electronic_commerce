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
                <h2 class="text-xl sm:text-3xl font-black text-slate-900 tracking-tighter uppercase">Order #{{ $order->order_id }}</h2>
                <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Placed on {{ $order->created_at->format('d M, Y \a\t h:i A') }}</p>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            @php
                $statusStyles = \App\Enums\OrderStatus::badge($order->status);
            @endphp
            <span class="px-6 py-2.5 rounded-2xl border text-[10px] font-black uppercase tracking-widest {{ $statusStyles }} shadow-sm">
                {{ $order->statusEnum()?->label() ?? $order->status }}
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
                            <div class="px-6 py-6 md:px-8 md:py-8 flex flex-col sm:flex-row sm:items-center gap-6 sm:gap-8 group hover:bg-slate-50/30 transition-colors">
                                <!-- Product Image placeholder -->
                                <div class="w-16 h-16 sm:w-20 sm:h-20 bg-slate-50 rounded-2xl flex-shrink-0 flex items-center justify-center border border-slate-100 overflow-hidden group-hover:scale-105 transition-transform">
                                    @if($item->product && $item->product->images->first())
                                        <img src="{{ asset('storage/' . $item->product->images->first()->image) }}" class="w-full h-full object-contain p-2">
                                    @elseif(isset($item->product->thumbnail))
                                        <img src="{{ asset($item->product->thumbnail) }}" class="w-full h-full object-cover">
                                    @else
                                        <i class="bi bi-image text-slate-200 text-2xl"></i>
                                    @endif
                                </div>
                                
                                <div class="flex-grow space-y-1">
                                    <h4 class="text-sm font-black text-slate-900 tracking-tighter uppercase group-hover:text-primary transition-colors leading-tight">
                                        {{ $item->product->name ?? 'N/A' }}
                                    </h4>
                                    @if($item->variant && $item->variant->attributeValues->isNotEmpty())
                                        <p class="text-[10px] font-bold text-slate-500">{{ $item->variant->attributeValues->map(fn ($v) => $v->attribute?->name . ': ' . $v->value)->implode(' · ') }}</p>
                                    @endif
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                        Qty: <span class="text-slate-900">{{ $item->quantity }}</span>
                                        @if($item->variant?->sku) · SKU <span class="text-slate-600">{{ $item->variant->sku }}</span> @endif
                                    </p>
                                </div>
                                
                                <div class="text-left sm:text-right w-full sm:w-auto flex sm:flex-col justify-between sm:justify-start items-center sm:items-end border-t border-slate-50 sm:border-0 pt-4 sm:pt-0 mt-2 sm:mt-0">
                                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest sm:mb-1">Price</span>
                                    <div class="text-right">
                                        <p class="text-sm font-black text-slate-900">tk. {{ number_format($item->price * $item->quantity, 2) }}</p>
                                        @if($item->quantity > 1)
                                            <p class="text-[9px] font-medium text-slate-400">tk. {{ number_format($item->price, 2) }} / unit</p>
                                        @endif
                                    </div>
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
            <div class="card border-0 shadow-sm p-6 sm:p-10 lg:p-12 bg-white relative overflow-hidden">
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
                            <p class="text-xs font-black text-slate-900 uppercase mb-2">{{ $order->customerDisplayName() }}</p>
                            @if($order->customerDisplayPhone())
                                <p class="text-xs font-bold text-slate-600 mb-2"><i class="bi bi-telephone"></i> {{ $order->customerDisplayPhone() }}</p>
                            @endif
                            <p class="text-xs font-medium text-slate-500 leading-relaxed">{{ $order->shipping_address }}</p>
                            <p class="text-xs font-black text-slate-900 mt-2">{{ collect([$order->city, $order->region])->filter()->implode(', ') }}</p>
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
                                    <p class="text-[10px] font-black text-slate-900 uppercase tracking-tighter">{{ $order->payment_method === 'cod' ? 'Cash on delivery' : 'Online payment' }}</p>
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded-md border text-[9px] font-black uppercase tracking-widest {{ \App\Enums\PaymentStatus::badge($order->payment_status) }}">{{ $order->paymentStatusEnum()?->label() ?? $order->payment_status }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tracking & Timeline -->
            <div class="card border-0 shadow-sm p-6 sm:p-10 bg-white space-y-8">
                <h4 class="text-lg font-black text-slate-900 tracking-tighter uppercase flex items-center gap-4">
                    <span class="w-10 h-10 bg-slate-100 text-primary rounded-xl flex items-center justify-center shadow-inner"><i class="bi bi-clock-history"></i></span>
                    Order Timeline
                </h4>

                @if($order->orderCourier)
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">{{ $order->orderCourier->courierLabel() }} tracking code</p>
                            <p class="text-sm font-black text-slate-900 font-mono">{{ $order->orderCourier->tracking_code ?: $order->orderCourier->consignment_id }}</p>
                        </div>
                        @if($order->orderCourier->trackingUrl())
                            <a href="{{ $order->orderCourier->trackingUrl() }}" target="_blank" rel="noopener" class="px-4 py-2 rounded-xl bg-primary text-white text-[10px] font-black uppercase tracking-widest">Track parcel <i class="bi bi-box-arrow-up-right"></i></a>
                        @endif
                    </div>
                @endif

                <ol class="relative border-l border-slate-100 ml-4 space-y-6">
                    @foreach($order->statusHistories->reverse() as $history)
                        <li class="ml-6">
                            <span class="absolute -left-3 flex items-center justify-center w-6 h-6 rounded-full border {{ \App\Enums\OrderStatus::badge($history->to_status) }}">
                                <i class="bi {{ $history->toStatus()?->icon() ?? 'bi-dot' }} text-[10px]"></i>
                            </span>
                            <p class="text-xs font-black uppercase tracking-widest text-slate-900">{{ $history->toStatus()?->label() ?? $history->to_status }}</p>
                            <p class="text-[11px] text-slate-500">{{ $history->note ?: $history->toStatus()?->customerMessage() }}</p>
                            <p class="text-[10px] font-bold text-slate-400 mt-1">{{ $history->created_at->format('d M Y, h:i A') }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>

        <!-- Sidebar: Order Summary -->
        <div class="lg:col-span-4 space-y-8">
            <div class="card border-0 shadow-xl p-6 sm:p-10 lg:p-12 bg-slate-900 text-white relative overflow-hidden group">
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-primary/20 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
                
                <div class="relative z-10 space-y-10">
                    <h4 class="text-lg font-black tracking-tighter uppercase border-b border-white/10 pb-6 flex items-center justify-between">
                        Order Summary
                        <i class="bi bi-receipt text-primary"></i>
                    </h4>

                    <div class="space-y-5">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-black uppercase text-slate-500 tracking-widest">Subtotal</span>
                            <span class="text-sm font-bold">tk. {{ number_format($order->itemsSubtotal(), 2) }}</span>
                        </div>
                        @if((float) $order->discount_amount > 0)
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] font-black uppercase text-emerald-400 tracking-widest">Coupon {{ $order->coupon_code }}</span>
                                <span class="text-sm font-bold text-emerald-400">− tk. {{ number_format($order->discount_amount, 2) }}</span>
                            </div>
                        @endif
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
