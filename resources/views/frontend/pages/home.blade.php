@extends('frontend.layout')

@section('title', __('Phones, laptops, home appliances & more'))

@use('App\Support\Storefront')

@section('content')
@php
    $threshold = Storefront::freeShippingThreshold();
    $trust = [
        ['icon' => 'bi-cash-coin', 'title' => __('Cash on delivery'), 'text' => __('Pay when your order arrives'), 'class' => 'lg:hidden'],
        $threshold > 0
            ? ['icon' => 'bi-truck', 'title' => __('Free delivery'), 'text' => __('On orders over :amount', ['amount' => money($threshold)]), 'class' => '']
            : ['icon' => 'bi-truck', 'title' => __('Fast delivery'), 'text' => __('Across Bangladesh'), 'class' => ''],
        ['icon' => 'bi-arrow-counterclockwise', 'title' => __(':days-day returns', ['days' => localized_number(config('store.return_days', 30))]), 'text' => __('Easy return & refund'), 'class' => ''],
        ['icon' => 'bi-shield-lock', 'title' => __('Secure payment'), 'text' => 'bKash · Nagad · Visa · Mastercard', 'class' => ''],
        ['icon' => 'bi-headset', 'title' => __('24/7 support'), 'text' => __('Chat or call any time'), 'class' => ''],
    ];
@endphp

<div class="mx-auto max-w-7xl px-4">
    {{-- Category chips (mobile) --}}
    @if($departments->isNotEmpty())
        <section class="pt-4 md:hidden" aria-labelledby="chips-title">
            <div class="mb-2 flex items-center justify-between">
                <h2 id="chips-title" class="text-sm font-semibold text-slate-900">{{ __('Categories') }}</h2>
                <a href="{{ route('products.index') }}" class="flex min-h-11 items-center gap-1 text-sm font-semibold text-[var(--sf-accent-strong)] sf-focus rounded">{{ __('See all') }} <i class="bi bi-chevron-right text-xs" aria-hidden="true"></i></a>
            </div>
            <ul class="no-scrollbar sf-snap-row -mx-4 flex gap-2 overflow-x-auto px-4 pb-1">
                @foreach($departments as $department)
                    <li class="shrink-0">
                        <a href="{{ route('products.index', ['category' => $department['id']]) }}"
                           class="flex min-h-11 items-center gap-2 rounded-full border border-[var(--sf-border)] bg-white py-1.5 pl-1.5 pr-4 text-sm font-medium text-slate-700 shadow-[var(--sf-shadow-sm)] active:bg-slate-50 sf-focus">
                            <span class="flex size-8 items-center justify-center rounded-full bg-brand-50 text-brand-600" aria-hidden="true"><i class="bi {{ $department['icon'] }}"></i></span>
                            {{ $department['name'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Hero + promo banners --}}
    <section class="pt-3 md:pt-6" aria-label="{{ __('Featured offers') }}">
        <div @class(['grid gap-4', 'lg:grid-cols-3' => $subBanners->isNotEmpty()])>
            <x-store.hero-carousel :banners="$heroBanners"
                                   :class="'aspect-[16/9] sm:aspect-[21/9] lg:aspect-auto lg:h-[400px]' . ($subBanners->isNotEmpty() ? ' lg:col-span-2' : '')" />
            @if($subBanners->isNotEmpty())
                <div class="hidden gap-4 lg:grid lg:grid-rows-2">
                    @foreach($subBanners as $banner)
                        <a href="{{ $banner->link ?: route('flash-deals') }}" class="group relative block overflow-hidden rounded-[var(--sf-radius-lg)] bg-navy-800 sf-focus">
                            <x-store.image :src="Storefront::imageUrl($banner->image)" :alt="$banner->title ?: __('Promotion')" fit="cover" icon="bi-image" :width="420" :height="192"
                                           class="absolute inset-0" img-class="transition-transform duration-500 group-hover:scale-105" />
                            @if($banner->title)
                                <span class="absolute inset-0 bg-gradient-to-t from-navy-950/80 to-transparent" aria-hidden="true"></span>
                                <span class="absolute inset-x-4 bottom-3 text-white">
                                    <span class="block font-display text-lg font-bold leading-tight">{{ $banner->title }}</span>
                                    @if($banner->subtitle)<span class="block text-xs text-white/80">{{ $banner->subtitle }}</span>@endif
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- Trust strip --}}
    <section class="mt-4 md:mt-6" aria-label="{{ __('Why shop with us') }}">
        <ul class="no-scrollbar sf-snap-row -mx-4 flex gap-3 overflow-x-auto px-4 pb-1 lg:mx-0 lg:grid lg:grid-cols-4 lg:gap-4 lg:overflow-visible lg:px-0">
            @foreach($trust as $item)
                <li class="{{ $item['class'] }} flex min-w-[210px] shrink-0 items-center gap-3 rounded-[var(--sf-radius)] border border-[var(--sf-border)] bg-white p-3 lg:min-w-0 lg:p-4">
                    <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-lg text-brand-600 lg:size-12 lg:text-xl" aria-hidden="true"><i class="bi {{ $item['icon'] }}"></i></span>
                    <span class="min-w-0">
                        <span class="block text-sm font-semibold text-slate-900">{{ $item['title'] }}</span>
                        <span class="block truncate text-xs text-slate-500">{{ $item['text'] }}</span>
                    </span>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- Shop by category (desktop) --}}
    @if($departments->isNotEmpty())
        <section class="mt-12 hidden md:block" aria-labelledby="categories-title">
            <x-store.section-header :title="__('Shop by category')" id="categories-title" :href="route('products.index')" :link-label="__('All products')" />
            <ul class="grid grid-cols-5 gap-4">
                @foreach($departments->take(16) as $department)
                    <li>
                        <a href="{{ route('products.index', ['category' => $department['id']]) }}"
                           class="group flex h-full flex-col items-center gap-2 rounded-[var(--sf-radius)] border border-[var(--sf-border)] bg-white p-4 text-center transition hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-[var(--sf-shadow)] sf-focus">
                            @if($department['image'])
                                <x-store.image :src="$department['image']" alt="" fit="cover" :icon="$department['icon']" :width="64" :height="64" class="size-16 rounded-full bg-brand-50" />
                            @else
                                <span class="flex size-16 items-center justify-center rounded-full bg-brand-50 text-2xl text-brand-600 transition-colors group-hover:bg-primary group-hover:text-white" aria-hidden="true"><i class="bi {{ $department['icon'] }}"></i></span>
                            @endif
                            <span class="line-clamp-2 text-sm font-semibold leading-tight text-slate-800">{{ $department['name'] }}</span>
                            <span class="text-xs text-slate-500">{{ trans_choice(':count item|:count items', $department['count'], ['count' => localized_number($department['count'])]) }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    {{-- Flash sale --}}
    @if($flashDealProducts->isNotEmpty())
        <section class="mt-8 md:mt-12" aria-labelledby="flash-title">
            <div class="overflow-hidden rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white shadow-[var(--sf-shadow-sm)]">
                <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 bg-gradient-to-r from-sale to-[#f97316] px-4 py-3 text-white sm:px-6">
                    <div class="flex items-center gap-2">
                        <i class="bi bi-lightning-charge-fill text-xl text-yellow-300" aria-hidden="true"></i>
                        <h2 id="flash-title" class="font-display text-lg font-bold sm:text-2xl">{{ $flashDealTitle }}</h2>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="hidden text-sm text-white/85 sm:inline">{{ __('Ends in') }}</span>
                        <x-store.countdown :end="$flashDealEndTime" tone="dark" />
                        <a href="{{ route('flash-deals') }}" class="hidden min-h-11 items-center gap-1 rounded text-sm font-semibold text-white hover:underline sm:flex sf-focus">{{ __('View all flash deals') }} <i class="bi bi-chevron-right text-xs" aria-hidden="true"></i></a>
                    </div>
                </div>

                <div x-data="scrollRow" class="relative p-3 sm:p-4">
                    <ul x-ref="track" @scroll.debounce.60ms="update()" class="no-scrollbar sf-snap-row flex gap-3 overflow-x-auto pb-1">
                        @foreach($flashDealProducts as $product)
                            <li class="w-[150px] shrink-0 sm:w-[190px] lg:w-[calc((100%-3rem)/5)]">
                                <x-store.product-card :product="$product" :wishlist-ids="$wishlistIds" variant="rail" :sold="(int) $product->order_items_sum_quantity" />
                            </li>
                        @endforeach
                    </ul>
                    <button type="button" @click="scroll(-1)" x-show="!atStart" x-cloak class="absolute left-1 top-1/2 hidden size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white text-slate-700 shadow-[var(--sf-shadow)] ring-1 ring-black/5 hover:text-brand-700 lg:flex sf-focus">
                        <span class="sr-only">{{ __('Previous') }}</span><i class="bi bi-chevron-left" aria-hidden="true"></i>
                    </button>
                    <button type="button" @click="scroll(1)" x-show="!atEnd" class="absolute right-1 top-1/2 hidden size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white text-slate-700 shadow-[var(--sf-shadow)] ring-1 ring-black/5 hover:text-brand-700 lg:flex sf-focus">
                        <span class="sr-only">{{ __('Next') }}</span><i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </button>
                    <a href="{{ route('flash-deals') }}" class="mt-2 flex min-h-11 items-center justify-center gap-1 rounded-[var(--sf-radius-sm)] bg-sale-soft text-sm font-semibold text-sale sm:hidden sf-focus">{{ __('View all flash deals') }} <i class="bi bi-chevron-right text-xs" aria-hidden="true"></i></a>
                </div>
            </div>
        </section>
    @endif

    {{-- Just for you --}}
    <section class="mt-8 md:mt-12" aria-labelledby="feed-title"
             x-data="productFeed({ url: @js(route('load.more.products')), initial: 15, step: 10, hasMore: @js($feedHasMore) })">
        <x-store.section-header :title="__('Just For You')" id="feed-title" icon="bi-stars" />

        @if($feedTabs->count() > 1)
            <div class="no-scrollbar -mx-4 mb-4 flex gap-2 overflow-x-auto px-4 md:mx-0 md:px-0" role="group" aria-label="{{ __('Filter by category') }}">
                @foreach(collect([['id' => '', 'name' => __('All')]])->concat($feedTabs) as $tab)
                    <button type="button" @click="select(@js((string) $tab['id']))" :aria-pressed="(category === @js((string) $tab['id'])).toString()"
                            class="min-h-11 shrink-0 rounded-full border px-4 text-sm font-medium transition-colors sf-focus"
                            :class="category === @js((string) $tab['id']) ? 'border-navy-900 bg-navy-900 text-white' : 'border-[var(--sf-border)] bg-white text-slate-700 hover:border-slate-300'">
                        {{ $tab['name'] }}
                    </button>
                @endforeach
            </div>
        @endif

        <div x-ref="grid" class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:gap-4 lg:grid-cols-5" :aria-busy="loading.toString()" aria-live="polite">
            @foreach($latestProducts as $product)
                <x-store.product-card :product="$product" :wishlist-ids="$wishlistIds" :eager="$loop->index < 2 && $flashDealProducts->isEmpty()" />
            @endforeach
        </div>
        <div x-show="loading" x-cloak class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 md:mt-4 md:gap-4 lg:grid-cols-5">
            <x-store.skeleton-card :count="5" />
        </div>
        <p x-show="empty && !loading" x-cloak class="rounded-[var(--sf-radius)] border border-dashed border-[var(--sf-border)] bg-white p-8 text-center text-sm text-slate-500">{{ __('No products in this category yet.') }}</p>

        <div class="mt-6 text-center" x-show="hasMore">
            <button type="button" @click="more()" :disabled="loading"
                    class="inline-flex min-h-12 items-center gap-2 rounded-full border-2 border-navy-900 px-8 text-sm font-semibold text-navy-900 transition-colors hover:bg-navy-900 hover:text-white disabled:opacity-60 sf-focus">
                <i class="bi" :class="loading ? 'bi-arrow-repeat animate-spin' : 'bi-plus-lg'" aria-hidden="true"></i>
                <span x-text="loading ? @js(__('Loading…')) : @js(__('Load more'))">{{ __('Load more') }}</span>
            </button>
        </div>
    </section>

    {{-- Brand strip (desktop) --}}
    @if($brands->isNotEmpty())
        <section class="mt-14 hidden md:block" aria-labelledby="brands-title">
            <x-store.section-header :title="__('Top brands')" id="brands-title" />
            <ul class="grid grid-cols-6 gap-3">
                @foreach($brands as $brand)
                    <li>
                        <a href="{{ route('products.index', ['brand' => $brand->slug]) }}"
                           class="flex h-16 items-center justify-center rounded-[var(--sf-radius)] border border-[var(--sf-border)] bg-white px-4 text-sm font-bold uppercase tracking-wide text-slate-700 transition hover:border-brand-200 hover:text-brand-700 hover:shadow-[var(--sf-shadow-sm)] sf-focus">
                            @if($brand->logo)
                                <img src="{{ Storefront::imageUrl($brand->logo) }}" alt="{{ $brand->name }}" class="max-h-9 max-w-full object-contain" loading="lazy" width="120" height="36">
                            @else
                                {{ $brand->name }}
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>

{{-- Newsletter --}}
<section class="mx-auto mt-10 max-w-7xl px-4 pb-10 md:mt-16 md:pb-16" aria-labelledby="newsletter-title">
    <div x-data="newsletter" class="relative overflow-hidden rounded-[var(--sf-radius-lg)] bg-navy-900 px-5 py-6 text-white md:flex md:items-center md:justify-between md:gap-10 md:px-12 md:py-12">
        <div aria-hidden="true" class="pointer-events-none absolute -right-20 -top-24 size-72 rounded-full bg-primary/25 blur-3xl"></div>
        <div class="relative max-w-xl">
            <h2 id="newsletter-title" class="font-display text-xl font-bold md:text-3xl">{{ __('Get deals before everyone else') }}</h2>
            <p class="mt-1 text-sm text-slate-300 md:mt-2 md:text-base">{{ __('Flash sale alerts, new arrivals and coupon codes. No spam, unsubscribe any time.') }}</p>
        </div>
        <form @submit.prevent="submit()" class="relative mt-4 w-full md:mt-0 md:max-w-md">
            <div class="flex gap-2">
                <label for="newsletter-email" class="sr-only">{{ __('Email address') }}</label>
                <input id="newsletter-email" type="email" x-model="email" required autocomplete="email" placeholder="{{ __('Your email address') }}"
                       class="h-12 min-w-0 flex-1 rounded-full border-0 bg-white/10 px-5 text-sm text-white ring-1 ring-white/20 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary">
                <button type="submit" :disabled="loading" class="h-12 shrink-0 rounded-full bg-primary px-6 text-sm font-semibold text-white hover:bg-primary-dark disabled:opacity-60 sf-focus">
                    <span x-show="!loading">{{ __('Subscribe') }}</span><span x-show="loading" x-cloak>{{ __('Wait…') }}</span>
                </button>
            </div>
            <p x-show="message" x-cloak x-text="message" class="mt-2 text-sm" :class="ok ? 'text-emerald-300' : 'text-rose-300'" role="status"></p>
        </form>
    </div>
</section>
@endsection
