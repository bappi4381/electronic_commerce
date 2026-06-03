<div class="group relative bg-white border border-slate-200/60 rounded-[32px] p-4 hover:shadow-[0_30px_60px_-15px_rgba(32,167,219,0.1)] transition-all duration-500 hover:-translate-y-2"
     x-data="{ 
        inWishlist: {{ in_array($product->id, $wishlistIds ?? []) ? 'true' : 'false' }},
        toggleWishlist() {
            fetch('{{ route('wishlist.toggle', $product->id) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (response.status === 401) {
                    window.location.href = '{{ route('user.auth.login') }}';
                    return;
                }
                return response.json();
            })
            .then(data => {
                if (data) {
                    this.inWishlist = (data.status === 'added');
                    const counter = document.getElementById('wishlist-counter');
                    if (counter) counter.innerText = data.count;
                    const counterMobile = document.getElementById('wishlist-counter-mobile');
                    if (counterMobile) counterMobile.innerText = data.count;
                    window.dispatchEvent(new CustomEvent('wishlist-changed'));
                }
            })
            .catch(error => console.error('Error:', error));
        }
     }" 
     @wishlist-updated.window="if($event.detail.productId == '{{ $product->id }}') inWishlist = ($event.detail.status === 'added')">
    
    <!-- Image Area -->
    <div class="relative bg-slate-50 rounded-[24px] mb-4 overflow-hidden group-hover:bg-white transition-colors duration-500 h-52 flex items-center justify-center border border-slate-50">
        <!-- Badges -->
        <div class="absolute top-3 left-3 flex flex-col gap-2 z-10">
            @if($product->created_at->diffInDays(now()) < 7)
                <span class="bg-indigo-600 text-white text-[8px] font-black uppercase tracking-widest px-2.5 py-1.5 rounded-lg shadow-lg border border-white/10">New</span>
            @endif
            @if($product->discount > 0)
                <div class="bg-[#20A7DB] text-white text-[8px] font-black px-2.5 py-1.5 rounded-lg shadow-lg border border-white/10">
                    -{{ intval($product->discount) }}%
                </div>
            @endif
        </div>
        
        <!-- Wishlist Shortcut -->
        <button @click="toggleWishlist()" 
                class="absolute top-3 right-3 z-20 w-10 h-10 bg-white/90 backdrop-blur-md rounded-xl flex items-center justify-center transition-all shadow-md border border-slate-100 hover:scale-110 active:scale-95"
                :class="inWishlist ? 'text-[#20A7DB]' : 'text-slate-300 hover:text-[#20A7DB]'">
            <i :class="inWishlist ? 'bi bi-heart-fill' : 'bi bi-heart'"></i>
        </button>

        <a href="{{ route('products.show', $product->id) }}" class="flex items-center justify-center w-full h-full p-5">
            <img src="{{ $product->images->first() ? asset('storage/' . $product->images->first()->image) : 'https://images.unsplash.com/photo-1526733169359-81173747976e?q=80&w=400&auto=format&fit=crop' }}" 
                 onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1526733169359-81173747976e?q=80&w=400&auto=format&fit=crop';"
                 class="max-w-full max-h-full w-auto h-auto object-contain group-hover:scale-110 transition-transform duration-700 mix-blend-multiply" 
                 alt="{{ $product->name }}"
                 style="max-height: 160px;">
        </a>

        <!-- Action Overlay -->
        <div class="absolute bottom-4 left-4 right-4 translate-y-full opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-500 flex gap-2 z-20">
            <form action="{{ route('cart.add') }}" method="POST" class="w-full">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <button type="submit" class="w-full h-12 bg-[#20A7DB] hover:bg-[#1c96c5] text-white rounded-2xl flex items-center justify-center gap-2 font-black text-[10px] uppercase tracking-widest shadow-xl shadow-[#20A7DB]/20 transition-all hover:scale-[1.02] active:scale-95">
                    <i class="bi bi-cart-plus-fill text-base"></i> Add To Cart
                </button>
            </form>
        </div>
    </div>

    <!-- Product Info -->
    <div class="space-y-3 px-1">
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-0.5 text-yellow-500 text-[8px]">
                @for($i=0; $i<5; $i++) <i class="bi bi-star-fill"></i> @endfor
            </div>
            <span class="text-[8px] font-black text-slate-300 uppercase tracking-widest">(4.9)</span>
        </div>
        
        <h3 class="text-[11px] font-black text-slate-900 group-hover:text-[#20A7DB] transition-colors leading-tight uppercase line-clamp-2 min-h-[28px] tracking-tight">
            <a href="{{ route('products.show', $product->id) }}">{{ $product->name }}</a>
        </h3>

        <div class="flex items-baseline justify-between pt-3 border-t border-slate-50">
            <div class="flex flex-col">
                @if($product->discount > 0)
                    <span class="text-[9px] text-slate-300 line-through font-bold leading-none mb-1">tk. {{ number_format($product->price, 0) }}</span>
                    <span class="text-lg font-black text-[#20A7DB] tracking-tighter italic leading-none">tk. {{ number_format($product->discounted_price, 0) }}</span>
                @else
                    <span class="text-lg font-black text-slate-900 tracking-tighter italic leading-none">tk. {{ number_format($product->price, 0) }}</span>
                @endif
            </div>
            
            <div class="flex items-center gap-1.5 px-2 py-1 bg-slate-50 rounded-lg border border-slate-100 group-hover:bg-[#20A7DB]/5 group-hover:border-[#20A7DB]/20 transition-all">
                <span class="w-1 h-1 bg-green-500 rounded-full animate-pulse"></span>
                <span class="text-[7px] font-black text-slate-400 uppercase tracking-widest group-hover:text-[#20A7DB]">In Stock</span>
            </div>
        </div>
    </div>
</div>
