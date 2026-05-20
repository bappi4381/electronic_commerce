<div class="group relative bg-white border border-slate-100 rounded-[32px] p-4 hover:shadow-[0_20px_40px_-10px_rgba(0,0,0,0.08)] transition-all duration-500 hover:-translate-y-1">
    <!-- Image Area -->
    <div class="relative bg-slate-50 rounded-[24px] overflow-hidden aspect-[4/5] mb-6 flex items-center justify-center p-8 group-hover:bg-slate-100/50 transition-colors">
        <!-- Badges -->
        <div class="absolute top-4 left-4 flex flex-col gap-2 z-10">
            @if($product->created_at->diffInDays(now()) < 7)
                <span class="bg-primary text-white text-[8px] font-black uppercase tracking-widest px-3 py-1.5 rounded-full shadow-lg shadow-primary/30 text-center">New</span>
            @endif
            @if($product->discount > 0)
                <span class="bg-red-500 text-white text-[8px] font-black uppercase tracking-widest px-3 py-1.5 rounded-full shadow-lg shadow-red-500/30 text-center">-{{ intval($product->discount) }}%</span>
            @endif
        </div>
        
        <!-- Wishlist Btn -->
        <div x-data="{ 
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
                        // Update global counter if exists
                        const counter = document.getElementById('wishlist-counter');
                        if (counter) counter.innerText = data.count;
                        // Tell the modal to refresh
                        window.dispatchEvent(new CustomEvent('wishlist-changed'));
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        }" 
        @wishlist-updated.window="if($event.detail.productId == '{{ $product->id }}') inWishlist = ($event.detail.status === 'added')"
        class="absolute top-4 right-4 z-10">
            <button @click="toggleWishlist()" 
                    :class="inWishlist ? 'text-red-500 scale-110 opacity-100 translate-y-0' : 'text-slate-400 opacity-0 translate-y-2'"
                    class="w-10 h-10 bg-white shadow-md rounded-full flex items-center justify-center hover:scale-110 transition-all group-hover:opacity-100 group-hover:translate-y-0">
                <i :class="inWishlist ? 'bi bi-heart-fill' : 'bi bi-heart'"></i>
            </button>
        </div>

        <!-- Image -->
        <img src="{{ $product->images->first() ? asset('storage/' . $product->images->first()->image) : 'https://images.unsplash.com/photo-1526733169359-81173747976e?q=80&w=1470&auto=format&fit=crop' }}" 
             alt="{{ $product->name }}" 
             class="max-w-full max-h-full object-contain group-hover:scale-110 transition-transform duration-700">
        
        <!-- Add to cart overlay -->
        <div class="absolute inset-x-4 bottom-4 translate-y-12 opacity-0 group-hover:translate-y-0 group-hover:opacity-100 transition-all duration-500 z-10">
            <form action="{{ route('cart.add') }}" method="POST">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <button type="submit" class="w-full bg-slate-900 hover:bg-primary text-white py-3.5 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-xl transition-colors flex items-center justify-center gap-2 active:scale-95">
                    <i class="bi bi-bag-plus"></i> Add To Cart
                </button>
            </form>
        </div>
    </div>
    
    <!-- Content Area -->
    <div class="px-2 pb-2 text-center">
        <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest mb-1.5 block">{{ $product->brand ?? 'Premium Brand' }}</span>
        <h3 class="text-sm font-black text-slate-900 mb-2 leading-snug line-clamp-2 min-h-[2.5rem] group-hover:text-primary transition-colors">
            <a href="{{ route('products.show', $product->id) }}" class="no-underline text-inherit block">{{ $product->name }}</a>
        </h3>
        
        <div class="flex items-center justify-center gap-2 mb-3">
            <div class="flex text-yellow-400 text-[10px]">
                <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
            </div>
        </div>

        <div class="flex flex-col items-center">
            @if($product->discount > 0)
                <span class="text-[10px] text-slate-400 line-through font-bold">tk. {{ number_format($product->price, 0) }}</span>
                <div class="text-lg font-black text-primary tracking-tighter">tk. {{ number_format($product->discounted_price, 0) }}</div>
            @else
                <div class="text-lg font-black text-primary tracking-tighter">tk. {{ number_format($product->price, 0) }}</div>
            @endif
        </div>
    </div>
</div>
