@extends('user.layout')

@section('title', 'Dashboard Overview')

@section('content')
<div class="space-y-8">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-3xl font-black text-slate-900 tracking-tighter uppercase">Overview</h2>
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-[0.2em] mt-1">Everything you need to manage your account.</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-[10px] font-black uppercase text-slate-500 tracking-widest bg-white px-5 py-3 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-2">
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                System Online
            </span>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Orders -->
        <div class="card p-8 border-0 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group overflow-hidden relative">
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">Total Orders</p>
                    <h4 class="text-4xl font-black text-slate-900 tracking-tighter">{{ $stats['total_orders'] }}</h4>
                </div>
                <div class="w-14 h-14 bg-sky-50 text-sky-500 rounded-[22px] flex items-center justify-center text-2xl group-hover:bg-sky-500 group-hover:text-white transition-all duration-500 shadow-inner">
                    <i class="bi bi-bag-check-fill"></i>
                </div>
            </div>
            <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-sky-500/5 rounded-full blur-3xl group-hover:bg-sky-500/10 transition-colors"></div>
        </div>

        <!-- Pending -->
        <div class="card p-8 border-0 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group overflow-hidden relative">
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">Pending</p>
                    <h4 class="text-4xl font-black text-slate-900 tracking-tighter">{{ $stats['pending_orders'] }}</h4>
                </div>
                <div class="w-14 h-14 bg-amber-50 text-amber-500 rounded-[22px] flex items-center justify-center text-2xl group-hover:bg-amber-500 group-hover:text-white transition-all duration-500 shadow-inner">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
            <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-amber-500/5 rounded-full blur-3xl group-hover:bg-amber-500/10 transition-colors"></div>
        </div>

        <!-- Delivered -->
        <div class="card p-8 border-0 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group overflow-hidden relative">
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">Delivered</p>
                    <h4 class="text-4xl font-black text-slate-900 tracking-tighter">{{ $stats['delivered_orders'] }}</h4>
                </div>
                <div class="w-14 h-14 bg-green-50 text-green-500 rounded-[22px] flex items-center justify-center text-2xl group-hover:bg-green-500 group-hover:text-white transition-all duration-500 shadow-inner">
                    <i class="bi bi-truck"></i>
                </div>
            </div>
            <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-green-500/5 rounded-full blur-3xl group-hover:bg-green-500/10 transition-colors"></div>
        </div>

        <!-- Wishlist -->
        <div class="card p-8 border-0 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group overflow-hidden relative">
            <div class="flex items-center justify-between relative z-10">
                <div>
                    <p class="text-[10px] font-black uppercase text-slate-400 tracking-widest mb-2">Wishlist</p>
                    <h4 class="text-4xl font-black text-slate-900 tracking-tighter">{{ $stats['wishlist_count'] }}</h4>
                </div>
                <div class="w-14 h-14 bg-rose-50 text-rose-500 rounded-[22px] flex items-center justify-center text-2xl group-hover:bg-rose-500 group-hover:text-white transition-all duration-500 shadow-inner">
                    <i class="bi bi-heart-fill"></i>
                </div>
            </div>
            <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-rose-500/5 rounded-full blur-3xl group-hover:bg-rose-500/10 transition-colors"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Recent Orders -->
        <div class="lg:col-span-2 space-y-6">
            <div class="card border-0 shadow-sm overflow-hidden bg-white">
                <div class="px-8 py-6 border-b border-slate-50 flex items-center justify-between">
                    <h3 class="text-sm font-black text-slate-900 tracking-widest uppercase flex items-center gap-3">
                        <i class="bi bi-list-stars text-primary"></i>
                        Recent Orders
                    </h3>
                    <a href="{{ route('user.orders.index') }}" class="text-[10px] font-black text-primary uppercase tracking-widest hover:underline">View All</a>
                </div>
                
                @if($recentOrders->isEmpty())
                    <div class="p-16 text-center">
                        <div class="w-20 h-20 bg-slate-50 text-slate-200 rounded-[30px] flex items-center justify-center text-4xl mx-auto mb-6">
                            <i class="bi bi-bag-x"></i>
                        </div>
                        <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest">No recent orders found</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50/50">
                                    <th class="px-8 py-4 text-[9px] font-black uppercase text-slate-400 tracking-widest">Order ID</th>
                                    <th class="px-8 py-4 text-[9px] font-black uppercase text-slate-400 tracking-widest">Date</th>
                                    <th class="px-8 py-4 text-[9px] font-black uppercase text-slate-400 tracking-widest">Total</th>
                                    <th class="px-8 py-4 text-[9px] font-black uppercase text-slate-400 tracking-widest text-right">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach($recentOrders as $order)
                                    <tr class="hover:bg-slate-50/50 transition-colors cursor-pointer" onclick="window.location='{{ route('user.orders.details', $order->id) }}'">
                                        <td class="px-8 py-5">
                                            <span class="text-xs font-black text-slate-900 uppercase tracking-tighter">{{ $order->order_id }}</span>
                                        </td>
                                        <td class="px-8 py-5">
                                            <span class="text-[11px] font-bold text-slate-500">{{ $order->created_at->format('d M, Y') }}</span>
                                        </td>
                                        <td class="px-8 py-5">
                                            <span class="text-xs font-black text-slate-900">tk. {{ number_format($order->total_price, 2) }}</span>
                                        </td>
                                        <td class="px-8 py-5 text-right">
                                            @php
                                                $statusStyles = match($order->status) {
                                                    'pending' => 'bg-amber-50 text-amber-600 border-amber-100',
                                                    'processing' => 'bg-blue-50 text-blue-600 border-blue-100',
                                                    'completed' => 'bg-green-50 text-green-600 border-green-100',
                                                    'cancelled' => 'bg-rose-50 text-rose-600 border-rose-100',
                                                    default => 'bg-slate-50 text-slate-600 border-slate-100',
                                                };
                                            @endphp
                                            <span class="px-3 py-1 rounded-full border text-[8px] font-black uppercase tracking-widest {{ $statusStyles }}">
                                                {{ $order->status }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Quick Actions & Updates -->
        <div class="space-y-8">
            <div class="card border-0 shadow-sm p-8 bg-slate-900 text-white relative overflow-hidden group">
                <div class="relative z-10">
                    <h3 class="text-lg font-black tracking-tighter uppercase mb-4">Quick Help</h3>
                    <p class="text-xs text-slate-400 font-medium leading-relaxed mb-8">Having trouble with an order? Our support team is ready to help you 24/7.</p>
                    <a href="{{ route('user.messages.index') }}" class="block w-full py-4 bg-primary hover:bg-primary-dark text-white text-center font-black uppercase tracking-widest text-[10px] rounded-xl transition-all shadow-lg shadow-primary/20">
                        Contact Support
                    </a>
                </div>
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-primary/20 rounded-full blur-3xl group-hover:scale-150 transition-transform duration-700"></div>
            </div>

            <div class="card border-0 shadow-sm p-8 space-y-6">
                <h3 class="text-xs font-black text-slate-900 tracking-[0.2em] uppercase">Account Security</h3>
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 bg-green-50 text-green-500 rounded-xl flex items-center justify-center flex-shrink-0">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div>
                        <p class="text-[11px] font-black text-slate-900 uppercase tracking-tighter">Email Verified</p>
                        <p class="text-[10px] font-medium text-slate-400 mt-1">Your account is fully secured.</p>
                    </div>
                </div>
                <div class="pt-4 border-t border-slate-100">
                    <a href="{{ route('user.profile') }}" class="text-[9px] font-black text-primary uppercase tracking-widest flex items-center justify-between group/link">
                        Manage Settings
                        <i class="bi bi-arrow-right group-hover/link:translate-x-1 transition-transform"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection