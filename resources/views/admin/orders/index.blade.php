@extends('admin.layouts')
@section('title', 'Order Management')

@section('content')
<div class="space-y-8">
    {{-- Header Section --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-slate-900 leading-none">Order Logistics</h1>
            <p class="text-sm font-bold text-slate-400 uppercase tracking-[0.2em] mt-3">Track and process customer transactions</p>
        </div>
        <div class="flex items-center gap-4">
            <button class="bg-white border border-slate-200 text-slate-600 px-6 py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-slate-50 transition-all flex items-center gap-2">
                <i class="bi bi-filter"></i> Advanced Filters
            </button>
            <a href="{{ route('orders.create') }}" class="bg-primary text-white px-8 py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-primary-dark transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
                <i class="bi bi-plus-lg"></i> Manual Order
            </a>
        </div>
    </div>

    {{-- Feedback Messages --}}
    @if(session('success'))
        <div class="bg-green-50 border border-green-100 p-4 rounded-2xl flex items-center gap-4 animate-fadeIn">
            <div class="w-10 h-10 bg-green-500 rounded-xl flex items-center justify-center text-white text-xl shadow-lg shadow-green-500/20">
                <i class="bi bi-check-lg"></i>
            </div>
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-green-700">Order System</p>
                <p class="text-sm font-bold text-green-600">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    {{-- Orders Table Card --}}
    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Order Ref</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Customer Info</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Total Amount</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Fulfillment</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Timestamp</th>
                        <th class="px-8 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Operations</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="px-8 py-6">
                                <div class="flex flex-col">
                                    <span class="text-sm font-black text-slate-900 tracking-tight italic uppercase group-hover:text-primary transition-colors">#{{ $order->order_id }}</span>
                                    <span class="text-[8px] font-black text-slate-300 uppercase tracking-widest mt-1">Ref ID: {{ $order->id }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-xs font-black text-slate-400 group-hover:bg-primary/10 group-hover:text-primary transition-all">
                                        {{ strtoupper(substr($order->user->name ?? 'G', 0, 1)) }}
                                    </div>
                                    <div class="flex flex-col min-w-0">
                                        <span class="text-sm font-bold text-slate-700 truncate">{{ $order->user->name ?? 'Guest Customer' }}</span>
                                        <span class="text-[10px] text-slate-400 font-bold truncate">{{ $order->user->email ?? 'No email provided' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <span class="text-sm font-black text-slate-900 tracking-tight italic uppercase">TK {{ number_format($order->total_price, 2) }}</span>
                            </td>
                            <td class="px-8 py-6 text-center">
                                @php
                                    $statusClasses = match($order->status) {
                                        'pending' => 'bg-amber-100 text-amber-600',
                                        'processing' => 'bg-cyan-100 text-cyan-600',
                                        'completed' => 'bg-green-100 text-green-600',
                                        'cancelled' => 'bg-red-100 text-red-600',
                                        default => 'bg-slate-100 text-slate-600'
                                    };
                                @endphp
                                <span class="px-4 py-1.5 rounded-xl text-[9px] font-black uppercase tracking-[0.2em] {{ $statusClasses }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-slate-600">{{ $order->created_at->format('d M, Y') }}</span>
                                    <span class="text-[9px] text-slate-400 font-bold">{{ $order->created_at->format('h:i A') }}</span>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex justify-end items-center gap-2">
                                    <a href="{{ route('orders.show', $order->id) }}" class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 hover:bg-primary hover:text-white transition-all shadow-sm">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <form action="{{ route('orders.destroy', $order->id) }}" method="POST" onsubmit="return confirm('Purge this order record?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-10 h-10 rounded-xl bg-slate-50 flex items-center justify-center text-slate-400 hover:bg-red-500 hover:text-white transition-all shadow-sm">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-8 py-24 text-center">
                                <div class="flex flex-col items-center opacity-20">
                                    <i class="bi bi-collection text-6xl"></i>
                                    <span class="text-xs font-black uppercase tracking-widest mt-4">Zero Orders Recorded</span>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Table Footer / Pagination --}}
        @if($orders->hasPages())
            <div class="px-8 py-6 bg-slate-50/50 border-t border-slate-100">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

