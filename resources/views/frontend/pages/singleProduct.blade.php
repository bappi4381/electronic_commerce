@extends('frontend.layout')

@section('title', $product->name)

@section('content')
<section class="py-12 bg-white" x-data="productPageHandler()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Breadcrumb (Professional) --}}
        <nav class="flex items-center gap-2 mb-8 overflow-x-auto no-scrollbar whitespace-nowrap text-sm font-medium text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-primary transition-colors">Home</a>
            <i class="bi bi-chevron-right text-xs text-slate-300"></i>
            <a href="{{ route('products.index') }}" class="hover:text-primary transition-colors">Products</a>
            <i class="bi bi-chevron-right text-xs text-slate-300"></i>
            <span class="text-slate-900">{{ $product->name }}</span>
        </nav>

        <div class="flex flex-col lg:flex-row gap-12 lg:gap-16">
            <!-- Image Portfolio -->
            <div class="w-full lg:w-1/2 space-y-6">
                <div class="relative aspect-square bg-slate-50 rounded-2xl overflow-hidden border border-slate-100 flex items-center justify-center p-8 group">
                    <img src="{{ asset('storage/' . $product->images->first()->image) }}" 
                         id="mainProductImage"
                         alt="{{ $product->name }}" 
                         class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-[1.02]">
                    
                    {{-- Reaction Badge --}}
                    <div class="absolute top-6 left-6 flex items-center gap-2 bg-white/90 backdrop-blur-sm px-4 py-2 rounded-xl border border-slate-100 shadow-sm z-10">
                        <button @click="toggleLike" 
                                :class="liked ? 'text-red-500' : 'text-slate-400 hover:text-red-500'"
                                class="transition-colors outline-none flex items-center justify-center">
                            <i class="bi text-lg" :class="liked ? 'bi-heart-fill' : 'bi-heart'"></i>
                        </button>
                        <span class="text-sm font-semibold text-slate-700" x-text="count"></span>
                    </div>

                    @if($product->discount)
                        <div class="absolute top-6 right-6 bg-red-500 text-white px-3 py-1 rounded-lg text-sm font-bold shadow-sm">
                            -{{ $product->discount }}%
                        </div>
                    @endif
                </div>
                
                @if($product->images->count() > 1)
                <div class="flex gap-4 overflow-x-auto pb-2 custom-scrollbar snap-x">
                    @foreach($product->images as $image)
                        <button type="button" 
                                class="flex-shrink-0 w-20 h-20 bg-slate-50 rounded-xl border-2 {{ $loop->first ? 'border-primary' : 'border-transparent' }} p-2 transition-colors hover:border-primary snap-center outline-none thumbnail-box"
                                onclick="
                                    document.getElementById('mainProductImage').src = '{{ asset('storage/' . $image->image) }}';
                                    document.querySelectorAll('.thumbnail-box').forEach(el => el.classList.remove('border-primary', 'border-transparent'));
                                    document.querySelectorAll('.thumbnail-box').forEach(el => el.classList.add('border-transparent'));
                                    this.classList.remove('border-transparent');
                                    this.classList.add('border-primary');
                                ">
                            <img src="{{ asset('storage/' . $image->image) }}" class="w-full h-full object-contain">
                        </button>
                    @endforeach
                </div>
                @endif
            </div>

            <!-- Product Intel -->
            <div class="w-full lg:w-1/2 flex flex-col justify-center">
                <div class="mb-8 space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-semibold text-primary">{{ $product->category->name ?? 'Electronics' }}</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                        @if($product->stock > 0)
                            <span class="text-sm font-medium text-emerald-600 flex items-center gap-1.5">
                                <i class="bi bi-check-circle-fill"></i> In Stock ({{ $product->stock }})
                            </span>
                        @else
                            <span class="text-sm font-medium text-red-500 flex items-center gap-1.5">
                                <i class="bi bi-x-circle-fill"></i> Out of Stock
                            </span>
                        @endif
                    </div>
                    
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-slate-900 tracking-tight leading-tight">{{ $product->name }}</h1>
                    
                    <div class="flex items-baseline gap-4 mt-2">
                        <span class="text-3xl font-bold text-slate-900">৳ {{ number_format($product->price, 0) }}</span>
                        @if($product->discount)
                            <span class="text-lg text-slate-400 line-through">৳ {{ number_format($product->price * (1 + $product->discount/100), 0) }}</span>
                        @endif
                    </div>
                </div>

                {{-- Key Specs Grid --}}
                <div class="grid grid-cols-2 gap-6 py-6 border-y border-slate-100 mb-8">
                    @foreach([['Brand','brand'],['Model','model'],['SKU','product_id'],['Warranty','warranty_period']] as $spec)
                        <div>
                            <p class="text-sm text-slate-500 mb-1">{{ $spec[0] }}</p>
                            <p class="text-base font-semibold text-slate-900">{{ $product->{$spec[1]} ?? 'Standard' }}</p>
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-col sm:flex-row gap-3 mb-8">
                    <form action="{{ route('cart.add') }}" method="POST" class="flex-1 flex gap-3">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        
                        <button type="submit" {{ $product->stock <= 0 ? 'disabled' : '' }}
                                class="flex-1 bg-slate-900 text-white py-4 px-4 rounded-xl font-semibold text-sm flex items-center justify-center gap-2 hover:bg-slate-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="bi bi-cart-plus text-lg"></i>
                            Add to Cart
                        </button>

                        <button type="submit" name="buy_now" value="1" {{ $product->stock <= 0 ? 'disabled' : '' }}
                                class="flex-1 bg-primary text-white py-4 px-4 rounded-xl font-semibold text-sm flex items-center justify-center gap-2 hover:bg-primary-dark shadow-lg shadow-primary/30 hover:shadow-primary/50 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="bi bi-bag-check text-lg"></i>
                            Order Now
                        </button>
                    </form>

                    <button x-data @click.prevent="$dispatch('toggle-wishlist', { id: {{ $product->id }} })" 
                            class="w-full sm:w-auto py-4 px-6 bg-white border border-slate-200 rounded-xl text-slate-600 hover:text-red-500 hover:border-red-200 hover:bg-red-50 transition-colors font-medium flex items-center justify-center gap-2" title="Add to Wishlist">
                        <i class="bi bi-heart text-lg"></i>
                    </button>
                </div>

                {{-- Trust Badge --}}
                <div class="p-5 bg-slate-50 rounded-xl border border-slate-100 flex items-center gap-4">
                    <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center text-emerald-600 shadow-sm shrink-0">
                        <i class="bi bi-shield-check text-xl"></i>
                    </div>
                    <div>
                        <h6 class="text-sm font-semibold text-slate-900">Authentic Product</h6>
                        <p class="text-sm text-slate-500 mt-0.5">100% Genuine product sourced from official distributors.</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Interactive Information Hub --}}
        <div class="mt-20">
            {{-- Tab Triggers --}}
            <div class="flex flex-wrap justify-start gap-2 mb-8 border-b border-slate-200">
                <template x-for="tab in tabs" :key="tab.id">
                    <button @click="currentTab = tab.id" 
                            :class="currentTab === tab.id ? 'border-primary text-primary' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                            class="px-6 py-4 text-base font-medium border-b-2 transition-colors flex items-center gap-2 outline-none">
                        <span x-text="tab.label"></span>
                        <template x-if="tab.id === 'discussions' && count > 0">
                            <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full text-xs" x-text="count"></span>
                        </template>
                    </button>
                </template>
            </div>

            <div>
                {{-- Overview Tab --}}
                <div x-show="currentTab === 'overview'" x-transition:enter="transition ease-out duration-200 opacity-0" x-cloak>
                    <div class="prose prose-slate max-w-none text-slate-600 leading-relaxed">
                        {!! $product->description !!}
                    </div>
                </div>

                {{-- Specs Tab --}}
                <div x-show="currentTab === 'specs'" x-transition:enter="transition ease-out duration-200 opacity-0" x-cloak>
                    <div class="space-y-8 max-w-4xl">
                        {{-- General Specifications (Quick Specs) --}}
                        @php
                            $quickSpecs = [
                                'Brand' => $product->brand,
                                'Model' => $product->model,
                                'RAM' => $product->ram,
                                'Storage' => $product->storage,
                                'Battery' => $product->battery_capacity,
                                'Screen Size' => $product->screen_size,
                                'Operating System' => $product->operating_system,
                                'Color' => $product->color,
                                'Warranty' => $product->warranty_period,
                            ];
                            $hasQuickSpecs = collect($quickSpecs)->filter()->isNotEmpty();
                        @endphp

                        @if($hasQuickSpecs)
                            <div>
                                <h4 class="text-lg font-semibold text-slate-900 mb-4">General Features</h4>
                                <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                                    <table class="w-full text-sm text-left">
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($quickSpecs as $label => $val)
                                                @if(!empty($val))
                                                <tr class="hover:bg-slate-50 transition-colors">
                                                    <th class="px-6 py-4 font-medium text-slate-500 w-1/3 bg-slate-50/50">{{ $label }}</th>
                                                    <td class="px-6 py-4 text-slate-900">{{ $val }}</td>
                                                </tr>
                                                @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif

                        {{-- Dynamic Specifications --}}
                        @php
                            $validDynamicSpecs = collect($product->specifications ?? [])->filter(function($group) {
                                return !empty($group['title']) || (!empty($group['attributes']) && collect($group['attributes'])->filter(fn($attr) => !empty($attr['label']) || !empty($attr['value']))->isNotEmpty());
                            });
                        @endphp

                        @if($validDynamicSpecs->isNotEmpty())
                            @foreach($validDynamicSpecs as $group)
                                <div>
                                    <h4 class="text-lg font-semibold text-slate-900 mb-4">{{ $group['title'] ?? 'Other Features' }}</h4>
                                    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
                                        <table class="w-full text-sm text-left">
                                            <tbody class="divide-y divide-slate-100">
                                                @foreach($group['attributes'] as $attr)
                                                    @if(!empty($attr['label']) || !empty($attr['value']))
                                                    <tr class="hover:bg-slate-50 transition-colors">
                                                        <th class="px-6 py-4 font-medium text-slate-500 w-1/3 bg-slate-50/50">{{ $attr['label'] ?? '' }}</th>
                                                        <td class="px-6 py-4 text-slate-900">{{ $attr['value'] ?? '' }}</td>
                                                    </tr>
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        @endif

                        @if(!$hasQuickSpecs && $validDynamicSpecs->isEmpty())
                            <div class="py-12 text-center text-slate-500">
                                <i class="bi bi-card-list text-3xl mb-3 block text-slate-300"></i>
                                <p>No detailed specifications available for this product.</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Discussions Tab --}}
                <div x-show="currentTab === 'discussions'" x-transition:enter="transition ease-out duration-200 opacity-0" x-cloak>
                    <div class="max-w-4xl">
                        @if(session('success'))
                            <div class="mb-8 p-4 bg-emerald-50 text-emerald-700 text-sm font-medium rounded-xl border border-emerald-100">
                                {{ session('success') }}
                            </div>
                        @endif

                        @auth
                            <div class="bg-white border border-slate-200 rounded-xl p-6 mb-12 shadow-sm">
                                <h4 class="text-base font-semibold text-slate-900 mb-4">Write a Review</h4>
                                <form action="{{ route('product.comment', $product->id) }}" method="POST" class="space-y-4">
                                    @csrf
                                    <textarea name="comment" rows="3" required
                                              class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors outline-none resize-y"
                                              placeholder="Share your experience with this product..."></textarea>
                                    <div class="flex justify-end">
                                        <button type="submit" class="px-6 py-2.5 bg-slate-900 text-white font-medium text-sm rounded-lg hover:bg-primary transition-colors">
                                            Post Review
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @else
                            <div class="p-8 bg-slate-50 rounded-xl text-center border border-slate-200 mb-12">
                                <h4 class="text-base font-medium text-slate-900 mb-2">Want to share your thoughts?</h4>
                                <p class="text-sm text-slate-500 mb-6">Please log in to write a review or join the discussion.</p>
                                <a href="{{ route('user.auth.login') }}" class="inline-flex px-6 py-2.5 bg-white text-slate-700 border border-slate-300 rounded-lg font-medium text-sm hover:bg-slate-50 transition-colors shadow-sm">Sign In</a>
                            </div>
                        @endauth

                        <div class="space-y-6">
                            <h4 class="text-lg font-semibold text-slate-900 border-b border-slate-100 pb-4">Customer Reviews ({{ $product->comments->count() }})</h4>
                            
                            @forelse($product->comments->sortByDesc('created_at') as $comment)
                                <div class="py-6 border-b border-slate-100 last:border-0" x-data="{ editing: false }">
                                    <div class="flex gap-4">
                                        <div class="w-12 h-12 bg-slate-100 text-slate-600 rounded-full flex items-center justify-center text-lg font-semibold shrink-0">
                                            {{ substr($comment->user->name, 0, 1) }}
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-2 gap-2">
                                                <div>
                                                    <span class="text-sm font-semibold text-slate-900">{{ $comment->user->name }}</span>
                                                    <span class="text-xs text-slate-500 ml-2">{{ $comment->created_at->format('M d, Y') }}</span>
                                                </div>
                                                
                                                @if(auth()->id() === $comment->user_id)
                                                    <div class="flex items-center gap-3">
                                                        <button @click="editing = !editing" type="button" class="text-sm text-slate-400 hover:text-primary transition-colors">
                                                            <span x-show="!editing">Edit</span>
                                                            <span x-show="editing">Cancel</span>
                                                        </button>
                                                        
                                                        <form x-ref="deleteForm{{ $comment->id }}" action="{{ route('product.comment.delete', $comment->id) }}" method="POST" class="hidden">
                                                            @csrf @method('DELETE')
                                                        </form>
                                                        <button @click="if(confirm('Are you sure you want to delete this review?')) $refs['deleteForm{{ $comment->id }}'].submit()" type="button" class="text-sm text-slate-400 hover:text-red-500 transition-colors">
                                                            Delete
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>

                                            <div x-show="!editing" class="text-sm text-slate-700 leading-relaxed">
                                                {{ $comment->comment }}
                                            </div>

                                            <div x-show="editing" x-cloak class="mt-3">
                                                <form action="{{ route('product.comment.update', $comment->id) }}" method="POST" class="space-y-3">
                                                    @csrf @method('PUT')
                                                    <textarea name="comment" class="w-full bg-white border border-slate-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-primary/20 outline-none" rows="3">{{ $comment->comment }}</textarea>
                                                    <button type="submit" class="px-4 py-2 bg-slate-900 text-white text-xs font-medium rounded hover:bg-primary transition-colors">Save Update</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-12">
                                    <p class="text-slate-500 text-sm">No reviews yet. Be the first to share your experience!</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        @if($relatedProducts->count() > 0)
        <section class="mt-24 pt-16 border-t border-slate-100">
            <h3 class="text-2xl font-bold text-slate-900 mb-8">Related Products</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                @foreach($relatedProducts as $related)
                    <a href="{{ route('products.show', $related->id) }}" class="group block border border-slate-100 rounded-2xl p-4 hover:shadow-lg transition-all bg-white">
                        <div class="aspect-square bg-slate-50 rounded-xl overflow-hidden mb-4 p-4">
                            <img src="{{ asset('storage/' . $related->images->first()->image) }}" class="w-full h-full object-contain transition-transform duration-300 group-hover:scale-110" alt="{{ $related->name }}">
                        </div>
                        <h4 class="text-sm font-semibold text-slate-900 mb-1 line-clamp-2 group-hover:text-primary transition-colors">{{ $related->name }}</h4>
                        <p class="text-sm font-bold text-slate-900">৳ {{ number_format($related->price, 0) }}</p>
                    </a>
                @endforeach
            </div>
        </section>
        @endif

    </div>
</section>

<script>
function productPageHandler() {
    return {
        currentTab: 'overview',
        liked: @json($product->isLikedBy(auth()->user())),
        count: @json($product->reactions->count()),
        tabs: [
            { id: 'overview', label: 'Overview' },
            { id: 'specs', label: 'Specifications' },
            { id: 'discussions', label: 'Reviews' }
        ],
        async toggleLike() {
            if (!@json(auth()->check())) {
                window.location.href = "{{ route('user.auth.login') }}";
                return;
            }
            try {
                const response = await fetch("{{ route('product.react', $product->id) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.liked = data.liked;
                    this.count = data.count;
                }
            } catch (error) {
                console.error('Error toggling reaction:', error);
            }
        }
    }
}
</script>

<style>
    [x-cloak] { display: none !important; }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    
    /* Standardize prose for product description */
    .prose { max-width: 100%; }
    .prose h1, .prose h2, .prose h3 { font-weight: 700; color: #0f172a; margin-top: 2em; margin-bottom: 1em; letter-spacing: -0.025em; }
    .prose p { margin-top: 1.25em; margin-bottom: 1.25em; line-height: 1.75; }
    .prose ul { list-style-type: disc; padding-left: 1.625em; }
    .prose li { margin-top: 0.5em; margin-bottom: 0.5em; }
    .prose table { width: 100%; border-collapse: collapse; margin-top: 2em; margin-bottom: 2em; font-size: 0.875rem; }
    .prose th, .prose td { border: 1px solid #e2e8f0; padding: 0.75rem 1rem; }
    .prose th { background-color: #f8fafc; font-weight: 600; text-align: left; }
</style>
@endsection
