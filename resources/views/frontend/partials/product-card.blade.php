<div class="group relative bg-white p-4 shadow-md hover:shadow-xl transition-all duration-500 hover:-translate-y-1"
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
    <div class="relative bg-white mb-5 overflow-hidden h-[200px] flex items-center justify-center p-2">
        <!-- Badges -->
        <div class="absolute top-2 left-2 z-10 flex flex-col gap-2">
            @if($product->created_at->diffInDays(now()) < 7)
                <span class="bg-indigo-600 text-white text-[9px] font-black uppercase px-2.5 py-1 rounded-full shadow-lg">New</span>
            @endif
            @if($product->discount > 0)
                <div class="text-white text-[11px] font-black px-3 py-1.5 rounded-full shadow-lg flex items-center gap-1" style="background: linear-gradient(to right, #dc2626, #f97316);">
                    <i class="bi bi-lightning-charge-fill"></i> -{{ intval($product->discount) }}%
                </div>
            @endif
        </div>
        
        <!-- Wishlist Shortcut -->
        <button @click="toggleWishlist()" 
                class="absolute top-3 right-3 z-20 w-8 h-8 bg-black/5 rounded-full flex items-center justify-center transition-all hover:bg-black/10"
                :style="inWishlist ? 'color: #20A7DB;' : 'color: #94a3b8;'">
            <i :class="inWishlist ? 'bi bi-heart-fill' : 'bi bi-heart'"></i>
        </button>

        <a href="{{ route('products.show', $product->id) }}" class="flex items-center justify-center w-full h-full p-2">
            <img src="{{ $product->images->first() ? asset('storage/' . $product->images->first()->image) : 'https://images.unsplash.com/photo-1526733169359-81173747976e?q=80&w=400&auto=format&fit=crop' }}" 
                 onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1526733169359-81173747976e?q=80&w=400&auto=format&fit=crop';"
                 class="max-w-full max-h-full w-auto h-auto object-contain group-hover:scale-110 transition-transform duration-700 mix-blend-multiply" 
                 alt="{{ $product->name }}">
        </a>
    </div>

    <!-- Product Info -->
    <div class="px-1 flex flex-col h-[110px] justify-between">
        <div>
            @if($product->brand)
                <div class="text-[10px] uppercase font-black tracking-widest mb-1.5" style="color: #20A7DB;">
                    {{ $product->brand }}
                </div>
            @endif
            
            <h3 class="text-[14px] font-bold leading-snug line-clamp-2 transition-colors text-slate-900">
                <a href="{{ route('products.show', $product->id) }}">{{ $product->name }}</a>
            </h3>
        </div>

        <div class="flex items-end justify-between mt-3">
            <div class="flex flex-col">
                @if($product->discount > 0)
                    <span class="text-[11px] text-slate-400 line-through font-bold leading-none mb-1">৳{{ number_format($product->price, 0) }}</span>
                    <span class="text-xl font-black tracking-tighter leading-none text-slate-900">৳{{ number_format($product->discounted_price, 0) }}</span>
                @else
                    <span class="text-xl font-black tracking-tighter leading-none text-slate-900">৳{{ number_format($product->price, 0) }}</span>
                @endif
            </div>
            
            <form action="{{ route('cart.add') }}" method="POST">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <button type="submit" class="w-10 h-10 flex items-center justify-center transition-all active:scale-95 group/btn" style="background: rgba(32,167,219,0.1); border: 1px solid rgba(32,167,219,0.3); color: #20A7DB;">
                    <i class="bi bi-cart-plus text-lg group-hover/btn:scale-110 transition-transform"></i>
                </button>
            </form>
        </div>
    </div>
</div>
