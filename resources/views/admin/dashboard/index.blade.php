@extends('admin.layouts')
@section('title', 'Analytics Overview')
@section('content')

<div class="space-y-10">
    {{-- Header Section --}}
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div>
            <h1 class="text-3xl font-black tracking-tight text-slate-900 leading-none">Business Pulse</h1>
            <p class="text-sm font-bold text-slate-400 uppercase tracking-[0.2em] mt-3">Real-time performance analytics</p>
        </div>
        <div class="flex items-center gap-4">
            <button class="bg-white border border-slate-200 text-slate-600 px-6 py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-slate-50 transition-all flex items-center gap-2">
                <i class="bi bi-download"></i> Export Report
            </button>
            <button class="bg-primary text-white px-8 py-3 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-primary-dark transition-all shadow-lg shadow-primary/20 flex items-center gap-2">
                <i class="bi bi-plus-lg"></i> New Product
            </button>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-8">
        {{-- Total Sales --}}
        <div class="group bg-white p-8 rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/40 hover:shadow-2xl transition-all relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-[100px] -mr-10 -mt-10 group-hover:scale-110 transition-transform"></div>
            <div class="flex flex-col gap-6 relative z-10">
                <div class="w-14 h-14 bg-primary/10 rounded-2xl flex items-center justify-center text-primary text-2xl group-hover:bg-primary group-hover:text-white transition-all">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div>
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Total Revenue</span>
                    <h3 class="text-3xl font-black tracking-tighter mt-1">tk. {{ number_format($totalSales, 2) }}</h3>
                </div>
                <div class="flex items-center gap-2 text-xs font-bold text-green-500">
                    <i class="bi bi-arrow-up-right"></i>
                    <span>+12.5% from last month</span>
                </div>
            </div>
        </div>

        {{-- Total Orders --}}
        <div class="group bg-white p-8 rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/40 hover:shadow-2xl transition-all relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-green-500/5 rounded-bl-[100px] -mr-10 -mt-10 group-hover:scale-110 transition-transform"></div>
            <div class="flex flex-col gap-6 relative z-10">
                <div class="w-14 h-14 bg-green-500/10 rounded-2xl flex items-center justify-center text-green-500 text-2xl group-hover:bg-green-500 group-hover:text-white transition-all">
                    <i class="bi bi-cart-check"></i>
                </div>
                <div>
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Total Orders</span>
                    <h3 class="text-3xl font-black tracking-tighter mt-1">{{ $totalOrders }}</h3>
                </div>
                <div class="flex items-center gap-2 text-xs font-bold text-green-500">
                    <i class="bi bi-arrow-up-right"></i>
                    <span>{{ $totalOrders > 0 ? '+8.2%' : '0%' }} growth rate</span>
                </div>
            </div>
        </div>

        {{-- Total Customers --}}
        <div class="group bg-white p-8 rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/40 hover:shadow-2xl transition-all relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-orange-500/5 rounded-bl-[100px] -mr-10 -mt-10 group-hover:scale-110 transition-transform"></div>
            <div class="flex flex-col gap-6 relative z-10">
                <div class="w-14 h-14 bg-orange-500/10 rounded-2xl flex items-center justify-center text-orange-500 text-2xl group-hover:bg-orange-500 group-hover:text-white transition-all">
                    <i class="bi bi-people"></i>
                </div>
                <div>
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Active Customers</span>
                    <h3 class="text-3xl font-black tracking-tighter mt-1">{{ $totalUsers }}</h3>
                </div>
                <div class="flex items-center gap-2 text-xs font-bold text-orange-500">
                    <i class="bi bi-shield-check"></i>
                    <span>Verified accounts</span>
                </div>
            </div>
        </div>

        {{-- Total Products --}}
        <div class="group bg-white p-8 rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/40 hover:shadow-2xl transition-all relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-purple-500/5 rounded-bl-[100px] -mr-10 -mt-10 group-hover:scale-110 transition-transform"></div>
            <div class="flex flex-col gap-6 relative z-10">
                <div class="w-14 h-14 bg-purple-500/10 rounded-2xl flex items-center justify-center text-purple-500 text-2xl group-hover:bg-purple-500 group-hover:text-white transition-all">
                    <i class="bi bi-box"></i>
                </div>
                <div>
                    <span class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Inventory Items</span>
                    <h3 class="text-3xl font-black tracking-tighter mt-1">{{ $totalProducts }}</h3>
                </div>
                <div class="flex items-center gap-2 text-xs font-bold text-purple-500">
                    <i class="bi bi-check2-circle"></i>
                    <span>Live in store</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Analytics Row --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        {{-- Chart Container --}}
        <div class="xl:col-span-2 bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 p-10">
            <div class="flex items-center justify-between mb-10">
                <div>
                    <h3 class="text-xl font-black tracking-tight text-slate-900 leading-none">Revenue Stream</h3>
                    <span class="text-[9px] text-slate-400 font-black uppercase tracking-[0.3em] mt-1.5 block">6-Month historical data</span>
                </div>
                <div class="flex bg-slate-50 p-1 rounded-xl">
                    <button class="px-4 py-2 text-[10px] font-black uppercase tracking-widest bg-white rounded-lg shadow-sm text-primary">Bar</button>
                    <button class="px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-400 hover:text-slate-600">Line</button>
                </div>
            </div>
            <div class="relative h-[400px]">
                <canvas id="salesChart"></canvas>
            </div>
        </div>

        {{-- Recent Activities / Right Sidebar --}}
        <div class="space-y-8">
            {{-- Low Stock Alert --}}
            <div class="bg-white rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
                <div class="bg-red-500 p-6 flex items-center justify-between">
                    <div class="flex items-center gap-3 text-white">
                        <i class="bi bi-exclamation-octagon-fill text-xl"></i>
                        <span class="text-xs font-black uppercase tracking-widest">Inventory Alerts</span>
                    </div>
                    <span class="bg-white/20 text-white text-[10px] px-2 py-1 rounded font-black">{{ count($lowStockProducts) }} Items</span>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @forelse($lowStockProducts as $product)
                            <div class="flex items-center justify-between group">
                                <div class="flex flex-col min-w-0">
                                    <span class="text-sm font-bold text-slate-700 truncate group-hover:text-primary transition-colors cursor-pointer">{{ $product->name }}</span>
                                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">Stock Level: {{ $product->stock }}</span>
                                </div>
                                <a href="{{ route('admin.products.edit', $product->id) }}" class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 hover:bg-primary hover:text-white transition-all">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            </div>
                        @empty
                            <div class="text-center py-6">
                                <i class="bi bi-check-circle-fill text-3xl text-green-500 opacity-20"></i>
                                <p class="text-xs font-bold text-slate-400 mt-4 uppercase tracking-widest">Stock is healthy</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Quick Links / Status --}}
            <div class="bg-slate-900 rounded-[2rem] p-8 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-primary/20 rounded-bl-[100px] -mr-10 -mt-10"></div>
                <h3 class="text-lg font-black tracking-tight leading-none mb-6">System Health</h3>
                <div class="space-y-6 relative z-10">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">API Status</span>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-black">Operational</span>
                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-500">Database</span>
                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-black">Optimal</span>
                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        </div>
                    </div>
                </div>
                <button class="w-full mt-10 bg-white/10 hover:bg-white/20 transition-all py-3 rounded-xl text-[10px] font-black uppercase tracking-[0.2em] border border-white/5">
                    Run System Diagnostics
                </button>
            </div>
        </div>
    </div>

    {{-- Recent Orders Table --}}
    <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
        <div class="px-10 py-8 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-xl font-black tracking-tight text-slate-900 leading-none">Order Logistics</h3>
                <span class="text-[9px] text-slate-400 font-black uppercase tracking-[0.3em] mt-1.5 block">Most recent transactions</span>
            </div>
            <a href="{{ route('orders.index') }}" class="text-[10px] font-black uppercase tracking-widest text-primary hover:underline">View All Orders <i class="bi bi-arrow-right ml-1"></i></a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-slate-50 text-left">
                        <th class="px-10 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Order ID</th>
                        <th class="px-10 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Customer</th>
                        <th class="px-10 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Payment Status</th>
                        <th class="px-10 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Amount</th>
                        <th class="px-10 py-5 text-[10px] font-black uppercase tracking-widest text-slate-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($recentOrders as $order)
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="px-10 py-6">
                                <span class="text-sm font-black text-slate-900 tracking-tight italic uppercase">#{{ $order->order_id }}</span>
                            </td>
                            <td class="px-10 py-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-[10px] font-black text-slate-400">
                                        {{ strtoupper(substr($order->user->name ?? 'G', 0, 1)) }}
                                    </div>
                                    <span class="text-sm font-bold text-slate-600">{{ $order->user->name ?? 'Guest User' }}</span>
                                </div>
                            </td>
                            <td class="px-10 py-6">
                                @php
                                    $status = $order->payment_status ?? 'unpaid';
                                    $colorClass = $status === 'paid' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600';
                                @endphp
                                <span class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest {{ $colorClass }}">
                                    {{ $status }}
                                </span>
                            </td>
                            <td class="px-10 py-6 text-sm font-black text-slate-900 tracking-tight">
                                tk. {{ number_format($order->total_price, 2) }}
                            </td>
                            <td class="px-10 py-6">
                                <a href="{{ route('orders.show', $order->id) }}" class="opacity-0 group-hover:opacity-100 transition-all text-primary hover:text-primary-dark font-black text-[10px] uppercase tracking-widest">
                                    Details <i class="bi bi-chevron-right ml-1"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Chart Script --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('salesChart');
        
        // Custom gradient
        const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(32, 167, 219, 0.4)');
        gradient.addColorStop(1, 'rgba(32, 167, 219, 0)');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($salesData->pluck('month')) !!},
                datasets: [{
                    label: 'Revenue',
                    data: {!! json_encode($salesData->pluck('total')) !!},
                    backgroundColor: 'rgba(32, 167, 219, 0.8)',
                    hoverBackgroundColor: '#20a7db',
                    borderRadius: 12,
                    barThickness: 35,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleFont: { size: 13, family: 'Outfit', weight: 'bold' },
                        bodyFont: { size: 12, family: 'Outfit' },
                        padding: 12,
                        cornerRadius: 12,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        grid: { color: '#f1f5f9', drawBorder: false },
                        ticks: { 
                            font: { family: 'Outfit', size: 10, weight: 'bold' },
                            color: '#94a3b8',
                            padding: 10
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { 
                            font: { family: 'Outfit', size: 10, weight: 'bold' },
                            color: '#94a3b8',
                            padding: 10
                        }
                    }
                }
            }
        });
    });
</script>

@endsection

