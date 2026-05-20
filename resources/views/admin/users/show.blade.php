@extends('admin.layouts')
@section('title', 'Customer Profile')

@section('content')
<div class="space-y-10 pb-20">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 bg-slate-900 rounded-[2rem] flex items-center justify-center text-white shadow-2xl shadow-slate-900/20">
                <i class="bi bi-person-badge text-2xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-black tracking-tight text-slate-900 leading-none">{{ $user->name }}</h1>
                <div class="flex items-center gap-2 mt-2">
                    <span class="w-1 h-1 bg-primary rounded-full"></span>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Customer Profile & Audit</p>
                </div>
            </div>
        </div>

        <a href="{{ route('users.index') }}" class="px-6 py-3 rounded-xl bg-slate-100 text-slate-600 text-[10px] font-black uppercase tracking-widest hover:bg-slate-200 transition-all flex items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Registry
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
        {{-- Identity Card --}}
        <div class="space-y-8">
            <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-xl shadow-slate-200/40 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-slate-50 rounded-full -mr-16 -mt-16 blur-3xl opacity-50"></div>
                
                <h4 class="text-[10px] font-black uppercase tracking-[0.2em] text-primary mb-8">01. Identity Core</h4>
                
                <div class="space-y-6 relative">
                    <div class="flex flex-col">
                        <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Legal Name</span>
                        <span class="text-sm font-black text-slate-900 mt-1">{{ $user->name }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Email Endpoint</span>
                        <span class="text-sm font-black text-slate-900 mt-1">{{ $user->email }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Primary Contact</span>
                        <span class="text-sm font-black text-slate-900 mt-1">{{ $user->phone ?? 'Not Linked' }}</span>
                    </div>
                    <div class="pt-6 border-t border-slate-50 flex items-center justify-between">
                        <div>
                            <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Registry Status</span>
                            <div class="flex items-center gap-2 mt-1">
                                <div class="w-2 h-2 rounded-full {{ $user->status === 'active' ? 'bg-emerald-500' : 'bg-red-500' }}"></div>
                                <span class="text-[10px] font-black uppercase tracking-widest text-slate-900">{{ $user->status }}</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Member Since</span>
                            <span class="text-[10px] font-black block mt-1 text-slate-900 italic tracking-tighter">{{ $user->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-slate-900 p-10 rounded-[3rem] text-white shadow-2xl shadow-slate-900/30">
                <i class="bi bi-geo-alt text-3xl text-primary"></i>
                <h4 class="mt-6 text-[10px] font-black uppercase tracking-widest text-slate-400">Logistics Data</h4>
                <div class="mt-4 space-y-4">
                    <p class="text-[11px] font-medium leading-relaxed opacity-80 uppercase tracking-tight">
                        {{ $user->address ?? 'No physical address provided in registry.' }}
                    </p>
                    @if($user->city || $user->country)
                        <div class="flex items-center gap-2">
                            <span class="bg-white/10 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest">{{ $user->city }}</span>
                            <span class="bg-white/10 px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest">{{ $user->country }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Order History --}}
        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white rounded-[3rem] border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
                <div class="px-10 py-8 border-b border-slate-50 flex items-center justify-between bg-slate-50/30">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-primary/10 rounded-xl flex items-center justify-center text-primary">
                            <i class="bi bi-clock-history text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xs font-black text-slate-900 uppercase tracking-[0.2em]">Transaction Audit</h2>
                            <p class="text-[9px] font-bold text-slate-400 uppercase mt-1 tracking-widest">Historical Order Record</p>
                        </div>
                    </div>
                    <span class="text-[10px] font-black text-slate-900 bg-white px-4 py-2 rounded-xl shadow-sm border border-slate-100 italic tracking-tighter">
                        Total Orders: {{ $user->orders->count() }}
                    </span>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-50">
                                <th class="px-10 py-5 text-[9px] font-black uppercase tracking-widest text-slate-400">Order Ref</th>
                                <th class="px-10 py-5 text-[9px] font-black uppercase tracking-widest text-slate-400">Settlement</th>
                                <th class="px-10 py-5 text-[9px] font-black uppercase tracking-widest text-slate-400 text-center">Status</th>
                                <th class="px-10 py-5 text-[9px] font-black uppercase tracking-widest text-slate-400 text-right">Date</th>
                                <th class="px-10 py-5 text-[9px] font-black uppercase tracking-widest text-slate-400 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($orders as $order)
                                <tr class="hover:bg-slate-50/50 transition-colors group">
                                    <td class="px-10 py-6">
                                        <span class="text-[11px] font-black text-slate-900 tracking-tighter uppercase">{{ $order->order_id }}</span>
                                    </td>
                                    <td class="px-10 py-6">
                                        <span class="text-[11px] font-black text-slate-900 italic tracking-tight">TK {{ number_format($order->total_price, 2) }}</span>
                                    </td>
                                    <td class="px-10 py-6 text-center">
                                        @php
                                            $color = match($order->status) {
                                                'completed', 'delivered' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                                'pending', 'processing' => 'bg-orange-50 text-orange-600 border-orange-100',
                                                'cancelled' => 'bg-red-50 text-red-600 border-red-100',
                                                default => 'bg-slate-50 text-slate-500 border-slate-100'
                                            };
                                        @endphp
                                        <span class="px-3 py-1 rounded-lg text-[8px] font-black uppercase tracking-widest border {{ $color }}">
                                            {{ $order->status }}
                                        </span>
                                    </td>
                                    <td class="px-10 py-6 text-right">
                                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ $order->created_at->format('M d, Y') }}</span>
                                    </td>
                                    <td class="px-10 py-6 text-right">
                                        <a href="{{ route('orders.show', $order->id) }}" class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-primary hover:text-slate-900 transition-all">
                                            Audit <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-10 py-20 text-center">
                                        <div class="flex flex-col items-center opacity-20">
                                            <i class="bi bi-cart-x text-5xl"></i>
                                            <p class="text-[10px] font-black uppercase tracking-widest mt-4">No Transaction History</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($orders->hasPages())
                    <div class="px-10 py-6 bg-slate-50/30 border-t border-slate-50">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
