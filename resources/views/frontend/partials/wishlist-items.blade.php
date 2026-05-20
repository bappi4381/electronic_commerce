<div class="space-y-6">
    @forelse($wishlistItems as $item)
        @php $product = $item->product; @endphp
        <div class="flex items-center gap-4 bg-slate-50 p-4 rounded-2xl group transition-all hover:bg-white hover:shadow-xl hover:shadow-slate-200/50 border border-transparent hover:border-slate-100">
            <div class="w-20 h-20 bg-white rounded-xl overflow-hidden flex-shrink-0 p-2 border border-slate-100">
                <img src="{{ $product->images->first() ? asset('storage/' . $product->images->first()->image) : 'https://images.unsplash.com/photo-1526733169359-81173747976e?q=80&w=1470&auto=format&fit=crop' }}" 
                     alt="{{ $product->name }}" 
                     class="w-full h-full object-contain group-hover:scale-110 transition-transform duration-500">
            </div>
            <div class="flex-1 min-w-0">
                <h4 class="text-xs font-black text-slate-900 truncate uppercase tracking-tight mb-1 group-hover:text-primary transition-colors">
                    {{ $product->name }}
                </h4>
                <div class="flex items-center gap-3">
                    <span class="text-sm font-black text-primary tracking-tighter">tk.{{ number_format($product->discounted_price, 0) }}</span>
                    @if($product->discount > 0)
                        <span class="text-[10px] text-slate-400 line-through font-bold">tk.{{ number_format($product->price, 0) }}</span>
                    @endif
                </div>
            </div>
            <div class="flex flex-col gap-2">
                <button @click="toggleWishlist('{{ $product->id }}')" class="w-8 h-8 bg-red-50 text-red-500 rounded-lg flex items-center justify-center hover:bg-red-500 hover:text-white transition-all shadow-sm">
                    <i class="bi bi-trash3 text-xs"></i>
                </button>
                <form action="{{ route('cart.add') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <button type="submit" class="w-8 h-8 bg-slate-900 text-white rounded-lg flex items-center justify-center hover:bg-primary transition-all shadow-sm">
                        <i class="bi bi-bag-plus text-xs"></i>
                    </button>
                </form>
            </div>
        </div>
    @empty
        <div class="py-12 text-center">
            <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-200 mx-auto text-3xl mb-4 shadow-inner">
                <i class="bi bi-heart"></i>
            </div>
            <p class="text-xs font-black text-slate-400 uppercase tracking-[0.2em]">Wishlist is empty</p>
        </div>
    @endforelse
</div>
