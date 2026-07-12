@extends('admin.layouts')
@section('title', 'Payment Transactions')

@section('content')
<div class="space-y-6">
    {{-- Header & Search --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Payment Ledger</h1>
            <p class="text-sm text-slate-500 mt-1">Track all incoming transactions and financial records.</p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 w-full md:w-auto">
            <form action="{{ route('payments.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3 flex-grow lg:min-w-[400px]">
                <div class="relative flex-grow">
                    <i class="bi bi-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Search by Order ID or User..."
                        class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-colors">
                </div>
                @if(request('search'))
                    <a href="{{ route('payments.index') }}" class="bg-slate-100 text-slate-600 px-4 py-2.5 rounded-xl font-bold text-sm hover:bg-slate-200 transition-colors flex items-center justify-center">
                        Clear
                    </a>
                @endif
                <button type="submit" class="bg-primary text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-primary-dark transition-colors flex items-center justify-center shadow-sm">
                    Search
                </button>
            </form>

            <a href="{{ route('payments.export',['type' => 'xlsx']) }}"
                class="bg-green-500 text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-green-600 transition-colors flex items-center justify-center gap-2 shadow-sm whitespace-nowrap">
                <i class="bi bi-file-earmark-excel"></i> Export
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-2xl flex items-center gap-3">
            <i class="bi bi-check-circle-fill text-emerald-500 text-xl"></i>
            <p class="text-sm font-semibold text-emerald-800">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Payments Table --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider">Transaction ID</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider text-center">Order ID</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider text-right">Amount (TK)</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider text-center">Status</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider text-center">Method</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider text-right">Date</th>
                        <th class="px-6 py-4 font-bold text-slate-600 text-xs uppercase tracking-wider text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($payments as $payment)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <span class="font-black text-slate-900 tracking-tighter">#{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="font-bold text-slate-600">{{ $payment->order->order_id ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-xs uppercase">
                                    {{ strtoupper(substr($payment->user->name ?? 'G', 0, 1)) }}
                                </div>
                                <span class="font-bold text-slate-800">{{ $payment->user->name ?? '-' }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="font-black text-slate-900">৳{{ number_format($payment->amount, 2) }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @php
                                $statusClass = match($payment->status) {
                                    'completed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    'pending'   => 'bg-amber-100 text-amber-700 border-amber-200',
                                    default     => 'bg-red-100 text-red-700 border-red-200'
                                };
                            @endphp
                            <span class="px-2.5 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border {{ $statusClass }}">
                                {{ $payment->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-xs font-bold text-slate-500 bg-slate-100 px-2 py-1 rounded-md">{{ ucfirst($payment->method) }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-xs font-semibold text-slate-500">{{ $payment->created_at->format('d M Y, h:i A') }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end items-center gap-2">
                                <a href="{{ route('payments.show', $payment) }}"
                                    class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 hover:bg-indigo-600 hover:text-white transition-colors" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-16 text-center text-slate-500">
                            <i class="bi bi-cash-stack text-5xl block mb-3 text-slate-200"></i>
                            <p class="font-bold text-slate-600">No payments found</p>
                            <p class="text-sm mt-1">Transactions will appear here once processed.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    @if($payments->hasPages())
        <div class="flex justify-end">
            {{ $payments->links() }}
        </div>
    @endif
</div>
@endsection
