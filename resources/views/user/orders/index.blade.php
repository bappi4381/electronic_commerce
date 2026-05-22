@extends('user.layout')

@section('title', 'Order History')

@section('content')
<div class="space-y-8 animate-in fade-in slide-in-from-bottom-4 duration-700">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tighter uppercase">Order History</h2>
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Manage and track all your purchases.</p>
        </div>
        
        <div class="flex items-center gap-3 overflow-x-auto pb-2 md:pb-0 no-scrollbar">
            @php
                $filters = ['All', 'Pending', 'Processing', 'Delivered', 'Cancelled'];
            @endphp
            @foreach($filters as $filter)
                <button class="px-5 py-2.5 bg-white border border-slate-100 rounded-xl text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-primary hover:border-primary/20 transition-all shadow-sm whitespace-nowrap {{ $loop->first ? 'text-primary border-primary/20 bg-primary/5' : '' }}">
                    {{ $filter }}
                </button>
            @endforeach
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-green-50 border border-green-100 text-green-700 rounded-2xl flex items-center gap-3 shadow-sm animate-in zoom-in-95 duration-300">
            <i class="bi bi-check-circle-fill text-xl"></i>
            <span class="font-bold text-sm">{{ session('success') }}</span>
        </div>
    @endif

    @if($orders->isEmpty())
        <div class="card border-0 shadow-sm p-16 text-center bg-white relative overflow-hidden">
            <div class="relative z-10">
                <div class="w-24 h-24 bg-slate-50 text-slate-200 rounded-[40px] flex items-center justify-center text-5xl mx-auto mb-8 shadow-inner">
                    <i class="bi bi-bag-x"></i>
                </div>
                <h3 class="text-2xl font-black text-slate-900 tracking-tighter uppercase mb-3">No Orders Found</h3>
                <p class="text-sm text-slate-400 font-medium mb-10 max-w-xs mx-auto">It looks like you haven't placed any orders yet. Start exploring our premium electronics collection!</p>
                <a href="{{ route('products.index') }}" class="inline-block px-12 py-5 bg-primary text-white font-black uppercase tracking-[0.2em] text-[10px] rounded-2xl shadow-xl shadow-primary/30 hover:scale-105 active:scale-95 transition-all">Start Shopping</a>
            </div>
            <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-primary/5 rounded-full blur-3xl"></div>
        </div>
    @else
        <!-- Desktop View Table -->
        <div class="hidden md:block card border-0 shadow-sm overflow-hidden bg-white">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-8 py-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Order Identifier</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Date & Time</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase text-slate-400 tracking-widest">Transaction Total</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase text-slate-400 tracking-widest text-center">Status</th>
                            <th class="px-8 py-6 text-[10px] font-black uppercase text-slate-400 tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($orders as $order)
                            <tr class="hover:bg-slate-50/30 transition-all group">
                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-black text-slate-900 tracking-tighter uppercase group-hover:text-primary transition-colors">{{ $order->order_id }}</span>
                                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mt-1.5 flex items-center gap-2">
                                            <i class="bi bi-credit-card text-slate-300"></i>
                                            {{ $order->payment_method }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-600">{{ $order->created_at->format('d M, Y') }}</span>
                                        <span class="text-[10px] font-medium text-slate-400 mt-1">{{ $order->created_at->format('h:i A') }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="text-sm font-black text-slate-900 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100 group-hover:border-primary/10 transition-colors">tk. {{ number_format($order->total_price, 2) }}</span>
                                </td>
                                <td class="px-8 py-6 text-center">
                                    @php
                                        $statusStyles = match($order->status) {
                                            'pending' => 'bg-amber-50 text-amber-600 border-amber-100',
                                            'processing' => 'bg-blue-50 text-blue-600 border-blue-100',
                                            'completed' => 'bg-green-50 text-green-600 border-green-100',
                                            'cancelled' => 'bg-rose-50 text-rose-600 border-rose-100',
                                            default => 'bg-slate-50 text-slate-600 border-slate-100',
                                        };
                                    @endphp
                                    <span class="px-4 py-1.5 rounded-full border text-[9px] font-black uppercase tracking-widest {{ $statusStyles }} shadow-sm">
                                        {{ $order->status }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('user.orders.details', $order->id) }}" 
                                           class="w-11 h-11 bg-white border border-slate-100 text-slate-400 rounded-xl flex items-center justify-center hover:bg-primary hover:text-white hover:border-primary transition-all shadow-sm group/btn relative">
                                            <i class="bi bi-eye"></i>
                                            <span class="absolute -top-10 left-1/2 -translate-x-1/2 bg-slate-900 text-white text-[8px] font-black uppercase py-1 px-2 rounded opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap">View Details</span>
                                        </a>

                                        @if($order->status === 'pending')
                                            <form action="{{ route('user.orders.cancel', $order->id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" 
                                                        class="w-11 h-11 bg-white border border-slate-100 text-rose-400 rounded-xl flex items-center justify-center hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-all shadow-sm group/btn relative"
                                                        onclick="return confirm('Are you sure you want to cancel this order?')">
                                                    <i class="bi bi-x-lg"></i>
                                                    <span class="absolute -top-10 left-1/2 -translate-x-1/2 bg-rose-900 text-white text-[8px] font-black uppercase py-1 px-2 rounded opacity-0 group-hover/btn:opacity-100 transition-opacity whitespace-nowrap">Cancel Order</span>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile View Card List -->
        <div class="block md:hidden space-y-6">
            @foreach($orders as $order)
                @php
                    $statusStyles = match($order->status) {
                        'pending' => 'bg-amber-50 text-amber-600 border-amber-100',
                        'processing' => 'bg-blue-50 text-blue-600 border-blue-100',
                        'completed' => 'bg-green-50 text-green-600 border-green-100',
                        'cancelled' => 'bg-rose-50 text-rose-600 border-rose-100',
                        default => 'bg-slate-50 text-slate-600 border-slate-100',
                    };
                @endphp
                <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-black text-slate-900 uppercase tracking-tighter">{{ $order->order_id }}</span>
                        <span class="px-3 py-1 rounded-full border text-[8px] font-black uppercase tracking-widest {{ $statusStyles }}">
                            {{ $order->status }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Date</p>
                            <p class="font-bold text-slate-700">{{ $order->created_at->format('d M, Y') }}</p>
                            <p class="text-[10px] text-slate-400 mt-0.5">{{ $order->created_at->format('h:i A') }}</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Payment</p>
                            <p class="font-bold text-slate-700 uppercase tracking-tight">{{ $order->payment_method }}</p>
                        </div>
                    </div>

                    <div class="border-t border-slate-50 pt-4 flex items-center justify-between">
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Total Amount</p>
                            <p class="text-base font-black text-primary">tk. {{ number_format($order->total_price, 2) }}</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('user.orders.details', $order->id) }}" 
                               class="px-4 py-2.5 bg-slate-900 text-white rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-primary transition-all shadow-sm">
                                Details
                            </a>
                            @if($order->status === 'pending')
                                <form action="{{ route('user.orders.cancel', $order->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" 
                                            class="px-4 py-2.5 bg-rose-50 text-rose-500 rounded-xl text-[9px] font-black uppercase tracking-widest border border-rose-100 hover:bg-rose-500 hover:text-white transition-all"
                                            onclick="return confirm('Are you sure you want to cancel this order?')">
                                        Cancel
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="pt-6">
            {{ $orders->links() }}
        </div>
    @endif
</div>
@endsection