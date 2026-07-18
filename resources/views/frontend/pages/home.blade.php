@extends('frontend.layout')

@section('title', 'ONEMALL | Premium Electronics Store')

@section('content')
    @php
        // Tailwind color name -> [background hex, text hex]
        $colorMap = [
            'red-500' => ['#ef4444', '#ffffff'],
            'red-600' => ['#dc2626', '#ffffff'],
            'red-400' => ['#f87171', '#ffffff'],
            'orange-500' => ['#f97316', '#ffffff'],
            'orange-600' => ['#ea580c', '#ffffff'],
            'orange-400' => ['#fb923c', '#ffffff'],
            'yellow-500' => ['#eab308', '#1e293b'],
            'yellow-400' => ['#facc15', '#1e293b'],
            'green-500' => ['#22c55e', '#ffffff'],
            'green-600' => ['#16a34a', '#ffffff'],
            'green-400' => ['#4ade80', '#ffffff'],
            'teal-500' => ['#14b8a6', '#ffffff'],
            'teal-600' => ['#0d9488', '#ffffff'],
            'teal-400' => ['#2dd4bf', '#ffffff'],
            'blue-500' => ['#3b82f6', '#ffffff'],
            'blue-600' => ['#2563eb', '#ffffff'],
            'blue-400' => ['#60a5fa', '#ffffff'],
            'indigo-500' => ['#6366f1', '#ffffff'],
            'indigo-600' => ['#4f46e5', '#ffffff'],
            'indigo-400' => ['#818cf8', '#ffffff'],
            'purple-500' => ['#a855f7', '#ffffff'],
            'purple-600' => ['#9333ea', '#ffffff'],
            'purple-400' => ['#c084fc', '#ffffff'],
            'pink-500' => ['#ec4899', '#ffffff'],
            'pink-600' => ['#db2777', '#ffffff'],
            'pink-400' => ['#f472b6', '#ffffff'],
            'rose-500' => ['#f43f5e', '#ffffff'],
            'rose-600' => ['#e11d48', '#ffffff'],
            'rose-400' => ['#fb7185', '#ffffff'],
            'cyan-500' => ['#06b6d4', '#ffffff'],
            'cyan-600' => ['#0891b2', '#ffffff'],
            'cyan-400' => ['#22d3ee', '#1e293b'],
            'slate-500' => ['#64748b', '#ffffff'],
            'slate-600' => ['#475569', '#ffffff'],
            'gray-500' => ['#6b7280', '#ffffff'],
        ];
        function getCategoryStyle($color, $colorMap)
        {
            if (!$color)
                return 'background-color:#f1f5f9;color:#64748b;';
            // If it's already a hex color
            if (str_starts_with($color, '#'))
                return "background-color:{$color};color:#ffffff;";
            $parts = array_map('trim', explode(' ', $color));
            // Try the first part as base color key
            $key = $parts[0];
            if (isset($colorMap[$key])) {
                [$bg, $text] = $colorMap[$key];
                // Soften background with opacity
                return "background-color:{$bg}20;color:{$bg};";
            }
            return 'background-color:#f1f5f9;color:#64748b;';
        }
    @endphp
    @php
        $flashDealTitle = \App\Models\Setting::get('flash_deal_title', 'Flash Deals');
        $flashDealEndTime = \App\Models\Setting::get('flash_deal_end_time');
    @endphp
    <!-- Mobile App View (Visible only on mobile) -->
    <div class="block md:hidden pb-32 pt-4 bg-gray-50 min-h-screen">

        <!-- Category Scroll -->
        <div class="px-4 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-[11px] font-black uppercase tracking-widest text-slate-900">{{ __('Categories') }}</h3>
                <a href="{{ route('products.index') }}"
                    class="text-[9px] font-black uppercase tracking-widest text-primary">{{ __('See All') }}</a>
            </div>
            <div class="flex gap-4 overflow-x-auto scrollbar-hide no-scrollbar pb-2">
                @php
                    // Use categories fetched from HomeController (type = 'product')
                    $mobileCategories = $allCategories->take(6);
                @endphp
                @foreach($mobileCategories as $cat)
                    <a href="{{ route('products.index', ['category' => $cat->id]) }}"
                        class="flex flex-col items-center gap-2.5 min-w-[65px]">
                        <div class="w-[60px] h-[60px] rounded-[1.2rem] flex items-center justify-center text-2xl shadow-sm border border-white/50 relative overflow-hidden group"
                            style="{{ getCategoryStyle($cat->color, $colorMap) }}">
                            <div class="absolute inset-0 bg-white/20 opacity-0 group-hover:opacity-100 transition-opacity">
                            </div>
                            <i
                                class="bi {{ $cat->icon ?? 'bi-tag' }} relative z-10 group-hover:scale-110 transition-transform"></i>
                        </div>
                        <span class="text-[9px] font-black uppercase text-slate-600 tracking-wider">{{ $cat->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Flash Deals -->
        <div class="px-4 mb-8">
            <div
                class="bg-red-500 rounded-t-2xl p-4 flex items-center justify-between text-white shadow-lg shadow-red-500/20">
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 bg-white/20 rounded-full flex items-center justify-center">
                        <i class="bi bi-lightning-charge-fill text-yellow-300 text-sm animate-pulse"></i>
                    </div>
                    <h3 class="text-xs font-black uppercase tracking-widest italic">{{ $flashDealTitle }}</h3>
                </div>
                <div class="flex gap-1" x-data="countdown('{{ $flashDealEndTime }}')" x-init="start()">
                    <template x-if="parseInt(days) > 0">
                        <div class="flex gap-1">
                            <div
                                class="bg-slate-900/40 w-6 h-6 rounded flex items-center justify-center text-[10px] font-black">
                                <span x-text="days"></span><span class="text-[7px] ml-0.5 mt-0.5 text-rose-200">d</span>
                            </div><span class="font-bold">:</span>
                        </div>
                    </template>
                    <div class="bg-slate-900/40 w-6 h-6 rounded flex items-center justify-center text-[10px] font-black">
                        <span x-text="hours"></span></div><span class="font-bold">:</span>
                    <div class="bg-slate-900/40 w-6 h-6 rounded flex items-center justify-center text-[10px] font-black">
                        <span x-text="minutes"></span></div><span class="font-bold">:</span>
                    <div class="bg-slate-900/40 w-6 h-6 rounded flex items-center justify-center text-[10px] font-black">
                        <span x-text="seconds"></span></div>
                </div>
            </div>
            <div class="bg-white border border-t-0 border-slate-100 rounded-b-2xl p-4 shadow-sm">
                @if($flashDealProducts->count() > 0)
                    <div class="flex overflow-x-auto scrollbar-hide no-scrollbar gap-4 pb-2">
                        @foreach ($flashDealProducts as $product)
                            <a href="{{ route('products.show', $product->id) }}" class="min-w-[130px] relative group block">
                                <div
                                    class="absolute top-2 left-2 bg-red-500 text-white text-[7px] font-black px-1.5 py-0.5 rounded shadow-sm z-10">
                                    -{{ intval($product->discount) }}%</div>
                                <div
                                    class="bg-slate-50 rounded-xl mb-3 h-[110px] flex items-center justify-center relative overflow-hidden border border-slate-100">
                                    <img src="{{ $product->images->first() ? asset('storage/' . $product->images->first()->image) : 'https://images.unsplash.com/photo-1526733169359-81173747976e?q=80&w=400&auto=format&fit=crop' }}"
                                        onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1526733169359-81173747976e?q=80&w=400&auto=format&fit=crop';"
                                        class="max-w-[85%] max-h-[90px] w-auto h-auto object-contain group-hover:scale-110 transition-transform duration-500"
                                        alt="{{ $product->name }}">
                                </div>
                                <h4 class="text-[9px] font-bold text-slate-800 line-clamp-2 leading-tight uppercase">
                                    {{ $product->name }}</h4>
                                <div class="mt-2 flex items-center justify-between">
                                    <div>
                                        <span
                                            class="text-[8px] text-slate-400 line-through font-bold block">tk.{{ number_format($product->price, 0) }}</span>
                                        <span
                                            class="text-xs font-black text-red-500 tracking-tighter">tk.{{ number_format($product->discounted_price, 0) }}</span>
                                    </div>
                                    <form action="{{ route('cart.add') }}" method="POST" class="inline"
                                        onclick="event.stopPropagation();">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit"
                                            class="w-6 h-6 bg-slate-100 rounded-full flex items-center justify-center text-slate-600 hover:bg-primary hover:text-white transition-colors active:scale-90"><i
                                                class="bi bi-cart2 text-[10px]"></i></button>
                                    </form>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center opacity-40">
                        <i class="bi bi-tag text-3xl text-slate-300"></i>
                        <p class="mt-2 text-[9px] font-black uppercase tracking-widest text-slate-400">
                            {{ __('No Flash Deals Right Now') }}</p>
                    </div>
                @endif
            </div>
        </div>


    </div>

    <!-- Desktop Original Layout Container -->
    <div class="hidden md:block">
        <!-- Main Hero Slider Area -->
        <section class="relative bg-white pt-6">
            <div class="max-w-7xl mx-auto px-4">
                <!-- Full Width Sliding Hero Banner (Desktop) -->
                <div class="w-full h-[380px] md:h-[400px] relative rounded-[2rem] sm:rounded-[2.5rem] overflow-hidden bg-slate-900 shadow-2xl group"
                    x-data="{ 
                         activeSlide: 0, 
                         slidesCount: {{ $heroBanners->count() }}
                     }">

                    @if($heroBanners->count() > 0)
                        <!-- Slides Track -->
                        <div class="w-full h-full relative overflow-hidden">
                            <div class="flex h-full transition-transform duration-700 ease-out"
                                :style="'transform: translateX(-' + (activeSlide * 100) + '%)'">

                                @foreach($heroBanners as $banner)
                                    <div class="w-full h-full flex-shrink-0 relative flex items-center bg-slate-950">
                                        <!-- Slide Image -->
                                        @php
                                            $imageUrl = (str_starts_with($banner->image, 'http://') || str_starts_with($banner->image, 'https://')) ? $banner->image : asset('storage/' . $banner->image);
                                        @endphp
                                        <img src="{{ $imageUrl }}"
                                            class="absolute inset-0 w-full h-full object-cover object-center"
                                            alt="Banner">
                                    </div>
                                @endforeach

                            </div>
                        </div>

                        @if($heroBanners->count() > 1)
                            <!-- Next/Prev Controls -->
                            <button @click="activeSlide = (activeSlide - 1 + slidesCount) % slidesCount"
                                class="absolute left-8 top-1/2 -translate-y-1/2 z-30 w-14 h-14 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center backdrop-blur-md border border-white/20 transition-all opacity-0 group-hover:opacity-100 active:scale-95 cursor-pointer">
                                <i class="bi bi-chevron-left text-xl"></i>
                            </button>

                            <button @click="activeSlide = (activeSlide + 1) % slidesCount"
                                class="absolute right-8 top-1/2 -translate-y-1/2 z-30 w-14 h-14 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center backdrop-blur-md border border-white/20 transition-all opacity-0 group-hover:opacity-100 active:scale-95 cursor-pointer">
                                <i class="bi bi-chevron-right text-xl"></i>
                            </button>

                            <!-- Slide Indicators (Dots) -->
                            <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-30 flex gap-2.5">
                                @foreach($heroBanners as $index => $banner)
                                    <button @click="activeSlide = {{ $index }}"
                                        class="h-2.5 rounded-full transition-all duration-300 cursor-pointer"
                                        :class="activeSlide === {{ $index }} ? 'w-10 bg-primary' : 'w-2.5 bg-white/40 hover:bg-white/60'"></button>
                                @endforeach
                            </div>
                        @endif

                    @else
                        <!-- Fallback Banner -->
                        <div class="w-full h-full relative flex items-center">
                            <img src="https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?q=80&w=1430&auto=format&fit=crop"
                                class="absolute inset-0 w-full h-full object-cover object-center" alt="Fallback Banner">
                        </div>
                    @endif

                </div>
            </div>
        </section>



        <!-- Service Features -->
        <section class="py-8 sm:py-12 bg-white border-y border-slate-100">
            <div class="max-w-7xl mx-auto px-4">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-8">
                    <div
                        class="flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-3 sm:gap-6 group">
                        <div
                            class="w-12 h-12 sm:w-16 sm:h-16 bg-slate-50 rounded-xl sm:rounded-2xl flex items-center justify-center text-2xl sm:text-3xl text-primary group-hover:bg-primary group-hover:text-white transition-all shadow-inner">
                            <i class="bi bi-truck"></i>
                        </div>
                        <div>
                            <h5 class="text-xs sm:text-sm font-black uppercase tracking-tight text-slate-900">Free Shipping
                            </h5>
                            <p class="text-[9px] sm:text-xs text-slate-400 font-bold uppercase tracking-widest">On Orders
                                Over $99</p>
                        </div>
                    </div>
                    <div
                        class="flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-3 sm:gap-6 group">
                        <div
                            class="w-12 h-12 sm:w-16 sm:h-16 bg-slate-50 rounded-xl sm:rounded-2xl flex items-center justify-center text-2xl sm:text-3xl text-primary group-hover:bg-primary group-hover:text-white transition-all shadow-inner">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                        <div>
                            <h5 class="text-xs sm:text-sm font-black uppercase tracking-tight text-slate-900">
                                {{ __('Money Return') }}</h5>
                            <p class="text-[9px] sm:text-xs text-slate-400 font-bold uppercase tracking-widest">
                                {{ __('30 Days Guarantee') }}</p>
                        </div>
                    </div>
                    <div
                        class="flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-3 sm:gap-6 group">
                        <div
                            class="w-12 h-12 sm:w-16 sm:h-16 bg-slate-50 rounded-xl sm:rounded-2xl flex items-center justify-center text-2xl sm:text-3xl text-primary group-hover:bg-primary group-hover:text-white transition-all shadow-inner">
                            <i class="bi bi-shield-lock"></i>
                        </div>
                        <div>
                            <h5 class="text-xs sm:text-sm font-black uppercase tracking-tight text-slate-900">
                                {{ __('Safe Shopping') }}</h5>
                            <p class="text-[9px] sm:text-xs text-slate-400 font-bold uppercase tracking-widest">
                                {{ __('Secure Payments') }}</p>
                        </div>
                    </div>
                    <div
                        class="flex flex-col sm:flex-row items-center sm:items-start text-center sm:text-left gap-3 sm:gap-6 group">
                        <div
                            class="w-12 h-12 sm:w-16 sm:h-16 bg-slate-50 rounded-xl sm:rounded-2xl flex items-center justify-center text-2xl sm:text-3xl text-primary group-hover:bg-primary group-hover:text-white transition-all shadow-inner">
                            <i class="bi bi-headset"></i>
                        </div>
                        <div>
                            <h5 class="text-xs sm:text-sm font-black uppercase tracking-tight text-slate-900">
                                {{ __('24/7 Support') }}</h5>
                            <p class="text-[9px] sm:text-xs text-slate-400 font-bold uppercase tracking-widest">
                                {{ __('Dedicated Support') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Desktop Flash Deals Campaign -->
        @if($flashDealProducts->count() > 0)
            <section class="py-12 sm:py-16 relative overflow-hidden"
                style="background: linear-gradient(135deg, #ffffff 0%, #e8f6fc 50%, #ffffff 100%);">
                <!-- Blue decorative elements -->
                <!-- <div class="absolute top-0 left-0 w-full h-1" style="background: linear-gradient(to right, #20A7DB, #0d6fa8, #20A7DB);"></div> -->
                <div class="absolute -top-20 -right-20 w-72 h-72 rounded-full blur-3xl opacity-15 pointer-events-none"
                    style="background: #20A7DB;"></div>
                <div class="absolute -bottom-20 -left-20 w-72 h-72 rounded-full blur-3xl opacity-10 pointer-events-none"
                    style="background: #20A7DB;"></div>

                <div class="max-w-7xl mx-auto px-4 relative z-10">

                    <!-- ===== SECTION HEADER ===== -->
                    <div class="flex flex-col md:flex-row items-center justify-between gap-6 mb-10">
                        <!-- Left: Title + Badge -->
                        <div class="flex items-center gap-5">
                            <!-- Icon -->
                            <div class="relative">
                                <div class="absolute inset-0 blur-xl opacity-40 rounded-full" style="background: #20A7DB;">
                                </div>
                                <div class="relative w-16 h-16 rounded-2xl flex items-center justify-center shadow-xl"
                                    style="background: linear-gradient(135deg, #20A7DB, #0d6fa8);">
                                    <i class="bi bi-lightning-charge-fill text-white text-3xl"></i>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="inline-block w-2 h-2 rounded-full animate-ping"
                                        style="background: #20A7DB;"></span>
                                    <span class="text-[10px] font-black uppercase tracking-[0.3em]"
                                        style="color: #20A7DB;">{{ __('Limited Time Only') }}</span>
                                </div>
                                <h2
                                    class="text-3xl sm:text-4xl font-black uppercase tracking-tighter italic leading-none text-slate-900">
                                    {{ $flashDealTitle }}
                                </h2>
                            </div>
                        </div>

                        <!-- Right: Countdown Timer -->
                        <div class="flex items-center gap-3 sm:gap-4" x-data="countdown('{{ $flashDealEndTime }}')"
                            x-init="start()">
                            <template x-if="parseInt(days) > 0">
                                <div class="flex items-center gap-3">
                                    <div class="flex flex-col items-center">
                                        <div class="relative">
                                            <div class="absolute inset-0 blur-md rounded-xl opacity-30"
                                                style="background: #20A7DB;"></div>
                                            <div class="relative w-16 h-16 sm:w-20 sm:h-20 bg-white border-2 rounded-xl flex items-center justify-center text-xl sm:text-3xl font-black tracking-tight shadow-lg"
                                                style="border-color: #20A7DB; color: #20A7DB;">
                                                <span x-text="days">00</span>
                                            </div>
                                        </div>
                                        <span class="text-[9px] font-black uppercase tracking-widest mt-1.5"
                                            style="color: #20A7DB;">{{ __('Days') }}</span>
                                    </div>
                                    <span class="text-2xl font-black -mt-5" style="color: #20A7DB;">:</span>
                                </div>
                            </template>
                            <div class="flex flex-col items-center">
                                <div class="relative">
                                    <div class="absolute inset-0 blur-md rounded-xl opacity-30" style="background: #20A7DB;">
                                    </div>
                                    <div class="relative w-16 h-16 sm:w-20 sm:h-20 bg-white border-2 rounded-xl flex items-center justify-center text-xl sm:text-3xl font-black tracking-tight shadow-lg"
                                        style="border-color: #20A7DB; color: #20A7DB;">
                                        <span x-text="hours">00</span>
                                    </div>
                                </div>
                                <span class="text-[9px] font-black uppercase tracking-widest mt-1.5"
                                    style="color: #20A7DB;">{{ __('Hrs') }}</span>
                            </div>
                            <span class="text-2xl font-black -mt-5" style="color: #20A7DB;">:</span>
                            <div class="flex flex-col items-center">
                                <div class="relative">
                                    <div class="absolute inset-0 blur-md rounded-xl opacity-30" style="background: #20A7DB;">
                                    </div>
                                    <div class="relative w-16 h-16 sm:w-20 sm:h-20 bg-white border-2 rounded-xl flex items-center justify-center text-xl sm:text-3xl font-black tracking-tight shadow-lg"
                                        style="border-color: #20A7DB; color: #20A7DB;">
                                        <span x-text="minutes">00</span>
                                    </div>
                                </div>
                                <span class="text-[9px] font-black uppercase tracking-widest mt-1.5"
                                    style="color: #20A7DB;">{{ __('Min') }}</span>
                            </div>
                            <span class="text-2xl font-black -mt-5" style="color: #20A7DB;">:</span>
                            <div class="flex flex-col items-center">
                                <div class="relative">
                                    <div class="absolute inset-0 blur-md rounded-xl opacity-30" style="background: #20A7DB;">
                                    </div>
                                    <div class="relative w-16 h-16 sm:w-20 sm:h-20 bg-white border-2 rounded-xl flex items-center justify-center text-xl sm:text-3xl font-black tracking-tight shadow-lg"
                                        style="border-color: #20A7DB; color: #0d6fa8;">
                                        <span x-text="seconds">00</span>
                                    </div>
                                </div>
                                <span class="text-[9px] font-black uppercase tracking-widest mt-1.5"
                                    style="color: #20A7DB;">{{ __('Sec') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- ===== PRODUCT CARDS ===== -->
                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                        @foreach ($flashDealProducts->take(10) as $product)
                            <div class="group/card relative bg-white border rounded-2xl p-4 flex flex-col hover:shadow-xl hover:-translate-y-1 transition-all duration-400 overflow-hidden"
                                style="border-color: #20A7DB30;">
                                <!-- Top border highlight on hover -->
                                <div class="absolute top-0 left-0 w-full h-0.5 opacity-0 group-hover/card:opacity-100 transition-opacity"
                                    style="background: #20A7DB;"></div>

                                <!-- Discount Badge -->
                                <div class="absolute top-3 left-3 z-20 text-white text-[9px] font-black px-2.5 py-1 rounded-full shadow-lg flex items-center gap-1"
                                    style="background: linear-gradient(to right, #20A7DB, #0d6fa8);">
                                    <i class="bi bi-lightning-fill text-[7px]"></i>
                                    -{{ intval($product->discount) }}%
                                </div>

                                <!-- Image -->
                                <a href="{{ route('products.show', $product->id) }}" class="block">
                                    <div class="rounded-xl mb-4 h-36 flex items-center justify-center overflow-hidden transition-colors"
                                        style="background: #f0f9fd;">
                                        <img src="{{ $product->images->first() ? asset('storage/' . $product->images->first()->image) : 'https://images.unsplash.com/photo-1526733169359-81173747976e?q=80&w=400&auto=format&fit=crop' }}"
                                            onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1526733169359-81173747976e?q=80&w=400&auto=format&fit=crop';"
                                            class="max-w-[85%] max-h-[110px] w-auto h-auto object-contain group-hover/card:scale-110 transition-transform duration-500 mix-blend-multiply"
                                            alt="{{ $product->name }}">
                                    </div>
                                </a>

                                <!-- Info -->
                                <div class="flex-1 flex flex-col">
                                    @if($product->brand)
                                        <span class="text-[8px] font-black uppercase tracking-widest mb-1"
                                            style="color: #20A7DB;">{{ $product->brand }}</span>
                                    @endif
                                    <a href="{{ route('products.show', $product->id) }}" class="no-underline">
                                        <h4 class="text-[11px] font-bold text-slate-800 line-clamp-2 leading-tight mb-3">
                                            {{ $product->name }}</h4>
                                    </a>

                                    <!-- Price row -->
                                    <div class="mt-auto flex items-end justify-between gap-2">
                                        <div>
                                            <span
                                                class="text-[9px] text-slate-400 line-through font-bold block leading-none mb-0.5">৳{{ number_format($product->price, 0) }}</span>
                                            <span class="text-base font-black tracking-tighter leading-none"
                                                style="color: #20A7DB;">৳{{ number_format($product->discounted_price, 0) }}</span>
                                        </div>
                                        <form action="{{ route('cart.add') }}" method="POST" class="flex-shrink-0">
                                            @csrf
                                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit"
                                                class="w-9 h-9 rounded-xl flex items-center justify-center transition-all active:scale-90 text-white"
                                                style="background: rgba(32,167,219,0.15); color: #20A7DB;"
                                                onmouseover="this.style.background='#20A7DB'; this.style.color='white';"
                                                onmouseout="this.style.background='rgba(32,167,219,0.15)'; this.style.color='#20A7DB';">
                                                <i class="bi bi-cart-plus text-sm"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- View All Link -->
                    <div class="text-center mt-8">
                        <a href="{{ route('products.index') }}"
                            class="inline-flex items-center gap-3 px-8 py-3.5 text-white font-black uppercase text-[11px] tracking-[0.2em] rounded-xl hover:opacity-90 transition-opacity shadow-xl"
                            style="background: linear-gradient(to right, #20A7DB, #0d6fa8); box-shadow: 0 10px 30px rgba(32,167,219,0.3);">
                            <i class="bi bi-lightning-charge-fill"></i>
                            {{ __('View All Flash Deals') }}
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>

                </div>
            </section>
        @endif

    </div>

    <!-- Just For You Section -->
    <section class="py-12 sm:py-20 bg-slate-50" x-data="loadMoreProducts()">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-8 sm:mb-12">
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight uppercase">{{ __('Just For You') }}
                </h2>
                <div class="w-16 h-1.5 bg-primary mx-auto mt-3 rounded-full"></div>
            </div>

            <!-- Grid Container -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4" id="for-you-grid">
                @foreach($latestProducts as $product)
                    @include('frontend.partials.product-card', ['product' => $product])
                @endforeach
            </div>

            <!-- Dynamic Loaded Content Will Be Appended Here -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 mt-3 sm:mt-4" x-ref="productGrid">
            </div>

            <!-- Load More Button -->
            <div class="text-center mt-10" x-show="hasMore">
                <button @click="loadMore()" :disabled="loading"
                    class="inline-flex items-center gap-2 px-10 py-3 sm:py-4 border-2 border-primary text-primary hover:bg-primary hover:text-white font-black uppercase text-[10px] sm:text-xs tracking-widest rounded-full transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!loading">{{ __('Load More') }}</span>
                    <span x-show="loading">{{ __('Loading...') }}</span>
                    <i class="bi bi-arrow-clockwise" :class="{'animate-spin': loading}" x-show="loading"></i>
                </button>
            </div>
        </div>
    </section>



    <!-- Final CTA / Newsletter Re-styled -->
    <section class="pt-16 pb-28 sm:py-24 bg-slate-900 text-white relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4">
            <div
                class="flex flex-col lg:flex-row items-center justify-between gap-8 sm:gap-12 bg-white/5 border border-white/10 p-8 sm:p-12 lg:p-20 rounded-[2rem] sm:rounded-[3rem] lg:rounded-[60px] backdrop-blur-xl relative z-10">
                <div class="max-w-xl space-y-4 sm:space-y-6 text-center lg:text-left">
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tighter leading-none uppercase">Join Our
                        Digital Community</h2>
                    <p class="text-slate-400 text-sm sm:text-lg font-medium leading-relaxed">Sign up for our newsletter to
                        receive the latest tech news, early bird discounts, and invitations to exclusive product launches.
                    </p>
                </div>
                <div class="w-full lg:w-1/2">
                    <form id="newsletter-form" class="flex flex-col sm:flex-row gap-3 sm:gap-4 w-full">
                        @csrf
                        <div class="flex-1 relative w-full">
                            <input type="email" name="email" id="newsletter-email" placeholder="Your Email Address" required
                                class="w-full bg-white/10 border border-white/20 rounded-xl sm:rounded-2xl px-6 py-4 sm:px-8 sm:py-5 text-sm sm:text-base text-white font-bold outline-none focus:bg-white/20 transition-all">
                            <p id="newsletter-message"
                                class="absolute -bottom-6 left-2 text-[9px] sm:text-[10px] font-bold tracking-widest uppercase hidden">
                            </p>
                        </div>
                        <button type="submit" id="newsletter-btn"
                            class="w-full sm:w-auto bg-primary hover:bg-primary-dark text-white px-8 py-4 sm:px-10 sm:py-5 rounded-xl sm:rounded-2xl font-black uppercase tracking-widest text-[11px] sm:text-sm shadow-xl shadow-primary/30 transition-all active:scale-95 disabled:opacity-50 whitespace-nowrap">
                            <span>Subscribe</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div
            class="absolute -bottom-40 -left-40 w-64 h-64 sm:w-96 sm:h-96 bg-primary rounded-full blur-[100px] sm:blur-[150px] opacity-20">
        </div>
    </section>
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('loadMoreProducts', () => ({
                    skip: 15,
                    loading: false,
                    hasMore: true,

                    loadMore() {
                        if (this.loading || !this.hasMore) return;

                        this.loading = true;

                        fetch(`{{ route('load.more.products') }}?skip=${this.skip}`)
                            .then(res => res.json())
                            .then(data => {
                                if (data.html) {
                                    this.$refs.productGrid.insertAdjacentHTML('beforeend', data.html);
                                    this.skip += 5;
                                }
                                this.hasMore = data.has_more;
                            })
                            .catch(err => {
                                console.error('Error loading products', err);
                            })
                            .finally(() => {
                                this.loading = false;
                            });
                    }
                }));
            });

            // Flash deals countdown logic for Alpine
            function countdown(endTimeStr) {
                let initialTarget = endTimeStr ? new Date(endTimeStr).getTime() : 0;
                // If target date is invalid or in the past, default to a rolling 12 hour timer
                if (!initialTarget || initialTarget < new Date().getTime()) {
                    initialTarget = new Date().getTime() + (12 * 60 * 60 * 1000);
                }

                return {
                    days: '00',
                    hours: '00',
                    minutes: '00',
                    seconds: '00',
                    target: initialTarget,
                    start() {
                        setInterval(() => {
                            let now = new Date().getTime();
                            let distance = this.target - now;

                            if (distance < 0) {
                                // If campaigns expires, just keep countdown at zero
                                this.days = '00';
                                this.hours = '00';
                                this.minutes = '00';
                                this.seconds = '00';
                                return;
                            }

                            let d = Math.floor(distance / (1000 * 60 * 60 * 24));
                            let h = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                            let m = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                            let s = Math.floor((distance % (1000 * 60)) / 1000);

                            this.days = d < 10 ? '0' + d : d;
                            this.hours = h < 10 ? '0' + h : h;
                            this.minutes = m < 10 ? '0' + m : m;
                            this.seconds = s < 10 ? '0' + s : s;
                        }, 1000);
                    }
                }
            }

            document.getElementById('newsletter-form').addEventListener('submit', function (e) {
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

                        if (data.success) {
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