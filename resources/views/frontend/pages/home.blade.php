@extends('frontend.layout')

@section('title', 'ONEMALL | Premium Electronics Store')

@section('content')
    <!-- Main Hero Slider Area -->
    <section class="relative bg-white pt-8">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-col lg:flex-row gap-6">
                <!-- Main Slider (Left 75%) -->
                <div class="w-full lg:w-3/4">
                    @if($heroBanner)
                    <div class="relative rounded-[2.5rem] overflow-hidden group bg-slate-900 aspect-[16/8] lg:aspect-[16/7] flex items-center shadow-2xl shadow-slate-200/50">
                        <div class="absolute inset-0 bg-gradient-to-r from-slate-900/90 via-slate-900/40 to-transparent z-10"></div>
                        <div class="relative z-20 p-8 lg:p-16 space-y-6 max-w-2xl">
                            <div class="inline-flex items-center gap-3 px-4 py-2 bg-primary/10 border border-primary/20 rounded-full">
                                <span class="w-2 h-2 bg-primary rounded-full animate-pulse"></span>
                                <span class="text-primary font-black uppercase tracking-[0.3em] text-[10px]">{{ $heroBanner->subtitle ?? 'Best Selection' }}</span>
                            </div>
                            <h2 class="text-3xl lg:text-5xl font-black text-white leading-[1.2] tracking-tighter uppercase italic">
                                {!! nl2br(e($heroBanner->title)) !!}
                            </h2>
                            <div class="flex flex-wrap gap-4 pt-4">
                                <a href="{{ $heroBanner->link ?? '#' }}" class="px-8 py-4 bg-primary hover:bg-white hover:text-primary text-white font-black uppercase text-[11px] tracking-[0.2em] rounded-2xl transition-all shadow-xl shadow-primary/30 flex items-center gap-3 group/btn">
                                    Shop Collection <i class="bi bi-arrow-right text-lg group-hover/btn:translate-x-1 transition-transform"></i>
                                </a>
                                <button onclick="window.dispatchEvent(new CustomEvent('open-chat'))" class="px-8 py-4 bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/20 text-white font-black uppercase text-[11px] tracking-[0.2em] rounded-2xl transition-all flex items-center gap-3">
                                    <i class="bi bi-chat-left-text-fill text-primary"></i> Live Support
                                </button>
                            </div>
                        </div>
                        <img src="{{ asset('storage/' . $heroBanner->image) }}" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-[2000ms]" alt="{{ $heroBanner->title }}">
                    </div>
                    @else
                    <div class="relative rounded-[2.5rem] overflow-hidden group bg-slate-900 aspect-[16/8] lg:aspect-[16/7] flex items-center shadow-2xl shadow-slate-200/50">
                        <div class="absolute inset-0 bg-gradient-to-r from-slate-900/90 via-slate-900/40 to-transparent z-10"></div>
                        <div class="relative z-20 p-8 lg:p-16 space-y-6 max-w-2xl">
                            <div class="inline-flex items-center gap-3 px-4 py-2 bg-primary/10 border border-primary/20 rounded-full">
                                <span class="w-2 h-2 bg-primary rounded-full animate-pulse"></span>
                                <span class="text-primary font-black uppercase tracking-[0.3em] text-[10px]">Premium Selection 2026</span>
                            </div>
                            <h2 class="text-3xl lg:text-5xl font-black text-white leading-[1.2] tracking-tighter uppercase italic">
                                Anything you can do,<br><span class="text-primary">you can do better.</span>
                            </h2>
                            <div class="flex flex-wrap gap-4 pt-4">
                                <a href="#" class="px-8 py-4 bg-primary hover:bg-white hover:text-primary text-white font-black uppercase text-[11px] tracking-[0.2em] rounded-2xl transition-all shadow-xl shadow-primary/30 flex items-center gap-3 group/btn">
                                    Explore Now <i class="bi bi-arrow-right text-lg group-hover/btn:translate-x-1 transition-transform"></i>
                                </a>
                                <button onclick="window.dispatchEvent(new CustomEvent('open-chat'))" class="px-8 py-4 bg-white/10 hover:bg-white/20 backdrop-blur-md border border-white/20 text-white font-black uppercase text-[11px] tracking-[0.2em] rounded-2xl transition-all flex items-center gap-3">
                                    <i class="bi bi-chat-left-text-fill text-primary"></i> Live Support
                                </button>
                            </div>
                        </div>
                        <img src="https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?q=80&w=1430&auto=format&fit=crop" class="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:scale-110 transition-transform duration-[2000ms]" alt="Tablet">
                    </div>
                    @endif
                </div>
                <!-- Sub Banners (Right 25%) -->
                <div class="w-full lg:w-1/4 flex flex-col gap-6">
                    @forelse($subBanners as $subBanner)
                    <div class="flex-1 relative rounded-[2rem] overflow-hidden group bg-slate-900 text-white p-10 flex flex-col justify-end border border-white/5 shadow-xl">
                         <div class="relative z-10 space-y-3">
                            <span class="text-[10px] font-black text-primary uppercase tracking-[0.3em]">{{ $subBanner->subtitle }}</span>
                            <h4 class="text-lg lg:text-xl font-black leading-tight uppercase italic tracking-tighter">{{ $subBanner->title }}</h4>
                            <a href="{{ $subBanner->link ?? '#' }}" class="inline-flex items-center gap-2 text-[10px] font-black uppercase text-white border-b-2 border-primary pb-1 group-hover:gap-4 transition-all">Buy Now <i class="bi bi-chevron-right"></i></a>
                         </div>
                         <img src="{{ asset('storage/' . $subBanner->image) }}" class="absolute top-0 right-0 w-full h-full object-cover opacity-40 group-hover:scale-110 transition-transform duration-700" alt="{{ $subBanner->title }}">
                    </div>
                    @empty
                    <div class="flex-1 relative rounded-[2rem] overflow-hidden group bg-slate-900 text-white p-10 flex flex-col justify-end border border-white/5 shadow-xl">
                         <div class="relative z-10 space-y-3">
                            <span class="text-[10px] font-black text-primary uppercase tracking-[0.3em]">iPad Pro 2026</span>
                            <h4 class="text-xl font-black leading-tight uppercase italic tracking-tighter">12.9-inch Liquid<br>Retina Display</h4>
                            <a href="#" class="inline-flex items-center gap-2 text-[10px] font-black uppercase text-white border-b-2 border-primary pb-1 group-hover:gap-4 transition-all">Buy Now <i class="bi bi-chevron-right"></i></a>
                         </div>
                         <img src="https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?q=80&w=500&auto=format&fit=crop" class="absolute top-0 right-0 w-full h-full object-cover opacity-40 group-hover:scale-110 transition-transform duration-700" alt="iPad">
                    </div>
                    <div class="flex-1 relative rounded-[2rem] overflow-hidden group bg-slate-100 p-10 flex flex-col justify-end border border-slate-200 shadow-xl">
                         <div class="relative z-10 space-y-3">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em]">Beats Solo Pro</span>
                            <h4 class="text-xl font-black leading-tight text-slate-900 uppercase italic tracking-tighter">Experience Pure<br>Acoustic Sound</h4>
                            <a href="#" class="inline-flex items-center gap-2 text-[10px] font-black uppercase text-slate-900 border-b-2 border-primary pb-1 group-hover:gap-4 transition-all">Buy Now <i class="bi bi-chevron-right"></i></a>
                         </div>
                         <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?q=80&w=500&auto=format&fit=crop" class="absolute top-0 right-0 w-full h-full object-cover opacity-20 group-hover:scale-110 transition-transform duration-700" alt="Headphones">
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <!-- Info Banner Grid -->
    <section class="py-8 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @forelse($promoBanners->take(3) as $promo)
                <div class="relative h-48 rounded-3xl overflow-hidden group">
                    <img src="{{ asset('storage/' . $promo->image) }}" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" alt="{{ $promo->title }}">
                    <div class="absolute inset-0 bg-slate-900/40 p-8 flex flex-col justify-center items-start text-white {{ $loop->iteration == 2 ? 'text-right ml-auto' : '' }}">
                        <h3 class="text-xl font-black mb-2 uppercase tracking-tighter leading-none">{!! nl2br(e($promo->title)) !!}</h3>
                        <a href="{{ $promo->link ?? '#' }}" class="px-5 py-2 bg-primary text-[10px] font-black uppercase tracking-widest rounded-lg {{ $loop->iteration == 2 ? 'ml-auto' : '' }}">Explore</a>
                    </div>
                </div>
                @empty
                <div class="relative h-48 rounded-3xl overflow-hidden group">
                    <img src="https://images.unsplash.com/photo-1550009158-9ebf69173e03?q=80&w=1000&auto=format&fit=crop" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" alt="Tech">
                    <div class="absolute inset-0 bg-slate-900/40 p-8 flex flex-col justify-center items-start text-white">
                        <h3 class="text-xl font-black mb-2 uppercase tracking-tighter leading-none">Smart Home<br>Systems</h3>
                        <a href="#" class="px-5 py-2 bg-primary text-[10px] font-black uppercase tracking-widest rounded-lg">Explore</a>
                    </div>
                </div>
                <div class="relative h-48 rounded-3xl overflow-hidden group">
                    <img src="https://images.unsplash.com/photo-1593642632823-8f785ba67e45?q=80&w=1000&auto=format&fit=crop" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" alt="Tech">
                    <div class="absolute inset-0 bg-slate-900/40 p-8 flex flex-col justify-center items-start text-white text-right ml-auto">
                        <h3 class="text-xl font-black mb-2 uppercase tracking-tighter leading-none">Gaming<br>Laptops</h3>
                        <a href="#" class="px-5 py-2 bg-primary text-[10px] font-black uppercase tracking-widest rounded-lg ml-auto">Shop Now</a>
                    </div>
                </div>
                <div class="relative h-48 rounded-3xl overflow-hidden group">
                    <img src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=1000&auto=format&fit=crop" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" alt="Tech">
                    <div class="absolute inset-0 bg-slate-900/40 p-8 flex flex-col justify-center items-start text-white">
                        <h3 class="text-xl font-black mb-2 uppercase tracking-tighter leading-none">Wearable<br>Technology</h3>
                        <a href="#" class="px-5 py-2 bg-primary text-[10px] font-black uppercase tracking-widest rounded-lg">Buy Now</a>
                    </div>
                </div>
                @endforelse
            </div>
        </div>
    </section>

    <!-- Service Features -->
    <section class="py-12 bg-white border-y border-slate-100">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
                <div class="flex items-center gap-6 group">
                    <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-3xl text-primary group-hover:bg-primary group-hover:text-white transition-all shadow-inner">
                        <i class="bi bi-truck"></i>
                    </div>
                    <div>
                        <h5 class="text-sm font-black uppercase tracking-tight text-slate-900">Free Shipping</h5>
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">On Orders Over $99</p>
                    </div>
                </div>
                <div class="flex items-center gap-6 group">
                    <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-3xl text-primary group-hover:bg-primary group-hover:text-white transition-all shadow-inner">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div>
                        <h5 class="text-sm font-black uppercase tracking-tight text-slate-900">Money Return</h5>
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">30 Days Guarantee</p>
                    </div>
                </div>
                <div class="flex items-center gap-6 group">
                    <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-3xl text-primary group-hover:bg-primary group-hover:text-white transition-all shadow-inner">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <div>
                        <h5 class="text-sm font-black uppercase tracking-tight text-slate-900">Safe Shopping</h5>
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Secure Payments</p>
                    </div>
                </div>
                <div class="flex items-center gap-6 group">
                    <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-3xl text-primary group-hover:bg-primary group-hover:text-white transition-all shadow-inner">
                        <i class="bi bi-headset"></i>
                    </div>
                    <div>
                        <h5 class="text-sm font-black uppercase tracking-tight text-slate-900">24/7 Support</h5>
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Dedicated Support</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content Tabs -->
    <section class="py-24 bg-white" x-data="{ activeTab: 'latest' }">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-col md:flex-row items-center justify-between gap-8 mb-16 border-b border-slate-100 pb-6">
                <div class="flex items-center gap-10">
                    <button @click="activeTab = 'latest'" :class="activeTab === 'latest' ? 'text-primary after:scale-x-100' : 'text-slate-400 after:scale-x-0'" class="text-sm font-black uppercase tracking-[0.2em] whitespace-nowrap relative transition-all duration-300 after:absolute after:bottom-[-25px] after:left-0 after:w-full after:h-1.5 after:bg-primary after:rounded-full after:transition-transform after:origin-left">Latest Products</button>
                    <button @click="activeTab = 'bestseller'" :class="activeTab === 'bestseller' ? 'text-primary after:scale-x-100' : 'text-slate-400 after:scale-x-0'" class="text-sm font-black uppercase tracking-[0.2em] whitespace-nowrap relative transition-all duration-300 after:absolute after:bottom-[-25px] after:left-0 after:w-full after:h-1.5 after:bg-primary after:rounded-full after:transition-transform after:origin-left">Best Selling</button>
                    <button @click="activeTab = 'featured'" :class="activeTab === 'featured' ? 'text-primary after:scale-x-100' : 'text-slate-400 after:scale-x-0'" class="text-sm font-black uppercase tracking-[0.2em] whitespace-nowrap relative transition-all duration-300 after:absolute after:bottom-[-25px] after:left-0 after:w-full after:h-1.5 after:bg-primary after:rounded-full after:transition-transform after:origin-left">Featured Products</button>
                </div>
                <a href="{{ route('products.index') }}" class="text-xs font-black uppercase tracking-widest text-primary hover:text-primary-dark transition-colors flex items-center gap-2">View All Catalog <i class="bi bi-arrow-right"></i></a>
            </div>

            <!-- Grid Container -->
            <div>
                {{-- Latest Products Grid --}}
                <div x-show="activeTab === 'latest'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    @forelse($latestProducts as $product)
                        @include('frontend.partials.product-card', ['product' => $product])
                    @empty
                        <div class="col-span-full py-20 text-center opacity-20">
                            <i class="bi bi-box-seam text-6xl"></i>
                            <p class="mt-4 font-black uppercase tracking-widest text-xs">No Recent Arrivals</p>
                        </div>
                    @endforelse
                </div>

                {{-- Best Selling Grid --}}
                <div x-show="activeTab === 'bestseller'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8" x-cloak>
                    @forelse($bestSellers as $product)
                        @include('frontend.partials.product-card', ['product' => $product])
                    @empty
                        <div class="col-span-full py-20 text-center opacity-20">
                            <i class="bi bi-lightning-charge text-6xl"></i>
                            <p class="mt-4 font-black uppercase tracking-widest text-xs">No Best Sellers Marked</p>
                        </div>
                    @endforelse
                </div>

                {{-- Featured Products Grid --}}
                <div x-show="activeTab === 'featured'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8" x-cloak>
                    @forelse($featuredProducts as $product)
                        @include('frontend.partials.product-card', ['product' => $product])
                    @empty
                        <div class="col-span-full py-20 text-center opacity-20">
                            <i class="bi bi-star text-6xl"></i>
                            <p class="mt-4 font-black uppercase tracking-widest text-xs">No Featured Products Yet</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <!-- Brand Grid -->
    <section class="py-16 bg-white border-y border-slate-100">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-10">
                 <span class="text-[10px] font-black uppercase text-slate-400 tracking-[0.3em]">Official Partner Brands</span>
            </div>
            <div class="flex flex-wrap items-center justify-center gap-10 md:gap-16 lg:gap-20 opacity-60 hover:opacity-100 transition-opacity duration-500">
                <div class="grayscale hover:grayscale-0 transition-all duration-300 hover:-translate-y-1 cursor-pointer">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/2/24/Samsung_Logo.svg" class="h-6 md:h-8 w-auto object-contain" alt="Samsung">
                </div>
                <div class="grayscale hover:grayscale-0 transition-all duration-300 hover:-translate-y-1 cursor-pointer">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/2/20/LG_logo_%282015%29.svg" class="h-8 md:h-10 w-auto object-contain" alt="LG">
                </div>
                <div class="grayscale hover:grayscale-0 transition-all duration-300 hover:-translate-y-1 cursor-pointer">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/f/fa/Apple_logo_black.svg" class="h-8 md:h-10 w-auto object-contain" alt="Apple">
                </div>
                <div class="grayscale hover:grayscale-0 transition-all duration-300 hover:-translate-y-1 cursor-pointer">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/c/ca/Sony_logo.svg" class="h-4 md:h-6 w-auto object-contain" alt="Sony">
                </div>
                <div class="grayscale hover:grayscale-0 transition-all duration-300 hover:-translate-y-1 cursor-pointer">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/0/0e/Panasonic_logo.svg" class="h-4 md:h-6 w-auto object-contain" alt="Panasonic">
                </div>
                <div class="grayscale hover:grayscale-0 transition-all duration-300 hover:-translate-y-1 cursor-pointer">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/c/ca/Fujifilm_logo.svg" class="h-6 md:h-8 w-auto object-contain" alt="Fujifilm">
                </div>
                <div class="grayscale hover:grayscale-0 transition-all duration-300 hover:-translate-y-1 cursor-pointer">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/d/de/Dell_Logo.svg" class="h-8 md:h-10 w-auto object-contain" alt="Dell">
                </div>
            </div>
        </div>
    </section>

    <!-- Promo Banners -->
    <section class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Watch Promo -->
                <div class="relative h-[300px] rounded-[40px] overflow-hidden group">
                    <img src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?q=80&w=1000&auto=format&fit=crop" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" alt="Watches">
                    <div class="absolute inset-0 bg-slate-900/60 p-12 flex flex-col justify-center items-start text-white">
                        <span class="text-primary font-black uppercase tracking-[0.3em] text-xs mb-4">New Arrivals</span>
                        <h2 class="text-4xl font-black tracking-tighter mb-6 leading-none">Smart Watches<br>Collection 2026</h2>
                        <a href="#" class="px-8 py-4 bg-primary text-white font-black uppercase tracking-widest text-xs rounded-xl shadow-lg shadow-primary/30">Shop Now</a>
                    </div>
                </div>
                <!-- Camera Promo -->
                <div class="relative h-[300px] rounded-[40px] overflow-hidden group bg-orange-500">
                    <img src="https://images.unsplash.com/photo-1516035069371-29a1b244cc32?q=80&w=1000&auto=format&fit=crop" class="absolute inset-0 w-full h-full object-cover opacity-60 group-hover:scale-105 transition-transform duration-700" alt="Cameras">
                    <div class="absolute inset-0 p-12 flex flex-col justify-center items-start text-white">
                        <span class="text-white font-black uppercase tracking-[0.3em] text-xs mb-4">Summer Sale</span>
                        <h2 class="text-4xl font-black tracking-tighter mb-6 leading-none">Camera & Accessories<br>Up To 50% Off</h2>
                        <a href="#" class="px-8 py-4 bg-white text-orange-500 font-black uppercase tracking-widest text-xs rounded-xl shadow-lg">Grab Deals</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- New Arrivals -->
    <section class="py-24 bg-slate-50" x-data="{ activeArrivalTab: '{{ $arrivalCategories->first()->id ?? 0 }}' }">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center mb-16 gap-6">
                <div class="space-y-2">
                    <span class="text-primary font-black uppercase tracking-[0.2em] text-xs">Fresh Tech</span>
                    <h2 class="text-4xl lg:text-5xl font-black text-slate-900 tracking-tighter">New Arrivals</h2>
                    <div class="w-20 h-1.5 bg-primary rounded-full"></div>
                </div>
                <div class="flex items-center gap-6 text-[10px] font-black uppercase tracking-widest text-slate-400">
                    @foreach($arrivalCategories as $cat)
                        <button @click="activeArrivalTab = '{{ $cat->id }}'" 
                                :class="activeArrivalTab == '{{ $cat->id }}' ? 'text-primary border-b-2 border-primary pb-1' : 'hover:text-slate-900 transition-colors'"
                                class="transition-all duration-300">
                            {{ $cat->name }}
                        </button>
                    @endforeach
                </div>
            </div>

            @foreach($arrivalCategories as $cat)
                <div x-show="activeArrivalTab == '{{ $cat->id }}'" 
                     x-transition:enter="transition ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4" 
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                    @forelse($cat->products as $product)
                        @include('frontend.partials.product-card', ['product' => $product])
                    @empty
                        <div class="col-span-full py-20 text-center opacity-20">
                            <i class="bi bi-cart-x text-6xl"></i>
                            <p class="mt-4 font-black uppercase tracking-widest text-xs">No Arrivals in {{ $cat->name }}</p>
                        </div>
                    @endforelse
                </div>
            @endforeach
        </div>
    </section>

    <!-- Top Collection / Categories Icons -->
    <section class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-16 space-y-4">
                <span class="text-primary font-black uppercase tracking-[0.2em] text-xs">Browse By Category</span>
                <h2 class="text-4xl lg:text-5xl font-black text-slate-900 tracking-tighter">Top Collections</h2>
                <div class="w-20 h-1.5 bg-primary rounded-full mx-auto"></div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                @php
                    $categories_icons = [
                        ['name' => 'Smartphones', 'icon' => 'bi-phone', 'color' => 'bg-blue-50 text-blue-500'],
                        ['name' => 'Laptops', 'icon' => 'bi-laptop', 'color' => 'bg-indigo-50 text-indigo-500'],
                        ['name' => 'Tablets', 'icon' => 'bi-tablet', 'color' => 'bg-purple-50 text-purple-500'],
                        ['name' => 'Watches', 'icon' => 'bi-smartwatch', 'color' => 'bg-pink-50 text-pink-500'],
                        ['name' => 'Cameras', 'icon' => 'bi-camera', 'color' => 'bg-orange-50 text-orange-500'],
                        ['name' => 'Accessories', 'icon' => 'bi-earbuds', 'color' => 'bg-cyan-50 text-cyan-500'],
                    ];
                @endphp
                @foreach($categories_icons as $cat)
                    <a href="#" class="group p-8 bg-slate-50 rounded-[40px] border border-transparent hover:border-primary hover:bg-white transition-all text-center no-underline">
                        <div class="w-20 h-20 {{ $cat['color'] }} rounded-3xl mx-auto mb-6 flex items-center justify-center text-4xl group-hover:scale-110 transition-transform">
                            <i class="bi {{ $cat['icon'] }}"></i>
                        </div>
                        <h5 class="text-xs font-black uppercase tracking-widest text-slate-900">{{ $cat['name'] }}</h5>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Hot Deals List -->
    <section class="py-24 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-16 space-y-4">
                <span class="text-red-500 font-black uppercase tracking-[0.2em] text-xs">Don't Miss Out</span>
                <h2 class="text-4xl lg:text-5xl font-black text-slate-900 tracking-tighter uppercase italic">Hot Sales</h2>
                <div class="w-20 h-1.5 bg-red-500 rounded-full mx-auto"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($latestProducts->take(6) as $product)
                    <div class="group bg-white rounded-3xl p-6 flex items-center gap-6 border border-slate-100 hover:shadow-xl transition-all">
                        <div class="w-24 h-24 bg-slate-50 rounded-2xl flex-shrink-0 p-4 relative overflow-hidden">
                             @if($product->discount)
                                <div class="absolute top-0 left-0 bg-red-500 text-white text-[7px] font-black px-1.5 py-0.5 rounded-br-lg z-10 shadow-lg">
                                    HOT
                                </div>
                            @endif
                            <img src="{{ $product->images->first() ? asset('storage/' . $product->images->first()->image) : 'https://images.unsplash.com/photo-1526733169359-81173747976e?q=80&w=1470&auto=format&fit=crop' }}" alt="{{ $product->name }}" class="w-full h-full object-contain group-hover:scale-110 transition-transform">
                        </div>
                        <div class="flex-1 space-y-2">
                             <div class="flex items-center gap-1 text-yellow-400 text-[8px]">
                                <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-900 truncate uppercase tracking-tight">{{ $product->name }}</h3>
                            <div class="flex items-center gap-3">
                                <span class="text-lg font-black text-primary tracking-tighter">tk.{{ number_format($product->price, 2) }}</span>
                            </div>
                            <button class="text-[9px] font-black uppercase tracking-widest text-primary border-b-2 border-primary/20 hover:border-primary transition-all pb-1">+ Add To Cart</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center space-y-12">
                <div class="flex justify-center -space-x-4">
                    <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?q=80&w=100&auto=format&fit=crop" class="w-16 h-16 rounded-full border-4 border-white shadow-xl">
                    <img src="https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?q=80&w=100&auto=format&fit=crop" class="w-16 h-16 rounded-full border-4 border-white shadow-xl relative z-10">
                    <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=100&auto=format&fit=crop" class="w-16 h-16 rounded-full border-4 border-white shadow-xl">
                </div>
                <div class="space-y-6">
                    <i class="bi bi-quote text-6xl text-primary/20"></i>
                    <p class="text-2xl lg:text-3xl font-bold text-slate-800 leading-relaxed italic">
                        "Fastest delivery I've ever experienced! The gadget quality is top-notch, and the customer support was incredibly helpful. Highly recommended!"
                    </p>
                    <div>
                        <h4 class="text-lg font-black text-slate-900 uppercase tracking-widest">James Wilson</h4>
                        <span class="text-sm font-bold text-slate-400 uppercase tracking-widest">Verified Customer</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA / Newsletter Re-styled -->
    <section class="py-24 bg-slate-900 text-white relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex flex-col lg:flex-row items-center justify-between gap-12 bg-white/5 border border-white/10 p-12 lg:p-20 rounded-[60px] backdrop-blur-xl relative z-10">
                <div class="max-w-xl space-y-6 text-center lg:text-left">
                    <h2 class="text-4xl lg:text-5xl font-black tracking-tighter leading-none uppercase">Join Our Digital Community</h2>
                    <p class="text-slate-400 text-lg font-medium leading-relaxed">Sign up for our newsletter to receive the latest tech news, early bird discounts, and invitations to exclusive product launches.</p>
                </div>
                <div class="w-full lg:w-1/2">
                    <form id="newsletter-form" class="flex flex-col sm:flex-row gap-4">
                        @csrf
                        <div class="flex-1 relative">
                            <input type="email" name="email" id="newsletter-email" placeholder="Your Email Address" required class="w-full bg-white/10 border border-white/20 rounded-2xl px-8 py-5 text-white font-bold outline-none focus:bg-white/20 transition-all">
                            <p id="newsletter-message" class="absolute -bottom-6 left-2 text-[10px] font-bold tracking-widest uppercase hidden"></p>
                        </div>
                        <button type="submit" id="newsletter-btn" class="bg-primary hover:bg-primary-dark text-white px-10 py-5 rounded-2xl font-black uppercase tracking-widest text-sm shadow-xl shadow-primary/30 transition-all active:scale-95 disabled:opacity-50">
                            <span>Subscribe</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-primary rounded-full blur-[150px] opacity-20"></div>
    </section>

    @push('scripts')
    <script>
        document.getElementById('newsletter-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            let form = this;
            let btn = document.getElementById('newsletter-btn');
            let msg = document.getElementById('newsletter-message');
            let email = document.getElementById('newsletter-email').value;
            let token = form.querySelector('input[name="_token"]').value;
            
            btn.disabled = true;
            btn.innerHTML = '<span class="animate-pulse">Wait...</span>';
            msg.classList.add('hidden');
            
            fetch('{{ route("newsletter.subscribe") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ email: email })
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = '<span>Subscribe</span>';
                msg.classList.remove('hidden');
                
                if(data.success) {
                    msg.textContent = data.message;
                    msg.classList.remove('text-red-400');
                    msg.classList.add('text-green-400');
                    form.reset();
                } else {
                    msg.textContent = data.message || (data.errors && data.errors.email ? data.errors.email[0] : 'Something went wrong');
                    msg.classList.remove('text-green-400');
                    msg.classList.add('text-red-400');
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = '<span>Subscribe</span>';
                msg.classList.remove('hidden');
                msg.textContent = 'Network Error. Please try again.';
                msg.classList.remove('text-green-400');
                msg.classList.add('text-red-400');
            });
        });
    </script>
    @endpush
@endsection