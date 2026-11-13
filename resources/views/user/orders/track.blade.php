@extends('user.layout')

@section('title', 'Track Order')

@section('content')
<div class="max-w-6xl mx-auto space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tighter uppercase">{{ __('Track Order') }}</h2>
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">{{ __('Real-time updates on your package status.') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Tracking Form -->
        <div class="lg:col-span-5 space-y-8">
            <div class="card border-0 shadow-sm p-6 sm:p-10 lg:p-12 bg-white relative overflow-hidden">
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-primary/5 rounded-full blur-3xl"></div>
                
                <div class="relative z-10">
                    <h3 class="text-lg font-black text-slate-900 tracking-tighter uppercase mb-2">{{ __('Check Status') }}</h3>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-10">{{ __('Enter your Order ID below.') }}</p>

                    <form action="{{ route('user.trackOrder.submit') }}" method="POST" class="space-y-6">
                        @csrf
                        <div class="space-y-3">
                            <label for="order_id" class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">{{ __('Order Identifier') }}</label>
                            <div class="relative group">
                                <i class="bi bi-hash absolute left-6 top-1/2 -translate-y-1/2 text-slate-300 group-focus-within:text-primary transition-colors text-xl"></i>
                                <input type="text" name="order_id" id="order_id" value="{{ old('order_id', request('order_id') ?? ($order->order_id ?? '')) }}" 
                                       class="w-full bg-slate-50/50 border border-slate-100 rounded-2xl pl-14 pr-6 py-5 text-sm font-black focus:ring-4 focus:ring-primary/5 focus:border-primary focus:bg-white transition-all outline-none uppercase placeholder:text-slate-300" 
                                       placeholder="e.g. ORD-2024..." required>
                            </div>
                            @error('order_id') <p class="text-[10px] text-red-500 font-bold mt-1 ml-1">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="w-full py-5 bg-slate-900 hover:bg-black text-white font-black uppercase tracking-[0.2em] text-[10px] rounded-2xl shadow-2xl shadow-slate-900/30 transition-all active:scale-95 flex items-center justify-center gap-3 group">
                            {{ __('Track Package') }}
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
                <h4 class="text-[10px] font-black text-slate-900 tracking-widest uppercase mb-4">{{ __('Tracking Tips') }}</h4>
                <ul class="space-y-3">
                    <li class="flex items-start gap-3 text-[10px] font-medium text-slate-500">
                        <i class="bi bi-check2-circle text-primary mt-0.5"></i>
                        {{ __('Check your order confirmation email for the ID.') }}
                    </li>
                    <li class="flex items-start gap-3 text-[10px] font-medium text-slate-500">
                        <i class="bi bi-check2-circle text-primary mt-0.5"></i>
                        {{ __('Updates might take 24h to appear after shipment.') }}
                    </li>
                </ul>
            </div>
        </div>

        <!-- Tracking Results -->
        <div class="lg:col-span-7">
            @isset($order)
                <div class="card border-0 shadow-sm p-6 sm:p-10 lg:p-12 bg-white space-y-12">
                    <div class="flex items-center justify-between pb-8 border-b border-slate-50">
                        <div>
                            <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">{{ __('Package Details') }}</p>
                            <h3 class="text-base sm:text-2xl font-black text-slate-900 tracking-tighter uppercase break-all">{{ $order->order_id }}</h3>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">{{ __('Estimated Arrival') }}</p>
                            <p class="text-sm font-black text-slate-900 tracking-tighter uppercase">{{ $order->created_at->addDays(5)->format('d M, Y') }}</p>
                        </div>
                    </div>

                    <!-- Visual Progress Bar -->
                    @php
                        $statusEnum = $order->statusEnum();
                        $stepStatuses = \App\Enums\OrderStatus::fulfilmentSteps();
                        $currentStatusIndex = array_search($statusEnum, $stepStatuses, true);
                        $isStopped = $currentStatusIndex === false;
                        $currentStatusIndex = $isStopped ? -1 : $currentStatusIndex;
                        $progressWidth = $isStopped ? 0 : round($currentStatusIndex / (count($stepStatuses) - 1) * 100);
                    @endphp
                    <div class="relative pt-4 px-4">
                        <div class="absolute left-4 right-4 top-1/2 -translate-y-1/2 h-1 bg-slate-50 rounded-full overflow-hidden">
                            <div class="h-full bg-primary transition-all duration-1000 shadow-[0_0_15px_rgba(59,130,246,0.5)]" style="width: {{ $progressWidth }}%"></div>
                        </div>
                        <div class="flex justify-between relative">
                            @foreach($stepStatuses as $index => $step)
                                @php $isReached = $index <= $currentStatusIndex; @endphp
                                <div class="flex flex-col items-center gap-4">
                                    <div class="w-10 h-10 rounded-2xl flex items-center justify-center text-sm transition-all duration-500 z-10 shadow-lg
                                        {{ $isReached ? 'bg-primary text-white border-4 border-white' : 'bg-white text-slate-300 border-2 border-slate-50' }}">
                                        <i class="bi {{ $step->icon() }}"></i>
                                    </div>
                                    <span class="text-[9px] font-black uppercase tracking-widest {{ $isReached ? 'text-slate-900' : 'text-slate-300' }}">{{ $step->label() }}</span>
                                </div>
                            @endforeach
                        </div>
                        @if($isStopped && $statusEnum)
                            <p class="mt-6 text-center text-[10px] font-black uppercase tracking-widest text-rose-500"><i class="bi {{ $statusEnum->icon() }}"></i> {{ $statusEnum->label() }}</p>
                        @endif
                    </div>

                    @if($order->orderCourier)
                        <div class="mt-8 p-4 rounded-2xl bg-slate-50 border border-slate-100 flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p class="text-[9px] font-black uppercase tracking-widest text-slate-400">{{ $order->orderCourier->courierLabel() }} tracking</p>
                                <p class="text-sm font-black text-slate-900 font-mono">{{ $order->orderCourier->tracking_code ?: $order->orderCourier->consignment_id }}</p>
                            </div>
                            @if($order->orderCourier->trackingUrl())
                                <a href="{{ $order->orderCourier->trackingUrl() }}" target="_blank" rel="noopener" class="px-4 py-2 rounded-xl bg-primary text-white text-[10px] font-black uppercase tracking-widest">Track parcel <i class="bi bi-box-arrow-up-right"></i></a>
                            @endif
                        </div>
                    @endif

                    <!-- Timeline Details -->
                    <div class="pt-8 space-y-10 relative before:absolute before:left-[19px] before:top-2 before:bottom-2 before:w-[2px] before:bg-slate-50">
                        @forelse($order->statusHistories->reverse() as $history)
                            <div class="relative pl-14 flex items-start justify-between gap-2 group">
                                <div class="absolute left-0 w-10 h-10 rounded-xl flex items-center justify-center z-10 bg-white border-2 border-primary text-primary shadow-sm">
                                    <i class="bi {{ $history->toStatus()?->icon() ?? 'bi-check-lg' }}"></i>
                                </div>
                                <div class="space-y-1">
                                    <h4 class="text-xs font-black uppercase tracking-widest text-slate-900">{{ $history->toStatus()?->label() ?? $history->to_status }}</h4>
                                    <p class="text-[10px] font-medium text-slate-500">{{ $history->note ?: $history->toStatus()?->customerMessage() }}</p>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="text-[9px] font-black uppercase tracking-widest text-slate-400">{{ $history->created_at->format('d M, h:i A') }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-300 pl-14">No updates yet.</p>
                        @endforelse
                    </div>
                </div>
            @else
                <div class="h-full flex flex-col items-center justify-center p-10 sm:p-20 bg-white rounded-[48px] border-4 border-dashed border-slate-50 animate-in fade-in zoom-in-95 duration-700">
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
