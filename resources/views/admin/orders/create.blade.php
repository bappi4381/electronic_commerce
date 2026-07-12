@extends('admin.layouts')
@section('title', 'Create Manual Order')

@section('content')
<div class="space-y-8 max-w-4xl mx-auto">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight">Create Manual Order</h1>
            <p class="text-sm text-slate-400 mt-1 font-bold uppercase tracking-widest">Admin-generated order entry</p>
        </div>
        <a href="{{ route('orders.index') }}"
           class="flex items-center gap-2 bg-white border border-slate-200 text-slate-600 px-5 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-slate-50 transition-all">
            <i class="bi bi-arrow-left"></i> Back to Orders
        </a>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-2xl flex items-center gap-3">
            <i class="bi bi-check-circle-fill text-emerald-500 text-xl"></i>
            <p class="text-sm font-bold text-emerald-700">{{ session('success') }}</p>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-100 p-4 rounded-2xl flex items-center gap-3">
            <i class="bi bi-exclamation-circle-fill text-red-500 text-xl"></i>
            <p class="text-sm font-bold text-red-700">{{ session('error') }}</p>
        </div>
    @endif
    @if($errors->any())
        <div class="bg-red-50 border border-red-100 p-4 rounded-2xl space-y-1">
            @foreach($errors->all() as $err)
                <p class="text-xs font-bold text-red-600 flex items-center gap-2">
                    <i class="bi bi-x-circle-fill"></i> {{ $err }}
                </p>
            @endforeach
        </div>
    @endif

    <form action="{{ route('orders.store') }}" method="POST" class="space-y-6">
        @csrf

        {{-- Customer Info Card --}}
        <div class="bg-white rounded-[2rem] border border-slate-100 shadow-lg shadow-slate-200/40 overflow-hidden">
            <div class="px-8 py-5 bg-slate-50 border-b border-slate-100 flex items-center gap-3">
                <div class="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center text-primary">
                    <i class="bi bi-person-fill"></i>
                </div>
                <h2 class="text-xs font-black uppercase tracking-[0.2em] text-slate-800">Customer Information</h2>
            </div>
            <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-1.5">
                    <label class="text-[9px] font-black uppercase tracking-widest text-slate-400">
                        Email <span class="text-red-400">*</span>
                    </label>
                    <input type="email" name="email" value="{{ old('email') }}"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all"
                        placeholder="customer@example.com" required>
                    <p class="text-[9px] text-slate-400 font-bold">নতুন ইমেইল হলে account auto-create হবে।</p>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[9px] font-black uppercase tracking-widest text-slate-400">
                        Customer Name <span class="text-red-400">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all"
                        placeholder="Full name" required>
                </div>
                <div class="space-y-1.5">
                    <label class="text-[9px] font-black uppercase tracking-widest text-slate-400">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all"
                        placeholder="01XXXXXXXXX">
                </div>
                <div class="space-y-1.5">
                    <label class="text-[9px] font-black uppercase tracking-widest text-slate-400">Delivery Address</label>
                    <input type="text" name="address" value="{{ old('address') }}"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-primary focus:ring-2 focus:ring-primary/10 transition-all"
                        placeholder="Street address...">
                </div>
            </div>
        </div>

        {{-- Products Selection Card --}}
        <div class="bg-white rounded-[2rem] border border-slate-100 shadow-lg shadow-slate-200/40 overflow-hidden">
            <div class="px-8 py-5 bg-slate-50 border-b border-slate-100 flex items-center gap-3">
                <div class="w-8 h-8 bg-indigo-500/10 rounded-lg flex items-center justify-center text-indigo-600">
                    <i class="bi bi-box-seam-fill"></i>
                </div>
                <h2 class="text-xs font-black uppercase tracking-[0.2em] text-slate-800">
                    Select Products <span class="text-red-400">*</span>
                </h2>
            </div>
            <div class="p-8">
                <div class="space-y-3">
                    @foreach($products as $product)
                        @php
                            $productName = is_array($product->name)
                                ? ($product->name['en'] ?? 'N/A')
                                : ($product->name ?? 'N/A');
                            $displayPrice = ($product->discounted_price > 0 && $product->discounted_price < $product->price)
                                ? $product->discounted_price
                                : $product->price;
                        @endphp
                        <div class="flex items-center gap-4 p-4 rounded-xl border border-slate-100 bg-slate-50/50 hover:bg-indigo-50/40 hover:border-indigo-100 transition-all group">
                            <input type="checkbox"
                                name="products[]"
                                value="{{ $product->id }}"
                                id="product_{{ $product->id }}"
                                {{ in_array($product->id, old('products', [])) ? 'checked' : '' }}
                                class="w-4 h-4 accent-primary rounded cursor-pointer"
                                onchange="toggleQty(this, {{ $product->id }})">
                            <label for="product_{{ $product->id }}" class="flex-1 cursor-pointer">
                                <span class="text-sm font-bold text-slate-800 block">{{ $productName }}</span>
                                <span class="text-xs text-slate-400 font-bold">
                                    ৳{{ number_format($displayPrice, 2) }}
                                    @if($product->discount > 0)
                                        <span class="text-red-400">{{ $product->discount }}% OFF</span>
                                    @endif
                                    · {{ $product->brand ?? '' }}
                                </span>
                            </label>
                            {{-- Quantity input — shown only when checkbox is checked --}}
                            <div id="qty_box_{{ $product->id }}" class="hidden items-center gap-2">
                                <label class="text-[9px] font-black uppercase tracking-widest text-slate-400">Qty</label>
                                <input type="number"
                                    name="quantities[{{ $product->id }}]"
                                    id="qty_{{ $product->id }}"
                                    value="{{ old('quantities.' . $product->id, 1) }}"
                                    min="1" max="999"
                                    class="w-20 bg-white border border-indigo-200 rounded-lg px-3 py-2 text-sm font-black text-slate-700 outline-none focus:border-primary transition-all text-center">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Payment Method --}}
        <div class="bg-white rounded-[2rem] border border-slate-100 shadow-lg shadow-slate-200/40 overflow-hidden">
            <div class="px-8 py-5 bg-slate-50 border-b border-slate-100 flex items-center gap-3">
                <div class="w-8 h-8 bg-emerald-500/10 rounded-lg flex items-center justify-center text-emerald-600">
                    <i class="bi bi-credit-card-fill"></i>
                </div>
                <h2 class="text-xs font-black uppercase tracking-[0.2em] text-slate-800">Payment Method</h2>
            </div>
            <div class="p-8">
                <div class="grid grid-cols-2 gap-4">
                    <label class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer hover:border-primary/30 hover:bg-primary/5 transition-all has-[:checked]:border-primary has-[:checked]:bg-primary/5">
                        <input type="radio" name="payment_method" value="cod"
                            {{ old('payment_method', 'cod') == 'cod' ? 'checked' : '' }}
                            class="accent-primary w-4 h-4">
                        <div>
                            <span class="text-sm font-black text-slate-800 block">💵 Cash on Delivery</span>
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Pay on receive</span>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer hover:border-indigo-300 hover:bg-indigo-50 transition-all has-[:checked]:border-indigo-400 has-[:checked]:bg-indigo-50">
                        <input type="radio" name="payment_method" value="online"
                            {{ old('payment_method') == 'online' ? 'checked' : '' }}
                            class="accent-indigo-600 w-4 h-4">
                        <div>
                            <span class="text-sm font-black text-slate-800 block">💳 Online Payment</span>
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Card / Mobile banking</span>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex justify-end gap-4">
            <a href="{{ route('orders.index') }}"
               class="px-8 py-3.5 rounded-xl bg-white border border-slate-200 text-slate-600 text-xs font-black uppercase tracking-widest hover:bg-slate-50 transition-all">
                Cancel
            </a>
            <button type="submit"
                class="px-10 py-3.5 rounded-xl bg-primary text-white text-xs font-black uppercase tracking-widest shadow-lg shadow-primary/20 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center gap-2">
                <i class="bi bi-check-lg text-base"></i> Create Order
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Show/hide quantity input when a product checkbox is toggled
    function toggleQty(checkbox, productId) {
        const qtyBox = document.getElementById('qty_box_' + productId);
        const qtyInput = document.getElementById('qty_' + productId);
        if (checkbox.checked) {
            qtyBox.classList.remove('hidden');
            qtyBox.classList.add('flex');
            qtyInput.value = qtyInput.value || 1;
        } else {
            qtyBox.classList.add('hidden');
            qtyBox.classList.remove('flex');
            qtyInput.value = 1;
        }
    }

    // On page load, restore checked state (after validation fail)
    document.querySelectorAll('input[name="products[]"]:checked').forEach(cb => {
        const productId = cb.value;
        const qtyBox = document.getElementById('qty_box_' + productId);
        if (qtyBox) {
            qtyBox.classList.remove('hidden');
            qtyBox.classList.add('flex');
        }
    });
</script>
@endpush
@endsection
