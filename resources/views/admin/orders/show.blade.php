@extends('admin.layouts')
@section('title', 'Order Details')

@section('content')
<div class="space-y-8">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900">Order Details</h1>
            <p class="text-sm text-slate-400 mt-1 font-mono">#{{ $order->order_id }}</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.orders.invoice.generate', $order->id) }}"
               class="flex items-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition">
                <i class="bi bi-file-earmark-text"></i> Generate Invoice
            </a>
            @if($order->invoice_path)
                <a href="{{ route('admin.orders.invoice.download', $order->id) }}"
                   class="flex items-center gap-2 bg-green-500 hover:bg-green-600 text-white px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition shadow-md shadow-green-500/20">
                    <i class="bi bi-download"></i> Download
                </a>
            @endif
        </div>
    </div>

    {{-- Info + Status Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Customer Info --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-6">
            <div>
                <h2 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">Customer & Contact Information</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-300 mb-1">Name</p>
                        <p class="text-sm font-bold text-slate-800">{{ $order->user->name ?? 'Guest' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-300 mb-1">Email</p>
                        <p class="text-sm font-bold text-slate-800">{{ $order->user->email ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-300 mb-1">Phone Number</p>
                        <p class="text-sm font-bold text-slate-800">{{ $order->user->phone ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-300 mb-1">Alternative Phone</p>
                        <p class="text-sm font-bold text-slate-800">{{ $order->alt_phone ?? 'None' }}</p>
                    </div>
                </div>
            </div>

            <hr class="border-slate-100">

            <div>
                <h2 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-4">Delivery Address</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-300 mb-1">Full Shipping Address</p>
                        <p class="text-sm font-bold text-slate-800 bg-slate-50 p-3 rounded-lg border border-slate-100">{{ $order->shipping_address ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-300 mb-1">City</p>
                        <p class="text-sm font-bold text-slate-800">{{ $order->city ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-300 mb-1">Region</p>
                        <p class="text-sm font-bold text-slate-800">{{ $order->region ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            @if($order->order_notes)
                <hr class="border-slate-100">
                <div>
                    <h2 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-2">Special Order Notes</h2>
                    <div class="bg-indigo-50/50 border border-indigo-100 text-indigo-950 p-4 rounded-xl text-xs font-medium leading-relaxed">
                        <i class="bi bi-info-circle-fill text-indigo-500 mr-1.5"></i>
                        {{ $order->order_notes }}
                    </div>
                </div>
            @endif

            <hr class="border-slate-100">

            <div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-300 mb-1">Current Status</p>
                        @php
                            $statusColor = match($order->status) {
                                'pending'    => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                'processing' => 'bg-blue-100 text-blue-700 border-blue-200',
                                'completed'  => 'bg-green-100 text-green-700 border-green-200',
                                default      => 'bg-red-100 text-red-700 border-red-200',
                            };
                        @endphp
                        <span class="inline-block px-3 py-1 rounded-xl text-[10px] font-black uppercase tracking-widest border {{ $statusColor }}">
                            {{ ucfirst($order->status) }}
                        </span>
                    </div>
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-300 mb-1">Payment Method</p>
                        <span class="inline-block bg-slate-100 text-slate-700 border border-slate-200 px-3 py-1 rounded-xl text-[10px] font-black uppercase tracking-widest">
                            {{ strtoupper($order->payment_method ?? 'COD') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Status Actions --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <h2 class="text-xs font-black uppercase tracking-widest text-slate-400 mb-5">Update Status</h2>
            <div class="space-y-3">
                <form action="{{ route('orders.updateStatus', [$order->id, 'processing']) }}" method="POST">
                    @csrf @method('PATCH')
                    <button type="submit" class="w-full flex items-center gap-3 bg-blue-50 hover:bg-blue-500 text-blue-600 hover:text-white border border-blue-100 hover:border-blue-500 px-4 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition">
                        <i class="bi bi-arrow-repeat"></i> Mark Processing
                    </button>
                </form>
                <form action="{{ route('orders.updateStatus', [$order->id, 'completed']) }}" method="POST">
                    @csrf @method('PATCH')
                    <button type="submit" class="w-full flex items-center gap-3 bg-green-50 hover:bg-green-500 text-green-600 hover:text-white border border-green-100 hover:border-green-500 px-4 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition">
                        <i class="bi bi-check-circle"></i> Mark Completed
                    </button>
                </form>
                <form action="{{ route('orders.updateStatus', [$order->id, 'cancelled']) }}" method="POST">
                    @csrf @method('PATCH')
                    <button type="submit" class="w-full flex items-center gap-3 bg-red-50 hover:bg-red-500 text-red-600 hover:text-white border border-red-100 hover:border-red-500 px-4 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition">
                        <i class="bi bi-x-circle"></i> Cancel Order
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Order Items --}}
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100">
            <h2 class="text-xs font-black uppercase tracking-widest text-slate-400">Order Items</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">Product</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 text-center">Qty</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Unit Price</th>
                        <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($order->orderItems as $item)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 text-sm font-semibold text-slate-800">{{ $item->product->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="bg-slate-100 text-slate-600 text-xs font-black px-3 py-1 rounded-lg">{{ $item->quantity }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-slate-600 text-right">Tk {{ number_format($item->price, 2) }}</td>
                            <td class="px-6 py-4 text-sm font-black text-slate-800 text-right">Tk {{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t-2 border-slate-100">
                    <tr>
                        <td colspan="3" class="px-6 py-3 text-right text-xs font-black uppercase tracking-widest text-slate-400">Subtotal</td>
                        <td class="px-6 py-3 text-right text-sm font-bold text-slate-700">Tk {{ number_format($order->total_price - $order->delivery_charge, 2) }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="px-6 py-3 text-right text-xs font-black uppercase tracking-widest text-slate-400">Delivery Charge</td>
                        <td class="px-6 py-3 text-right text-sm font-bold text-slate-700">Tk {{ number_format($order->delivery_charge, 2) }}</td>
                    </tr>
                    <tr class="bg-slate-50">
                        <td colspan="3" class="px-6 py-4 text-right text-xs font-black uppercase tracking-widest text-slate-700">Grand Total</td>
                        <td class="px-6 py-4 text-right text-base font-black text-cyan-600">Tk {{ number_format($order->total_price, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>
@endsection
