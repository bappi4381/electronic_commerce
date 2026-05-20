@extends('admin.layouts')
@section('title', 'Marketing & Promotions')

@section('content')
<div class="space-y-10 pb-20">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-center gap-5">
            <div class="w-16 h-16 bg-gradient-to-br from-indigo-600 to-violet-600 rounded-[2rem] flex items-center justify-center text-white shadow-2xl shadow-indigo-600/20">
                <i class="bi bi-megaphone text-2xl"></i>
            </div>
            <div>
                <h1 class="text-3xl font-black tracking-tight text-slate-900 leading-none uppercase">Promotions Hub</h1>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mt-2">Centralized Discount & Coupon Management</p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <div class="bg-white px-6 py-3 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
                <div class="text-right">
                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Active Sales</p>
                    <p class="text-lg font-black text-slate-900 leading-none">{{ $discountedProductsCount }} Items</p>
                </div>
                <div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center text-red-600">
                    <i class="bi bi-lightning-charge-fill text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-100 p-4 rounded-2xl flex items-center gap-4 animate-fadeIn">
            <div class="w-10 h-10 bg-emerald-500 rounded-xl flex items-center justify-center text-white text-xl shadow-lg shadow-emerald-500/20">
                <i class="bi bi-check-lg"></i>
            </div>
            <p class="text-sm font-bold text-emerald-700">{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-10">
        
        {{-- Section 1: Coupon Management --}}
        <div class="space-y-8">
            <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-xl shadow-slate-200/40 relative overflow-hidden">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600">
                        <i class="bi bi-ticket-perforated-fill text-xl"></i>
                    </div>
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-[0.2em] text-slate-700">Forge New Coupon</h4>
                        <p class="text-[9px] font-bold text-slate-400 uppercase mt-1 tracking-widest">Global Promo Codes</p>
                    </div>
                </div>

                <form action="{{ route('coupons.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6 relative">
                    @csrf
                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Promo Code</label>
                        <input type="text" name="code" placeholder="e.g. SAVE20" class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-indigo-600/20 outline-none transition-all uppercase" required>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Type</label>
                        <select name="type" class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-indigo-600/20 outline-none transition-all cursor-pointer" required>
                            <option value="percent">Percentage (%)</option>
                            <option value="fixed">Fixed (TK)</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Value</label>
                        <input type="number" step="0.01" name="value" placeholder="e.g. 10" class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-indigo-600/20 outline-none transition-all" required>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black uppercase tracking-widest text-slate-400 ml-1">Expires On</label>
                        <input type="date" name="expiry_date" class="w-full bg-slate-50 border-none rounded-xl px-4 py-4 text-xs font-bold focus:ring-2 focus:ring-indigo-600/20 outline-none transition-all cursor-pointer">
                    </div>
                    <div class="md:col-span-2 mt-2">
                        <button type="submit" class="w-full bg-slate-900 text-white py-5 rounded-[1.5rem] text-[10px] font-black uppercase tracking-widest shadow-2xl shadow-slate-900/20 hover:scale-[1.01] active:scale-[0.99] transition-all">
                            Initialize Coupon
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-[3rem] border border-slate-100 shadow-xl shadow-slate-200/40 overflow-hidden">
                <div class="px-10 py-8 border-b border-slate-50 bg-slate-50/30 flex items-center justify-between">
                    <h2 class="text-xs font-black text-slate-900 uppercase tracking-[0.2em]">Active Coupon Matrix</h2>
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest">{{ $coupons->count() }} Codes</span>
                </div>
                <div class="divide-y divide-slate-50">
                    @foreach($coupons as $coupon)
                        <div class="px-10 py-6 flex items-center justify-between gap-6 hover:bg-slate-50 transition-colors group">
                            <div class="flex items-center gap-5">
                                <div class="w-12 h-12 bg-white rounded-xl border-2 border-dashed {{ $coupon->status ? 'border-indigo-200 text-indigo-600' : 'border-slate-200 text-slate-300' }} flex items-center justify-center text-lg font-black italic shadow-sm">
                                    {{ $coupon->type == 'percent' ? '%' : '৳' }}
                                </div>
                                <div>
                                    <h3 class="text-sm font-black text-slate-900 uppercase tracking-tighter">{{ $coupon->code }}</h3>
                                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ $coupon->type == 'percent' ? $coupon->value.'%' : 'TK '.number_format($coupon->value, 0) }} OFF</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <form action="{{ route('coupons.toggle', $coupon) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="p-2 rounded-lg {{ $coupon->status ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400' }} transition-all">
                                        <i class="bi {{ $coupon->status ? 'bi-toggle-on' : 'bi-toggle-off' }} text-xl"></i>
                                    </button>
                                </form>
                                <form action="{{ route('coupons.destroy', $coupon) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-9 h-9 rounded-lg bg-slate-50 text-slate-300 hover:bg-red-500 hover:text-white transition-all flex items-center justify-center">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Section 2: Global Product Discount Controller --}}
        <div class="space-y-8">
            <div class="bg-white p-10 rounded-[3rem] border border-slate-100 shadow-xl shadow-slate-200/40 relative overflow-hidden h-full flex flex-col">
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center text-red-600">
                            <i class="bi bi-percent text-xl"></i>
                        </div>
                        <div>
                            <h4 class="text-xs font-black uppercase tracking-[0.2em] text-slate-700">Inventory Sale Controller</h4>
                            <p class="text-[9px] font-bold text-slate-400 uppercase mt-1 tracking-widest">Apply Global Discounts</p>
                        </div>
                    </div>
                </div>

                <div class="flex-grow divide-y divide-slate-50">
                    @foreach($products as $product)
                        <div class="py-6 flex items-center gap-5 group">
                            <div class="w-12 h-16 bg-slate-50 rounded-xl border border-slate-100 overflow-hidden flex-shrink-0">
                                @if($product->images->count())
                                    <img src="{{ asset('storage/' . $product->images->first()->image) }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-slate-200"><i class="bi bi-image"></i></div>
                                @endif
                            </div>
                            <div class="flex-grow min-w-0">
                                <h4 class="text-[11px] font-black text-slate-900 truncate leading-tight uppercase">{{ $product->name }}</h4>
                                <p class="text-[9px] font-bold text-slate-400 mt-1 uppercase tracking-widest">{{ $product->category->name ?? 'General' }}</p>
                                <div class="flex items-center gap-3 mt-2">
                                    <span class="text-[10px] font-black text-slate-900 italic">TK {{ number_format($product->price, 0) }}</span>
                                    @if($product->discount > 0)
                                        <span class="w-1 h-1 bg-red-500 rounded-full"></span>
                                        <span class="text-[10px] font-black text-red-500 italic">Now TK {{ number_format($product->discounted_price, 0) }}</span>
                                    @endif
                                </div>
                            </div>
                            
                            <form action="{{ route('marketing.updateDiscount', $product) }}" method="POST" class="flex items-center gap-2">
                                @csrf
                                <div class="relative w-20">
                                    <input type="number" name="discount" value="{{ $product->discount }}" placeholder="0" 
                                           class="w-full bg-slate-50 border-none rounded-lg px-3 py-3 text-[11px] font-black focus:ring-2 focus:ring-red-500/20 outline-none transition-all pr-8">
                                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[9px] font-black text-slate-300">%</span>
                                </div>
                                <button type="submit" class="w-10 h-10 bg-slate-900 text-white rounded-lg flex items-center justify-center hover:bg-primary transition-all shadow-lg shadow-slate-900/10">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
                
                <div class="mt-8 pt-8 border-t border-slate-50">
                    {{ $products->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .pagination { display: flex; gap: 0.5rem; justify-content: center; }
    .pagination li { list-style: none; }
    .pagination li a, .pagination li span { padding: 0.5rem 1rem; border-radius: 0.75rem; background: #f8fafc; font-size: 10px; font-weight: 900; color: #64748b; text-transform: uppercase; }
    .active span { background: #0ea5e9 !important; color: white !important; }
</style>
@endsection
