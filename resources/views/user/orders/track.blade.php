@extends('user.layout')

@section('title', 'Track Order')

@section('content')
<div class="max-w-6xl mx-auto space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tighter uppercase">Track Order</h2>
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Real-time updates on your package status.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Tracking Form -->
        <div class="lg:col-span-5 space-y-8">
            <div class="card border-0 shadow-sm p-10 lg:p-12 bg-white relative overflow-hidden">
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-primary/5 rounded-full blur-3xl"></div>
                
                <div class="relative z-10">
                    <h3 class="text-lg font-black text-slate-900 tracking-tighter uppercase mb-2">Check Status</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-10">Enter your Order ID below.</p>

                    <form action="{{ route('user.trackOrder.submit') }}" method="POST" class="space-y-6">
                        @csrf
                        <div class="space-y-3">
                            <label for="order_id" class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">Order Identifier</label>
                            <div class="relative group">
                                <i class="bi bi-hash absolute left-6 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-primary transition-colors text-xl"></i>
                                <input type="text" name="order_id" id="order_id" value="{{ old('order_id', request('order_id') ?? ($order->order_id ?? '')) }}" 
                                       class="w-full bg-slate-50/50 border border-slate-100 rounded-2xl pl-14 pr-6 py-5 text-sm font-black focus:ring-4 focus:ring-primary/5 focus:border-primary focus:bg-white transition-all outline-none uppercase placeholder:text-slate-300" 
                                       placeholder="e.g. ORD-2024..." required>
                            </div>
                            @error('order_id') <p class="text-[10px] text-red-500 font-bold mt-1 ml-1">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="w-full py-5 bg-slate-900 hover:bg-black text-white font-black uppercase tracking-[0.2em] text-[10px] rounded-2xl shadow-2xl shadow-slate-900/30 transition-all active:scale-95 flex items-center justify-center gap-3 group">
                            Track Package
                            <i class="bi bi-arrow-right group-hover:translate-x-1 transition-transform"></i>
                        </button>
                    </form>

                    @if(session('error'))
                        <div class="mt-8 p-5 bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl flex items-center gap-4 animate-in shake duration-500">
                            <i class="bi bi-exclamation-triangle-fill text-xl"></i>
                            <span class="font-bold text-[11px] uppercase tracking-widest">{{ session('error') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm p-8 bg-slate-50 border-slate-100 border-dashed">
                <h4 class="text-[10px] font-black text-slate-900 tracking-widest uppercase mb-4">Tracking Tips</h4>
                <ul class="space-y-3">
                    <li class="flex items-start gap-3 text-[10px] font-medium text-slate-500">
                        <i class="bi bi-check2-circle text-primary mt-0.5"></i>
                        Check your order confirmation email for the ID.
                    </li>
                    <li class="flex items-start gap-3 text-[10px] font-medium text-slate-500">
                        <i class="bi bi-check2-circle text-primary mt-0.5"></i>
                        Updates might take 24h to appear after shipment.
                    </li>
                </ul>
            </div>
        </div>

        <!-- Tracking Results -->
        <div class="lg:col-span-7">
            @isset($order)
                <div class="card border-0 shadow-sm p-10 lg:p-12 bg-white space-y-12">
                    <div class="flex items-center justify-between pb-8 border-b border-slate-50">
                        <div>
                            <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">Package Details</p>
                            <h3 class="text-2xl font-black text-slate-900 tracking-tighter uppercase">{{ $order->order_id }}</h3>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">Estimated Arrival</p>
                            <p class="text-sm font-black text-slate-900 tracking-tighter uppercase">{{ $order->created_at->addDays(5)->format('d M, Y') }}</p>
                        </div>
                    </div>

                    <!-- Visual Progress Bar -->
                    <div class="relative pt-4 px-4">
                        <div class="absolute left-4 right-4 top-1/2 -translate-y-1/2 h-1 bg-slate-50 rounded-full overflow-hidden">
                            @php
                                $progressWidth = match($order->status) {
                                    'pending' => '25%',
                                    'processing' => '50%',
                                    'shipped' => '75%',
                                    'delivered' => '100%',
                                    'cancelled' => '0%',
                                    default => '0%',
                                };
                            @endphp
                            <div class="h-full bg-primary transition-all duration-1000 shadow-[0_0_15px_rgba(59,130,246,0.5)]" style="width: {{ $progressWidth }}"></div>
                        </div>
                        <div class="flex justify-between relative">
                            @php
                                $statusList = ['pending', 'processing', 'shipped', 'delivered'];
                                $currentStatusIndex = array_search($order->status, $statusList);
                            @endphp
                            @foreach(['Placed', 'Processing', 'Shipped', 'Delivered'] as $index => $label)
                                @php
                                    $isReached = $index <= $currentStatusIndex;
                                    $isCurrent = $index === $currentStatusIndex;
                                @endphp
                                <div class="flex flex-col items-center gap-4">
                                    <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-sm transition-all duration-500 z-10 shadow-lg
                                        {{ $isReached ? 'bg-primary text-white border-4 border-white' : 'bg-white text-slate-300 border-2 border-slate-50' }}">
                                        <i class="bi {{ match($index) { 0 => 'bi-cart-check', 1 => 'bi-gear', 2 => 'bi-truck', 3 => 'bi-house-check' } }}"></i>
                                    </div>
                                    <span class="text-[9px] font-black uppercase tracking-widest {{ $isReached ? 'text-slate-900' : 'text-slate-300' }}">{{ $label }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Timeline Details -->
                    <div class="pt-8 space-y-10 relative before:absolute before:left-[19px] before:top-2 before:bottom-2 before:w-[2px] before:bg-slate-50">
                        @php
                            $stages = [
                                ['status' => 'pending', 'label' => 'Order Confirmed', 'desc' => 'We have received your order and payment.', 'time' => $order->created_at->format('h:i A')],
                                ['status' => 'processing', 'label' => 'Package Preparation', 'desc' => 'Our team is carefully packing your items.', 'time' => $order->created_at->addHours(2)->format('h:i A')],
                                ['status' => 'shipped', 'label' => 'Out for Delivery', 'desc' => 'Your package is on its way to your destination.', 'time' => 'In Progress'],
                                ['status' => 'delivered', 'label' => 'Successfully Delivered', 'desc' => 'Package has been received at the address.', 'time' => 'Pending'],
                            ];
                        @endphp

                        @foreach($stages as $index => $stage)
                            @php
                                $isReached = $index <= $currentStatusIndex;
                                $isCurrent = $index === $currentStatusIndex;
                            @endphp
                            <div class="relative pl-14 flex items-start justify-between group">
                                <div class="absolute left-0 w-10 h-10 rounded-xl flex items-center justify-center z-10 transition-all duration-500
                                    {{ $isReached ? 'bg-white border-2 border-primary text-primary shadow-sm' : 'bg-white border-2 border-slate-50 text-slate-200' }}">
                                    @if($isReached && !$isCurrent)
                                        <i class="bi bi-check-lg"></i>
                                    @else
                                        <div class="w-2 h-2 rounded-full {{ $isReached ? 'bg-primary animate-pulse' : 'bg-slate-100' }}"></div>
                                    @endif
                                </div>
                                <div class="space-y-1">
                                    <h4 class="text-xs font-black uppercase tracking-widest {{ $isReached ? 'text-slate-900' : 'text-slate-300' }}">{{ $stage['label'] }}</h4>
                                    <p class="text-[10px] font-medium {{ $isReached ? 'text-slate-500' : 'text-slate-300' }}">{{ $stage['desc'] }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-[9px] font-black uppercase tracking-widest {{ $isReached ? 'text-slate-400' : 'text-slate-200' }}">{{ $stage['time'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="h-full flex flex-col items-center justify-center p-20 bg-white rounded-[48px] border-4 border-dashed border-slate-50 animate-in fade-in zoom-in-95 duration-700">
                    <div class="w-32 h-32 bg-slate-50 text-slate-100 rounded-[50px] flex items-center justify-center text-6xl mb-10 shadow-inner">
                        <i class="bi bi-search"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-300 tracking-tighter uppercase mb-4">Track Status</h3>
                    <p class="text-[10px] font-bold text-slate-300 uppercase tracking-widest max-w-[200px] text-center leading-relaxed">Please provide a valid Order ID to see real-time updates.</p>
                </div>
            @endisset
        </div>
    </div>
</div>
@endsection
