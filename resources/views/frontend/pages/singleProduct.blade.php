@extends('frontend.layout')

@section('title', $product->name)

@section('content')

@php
    // Prepare Variants Data
    $availableAttributes = collect();
    $variantsData = [];

    if ($product->variants->count() > 0) {
        foreach ($product->variants as $variant) {
            $variantAttributes = [];
            foreach ($variant->attributeValues as $av) {
                $attrId = $av->attribute->id;
                $attrName = $av->attribute->name;
                
                if (!$availableAttributes->has($attrId)) {
                    $availableAttributes->put($attrId, [
                        'id' => $attrId,
                        'name' => $attrName,
                        'values' => collect()
                    ]);
                }
                
                if (!$availableAttributes[$attrId]['values']->contains('id', $av->id)) {
                    $availableAttributes[$attrId]['values']->push([
                        'id' => $av->id,
                        'value' => $av->value
                    ]);
                }
                $variantAttributes[$attrId] = $av->id;
            }
            
            $variantsData[] = [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => $variant->price ? $variant->price : ($product->discounted_price ?? $product->price),
                'base_price' => $product->price,
                'discount' => $product->discount,
                'stock' => $variant->stock,
                'attributes' => $variantAttributes
            ];
        }
    }
@endphp

<section class="pt-12 pb-36 bg-white" x-data="productPageHandler()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Breadcrumb (Professional) --}}
        <nav class="flex items-center gap-2 mb-8 overflow-x-auto no-scrollbar whitespace-nowrap text-sm font-medium text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-primary transition-colors">{{ __('Home') }}</a>
            <i class="bi bi-chevron-right text-xs text-slate-300"></i>
            <a href="{{ route('products.index') }}" class="hover:text-primary transition-colors">{{ __('Products') }}</a>
            <i class="bi bi-chevron-right text-xs text-slate-300"></i>
            <span class="text-slate-900">{{ $product->name }}</span>
        </nav>

        {{-- ═══════════════════════════════════════════════════════
             Product Detail — Two-column layout (Gallery | Info)
        ═══════════════════════════════════════════════════════ --}}
        <div class="flex flex-col lg:flex-row gap-10 relative"
             x-data="imageGallery()"
             x-init="init()">

            {{-- ══ LEFT: Gallery column (fixed 380px on desktop) ══ --}}
            <div class="flex-shrink-0 w-full lg:w-[380px] relative" id="gallery-col">

                {{-- Main image viewer --}}
                <div id="daraz-main-box"
                     class="relative w-full rounded-2xl border border-slate-200 bg-white overflow-hidden select-none"
                     style="aspect-ratio:1/1; cursor:zoom-in;"
                     @mousemove="onZoomMove($event, $el)"
                     @mouseenter="zoomVisible = true"
                     @mouseleave="zoomVisible = false"
                     @click="openLightbox(activeIndex)">

                    {{-- Product images (each absolutely stacked) --}}
                    <div class="absolute inset-0 flex items-center justify-center p-4">
                        @foreach($product->images as $i => $image)
                        <img id="daraz-src-{{ $i }}"
                             src="{{ asset('storage/' . $image->image) }}"
                             alt="{{ $product->name }}"
                             x-show="activeIndex === {{ $i }}"
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-[0.98]"
                             x-transition:enter-end="opacity-100 scale-100"
                             class="w-full h-full object-contain select-none pointer-events-none"
                             draggable="false">
                        @endforeach

                        @if($product->images->count() === 0)
                        <div class="flex flex-col items-center gap-3 text-slate-300">
                            <i class="bi bi-image text-6xl"></i>
                            <span class="text-sm">No images yet</span>
                        </div>
                        @endif
                    </div>

                    {{-- Badges --}}
                    @if($product->discount)
                    <div class="absolute top-3 left-3 bg-[#F05537] text-white text-xs font-bold px-2.5 py-1 rounded-md shadow pointer-events-none z-10">
                        -{{ $product->discount }}%
                    </div>
                    @endif

                    {{-- Wishlist/Heart --}}
                    <div class="absolute top-3 right-3 z-10">
                        <button @click.stop="toggleLike"
                                :class="liked ? 'text-red-500 border-red-200 bg-red-50' : 'text-slate-400 border-slate-200 bg-white hover:text-red-500 hover:bg-red-50'"
                                class="w-9 h-9 border rounded-full flex items-center justify-center shadow-sm transition-all">
                            <i class="bi text-sm" :class="liked ? 'bi-heart-fill' : 'bi-heart'"></i>
                        </button>
                        <p class="text-center text-[10px] text-slate-500 mt-0.5 font-semibold" x-text="count"></p>
                    </div>

                    {{-- Zoom hint icon --}}
                    @if($product->images->count())
                    <div class="absolute bottom-3 left-3 flex items-center gap-1 text-slate-400 text-[11px] pointer-events-none select-none">
                        <i class="bi bi-zoom-in text-sm"></i>
                        <span class="hidden sm:inline">Hover to zoom</span>
                    </div>
                    @endif

                    {{-- Expand to fullscreen --}}
                    @if($product->images->count())
                    <button @click.stop="openLightbox(activeIndex)"
                            class="absolute bottom-3 right-3 w-8 h-8 bg-white/80 backdrop-blur border border-slate-200 rounded-lg flex items-center justify-center text-slate-500 hover:text-slate-900 hover:bg-white transition-all shadow-sm z-10">
                        <i class="bi bi-arrows-fullscreen text-xs"></i>
                    </button>
                    @endif
                </div>

                {{-- ── Thumbnail strip ── --}}
                @if($product->images->count() > 1)
                <div class="flex items-center gap-2 mt-3">
                    <button @click="prev()"
                            :disabled="activeIndex === 0"
                            :class="activeIndex === 0 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-slate-100 cursor-pointer'"
                            class="flex-shrink-0 w-8 h-8 rounded-full border border-slate-200 bg-white flex items-center justify-center text-slate-600 transition-all shadow-sm">
                        <i class="bi bi-chevron-left text-xs"></i>
                    </button>

                    <div class="flex-1 flex gap-2 overflow-x-auto" style="scrollbar-width:none;">
                        @foreach($product->images as $i => $image)
                        <button @click="activeIndex = {{ $i }}; zoomVisible = false"
                                :class="activeIndex === {{ $i }}
                                    ? 'border-[#F05537] shadow-sm ring-2 ring-[#F05537]/20'
                                    : 'border-slate-200 hover:border-slate-400'"
                                class="flex-shrink-0 w-[62px] h-[62px] rounded-xl border-2 bg-white overflow-hidden p-1.5 transition-all outline-none">
                            <img src="{{ asset('storage/' . $image->image) }}"
                                 class="w-full h-full object-contain" alt="">
                        </button>
                        @endforeach
                    </div>

                    <button @click="next()"
                            :disabled="activeIndex === total - 1"
                            :class="activeIndex === total - 1 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-slate-100 cursor-pointer'"
                            class="flex-shrink-0 w-8 h-8 rounded-full border border-slate-200 bg-white flex items-center justify-center text-slate-600 transition-all shadow-sm">
                        <i class="bi bi-chevron-right text-xs"></i>
                    </button>
                </div>
                @endif

                {{-- ── Zoom panel (absolutely positioned, overlays right side) ── --}}
                @if($product->images->count())
                <div id="daraz-zoom-panel"
                     x-show="zoomVisible"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 scale-[0.98]"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-cloak
                     class="hidden lg:block absolute top-0 rounded-2xl border border-slate-200 bg-white shadow-xl overflow-hidden z-30"
                     style="left: calc(380px + 16px); width: 380px; aspect-ratio:1/1; pointer-events:none;">
                    @foreach($product->images as $i => $image)
                    <div x-show="activeIndex === {{ $i }}"
                         class="absolute inset-0"
                         style="overflow:hidden;">
                        <img id="daraz-zoom-img-{{ $i }}"
                             src="{{ asset('storage/' . $image->image) }}"
                             class="absolute select-none pointer-events-none"
                             style="width:250%; height:250%; object-fit:contain; top:0; left:0;"
                             draggable="false">
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- ══ RIGHT: Product Info column ══ --}}
            <div class="flex-1 flex flex-col justify-start min-w-0">

                <div class="mb-5 space-y-3">
                    {{-- Category + Stock status --}}
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-primary bg-primary/5 px-3 py-1 rounded-full">{{ $product->category->name ?? 'Electronics' }}</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>

                        <template x-if="currentVariantStock > 0">
                            <span class="text-sm font-medium flex items-center gap-1.5" :class="currentVariantStock <= 10 ? 'text-amber-500' : 'text-emerald-600'">
                                <i class="bi bi-check-circle-fill"></i>
                                <span x-text="currentVariantStock <= 10 ? `{{ __('Only') }} ${currentVariantStock} {{ __('left!') }}` : `{{ __('In Stock') }} (${currentVariantStock})`"></span>
                            </span>
                        </template>
                        <template x-if="currentVariantStock === 0 && hasVariants">
                            <span class="text-sm font-medium text-red-500 flex items-center gap-1.5">
                                <i class="bi bi-x-circle-fill"></i> {{ __('This variant is Out of Stock') }}
                            </span>
                        </template>
                        <template x-if="currentVariantStock === 0 && !hasVariants">
                            <span class="text-sm font-medium text-red-500 flex items-center gap-1.5">
                                <i class="bi bi-x-circle-fill"></i> {{ __('Currently Out of Stock') }}
                            </span>
                        </template>
                    </div>

                    {{-- Product title --}}
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-black text-slate-900 tracking-tight leading-tight">{{ $product->name }}</h1>

                    {{-- Price row --}}
                    <div class="flex flex-wrap items-baseline gap-3 pt-1">
                        <span class="text-3xl font-black text-slate-900" x-text="`৳ ${formatPrice(currentVariantPrice)}`"></span>
                        <template x-if="currentDiscount > 0">
                            <div class="flex items-baseline gap-3">
                                <span class="text-lg text-slate-400 line-through font-semibold" x-text="`৳ ${formatPrice(currentBasePrice)}`"></span>
                                <span class="bg-[#F05537] text-white text-[10px] font-black px-2 py-0.5 rounded-md uppercase tracking-widest" x-text="`-${currentDiscount}%`"></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Divider --}}
                <div class="border-t border-slate-100 mb-5"></div>

                {{-- Key specs --}}
                <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6">
                    @foreach([['Brand','brand'],['Model','model'],['Warranty','warranty_period']] as $spec)
                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">{{ __($spec[0]) }}</p>
                        <p class="text-sm font-semibold text-slate-900">{{ $product->{$spec[1]} ?? '—' }}</p>
                    </div>
                    @endforeach
                    <div>
                        <p class="text-xs text-slate-400 uppercase tracking-wide mb-0.5">{{ __('SKU') }}</p>
                        <p class="text-sm font-semibold text-slate-900" x-text="currentVariantSku"></p>
                    </div>
                </div>

                {{-- Variant Selector --}}
                @if($availableAttributes->isNotEmpty())
                <div class="space-y-4 mb-6 p-4 bg-slate-50 rounded-xl border border-slate-100">
                    @foreach($availableAttributes as $attr)
                    <div>
                        <h3 class="text-xs font-bold text-slate-700 mb-2 uppercase tracking-wider">{{ $attr['name'] }}</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($attr['values'] as $val)
                            <button @click="selectAttribute({{ $attr['id'] }}, {{ $val['id'] }})"
                                    :class="selectedAttributes[{{ $attr['id'] }}] === {{ $val['id'] }} ? 'bg-slate-900 text-white border-slate-900 shadow-sm' : 'bg-white text-slate-700 border-slate-200 hover:border-slate-400'"
                                    class="px-3.5 py-1.5 border rounded-lg text-sm font-semibold transition-all">
                                {{ $val['value'] }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- CTA Buttons --}}
                <div class="flex flex-col sm:flex-row gap-3 mb-6">
                    <form action="{{ route('cart.add') }}" method="POST" class="flex-1 flex gap-3" @submit="validateCartForm">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="variant_id" :value="currentVariantId">

                        <button type="submit"
                                :disabled="currentVariantStock <= 0 || (hasVariants && !currentVariantId)"
                                class="flex-1 bg-slate-900 text-white py-3.5 px-5 rounded-xl font-bold text-sm flex items-center justify-center gap-2 hover:bg-[#20A7DB] transition-all disabled:opacity-40 disabled:cursor-not-allowed group">
                            <i class="bi bi-cart-plus text-base group-hover:rotate-12 transition-transform"></i>
                            <span x-text="(hasVariants && !currentVariantId) ? '{{ __('Select Options') }}' : '{{ __('Add to Cart') }}'"></span>
                        </button>

                        <button type="submit" name="buy_now" value="1"
                                :disabled="currentVariantStock <= 0 || (hasVariants && !currentVariantId)"
                                class="flex-1 bg-[#20A7DB] text-white py-3.5 px-5 rounded-xl font-bold text-sm flex items-center justify-center gap-2 hover:bg-[#1c96c5] shadow-lg shadow-[#20A7DB]/30 transition-all disabled:opacity-40 disabled:cursor-not-allowed">
                            <i class="bi bi-bag-check text-base"></i>
                            {{ __('Order Now') }}
                        </button>
                    </form>

                    <button @click.prevent="$dispatch('toggle-wishlist', { id: {{ $product->id }} })"
                            class="sm:w-auto py-3.5 px-5 bg-white border border-slate-200 rounded-xl text-slate-500 hover:text-red-500 hover:border-red-200 hover:bg-red-50 transition-colors flex items-center justify-center gap-2" title="{{ __('Add to Wishlist') }}">
                        <i class="bi bi-heart text-base"></i>
                        <span class="sm:hidden text-sm font-medium">Wishlist</span>
                    </button>
                </div>

                {{-- Trust Badge --}}
                <div class="p-4 bg-emerald-50 rounded-xl border border-emerald-100 flex items-center gap-3">
                    <div class="w-9 h-9 bg-white rounded-lg flex items-center justify-center text-emerald-600 shadow-sm shrink-0">
                        <i class="bi bi-shield-check-fill text-lg"></i>
                    </div>
                    <div>
                        <h6 class="text-sm font-bold text-emerald-800">{{ __('Authentic Product') }}</h6>
                        <p class="text-xs text-emerald-700 mt-0.5">{{ __('100% Genuine product sourced from official distributors.') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Lightbox Overlay ── --}}

        @if($product->images->count())
        <div x-show="lightboxOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @keydown.escape.window="lightboxOpen = false"
             @keydown.arrow-left.window="lightboxPrev()"
             @keydown.arrow-right.window="lightboxNext()"
             class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/90 backdrop-blur-sm"
             x-cloak>

            {{-- Close --}}
            <button @click="lightboxOpen = false"
                    class="absolute top-5 right-5 w-10 h-10 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center transition-colors z-10">
                <i class="bi bi-x-lg text-lg"></i>
            </button>

            {{-- Counter --}}
            <div class="absolute top-5 left-1/2 -translate-x-1/2 text-white/70 text-sm font-medium">
                <span x-text="lightboxIndex + 1"></span> / {{ $product->images->count() }}
            </div>

            {{-- Main lightbox image --}}
            <div class="relative max-w-4xl max-h-[80vh] w-full mx-6 flex items-center justify-center">
                @foreach($product->images as $i => $image)
                <img x-show="lightboxIndex === {{ $i }}"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     src="{{ asset('storage/' . $image->image) }}"
                     class="max-w-full max-h-[75vh] object-contain rounded-xl select-none"
                     alt="{{ $product->name }}" x-cloak>
                @endforeach
            </div>

            {{-- Prev / Next --}}
            @if($product->images->count() > 1)
            <button @click="lightboxPrev()"
                    class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center transition-colors">
                <i class="bi bi-chevron-left text-xl"></i>
            </button>
            <button @click="lightboxNext()"
                    class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 bg-white/10 hover:bg-white/20 text-white rounded-full flex items-center justify-center transition-colors">
                <i class="bi bi-chevron-right text-xl"></i>
            </button>

            {{-- Thumbnail strip --}}
            <div class="absolute bottom-5 left-1/2 -translate-x-1/2 flex gap-2 overflow-x-auto max-w-sm px-4" style="scrollbar-width:none;">
                @foreach($product->images as $i => $image)
                <button @click="lightboxIndex = {{ $i }}"
                        :class="lightboxIndex === {{ $i }} ? 'border-white opacity-100' : 'border-transparent opacity-50 hover:opacity-80'"
                        class="flex-shrink-0 w-12 h-12 rounded-lg border-2 overflow-hidden transition-all">
                    <img src="{{ asset('storage/' . $image->image) }}" class="w-full h-full object-contain bg-white/10">
                </button>
                @endforeach
            </div>
            @endif
        </div>
        @endif


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
                                <h4 class="text-lg font-semibold text-slate-900 mb-4">{{ __('General Features') }}</h4>
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
                                <h4 class="text-base font-semibold text-slate-900 mb-4">{{ __('Write a Review') }}</h4>
                                <form action="{{ route('product.comment', $product->id) }}" method="POST" class="space-y-4">
                                    @csrf
                                    <textarea name="comment" rows="3" required
                                              class="w-full bg-slate-50 border border-slate-200 rounded-lg px-4 py-3 text-sm focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors outline-none resize-y"
                                              placeholder="{{ __('Share your experience with this product...') }}"></textarea>
                                    <div class="flex justify-end">
                                        <button type="submit" class="px-6 py-2.5 bg-slate-900 text-white font-medium text-sm rounded-lg hover:bg-primary transition-colors">
                                            {{ __('Post Review') }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @else
                            <div class="p-8 bg-slate-50 rounded-xl text-center border border-slate-200 mb-12">
                                <h4 class="text-base font-medium text-slate-900 mb-2">{{ __('Want to share your thoughts?') }}</h4>
                                <p class="text-sm text-slate-500 mb-6">{{ __('Please log in to write a review or join the discussion.') }}</p>
                                <a href="{{ route('user.auth.login') }}" class="inline-flex px-6 py-2.5 bg-white text-slate-700 border border-slate-300 rounded-lg font-medium text-sm hover:bg-slate-50 transition-colors shadow-sm">{{ __('Sign In') }}</a>
                            </div>
                        @endauth

                        <div class="space-y-6">
                            <h4 class="text-lg font-semibold text-slate-900 border-b border-slate-100 pb-4">{{ __('Customer Reviews') }} ({{ $product->comments->count() }})</h4>
                            
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
                                                            <span x-show="!editing">{{ __('Edit') }}</span>
                                                            <span x-show="editing">{{ __('Cancel') }}</span>
                                                        </button>
                                                        
                                                        <form x-ref="deleteForm{{ $comment->id }}" action="{{ route('product.comment.delete', $comment->id) }}" method="POST" class="hidden">
                                                            @csrf @method('DELETE')
                                                        </form>
                                                        <button @click="if(confirm('Are you sure you want to delete this review?')) $refs['deleteForm{{ $comment->id }}'].submit()" type="button" class="text-sm text-slate-400 hover:text-red-500 transition-colors">
                                                            {{ __('Delete') }}
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
                                                    <button type="submit" class="px-4 py-2 bg-slate-900 text-white text-xs font-medium rounded hover:bg-primary transition-colors">{{ __('Save Update') }}</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-12">
                                    <p class="text-slate-500 text-sm">{{ __('No reviews yet. Be the first to share your experience!') }}</p>
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
            <h3 class="text-2xl font-bold text-slate-900 mb-8">{{ __('Related Products') }}</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                @foreach($relatedProducts as $related)
                    <a href="{{ route('products.show', $related->id) }}" class="group block border border-slate-100 rounded-2xl p-4 hover:shadow-lg transition-all bg-white">
                        <div class="aspect-square bg-slate-50 rounded-xl overflow-hidden mb-4 p-4">
                            @if($related->images->count())
                                <img src="{{ asset('storage/' . $related->images->first()->image) }}" class="w-full h-full object-contain transition-transform duration-300 group-hover:scale-110" alt="{{ $related->name }}">
                            @endif
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
            { id: 'overview', label: '{{ __('Overview') }}' },
            { id: 'specs', label: '{{ __('Specifications') }}' },
            { id: 'discussions', label: '{{ __('Reviews') }}' }
        ],
        
        // Variants state
        variants: @json($variantsData),
        hasVariants: @json(count($variantsData) > 0),
        selectedAttributes: {},
        currentVariantId: null,
        currentVariantPrice: @json($product->discounted_price ?? $product->price),
        currentBasePrice: @json($product->price),
        currentDiscount: @json($product->discount),
        currentVariantStock: @json($product->total_stock ?? $product->stock ?? 0),
        currentVariantSku: @json($product->product_id),

        init() {
            // Auto-select first variant if variants exist
            if (this.hasVariants && this.variants.length > 0) {
                const firstVariant = this.variants[0];
                this.selectedAttributes = { ...firstVariant.attributes };
                this.updateVariantInfo();
            }
        },

        selectAttribute(attrId, valueId) {
            this.selectedAttributes[attrId] = valueId;
            this.updateVariantInfo();
        },

        updateVariantInfo() {
            // Find a variant that matches all selected attributes
            const matchedVariant = this.variants.find(variant => {
                for (const [attrId, valueId] of Object.entries(this.selectedAttributes)) {
                    if (variant.attributes[attrId] != valueId) {
                        return false;
                    }
                }
                return true;
            });

            if (matchedVariant) {
                this.currentVariantId = matchedVariant.id;
                this.currentVariantPrice = matchedVariant.price;
                this.currentBasePrice = matchedVariant.base_price;
                this.currentDiscount = matchedVariant.discount;
                this.currentVariantStock = matchedVariant.stock;
                this.currentVariantSku = matchedVariant.sku || this.currentVariantSku;
            } else {
                this.currentVariantId = null;
                this.currentVariantStock = 0; // Not available combination
            }
        },

        formatPrice(price) {
            return new Intl.NumberFormat('en-IN').format(price);
        },

        validateCartForm(e) {
            if (this.hasVariants && !this.currentVariantId) {
                e.preventDefault();
                alert('{{ __('Please select all options before adding to cart.') }}');
            }
        },

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
function imageGallery() {
    return {
        activeIndex: 0,
        lightboxOpen: false,
        lightboxIndex: 0,
        zoomVisible: false,
        total: {{ $product->images->count() }},

        init() {
            this.activeIndex = 0;
        },

        prev() {
            if (this.activeIndex > 0) this.activeIndex--;
        },

        next() {
            if (this.activeIndex < this.total - 1) this.activeIndex++;
        },

        openLightbox(index) {
            this.lightboxIndex = index;
            this.lightboxOpen = true;
            this.zoomVisible = false;
        },

        lightboxPrev() {
            this.lightboxIndex = (this.lightboxIndex - 1 + this.total) % this.total;
        },

        lightboxNext() {
            this.lightboxIndex = (this.lightboxIndex + 1) % this.total;
        },

        onZoomMove(event, el) {
            // Get bounding rect of the main image box
            const rect = el.getBoundingClientRect();
            const x = event.clientX - rect.left;   // mouse X relative to box
            const y = event.clientY - rect.top;    // mouse Y relative to box
            const pctX = x / rect.width;           // 0..1
            const pctY = y / rect.height;          // 0..1

            // Move the zoomed image inside the zoom box
            // zoom factor is 2.5x (250% width/height set in CSS)
            const zoomFactor = 2.5;
            const zoomBox = document.getElementById('daraz-zoom-box');
            if (!zoomBox) return;
            const zoomW = zoomBox.offsetWidth;
            const zoomH = zoomBox.offsetHeight;

            const imgEl = document.getElementById('daraz-zoom-img-' + this.activeIndex);
            if (!imgEl) return;

            // The img is 250% of the zoom box, so it overflows by (250%-100%) = 150%
            const overflowX = zoomW * (zoomFactor - 1);
            const overflowY = zoomH * (zoomFactor - 1);

            imgEl.style.left = (-pctX * overflowX) + 'px';
            imgEl.style.top  = (-pctY * overflowY) + 'px';
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
