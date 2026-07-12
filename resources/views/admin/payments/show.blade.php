@extends('admin.layouts')
@section('title', 'Payment Details')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900 leading-none">Payment Details</h1>
            <p class="text-sm font-bold text-slate-400 uppercase tracking-[0.2em] mt-2">Transaction #{{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</p>
        </div>
        <a href="{{ route('payments.index') }}" class="bg-white border border-slate-200 text-slate-600 px-5 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-slate-50 transition-all flex items-center gap-2 shadow-sm">
            <i class="bi bi-arrow-left"></i> Back to Ledger
        </a>
    </div>

    {{-- Details Card --}}
    <div class="bg-white rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-slate-100">
            {{-- Payment Info --}}
            <div class="p-8 lg:p-10 space-y-6">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center text-xl">
                        <i class="bi bi-receipt"></i>
                    </div>
                    <h3 class="text-lg font-black tracking-tight text-slate-900">Transaction Info</h3>
                </div>

                <div class="space-y-4">
                    <div>
                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Order ID</span>
                        <span class="text-sm font-bold text-slate-700">{{ $payment->order->order_id ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Amount</span>
                        <span class="text-xl font-black text-slate-900">৳{{ number_format($payment->amount, 2) }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Status</span>
                        @php
                            $statusClass = match($payment->status) {
                                'completed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                'pending'   => 'bg-amber-100 text-amber-700 border-amber-200',
                                default     => 'bg-red-100 text-red-700 border-red-200'
                            };
                        @endphp
                        <span class="inline-block px-3 py-1.5 rounded-lg text-xs font-black uppercase tracking-widest border {{ $statusClass }}">
                            {{ $payment->status }}
                        </span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Method</span>
                        <span class="text-sm font-bold text-slate-700 bg-slate-100 px-3 py-1 rounded-lg">{{ ucfirst($payment->method) }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Transaction ID (Gateway)</span>
                        <span class="text-sm font-bold text-slate-700 font-mono bg-slate-50 px-3 py-1 rounded border border-slate-100">{{ $payment->transaction_id ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>

            {{-- User Info --}}
            <div class="p-8 lg:p-10 space-y-6">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-orange-500/10 text-orange-500 flex items-center justify-center text-xl">
                        <i class="bi bi-person-badge"></i>
                    </div>
                    <h3 class="text-lg font-black tracking-tight text-slate-900">Customer Details</h3>
                </div>

                <div class="space-y-4">
                    <div>
                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Name</span>
                        <span class="text-sm font-bold text-slate-700">{{ $payment->user->name ?? 'Guest / Unknown' }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Email Address</span>
                        <span class="text-sm font-bold text-slate-700">{{ $payment->user->email ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Phone Number</span>
                        <span class="text-sm font-bold text-slate-700">{{ $payment->user->phone ?? '-' }}</span>
                    </div>
                    <div class="pt-4 border-t border-slate-100">
                        <span class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Timestamp</span>
                        <span class="text-sm font-bold text-slate-700">
                            <i class="bi bi-calendar-check mr-1 text-slate-400"></i>
                            {{ $payment->created_at->format('d M Y, h:i A') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
