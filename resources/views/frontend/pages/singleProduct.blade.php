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

<section class="pt-8 pb-32 bg-[#F8FAFC]" x-data="productPageHandler()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- ── Breadcrumb ── --}}
        <nav class="flex items-center gap-2 mb-6 overflow-x-auto no-scrollbar whitespace-nowrap text-[13px] font-semibold text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-primary transition-colors flex items-center gap-1.5"><i class="bi bi-house-door"></i> {{ __('Home') }}</a>
            <i class="bi bi-chevron-right text-[10px] text-slate-300"></i>
            <a href="{{ route('products.index') }}" class="hover:text-primary transition-colors">{{ __('Products') }}</a>
            @if($product->category)
                <i class="bi bi-chevron-right text-[10px] text-slate-300"></i>
                <a href="{{ route('products.index', ['category' => $product->category->id]) }}" class="hover:text-primary transition-colors">{{ $product->category->getTranslation('name', 'en') }}</a>
            @endif
            <i class="bi bi-chevron-right text-[10px] text-slate-300"></i>
            <span class="text-slate-800">{{ $product->name }}</span>
        </nav>

        {{-- ═══════════════════════════════════════════════════════
             Product Detail — Two-column layout
        ═══════════════════════════════════════════════════════ --}}
        <div class="flex flex-col lg:flex-row gap-8 lg:gap-12 relative" x-data="imageGallery()" x-init="init()">

            {{-- ══ LEFT: Gallery column (fixed 420px on desktop) ══ --}}
            <div class="flex-shrink-0 w-full lg:w-[420px] relative z-20" id="gallery-col">

                {{-- Main image viewer --}}
                <div id="daraz-main-box"
                     class="relative w-full rounded-[2rem] border border-white bg-white overflow-hidden select-none shadow-2xl shadow-slate-200/50 group"
                     style="aspect-ratio:1/1; cursor:zoom-in;"
                     @mousemove="onZoomMove($event, $el)"
                     @mouseenter="zoomVisible = true"
                     @mouseleave="zoomVisible = false"
                     @click="openLightbox(activeIndex)">

                    {{-- Product images --}}
                    <div class="absolute inset-0 flex items-center justify-center p-8 transition-transform duration-500 group-hover:scale-105">
                        @foreach($product->images as $i => $image)
                        <img id="daraz-src-{{ $i }}"
                             src="{{ asset('storage/' . $image->image) }}"
                             alt="{{ $product->name }}"
                             x-show="activeIndex === {{ $i }}"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0 scale-[0.95]"
                             x-transition:enter-end="opacity-100 scale-100"
                             class="w-full h-full object-contain select-none pointer-events-none drop-shadow-xl"
                             draggable="false" x-cloak>
                        @endforeach

                        @if($product->images->count() === 0)
                        <div class="flex flex-col items-center gap-3 text-slate-300">
                            <i class="bi bi-image text-6xl"></i>
                            <span class="text-sm font-medium">No images available</span>
                        </div>
                        @endif
                    </div>

                    {{-- Discount Badge (Top Left) --}}
                    @if($product->discount)
                    <div class="absolute top-5 left-5 bg-gradient-to-r from-[#FF3366] to-[#FF6B35] text-white text-xs font-black px-3 py-1.5 rounded-full shadow-lg shadow-red-500/30 pointer-events-none z-10 uppercase tracking-wider">
                        -{{ $product->discount }}% OFF
                    </div>
                    @endif

                    {{-- Floating Actions (Top Right) --}}
                    <div class="absolute top-5 right-5 z-10 flex flex-col gap-2">
                        <button @click.stop="toggleLike"
                                :class="liked ? 'text-[#FF3366] border-[#FF3366]/20 bg-[#FF3366]/10' : 'text-slate-400 border-slate-200 bg-white hover:text-[#FF3366] hover:bg-white'"
                                class="w-10 h-10 backdrop-blur-md rounded-full flex items-center justify-center shadow-sm transition-all hover:scale-110">
                            <i class="bi text-lg transition-transform duration-300" :class="liked ? 'bi-heart-fill scale-110' : 'bi-heart'"></i>
                        </button>
                    </div>

                    {{-- Expand to fullscreen (Bottom Right) --}}
                    @if($product->images->count())
                    <button @click.stop="openLightbox(activeIndex)"
                            class="absolute bottom-5 right-5 w-10 h-10 bg-white/80 backdrop-blur-md border border-slate-200 rounded-full flex items-center justify-center text-slate-600 hover:text-slate-900 hover:bg-white transition-all shadow-sm z-10 hover:scale-110 opacity-0 group-hover:opacity-100">
                        <i class="bi bi-arrows-fullscreen text-sm"></i>
                    </button>
                    @endif
                </div>

                {{-- ── Thumbnail strip ── --}}
                @if($product->images->count() > 1)
                <div class="flex items-center gap-3 mt-4">
                    <button @click="prev()"
                            :disabled="activeIndex === 0"
                            :class="activeIndex === 0 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-white hover:shadow-md cursor-pointer'"
                            class="flex-shrink-0 w-10 h-10 rounded-full border border-slate-200 bg-transparent flex items-center justify-center text-slate-600 transition-all">
                        <i class="bi bi-chevron-left text-sm"></i>
                    </button>

                    <div class="flex-1 flex gap-3 overflow-x-auto px-1 py-1" style="scrollbar-width:none;">
                        @foreach($product->images as $i => $image)
                        <button @click="activeIndex = {{ $i }}; zoomVisible = false"
                                :class="activeIndex === {{ $i }}
                                    ? 'border-primary ring-4 ring-primary/20 shadow-md scale-105'
                                    : 'border-slate-200 hover:border-slate-400 opacity-70 hover:opacity-100'"
                                class="flex-shrink-0 w-[72px] h-[72px] rounded-2xl border-2 bg-white overflow-hidden p-2 transition-all outline-none">
                            <img src="{{ asset('storage/' . $image->image) }}"
                                 class="w-full h-full object-contain mix-blend-multiply" alt="">
                        </button>
                        @endforeach
                    </div>

                    <button @click="next()"
                            :disabled="activeIndex === total - 1"
                            :class="activeIndex === total - 1 ? 'opacity-30 cursor-not-allowed' : 'hover:bg-white hover:shadow-md cursor-pointer'"
                            class="flex-shrink-0 w-10 h-10 rounded-full border border-slate-200 bg-transparent flex items-center justify-center text-slate-600 transition-all">
                        <i class="bi bi-chevron-right text-sm"></i>
                    </button>
                </div>
                @endif

                {{-- ── Zoom panel (Overlays right side) ── --}}
                @if($product->images->count())
                <div id="daraz-zoom-panel"
                     x-show="zoomVisible"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-x-[-20px]"
                     x-transition:enter-end="opacity-100 translate-x-0"
                     x-cloak
                     class="hidden lg:block absolute top-0 rounded-[2rem] border border-slate-100 bg-white shadow-2xl overflow-hidden z-30"
                     style="left: calc(420px + 24px); width: 420px; aspect-ratio:1/1; pointer-events:none;">
                    @foreach($product->images as $i => $image)
                    <div x-show="activeIndex === {{ $i }}" class="absolute inset-0" style="overflow:hidden;" x-cloak>
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
            <div class="flex-1 flex flex-col justify-start min-w-0 z-10 relative">

                {{-- Badges Row --}}
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    @if($product->is_featured)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-100 text-amber-800 text-[10px] font-black uppercase tracking-widest border border-amber-200">
                            <i class="bi bi-star-fill text-amber-500"></i> Featured
                        </span>
                    @endif
                    @if($product->is_best_seller)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase tracking-widest border border-emerald-200">
                            <i class="bi bi-trophy-fill text-emerald-500"></i> Best Seller
                        </span>
                    @endif
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-200 text-slate-700 text-[10px] font-black uppercase tracking-widest border border-slate-300">
                        {{ $product->category->getTranslation('name','en') ?? 'Product' }}
                    </span>
                </div>

                {{-- Title & Reactions --}}
                <div class="mb-5">
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-slate-900 tracking-tight leading-[1.1] mb-3">{{ $product->name }}</h1>
                    <div class="flex items-center gap-4 text-sm font-semibold text-slate-500">
                        <div class="flex items-center gap-1 text-amber-400">
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-fill"></i>
                            <i class="bi bi-star-half"></i>
                            <span class="text-slate-600 ml-1 underline decoration-slate-300 underline-offset-4 cursor-pointer hover:text-primary">({{ $product->comments->count() }} Reviews)</span>
                        </div>
                        <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                        <div class="flex items-center gap-1.5 text-slate-500">
                            <i class="bi bi-heart-fill text-slate-400"></i> <span x-text="count"></span> Likes
                        </div>
                    </div>
                </div>

                {{-- Price Block (Massive Display) --}}
                <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-sm mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Exclusive Price</p>
                        <div class="flex items-baseline gap-3">
                            <span class="text-4xl sm:text-5xl font-black text-slate-900" x-text="`৳${formatPrice(currentVariantPrice)}`"></span>
                            <template x-if="currentDiscount > 0">
                                <span class="text-xl text-slate-400 line-through font-bold decoration-slate-300" x-text="`৳${formatPrice(currentBasePrice)}`"></span>
                            </template>
                        </div>
                    </div>
                    
                    {{-- Stock Indicator inside Price Block --}}
                    <div class="flex items-center sm:justify-end">
                        <template x-if="currentVariantStock > 0">
                            <div class="inline-flex flex-col sm:items-end">
                                <span class="text-sm font-black flex items-center gap-2 px-4 py-2 rounded-xl" :class="currentVariantStock <= 10 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700'">
                                    <i class="bi" :class="currentVariantStock <= 10 ? 'bi-exclamation-circle-fill' : 'bi-check-circle-fill'"></i>
                                    <span x-text="currentVariantStock <= 10 ? `Only ${currentVariantStock} Left!` : `In Stock (${currentVariantStock})`"></span>
                                </span>
                            </div>
                        </template>
                        <template x-if="currentVariantStock === 0">
                            <span class="text-sm font-black flex items-center gap-2 px-4 py-2 rounded-xl bg-red-100 text-red-700">
                                <i class="bi bi-x-circle-fill"></i> Out of Stock
                            </span>
                        </template>
                    </div>
                </div>

                {{-- At A Glance Specs Grid --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8">
                    @foreach([['Brand','brand','bi-tags'],['Model','model','bi-cpu'],['Warranty','warranty_period','bi-shield-check']] as $spec)
                    <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-center items-start group hover:border-primary/30 transition-colors">
                        <i class="bi {{ $spec[2] }} text-slate-400 text-lg mb-2 group-hover:text-primary transition-colors"></i>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">{{ __($spec[0]) }}</p>
                        <p class="text-sm font-bold text-slate-800 line-clamp-1" title="{{ $product->{$spec[1]} ?? '—' }}">{{ $product->{$spec[1]} ?? '—' }}</p>
                    </div>
                    @endforeach
                    <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex flex-col justify-center items-start group hover:border-primary/30 transition-colors">
                        <i class="bi bi-upc-scan text-slate-400 text-lg mb-2 group-hover:text-primary transition-colors"></i>
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">{{ __('SKU') }}</p>
                        <p class="text-sm font-bold text-slate-800 line-clamp-1" x-text="currentVariantSku"></p>
                    </div>
                </div>

                {{-- Variant Selector --}}
                @if($availableAttributes->isNotEmpty())
                <div class="mb-8 space-y-6">
                    @foreach($availableAttributes as $attr)
                    <div>
                        <h3 class="text-xs font-black text-slate-800 mb-3 uppercase tracking-widest flex items-center gap-2">
                            {{ $attr['name'] }}
                            <span class="text-slate-400 font-medium text-[10px] normal-case tracking-normal">(Select one)</span>
                        </h3>
                        <div class="flex flex-wrap gap-3">
                            @foreach($attr['values'] as $val)
                            <button @click="selectAttribute({{ $attr['id'] }}, {{ $val['id'] }})"
                                    :class="selectedAttributes[{{ $attr['id'] }}] === {{ $val['id'] }} ? 'bg-slate-900 text-white border-slate-900 shadow-md shadow-slate-900/20 scale-105 ring-2 ring-slate-900/30 ring-offset-2 ring-offset-[#F8FAFC]' : 'bg-white text-slate-600 border-slate-200 hover:border-slate-400 hover:text-slate-900'"
                                    class="px-5 py-2.5 border-2 rounded-xl text-sm font-bold transition-all duration-300 outline-none">
                                {{ $val['value'] }}
                            </button>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- CTA Buttons --}}
                <div class="mt-auto">
                    <form action="{{ route('cart.add') }}" method="POST" class="flex flex-col sm:flex-row gap-4" @submit="validateCartForm">
                        @csrf
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        <input type="hidden" name="variant_id" :value="currentVariantId">

                        <button type="submit"
                                :disabled="currentVariantStock <= 0 || (hasVariants && !currentVariantId)"
                                class="flex-1 bg-white border-2 border-slate-900 text-slate-900 py-4 px-6 rounded-2xl font-black text-base flex items-center justify-center gap-3 hover:bg-slate-50 transition-all disabled:opacity-40 disabled:cursor-not-allowed group shadow-sm hover:shadow-md">
                            <i class="bi bi-cart-plus text-xl group-hover:-rotate-12 transition-transform duration-300"></i>
                            <span x-text="(hasVariants && !currentVariantId) ? '{{ __('Select Options First') }}' : '{{ __('Add to Cart') }}'"></span>
                        </button>

                        <button type="submit" name="buy_now" value="1"
                                :disabled="currentVariantStock <= 0 || (hasVariants && !currentVariantId)"
                                class="flex-1 bg-gradient-to-r from-primary to-blue-500 text-white py-4 px-6 rounded-2xl font-black text-base flex items-center justify-center gap-3 hover:shadow-xl hover:shadow-primary/30 transition-all duration-300 hover:-translate-y-1 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:translate-y-0 group">
                            <i class="bi bi-lightning-charge-fill text-xl group-hover:scale-110 transition-transform duration-300 text-yellow-300"></i>
                            {{ __('Order Now') }}
                        </button>
                    </form>

                    {{-- Trust Features --}}
                    <div class="mt-6 flex flex-wrap items-center justify-center sm:justify-start gap-4 sm:gap-8 text-xs font-bold text-slate-500">
                        <div class="flex items-center gap-2">
                            <i class="bi bi-shield-check text-emerald-500 text-lg"></i>
                            <span>100% Authentic</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="bi bi-arrow-return-left text-blue-500 text-lg"></i>
                            <span>7 Days Return</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="bi bi-truck text-amber-500 text-lg"></i>
                            <span>Fast Delivery</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ── Detailed Info Section (Tabs) ── --}}
<section class="py-20 bg-white" x-data="productPageHandler()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Modern Segmented Control Tabs --}}
        <div class="flex justify-center mb-12">
            <div class="inline-flex bg-[#F8FAFC] p-1.5 rounded-2xl border border-slate-100 shadow-inner">
                <template x-for="tab in tabs" :key="tab.id">
                    <button @click="currentTab = tab.id" 
                            :class="currentTab === tab.id ? 'bg-white text-slate-900 shadow-md font-black' : 'text-slate-500 hover:text-slate-700 font-bold'"
                            class="px-6 sm:px-10 py-3 text-sm sm:text-base rounded-xl transition-all duration-300 outline-none flex items-center gap-2">
                        <span x-text="tab.label"></span>
                        <template x-if="tab.id === 'discussions' && count > 0">
                            <span :class="currentTab === tab.id ? 'bg-slate-900 text-white' : 'bg-slate-200 text-slate-600'" class="px-2 py-0.5 rounded-full text-[10px] transition-colors" x-text="count"></span>
                        </template>
                    </button>
                </template>
            </div>
        </div>

        <div class="max-w-5xl mx-auto">
            {{-- Overview Tab --}}
            <div x-show="currentTab === 'overview'" x-transition:enter="transition ease-out duration-300 opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                <div class="prose prose-lg prose-slate max-w-none text-slate-600 leading-relaxed font-medium">
                    {!! $product->description !!}
                </div>
            </div>

            {{-- Specs Tab --}}
            <div x-show="currentTab === 'specs'" x-transition:enter="transition ease-out duration-300 opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-8 py-6 bg-slate-50 border-b border-slate-200">
                        <h4 class="text-xl font-black text-slate-900">{{ __('Detailed Specifications') }}</h4>
                        <p class="text-sm text-slate-500 mt-1">Comprehensive technical details of the product.</p>
                    </div>
                    
                    @php
                        $quickSpecs = [
                            'Brand' => $product->brand,
                            'Model' => $product->model,
                            'RAM' => $product->ram,
                            'Storage' => $product->storage,
                            'Battery Capacity' => $product->battery_capacity,
                            'Screen Size' => $product->screen_size,
                            'Operating System' => $product->operating_system,
                            'Color' => $product->color,
                            'Warranty Period' => $product->warranty_period,
                        ];
                        // Merge JSON specifications if any exist
                        if(is_array($product->specifications)) {
                            $quickSpecs = array_merge($quickSpecs, $product->specifications);
                        }
                        $quickSpecs = collect($quickSpecs)->filter();
                    @endphp

                    @if($quickSpecs->isNotEmpty())
                        <table class="w-full text-sm text-left">
                            <tbody class="divide-y divide-slate-100">
                                @foreach($quickSpecs as $label => $val)
                                    <tr class="hover:bg-slate-50/50 transition-colors group">
                                        <th class="px-8 py-5 font-bold text-slate-500 w-1/3 bg-slate-50/30 group-hover:bg-slate-50">{{ $label }}</th>
                                        <td class="px-8 py-5 text-slate-900 font-semibold">{{ $val }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="p-12 text-center text-slate-400">
                            <i class="bi bi-info-circle text-4xl mb-3 block"></i>
                            <p class="font-medium">No detailed specifications available.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Reviews Tab --}}
            <div x-show="currentTab === 'discussions'" x-transition:enter="transition ease-out duration-300 opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" x-cloak>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
                    
                    {{-- Reviews List --}}
                    <div class="lg:col-span-2 space-y-6">
                        <h4 class="text-2xl font-black text-slate-900 mb-8 flex items-center gap-3">
                            {{ __('Customer Reviews') }} 
                            <span class="bg-slate-100 text-slate-600 px-3 py-1 rounded-full text-sm">{{ $product->comments->count() }}</span>
                        </h4>
                        
                        @forelse($product->comments->sortByDesc('created_at') as $comment)
                            <div class="p-6 bg-white border border-slate-100 rounded-3xl shadow-sm hover:shadow-md transition-shadow" x-data="{ editing: false }">
                                <div class="flex gap-5">
                                    <div class="w-14 h-14 bg-gradient-to-br from-primary to-blue-600 text-white rounded-full flex items-center justify-center text-xl font-black shrink-0 shadow-inner">
                                        {{ substr($comment->user->name, 0, 1) }}
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-3 gap-2">
                                            <div>
                                                <span class="text-base font-black text-slate-900">{{ $comment->user->name }}</span>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <div class="flex text-amber-400 text-xs">
                                                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                                                    </div>
                                                    <span class="text-xs font-semibold text-slate-400">{{ $comment->created_at->diffForHumans() }}</span>
                                                </div>
                                            </div>
                                            
                                            @if(auth()->id() === $comment->user_id)
                                                <div class="flex items-center gap-3 bg-slate-50 px-3 py-1.5 rounded-full border border-slate-200">
                                                    <button @click="editing = !editing" type="button" class="text-xs font-bold text-slate-500 hover:text-primary transition-colors flex items-center gap-1">
                                                        <i class="bi bi-pencil-square"></i> <span x-show="!editing">{{ __('Edit') }}</span><span x-show="editing" x-cloak>{{ __('Cancel') }}</span>
                                                    </button>
                                                    <div class="w-px h-3 bg-slate-300"></div>
                                                    <form x-ref="deleteForm{{ $comment->id }}" action="{{ route('product.comment.delete', $comment->id) }}" method="POST" class="hidden">
                                                        @csrf @method('DELETE')
                                                    </form>
                                                    <button @click="if(confirm('Delete this review?')) $refs['deleteForm{{ $comment->id }}'].submit()" type="button" class="text-xs font-bold text-slate-500 hover:text-red-500 transition-colors flex items-center gap-1">
                                                        <i class="bi bi-trash"></i> {{ __('Delete') }}
                                                    </button>
                                                </div>
                                            @endif
                                        </div>

                                        <div x-show="!editing" class="text-sm font-medium text-slate-600 leading-relaxed bg-slate-50/50 p-4 rounded-2xl border border-slate-50">
                                            {{ $comment->comment }}
                                        </div>

                                        <div x-show="editing" x-cloak class="mt-4">
                                            <form action="{{ route('product.comment.update', $comment->id) }}" method="POST" class="space-y-3">
                                                @csrf @method('PUT')
                                                <textarea name="comment" class="w-full bg-white border-2 border-slate-200 rounded-xl px-4 py-3 text-sm focus:border-primary outline-none transition-colors" rows="3">{{ $comment->comment }}</textarea>
                                                <div class="flex justify-end">
                                                    <button type="submit" class="px-5 py-2.5 bg-slate-900 text-white text-xs font-black rounded-lg hover:bg-primary transition-colors shadow-md">{{ __('Save Update') }}</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-16 bg-slate-50 rounded-3xl border border-slate-100">
                                <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm text-slate-300">
                                    <i class="bi bi-chat-left-text text-3xl"></i>
                                </div>
                                <h5 class="text-lg font-bold text-slate-800 mb-1">No Reviews Yet</h5>
                                <p class="text-slate-500 text-sm font-medium">{{ __('Be the first to share your experience with this product!') }}</p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Write Review Sidebar --}}
                    <div class="lg:col-span-1">
                        <div class="sticky top-6">
                            @if(session('success'))
                                <div class="mb-6 p-4 bg-emerald-50 text-emerald-700 text-sm font-bold rounded-2xl border border-emerald-200 flex items-center gap-3 shadow-sm">
                                    <i class="bi bi-check-circle-fill text-lg"></i> {{ session('success') }}
                                </div>
                            @endif

                            @auth
                                <div class="bg-white border border-slate-200 rounded-3xl p-8 shadow-xl shadow-slate-200/40 relative overflow-hidden">
                                    <!-- Decorative bg -->
                                    <div class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-bl-full -z-10"></div>
                                    
                                    <h4 class="text-xl font-black text-slate-900 mb-2">{{ __('Write a Review') }}</h4>
                                    <p class="text-xs font-medium text-slate-500 mb-6">Share your thoughts with other customers.</p>
                                    
                                    <form action="{{ route('product.comment', $product->id) }}" method="POST" class="space-y-4">
                                        @csrf
                                        <textarea name="comment" rows="4" required
                                                  class="w-full bg-slate-50 border-2 border-slate-200 rounded-2xl px-5 py-4 text-sm font-medium focus:bg-white focus:border-primary transition-colors outline-none resize-y"
                                                  placeholder="{{ __('What did you like or dislike?') }}"></textarea>
                                        <button type="submit" class="w-full py-3.5 bg-slate-900 text-white font-black text-sm rounded-xl hover:bg-primary hover:shadow-lg hover:shadow-primary/30 transition-all duration-300 hover:-translate-y-0.5">
                                            {{ __('Post Review') }}
                                        </button>
                                    </form>
                                </div>
                            @else
                                <div class="bg-slate-900 rounded-3xl p-8 text-center relative overflow-hidden shadow-2xl shadow-slate-900/20">
                                    <!-- Decorative bg -->
                                    <div class="absolute -top-10 -right-10 w-40 h-40 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>
                                    <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-primary/20 rounded-full blur-2xl pointer-events-none"></div>

                                    <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center mx-auto mb-5 text-white backdrop-blur-sm border border-white/10">
                                        <i class="bi bi-lock-fill text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-black text-white mb-2">{{ __('Join the Discussion') }}</h4>
                                    <p class="text-sm font-medium text-slate-400 mb-8">{{ __('Log in to share your thoughts, rate products, and interact with the community.') }}</p>
                                    <a href="{{ route('user.auth.login') }}" class="block w-full py-3.5 bg-white text-slate-900 rounded-xl font-black text-sm hover:bg-primary hover:text-white transition-colors shadow-lg">
                                        {{ __('Sign In to Review') }}
                                    </a>
                                </div>
                            @endauth
                        </div>
                    </div>

                </div>
            </div>
        </div>
        
    </div>
</section>

<!-- Related Products -->
@if($relatedProducts->count() > 0)
<section class="py-20 bg-[#F8FAFC] border-t border-slate-200/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between mb-10">
            <div>
                <h3 class="text-3xl font-black text-slate-900 tracking-tight">{{ __('Related Products') }}</h3>
                <p class="text-slate-500 font-medium mt-2">You might also be interested in these items.</p>
            </div>
            <div class="hidden sm:flex gap-2">
                <!-- Optional slider controls if converted to slider -->
            </div>
        </div>
        
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 lg:gap-8">
            @foreach($relatedProducts as $related)
                <a href="{{ route('products.show', $related->id) }}" class="group bg-white rounded-3xl p-4 sm:p-5 border border-slate-100 hover:border-primary/20 hover:shadow-2xl hover:shadow-primary/5 transition-all duration-300 flex flex-col h-full hover:-translate-y-1">
                    <div class="relative w-full aspect-square bg-slate-50 rounded-2xl overflow-hidden mb-5 flex items-center justify-center p-6 group-hover:bg-primary/5 transition-colors">
                        @if($related->images->count())
                            <img src="{{ asset('storage/' . $related->images->first()->image) }}" class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-110 drop-shadow-md" alt="{{ $related->name }}">
                        @endif
                        @if($related->discount)
                            <div class="absolute top-3 left-3 bg-[#FF3366] text-white text-[10px] font-black px-2 py-1 rounded-md uppercase tracking-wider shadow-sm">
                                -{{ $related->discount }}%
                            </div>
                        @endif
                    </div>
                    <div class="mt-auto">
                        <span class="text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1.5 block">{{ $related->category->getTranslation('name','en') ?? 'Product' }}</span>
                        <h4 class="text-sm font-bold text-slate-800 mb-3 line-clamp-2 leading-snug group-hover:text-primary transition-colors">{{ $related->name }}</h4>
                        <div class="flex items-baseline gap-2 mt-auto">
                            <p class="text-lg font-black text-slate-900">৳{{ number_format($related->discounted_price ?? $related->price, 0) }}</p>
                            @if($related->discount)
                                <p class="text-xs font-bold text-slate-400 line-through">৳{{ number_format($related->price, 0) }}</p>
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ── Lightbox Overlay ── --}}
@if($product->images->count())
<div x-data="imageGallery()" x-init="init()" x-show="lightboxOpen"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 backdrop-blur-none"
     x-transition:enter-end="opacity-100 backdrop-blur-md"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 backdrop-blur-md"
     x-transition:leave-end="opacity-0 backdrop-blur-none"
     @keydown.escape.window="lightboxOpen = false"
     @keydown.arrow-left.window="lightboxPrev()"
     @keydown.arrow-right.window="lightboxNext()"
     class="fixed inset-0 z-[9999] flex items-center justify-center bg-slate-900/95"
     x-cloak>

    {{-- Close --}}
    <button @click="lightboxOpen = false"
            class="absolute top-6 right-6 w-12 h-12 bg-white/10 hover:bg-white text-white hover:text-slate-900 rounded-full flex items-center justify-center transition-all duration-300 z-10">
        <i class="bi bi-x-lg text-xl"></i>
    </button>

    {{-- Counter --}}
    <div class="absolute top-8 left-8 text-white/50 text-sm font-bold tracking-widest uppercase">
        <span class="text-white text-lg" x-text="lightboxIndex + 1"></span> / {{ $product->images->count() }}
    </div>

    {{-- Main lightbox image --}}
    <div class="relative w-full max-w-5xl h-full max-h-[85vh] mx-12 flex items-center justify-center">
        @foreach($product->images as $i => $image)
        <img x-show="lightboxIndex === {{ $i }}"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             src="{{ asset('storage/' . $image->image) }}"
             class="max-w-full max-h-full object-contain select-none drop-shadow-2xl"
             alt="{{ $product->name }}" x-cloak>
        @endforeach
    </div>

    {{-- Prev / Next --}}
    @if($product->images->count() > 1)
    <button @click="lightboxPrev()"
            class="absolute left-6 top-1/2 -translate-y-1/2 w-14 h-14 bg-white/5 hover:bg-white text-white hover:text-slate-900 rounded-full flex items-center justify-center transition-all duration-300 backdrop-blur-sm border border-white/10 hover:scale-110">
        <i class="bi bi-chevron-left text-2xl ml-[-2px]"></i>
    </button>
    <button @click="lightboxNext()"
            class="absolute right-6 top-1/2 -translate-y-1/2 w-14 h-14 bg-white/5 hover:bg-white text-white hover:text-slate-900 rounded-full flex items-center justify-center transition-all duration-300 backdrop-blur-sm border border-white/10 hover:scale-110">
        <i class="bi bi-chevron-right text-2xl mr-[-2px]"></i>
    </button>

    {{-- Thumbnail strip --}}
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex gap-3 overflow-x-auto max-w-2xl px-6 py-2" style="scrollbar-width:none;">
        @foreach($product->images as $i => $image)
        <button @click="lightboxIndex = {{ $i }}"
                :class="lightboxIndex === {{ $i }} ? 'border-primary ring-2 ring-primary scale-110 opacity-100' : 'border-white/20 opacity-40 hover:opacity-100'"
                class="flex-shrink-0 w-16 h-16 rounded-xl border-2 overflow-hidden transition-all duration-300 bg-white/5 backdrop-blur-sm p-1.5 outline-none">
            <img src="{{ asset('storage/' . $image->image) }}" class="w-full h-full object-contain drop-shadow-md">
        </button>
        @endforeach
    </div>
    @endif
</div>
@endif

<script>
// Prevent re-declaring if script runs multiple times in some SPAs
if (typeof productPageHandler === 'undefined') {
    window.productPageHandler = function() {
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
}

if (typeof imageGallery === 'undefined') {
    window.imageGallery = function() {
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
                const x = event.clientX - rect.left;
                const y = event.clientY - rect.top;
                const pctX = x / rect.width;
                const pctY = y / rect.height;

                const zoomFactor = 2.5;
                const zoomBox = document.getElementById('daraz-zoom-panel');
                if (!zoomBox) return;
                const zoomW = zoomBox.offsetWidth;
                const zoomH = zoomBox.offsetHeight;

                const imgEl = document.getElementById('daraz-zoom-img-' + this.activeIndex);
                if (!imgEl) return;

                const overflowX = zoomW * (zoomFactor - 1);
                const overflowY = zoomH * (zoomFactor - 1);

                imgEl.style.left = (-pctX * overflowX) + 'px';
                imgEl.style.top  = (-pctY * overflowY) + 'px';
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
    .prose h1, .prose h2, .prose h3, .prose h4 { font-weight: 900; color: #0f172a; margin-top: 1.5em; margin-bottom: 0.75em; letter-spacing: -0.025em; }
    .prose p { margin-top: 1em; margin-bottom: 1em; line-height: 1.8; color: #475569; }
    .prose ul { list-style-type: none; padding-left: 0; margin-top: 1em; margin-bottom: 1em; }
    .prose ul li { position: relative; padding-left: 1.5rem; margin-bottom: 0.5em; color: #475569; }
    .prose ul li::before { content: "•"; position: absolute; left: 0; color: #20A7DB; font-weight: bold; font-size: 1.2em; }
    .prose table { width: 100%; border-collapse: collapse; margin-top: 2em; margin-bottom: 2em; font-size: 0.875rem; border-radius: 0.75rem; overflow: hidden; border: 1px solid #e2e8f0; }
    .prose th, .prose td { border-bottom: 1px solid #e2e8f0; padding: 1rem 1.25rem; }
    .prose th { background-color: #f8fafc; font-weight: 700; text-align: left; color: #334155; }
    .prose img { border-radius: 1rem; margin-top: 2em; margin-bottom: 2em; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); }
</style>
@endsection
