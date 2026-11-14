@extends('frontend.layout')

@use('App\Support\Storefront')
@use('App\Services\CheckoutOptions')

@section('title', $product->name)
@section('meta_description', \Illuminate\Support\Str::limit(trim(strip_tags((string) $product->description)), 155))

@php
    $fmt = fn ($amount) => money($amount, null, fmod((float) $amount, 1.0) != 0.0 ? 2 : 0);
    $name = (string) $product->name;
    $images = $product->images->map(fn ($image) => Storefront::imageUrl($image->image))->filter()->values();
    $icon = Storefront::categoryIcon($product->category);
    $listPrice = (float) $product->price;
    $salePrice = (float) ($product->discounted_price ?: $product->price);

    $priceState = function (float $sale, float $base, int $stock, ?string $sku) use ($fmt) {
        $percent = $base > $sale && $base > 0 ? (int) round(($base - $sale) / $base * 100) : 0;
        return [
            'priceText' => $fmt($sale),
            'baseText' => $percent ? $fmt($base) : null,
            'saveText' => $percent ? __('You save :amount', ['amount' => $fmt($base - $sale)]) : null,
            'percent' => $percent,
            'stock' => $stock,
            'sku' => $sku,
        ];
    };

    // Option groups (e.g. Color, Storage) and every sellable variant with its own price and stock.
    $attributes = [];
    $variants = [];
    foreach ($product->variants as $variant) {
        $variant->setRelation('product', $product);
        $map = [];
        foreach ($variant->attributeValues as $value) {
            if (!$value->attribute) {
                continue;
            }
            $attributes[$value->attribute->id] ??= ['id' => $value->attribute->id, 'name' => $value->attribute->name, 'values' => []];
            $attributes[$value->attribute->id]['values'][$value->id] = ['id' => $value->id, 'value' => $value->value];
            $map[$value->attribute->id] = $value->id;
        }
        $base = (float) $variant->price > 0 ? (float) $variant->price : $listPrice;
        $variants[] = ['id' => $variant->id, 'attributes' => $map] + $priceState($variant->unitPrice(), $base, (int) $variant->stock, $variant->sku ?: $product->product_id);
    }
    $attributes = array_values(array_map(fn ($group) => ['id' => $group['id'], 'name' => $group['name'], 'values' => array_values($group['values'])], $attributes));
    $fallback = $priceState($salePrice, $listPrice, (int) $product->total_stock, $product->product_id);
    $initial = collect($variants)->firstWhere('stock', '>', 0) ?? ($variants[0] ?? $fallback);

    $config = [
        'id' => $product->id,
        'name' => $name,
        'images' => $images,
        'attributes' => $attributes,
        'variants' => $variants,
        'fallback' => $fallback,
        'initial' => $initial['attributes'] ?? [],
        'inWishlist' => in_array($product->id, $wishlistIds, true),
        'liked' => $liked,
        'likes' => (int) $product->reactions_count,
        'auth' => auth()->check(),
        'routes' => ['react' => route('product.react', $product->id), 'login' => route('user.auth.login')],
        'i18n' => [
            'copied' => __('Link copied'),
            'notify' => __('Please notify me when this is back in stock: :name', ['name' => $name]),
        ],
    ];

    $specs = collect([
        __('Brand') => $product->brand?->name,
        __('Model') => $product->model,
        __('Warranty') => $product->warranty_period,
        __('Category') => $product->category?->name,
        __('Product code') => $product->product_id,
    ])->merge(is_array($product->specifications) ? $product->specifications : [])->filter(fn ($value) => filled($value));

    $ratingAvg = (float) $product->rating_avg;
    $ratingCount = (int) $product->rating_count;
    $myReview = auth()->check() ? $product->comments->firstWhere('user_id', auth()->id()) : null;
    $returnDays = (int) config('store.return_days', 30);
    $videoUrl = filter_var($product->video_link, FILTER_VALIDATE_URL) ? $product->video_link : null;
    $card = 'rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white';
@endphp

@push('head')
    <style>@media (max-width: 1023px) { :root { --sf-fab-lift: 76px; } }</style>
@endpush

@section('content')
<div x-data="productPage(@js($config))" class="mx-auto max-w-7xl px-4 pb-28 pt-4 md:pt-6 lg:pb-16">

    {{-- Breadcrumb --}}
    <nav aria-label="{{ __('Breadcrumb') }}" class="text-xs text-slate-500">
        <ol class="no-scrollbar flex items-center gap-1.5 overflow-x-auto whitespace-nowrap">
            <li><a href="{{ route('home') }}" class="rounded hover:text-brand-700 sf-focus">{{ __('Home') }}</a></li>
            <li aria-hidden="true"><i class="bi bi-chevron-right text-[10px]"></i></li>
            <li><a href="{{ route('products.index') }}" class="rounded hover:text-brand-700 sf-focus">{{ __('Shop') }}</a></li>
            @foreach(array_filter([$product->category?->parent, $product->category]) as $crumb)
                <li aria-hidden="true"><i class="bi bi-chevron-right text-[10px]"></i></li>
                <li><a href="{{ route('products.index', ['category' => $crumb->id]) }}" class="rounded hover:text-brand-700 sf-focus">{{ $crumb->name }}</a></li>
            @endforeach
            <li aria-hidden="true"><i class="bi bi-chevron-right text-[10px]"></i></li>
            <li aria-current="page" class="max-w-[16rem] truncate font-medium text-slate-700">{{ $name }}</li>
        </ol>
    </nav>

    @foreach(['success' => 'border-emerald-200 bg-emerald-50 text-emerald-800', 'error' => 'border-rose-200 bg-rose-50 text-rose-800'] as $key => $tone)
        @if(session($key))
            <div class="mt-4 flex gap-3 rounded-[var(--sf-radius-sm)] border p-4 text-sm {{ $tone }}" role="{{ $key === 'error' ? 'alert' : 'status' }}">
                <i class="bi {{ $key === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill' }} mt-0.5" aria-hidden="true"></i><p>{{ session($key) }}</p>
            </div>
        @endif
    @endforeach

    <div class="mt-4 lg:grid lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)] lg:items-start lg:gap-10">
        {{-- ═══ Gallery ═══ --}}
        <section class="lg:sticky lg:top-24" aria-label="{{ __('Product images') }}">
            <div class="lg:grid lg:grid-cols-[72px_minmax(0,1fr)] lg:gap-4">
                {{-- Thumbnails (desktop) --}}
                @if($images->count() > 1 || $videoUrl)
                    <ul class="no-scrollbar hidden max-h-[560px] flex-col gap-2 overflow-y-auto lg:flex" aria-label="{{ __('Choose image') }}">
                        @foreach($images as $i => $src)
                            <li>
                                <button type="button" @click="go({{ $i }})" @mouseenter="go({{ $i }})" :aria-current="(active === {{ $i }}).toString()"
                                        class="flex size-[72px] items-center justify-center overflow-hidden rounded-[var(--sf-radius-sm)] border-2 bg-white p-1 transition-colors sf-focus"
                                        :class="active === {{ $i }} ? 'border-brand-600' : 'border-[var(--sf-border)] hover:border-slate-300'">
                                    <img src="{{ $src }}" alt="" class="size-full object-contain mix-blend-multiply" loading="lazy">
                                    <span class="sr-only">{{ __('Image :n', ['n' => $i + 1]) }}</span>
                                </button>
                            </li>
                        @endforeach
                        @if($videoUrl)
                            <li>
                                <a href="{{ $videoUrl }}" target="_blank" rel="noopener" class="flex size-[72px] flex-col items-center justify-center gap-0.5 rounded-[var(--sf-radius-sm)] border-2 border-[var(--sf-border)] bg-navy-900 text-white hover:border-slate-300 sf-focus">
                                    <i class="bi bi-play-circle-fill text-xl" aria-hidden="true"></i><span class="text-[10px] font-semibold">{{ __('Video') }}</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                @else
                    <div class="hidden lg:block" aria-hidden="true"></div>
                @endif

                {{-- Main image --}}
                <div class="relative">
                    <div class="relative aspect-square overflow-hidden rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white"
                         @touchstart.passive="touchStart($event)" @touchend="touchEnd($event)">
                        @forelse($images as $i => $src)
                            <button type="button" x-show="active === {{ $i }}" @if($i > 0) style="display: none" @endif
                                    @click="openLightbox()" @mouseenter="zoomStart()" @mouseleave="zoom = false" @mousemove="zoomMove($event)"
                                    class="absolute inset-0 flex cursor-zoom-in items-center justify-center p-6 sf-focus md:p-10" aria-label="{{ __('Open image :n in full screen', ['n' => $i + 1]) }}">
                                <img src="{{ $src }}" alt="{{ $i === 0 ? $name : '' }}" @if($i === 0) fetchpriority="high" @else loading="lazy" @endif
                                     class="size-full object-contain mix-blend-multiply transition-transform duration-200 ease-out"
                                     :style="zoom ? `transform: scale(2); transform-origin: ${origin}` : ''">
                            </button>
                        @empty
                            <div class="absolute inset-0 flex items-center justify-center text-7xl text-slate-200" aria-hidden="true"><i class="bi {{ $icon }}"></i></div>
                        @endforelse

                        <div class="pointer-events-none absolute left-3 top-3 flex flex-col items-start gap-1.5">
                            <span x-show="(current ?? fallback).percent > 0" @if(!$initial['percent']) style="display: none" @endif
                                  class="rounded-full bg-sale px-2.5 py-1 text-xs font-bold text-white" x-text="'-' + (current ?? fallback).percent + '%'">-{{ $initial['percent'] }}%</span>
                            @if($flashDealEnd)
                                <span class="flex items-center gap-1 rounded-full bg-navy-900 px-2.5 py-1 text-xs font-semibold text-white"><i class="bi bi-lightning-charge-fill text-amber-300" aria-hidden="true"></i>{{ __('Flash sale') }}</span>
                            @endif
                        </div>

                        @if($images->count() > 1)
                            <span class="pointer-events-none absolute bottom-3 left-3 rounded-full bg-navy-900/70 px-2.5 py-1 text-xs font-medium text-white lg:hidden" x-text="(active + 1) + ' / ' + images.length">1 / {{ $images->count() }}</span>
                            <button type="button" @click="go(active - 1)" class="absolute left-2 top-1/2 hidden size-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow-[var(--sf-shadow)] ring-1 ring-black/5 hover:text-brand-700 md:flex sf-focus">
                                <span class="sr-only">{{ __('Previous image') }}</span><i class="bi bi-chevron-left" aria-hidden="true"></i>
                            </button>
                            <button type="button" @click="go(active + 1)" class="absolute right-2 top-1/2 hidden size-10 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow-[var(--sf-shadow)] ring-1 ring-black/5 hover:text-brand-700 md:flex sf-focus">
                                <span class="sr-only">{{ __('Next image') }}</span><i class="bi bi-chevron-right" aria-hidden="true"></i>
                            </button>
                        @endif
                    </div>

                    {{-- Mobile: dots + video --}}
                    @if($images->count() > 1 || $videoUrl)
                        <div class="mt-3 flex items-center justify-center gap-3 lg:hidden">
                            @if($images->count() > 1)
                                <div class="flex items-center gap-1.5" aria-hidden="true">
                                    @foreach($images as $i => $src)
                                        <button type="button" tabindex="-1" @click="go({{ $i }})" class="h-1.5 rounded-full transition-all" :class="active === {{ $i }} ? 'w-5 bg-brand-600' : 'w-1.5 bg-slate-300'"></button>
                                    @endforeach
                                </div>
                            @endif
                            @if($videoUrl)
                                <a href="{{ $videoUrl }}" target="_blank" rel="noopener" class="flex min-h-9 items-center gap-1.5 rounded-full bg-navy-900 px-3 text-xs font-semibold text-white sf-focus"><i class="bi bi-play-circle-fill" aria-hidden="true"></i> {{ __('Watch video') }}</a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </section>

        {{-- ═══ Info & purchase ═══ --}}
        <section class="mt-6 lg:mt-0" aria-labelledby="product-title">
            <div class="flex flex-wrap items-center gap-2 text-sm">
                @if($product->brand)
                    <a href="{{ route('products.index', ['brand' => $product->brand->slug]) }}" class="rounded font-semibold uppercase tracking-wide text-[var(--sf-accent-strong)] hover:underline sf-focus">{{ $product->brand->name }}</a>
                @endif
                @if($product->category)
                    <span class="text-slate-300" aria-hidden="true">·</span>
                    <a href="{{ route('products.index', ['category' => $product->category->id]) }}" class="rounded text-slate-500 hover:text-brand-700 sf-focus">{{ $product->category->name }}</a>
                @endif
            </div>

            <h1 id="product-title" class="mt-1.5 font-display text-2xl font-bold leading-tight tracking-tight text-slate-900 md:text-3xl">{{ $name }}</h1>

            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                <a href="#reviews" class="flex items-center gap-1.5 rounded text-slate-600 hover:text-brand-700 sf-focus">
                    <span class="flex text-amber-400" aria-hidden="true">@include('frontend.partials.rating-stars', ['rating' => $ratingAvg])</span>
                    @if($ratingCount)
                        <span class="font-semibold text-slate-900">{{ number_format($ratingAvg, 1) }}</span>
                    @endif
                    <span class="underline-offset-2 hover:underline">{{ trans_choice(':count review|:count reviews', $ratingCount, ['count' => localized_number($ratingCount)]) }}</span>
                </a>
                <span class="text-slate-500">{{ __('Code') }}: <span class="font-mono text-slate-700" x-text="(current ?? fallback).sku">{{ $initial['sku'] }}</span></span>
            </div>

            {{-- Price --}}
            <div class="mt-5 rounded-[var(--sf-radius-lg)] bg-slate-50 p-4 md:p-5">
                @if($flashDealEnd)
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-[var(--sf-radius-sm)] bg-gradient-to-r from-sale to-[#f97316] px-3 py-2 text-white">
                        <span class="flex items-center gap-1.5 text-sm font-semibold"><i class="bi bi-lightning-charge-fill text-amber-200" aria-hidden="true"></i> {{ __('Flash sale ends in') }}</span>
                        <x-store.countdown :end="$flashDealEnd" tone="dark" />
                    </div>
                @endif
                <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                    <span class="font-display text-3xl font-bold tabular-nums text-slate-900 md:text-4xl" x-text="(current ?? fallback).priceText">{{ $initial['priceText'] }}</span>
                    <span x-show="(current ?? fallback).baseText" @if(!$initial['baseText']) style="display: none" @endif class="text-lg tabular-nums text-slate-400 line-through" x-text="(current ?? fallback).baseText">{{ $initial['baseText'] }}</span>
                    <span x-show="(current ?? fallback).percent > 0" @if(!$initial['percent']) style="display: none" @endif class="rounded bg-sale-soft px-2 py-0.5 text-sm font-bold text-sale" x-text="'-' + (current ?? fallback).percent + '%'">-{{ $initial['percent'] }}%</span>
                </div>
                <p x-show="(current ?? fallback).saveText" @if(!$initial['saveText']) style="display: none" @endif class="mt-1 text-sm font-semibold text-[var(--sf-success)]" x-text="(current ?? fallback).saveText">{{ $initial['saveText'] }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ __('Price includes VAT. Delivery charge calculated at checkout.') }}</p>
            </div>

            {{-- Options --}}
            @foreach($attributes as $group)
                <fieldset class="mt-5">
                    <legend class="mb-2 text-sm text-slate-600">
                        {{ $group['name'] }}:
                        <span class="font-semibold text-slate-900" x-text="valueName({{ $group['id'] }})"></span>
                    </legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach($group['values'] as $value)
                            <button type="button" @click="select({{ $group['id'] }}, {{ $value['id'] }})"
                                    :aria-pressed="(String(selected[{{ $group['id'] }}]) === '{{ $value['id'] }}').toString()"
                                    class="min-h-11 min-w-11 rounded-[var(--sf-radius-sm)] border-2 px-4 text-sm font-medium transition-colors sf-focus"
                                    :class="String(selected[{{ $group['id'] }}]) === '{{ $value['id'] }}'
                                        ? 'border-brand-600 bg-brand-50 text-brand-800'
                                        : (available({{ $group['id'] }}, {{ $value['id'] }}) ? 'border-[var(--sf-border)] bg-white text-slate-700 hover:border-slate-400' : 'border-dashed border-slate-200 bg-white text-slate-400 line-through')">
                                {{ $value['value'] }}
                            </button>
                        @endforeach
                    </div>
                </fieldset>
            @endforeach

            {{-- Stock --}}
            <p class="mt-5 flex items-center gap-2 text-sm font-medium" role="status">
                <template x-if="unavailable">
                    <span class="flex items-center gap-2 text-slate-500"><i class="bi bi-slash-circle" aria-hidden="true"></i> {{ __('This combination is not available') }}</span>
                </template>
                <template x-if="!unavailable && stock > 5">
                    <span class="flex items-center gap-2 text-[var(--sf-success)]"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> {{ __('In stock') }}</span>
                </template>
                <template x-if="!unavailable && stock > 0 && stock <= 5">
                    <span class="flex items-center gap-2 text-[var(--sf-warning)]"><i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i> <span x-text="@js(__('Only :count left – order soon')).replace(':count', stock)"></span></span>
                </template>
                <template x-if="!unavailable && stock <= 0">
                    <span class="flex items-center gap-2 text-rose-600"><i class="bi bi-x-circle-fill" aria-hidden="true"></i> {{ __('Out of stock') }}</span>
                </template>
            </p>

            {{-- Buy --}}
            <form x-ref="form" action="{{ route('cart.add') }}" method="POST" class="mt-4" @submit="if (!canBuy) $event.preventDefault()">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="variant_id" :value="variant ? variant.id : ''">
                <input type="hidden" name="quantity" :value="qty">

                <div x-show="canBuy" class="flex flex-wrap items-center gap-3">
                    <div class="flex h-12 items-center rounded-[var(--sf-radius-sm)] border border-slate-300 bg-white" role="group" aria-label="{{ __('Quantity') }}">
                        <button type="button" @click="dec()" :disabled="qty <= 1" class="flex size-12 items-center justify-center text-slate-600 hover:text-brand-700 disabled:text-slate-300 sf-focus">
                            <span class="sr-only">{{ __('Decrease quantity') }}</span><i class="bi bi-dash-lg" aria-hidden="true"></i>
                        </button>
                        <span class="w-10 text-center font-semibold tabular-nums text-slate-900" x-text="qty" aria-live="polite">1</span>
                        <button type="button" @click="inc()" :disabled="qty >= maxQty" class="flex size-12 items-center justify-center text-slate-600 hover:text-brand-700 disabled:text-slate-300 sf-focus">
                            <span class="sr-only">{{ __('Increase quantity') }}</span><i class="bi bi-plus-lg" aria-hidden="true"></i>
                        </button>
                    </div>
                    <button type="button" @click="add()" :disabled="adding"
                            class="flex h-12 min-w-40 flex-1 items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] border-2 border-navy-900 bg-white px-5 text-sm font-semibold text-navy-900 transition-colors hover:bg-navy-900 hover:text-white disabled:opacity-60 sf-focus"
                            :class="added && '!border-[var(--sf-success)] !bg-[var(--sf-success)] !text-white'">
                        <i class="bi" :class="added ? 'bi-check-lg' : (adding ? 'bi-arrow-repeat animate-spin' : 'bi-cart-plus')" aria-hidden="true"></i>
                        <span x-text="added ? @js(__('Added to cart')) : @js(__('Add to cart'))">{{ __('Add to cart') }}</span>
                    </button>
                    <button type="submit" name="buy_now" value="1"
                            class="flex h-12 w-full items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] bg-brand-600 px-6 text-sm font-semibold text-white shadow-[var(--sf-shadow)] transition-colors hover:bg-brand-700 sm:w-auto sm:flex-1 sf-focus">
                        <i class="bi bi-lightning-charge-fill" aria-hidden="true"></i> {{ __('Buy now') }}
                    </button>
                </div>
                <button type="button" x-show="!canBuy" @if($initial['stock'] > 0) style="display: none" @endif @click="notify()"
                        class="flex h-12 w-full items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] border-2 border-slate-300 bg-white text-sm font-semibold text-slate-800 hover:border-brand-600 hover:text-brand-700 sf-focus">
                    <i class="bi bi-bell" aria-hidden="true"></i> {{ __('Notify me when available') }}
                </button>
            </form>

            {{-- Secondary actions --}}
            <div class="mt-3 flex flex-wrap items-center gap-1">
                <button type="button" @click="toggleWishlist()" :aria-pressed="inWishlist.toString()" class="flex min-h-10 items-center gap-1.5 rounded-full px-3 text-sm font-medium text-slate-600 hover:bg-slate-100 sf-focus">
                    <i class="bi" :class="inWishlist ? 'bi-heart-fill text-sale' : 'bi-heart'" aria-hidden="true"></i>
                    <span x-text="inWishlist ? @js(__('Saved')) : @js(__('Save'))">{{ __('Save') }}</span>
                </button>
                <button type="button" @click="like()" :aria-pressed="liked.toString()" class="flex min-h-10 items-center gap-1.5 rounded-full px-3 text-sm font-medium text-slate-600 hover:bg-slate-100 sf-focus">
                    <i class="bi" :class="liked ? 'bi-hand-thumbs-up-fill text-brand-600' : 'bi-hand-thumbs-up'" aria-hidden="true"></i>
                    <span x-text="likes">{{ $config['likes'] }}</span><span class="sr-only">{{ __('likes') }}</span>
                </button>
                <button type="button" @click="share()" class="flex min-h-10 items-center gap-1.5 rounded-full px-3 text-sm font-medium text-slate-600 hover:bg-slate-100 sf-focus">
                    <i class="bi bi-share" aria-hidden="true"></i> {{ __('Share') }}
                </button>
            </div>

            {{-- Delivery & services --}}
            <div class="{{ $card }} mt-5 divide-y divide-[var(--sf-border)] text-sm">
                <div class="flex gap-3 p-4">
                    <i class="bi bi-truck mt-0.5 text-lg text-brand-600" aria-hidden="true"></i>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-slate-900">{{ __('Home delivery') }}</p>
                        <ul class="mt-1 space-y-0.5 text-slate-600">
                            @foreach($deliveryOptions as $zone => $option)
                                <li class="flex justify-between gap-3">
                                    <span>{{ $zone === CheckoutOptions::OTHER_ZONE ? __('Other areas') : $zone }}@if($option['eta']) <span class="text-slate-400">· {{ $option['eta'] }}</span>@endif</span>
                                    <span class="tabular-nums text-slate-900">{{ $fmt($option['charge']) }}</span>
                                </li>
                            @endforeach
                        </ul>
                        @if($freeShippingThreshold)
                            <p class="mt-1.5 text-xs font-medium text-[var(--sf-success)]">{{ __('Free delivery on orders over :amount', ['amount' => $fmt($freeShippingThreshold)]) }}</p>
                        @endif
                    </div>
                </div>
                @if($codAvailable)
                    <div class="flex items-center gap-3 p-4"><i class="bi bi-cash-coin text-lg text-brand-600" aria-hidden="true"></i><p><span class="font-semibold text-slate-900">{{ __('Cash on delivery') }}</span> <span class="text-slate-500">· {{ __('pay when it arrives') }}</span></p></div>
                @endif
                <div class="flex items-center gap-3 p-4"><i class="bi bi-arrow-counterclockwise text-lg text-brand-600" aria-hidden="true"></i><p><span class="font-semibold text-slate-900">{{ __(':days-day returns', ['days' => localized_number($returnDays)]) }}</span> <span class="text-slate-500">· {{ __('if it is not right') }}</span></p></div>
                <div class="flex items-center gap-3 p-4"><i class="bi bi-shield-check text-lg text-brand-600" aria-hidden="true"></i><p><span class="font-semibold text-slate-900">{{ $product->warranty_period ?: __('100% genuine product') }}</span>@if($product->warranty_period) <span class="text-slate-500">· {{ __('100% genuine') }}</span>@endif</p></div>
            </div>
        </section>
    </div>

    {{-- ═══ Details ═══ --}}
    <nav class="no-scrollbar -mx-4 mt-10 flex gap-2 overflow-x-auto border-b border-[var(--sf-border)] px-4 md:mx-0 md:px-0" aria-label="{{ __('Product details') }}">
        @foreach(['description' => __('Description'), 'specifications' => __('Specifications'), 'reviews' => __('Reviews') . ' (' . localized_number($ratingCount) . ')'] as $anchor => $label)
            <a href="#{{ $anchor }}" class="-mb-px shrink-0 border-b-2 border-transparent px-4 py-3 text-sm font-semibold text-slate-600 hover:border-slate-300 hover:text-slate-900 sf-focus">{{ $label }}</a>
        @endforeach
    </nav>

    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
        {{-- Description --}}
        <section id="description" class="{{ $card }} scroll-mt-28 self-start p-5 md:p-6" aria-labelledby="description-title" x-data="{ open: false, long: false }" x-init="$nextTick(() => long = $refs.body.scrollHeight > 560)">
            <h2 id="description-title" class="text-lg font-semibold text-slate-900">{{ __('Description') }}</h2>
            <div class="relative mt-3">
                <div x-ref="body" class="sf-prose overflow-hidden" :class="!open && long && 'max-h-[560px]'">
                    @if(filled(strip_tags((string) $product->description)))
                        {!! clean((string) $product->description) !!}
                    @else
                        <p class="text-slate-500">{{ __('No description yet.') }}</p>
                    @endif
                </div>
                <div x-show="!open && long" x-cloak class="pointer-events-none absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-white to-transparent"></div>
            </div>
            <button type="button" x-show="long" x-cloak @click="open = !open" class="mt-3 flex min-h-10 items-center gap-1.5 rounded text-sm font-semibold text-brand-700 hover:underline sf-focus">
                <span x-text="open ? @js(__('Show less')) : @js(__('Read more'))"></span><i class="bi text-xs" :class="open ? 'bi-chevron-up' : 'bi-chevron-down'" aria-hidden="true"></i>
            </button>
        </section>

        {{-- Specifications --}}
        <section id="specifications" class="{{ $card }} scroll-mt-28 self-start overflow-hidden" aria-labelledby="specs-title">
            <h2 id="specs-title" class="px-5 pt-5 text-lg font-semibold text-slate-900 md:px-6 md:pt-6">{{ __('Specifications') }}</h2>
            <dl class="mt-3 divide-y divide-[var(--sf-border)] text-sm">
                @foreach($specs as $label => $value)
                    <div class="grid grid-cols-[minmax(0,2fr)_minmax(0,3fr)] gap-4 px-5 py-3 odd:bg-slate-50/70 md:px-6">
                        <dt class="text-slate-500">{{ $label }}</dt>
                        <dd class="break-words font-medium text-slate-900">{{ is_array($value) ? implode(', ', $value) : $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </section>
    </div>

    {{-- ═══ Reviews ═══ --}}
    <section id="reviews" class="{{ $card }} mt-6 scroll-mt-28 p-5 md:p-6" aria-labelledby="reviews-title">
        <h2 id="reviews-title" class="text-lg font-semibold text-slate-900">{{ __('Ratings & reviews') }}</h2>
        <div class="mt-5 grid gap-8 lg:grid-cols-[280px_minmax(0,1fr)]">
            <div>
                <div class="flex items-center gap-4">
                    <p class="font-display text-5xl font-bold tabular-nums text-slate-900">{{ $ratingCount ? number_format($ratingAvg, 1) : '–' }}</p>
                    <div>
                        <p class="flex text-lg text-amber-400" aria-hidden="true">@include('frontend.partials.rating-stars', ['rating' => $ratingAvg])</p>
                        <p class="text-sm text-slate-500">{{ trans_choice(':count review|:count reviews', $ratingCount, ['count' => localized_number($ratingCount)]) }}</p>
                    </div>
                </div>
                <ul class="mt-4 space-y-1.5" aria-label="{{ __('Rating breakdown') }}">
                    @foreach([5, 4, 3, 2, 1] as $star)
                        @php($starCount = (int) ($ratingCounts[$star] ?? 0))
                        <li class="flex items-center gap-2 text-sm">
                            <span class="w-8 tabular-nums text-slate-600">{{ $star }} <i class="bi bi-star-fill text-[10px] text-amber-400" aria-hidden="true"></i></span>
                            <span class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100"><span class="block h-full rounded-full bg-amber-400" style="width: {{ $ratingCount ? round($starCount / $ratingCount * 100) : 0 }}%"></span></span>
                            <span class="w-8 text-right tabular-nums text-slate-500">{{ localized_number($starCount) }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-6 border-t border-[var(--sf-border)] pt-5">
                    @auth
                        @if($myReview)
                            <p class="flex items-start gap-2 text-sm text-slate-600"><i class="bi bi-check-circle-fill mt-0.5 text-[var(--sf-success)]" aria-hidden="true"></i> {{ __('You reviewed this product. You can edit your review below.') }}</p>
                        @else
                            <div x-data="{ open: @js($errors->has('rating') || $errors->has('comment')) }">
                                <button type="button" x-show="!open" @click="open = true" class="flex h-11 w-full items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] border-2 border-navy-900 text-sm font-semibold text-navy-900 hover:bg-navy-900 hover:text-white sf-focus">
                                    <i class="bi bi-pencil-square" aria-hidden="true"></i> {{ __('Write a review') }}
                                </button>
                                <form x-show="open" x-cloak action="{{ route('product.comment', $product->id) }}" method="POST" class="space-y-3">
                                    @csrf
                                    <p class="text-sm font-semibold text-slate-900">{{ __('Your rating') }}</p>
                                    @include('frontend.partials.rating-input', ['current' => old('rating')])
                                    @error('rating')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                                    <label for="review-comment" class="sr-only">{{ __('Your review') }}</label>
                                    <textarea id="review-comment" name="comment" rows="4" required maxlength="1000" placeholder="{{ __('What did you like or dislike? How are you using it?') }}"
                                              class="block w-full resize-y rounded-[var(--sf-radius-sm)] border border-slate-300 px-3 py-2.5 text-sm placeholder:text-slate-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-primary/25">{{ old('comment') }}</textarea>
                                    @error('comment')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                                    <button type="submit" class="flex h-11 w-full items-center justify-center rounded-[var(--sf-radius-sm)] bg-brand-600 text-sm font-semibold text-white hover:bg-brand-700 sf-focus">{{ __('Submit review') }}</button>
                                    <p class="text-xs text-slate-500">{{ __('Reviews are published after a quick check.') }}</p>
                                </form>
                            </div>
                        @endif
                    @else
                        <p class="text-sm text-slate-600">{{ __('Bought this product? Share your experience.') }}</p>
                        <a href="{{ route('user.auth.login') }}" class="mt-3 flex h-11 items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] border-2 border-navy-900 text-sm font-semibold text-navy-900 hover:bg-navy-900 hover:text-white sf-focus">{{ __('Log in to write a review') }}</a>
                    @endauth
                </div>
            </div>

            <div>
                @forelse($product->comments as $review)
                    <article @class(['py-5 first:pt-0', 'border-t border-[var(--sf-border)]' => !$loop->first]) x-data="{ editing: false }">
                        <header class="flex items-start gap-3">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-brand-50 font-semibold text-brand-700" aria-hidden="true">{{ mb_strtoupper(mb_substr($review->user?->name ?? 'U', 0, 1)) }}</span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <p class="font-semibold text-slate-900">{{ $review->user?->name ?? __('Customer') }}</p>
                                    @unless($review->isApproved())
                                        <span @class(['rounded-full px-2 py-0.5 text-[11px] font-semibold', 'bg-rose-50 text-rose-700' => $review->status === 'rejected', 'bg-amber-50 text-amber-700' => $review->status !== 'rejected'])>
                                            {{ $review->status === 'rejected' ? __('Not published') : __('Awaiting approval') }}
                                        </span>
                                    @endunless
                                </div>
                                <p class="mt-0.5 flex items-center gap-2 text-xs text-slate-500">
                                    @if($review->rating)<span class="flex text-amber-400" aria-label="{{ __(':rating out of 5 stars', ['rating' => $review->rating]) }}">@include('frontend.partials.rating-stars', ['rating' => $review->rating])</span>@endif
                                    <time datetime="{{ $review->created_at->toIso8601String() }}">{{ $review->created_at->format('d M Y') }}</time>
                                </p>
                            </div>
                            @if(auth()->id() === $review->user_id)
                                <div class="flex shrink-0 items-center gap-1">
                                    <button type="button" @click="editing = !editing" class="flex min-h-9 items-center gap-1 rounded-full px-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 sf-focus">
                                        <i class="bi bi-pencil" aria-hidden="true"></i> <span x-text="editing ? @js(__('Cancel')) : @js(__('Edit'))">{{ __('Edit') }}</span>
                                    </button>
                                    <form action="{{ route('product.comment.delete', $review->id) }}" method="POST" @submit="if (!confirm(@js(__('Delete your review?')))) $event.preventDefault()">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="flex min-h-9 items-center gap-1 rounded-full px-2.5 text-xs font-semibold text-slate-600 hover:bg-rose-50 hover:text-rose-700 sf-focus"><i class="bi bi-trash" aria-hidden="true"></i> {{ __('Delete') }}</button>
                                    </form>
                                </div>
                            @endif
                        </header>
                        <p x-show="!editing" class="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $review->comment }}</p>
                        @if(auth()->id() === $review->user_id)
                            <form x-show="editing" x-cloak action="{{ route('product.comment.update', $review->id) }}" method="POST" class="mt-3 space-y-3">
                                @csrf @method('PUT')
                                @include('frontend.partials.rating-input', ['current' => $review->rating])
                                <label for="edit-review-{{ $review->id }}" class="sr-only">{{ __('Your review') }}</label>
                                <textarea id="edit-review-{{ $review->id }}" name="comment" rows="3" maxlength="1000" required class="block w-full resize-y rounded-[var(--sf-radius-sm)] border border-slate-300 px-3 py-2.5 text-sm focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-primary/25">{{ $review->comment }}</textarea>
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-xs text-slate-500">{{ __('Edited reviews are checked again before they appear.') }}</p>
                                    <button type="submit" class="flex h-10 items-center rounded-[var(--sf-radius-sm)] bg-navy-900 px-4 text-sm font-semibold text-white hover:bg-navy-800 sf-focus">{{ __('Save') }}</button>
                                </div>
                            </form>
                        @endif
                    </article>
                @empty
                    <div class="flex h-full flex-col items-center justify-center rounded-[var(--sf-radius)] bg-slate-50 px-6 py-10 text-center">
                        <span class="flex size-14 items-center justify-center rounded-full bg-white text-2xl text-amber-400 shadow-[var(--sf-shadow-sm)]" aria-hidden="true"><i class="bi bi-star"></i></span>
                        <p class="mt-3 font-semibold text-slate-900">{{ __('No reviews yet') }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ __('Be the first to share your experience with this product.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- ═══ Related ═══ --}}
    @if($relatedProducts->isNotEmpty())
        <section class="mt-10" aria-labelledby="related-title">
            <x-store.section-header :title="__('You may also like')" id="related-title" :href="$product->category ? route('products.index', ['category' => $product->category->id]) : route('products.index')" :link-label="__('See more')" />
            <div x-data="scrollRow" class="relative">
                <ul x-ref="track" @scroll.debounce.60ms="update()" class="no-scrollbar sf-snap-row -mx-4 flex gap-3 overflow-x-auto px-4 pb-1 md:gap-4 lg:mx-0 lg:px-0">
                    @foreach($relatedProducts as $related)
                        <li class="w-[160px] shrink-0 sm:w-[200px] lg:w-[calc((100%-4rem)/5)]">
                            <x-store.product-card :product="$related" :wishlist-ids="$wishlistIds" />
                        </li>
                    @endforeach
                </ul>
                <button type="button" @click="scroll(-1)" x-show="!atStart" x-cloak class="absolute -left-3 top-1/3 hidden size-11 items-center justify-center rounded-full bg-white text-slate-700 shadow-[var(--sf-shadow)] ring-1 ring-black/5 hover:text-brand-700 lg:flex sf-focus">
                    <span class="sr-only">{{ __('Previous') }}</span><i class="bi bi-chevron-left" aria-hidden="true"></i>
                </button>
                <button type="button" @click="scroll(1)" x-show="!atEnd" class="absolute -right-3 top-1/3 hidden size-11 items-center justify-center rounded-full bg-white text-slate-700 shadow-[var(--sf-shadow)] ring-1 ring-black/5 hover:text-brand-700 lg:flex sf-focus">
                    <span class="sr-only">{{ __('Next') }}</span><i class="bi bi-chevron-right" aria-hidden="true"></i>
                </button>
            </div>
        </section>
    @endif

    {{-- Mobile: buy bar above the bottom navigation --}}
    <div class="fixed inset-x-0 bottom-[calc(var(--sf-bottom-nav-h)+var(--sf-safe-bottom))] z-[var(--sf-z-fab)] border-t border-[var(--sf-border)] bg-white/95 px-4 py-2.5 shadow-[0_-8px_24px_-12px_rgb(15_23_42/0.25)] backdrop-blur md:bottom-0 md:pb-[calc(0.625rem+var(--sf-safe-bottom))] lg:hidden">
        <div class="flex items-center gap-2">
            <div class="min-w-0 flex-1">
                <p class="truncate font-display text-lg font-bold tabular-nums text-slate-900" x-text="(current ?? fallback).priceText">{{ $initial['priceText'] }}</p>
                <p class="truncate text-xs text-slate-500" x-show="(current ?? fallback).baseText" @if(!$initial['baseText']) style="display: none" @endif><span class="line-through" x-text="(current ?? fallback).baseText">{{ $initial['baseText'] }}</span></p>
            </div>
            <template x-if="canBuy">
                <div class="flex gap-2">
                    <button type="button" @click="add()" :disabled="adding" class="flex size-12 items-center justify-center rounded-[var(--sf-radius-sm)] border-2 border-navy-900 text-navy-900 sf-focus"
                            :class="added && '!border-[var(--sf-success)] !bg-[var(--sf-success)] !text-white'">
                        <span class="sr-only">{{ __('Add to cart') }}</span><i class="bi text-lg" :class="added ? 'bi-check-lg' : (adding ? 'bi-arrow-repeat animate-spin' : 'bi-cart-plus')" aria-hidden="true"></i>
                    </button>
                    <button type="button" @click="buyNow()" class="flex h-12 items-center gap-2 rounded-[var(--sf-radius-sm)] bg-brand-600 px-5 text-sm font-semibold text-white sf-focus">
                        <i class="bi bi-lightning-charge-fill" aria-hidden="true"></i> {{ __('Buy now') }}
                    </button>
                </div>
            </template>
            <template x-if="!canBuy">
                <button type="button" @click="notify()" class="flex h-12 items-center gap-2 rounded-[var(--sf-radius-sm)] border-2 border-slate-300 px-4 text-sm font-semibold text-slate-800 sf-focus">
                    <i class="bi bi-bell" aria-hidden="true"></i> {{ __('Notify me') }}
                </button>
            </template>
        </div>
    </div>

    {{-- Lightbox --}}
    @if($images->isNotEmpty())
        <div x-show="lightbox" x-cloak x-transition.opacity @keydown.escape.window="lightbox = false" @keydown.arrow-left.window="lightbox && go(active - 1)" @keydown.arrow-right.window="lightbox && go(active + 1)"
             class="fixed inset-0 z-[var(--sf-z-overlay)] flex flex-col bg-navy-950/95" role="dialog" aria-modal="true" aria-label="{{ __('Product images') }}"
             @touchstart.passive="touchStart($event)" @touchend="touchEnd($event)">
            <div class="flex items-center justify-between px-4 py-3 text-white">
                <p class="text-sm" x-text="(active + 1) + ' / ' + images.length"></p>
                <button type="button" @click="lightbox = false" class="flex size-11 items-center justify-center rounded-full hover:bg-white/10 sf-focus">
                    <span class="sr-only">{{ __('Close') }}</span><i class="bi bi-x-lg text-xl" aria-hidden="true"></i>
                </button>
            </div>
            <div class="relative flex min-h-0 flex-1 items-center justify-center px-4" @click.self="lightbox = false">
                <img :src="images[active]" alt="{{ $name }}" class="max-h-full max-w-full rounded-[var(--sf-radius)] bg-white object-contain p-4">
                @if($images->count() > 1)
                    <button type="button" @click="go(active - 1)" class="absolute left-3 top-1/2 flex size-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 sf-focus">
                        <span class="sr-only">{{ __('Previous image') }}</span><i class="bi bi-chevron-left text-xl" aria-hidden="true"></i>
                    </button>
                    <button type="button" @click="go(active + 1)" class="absolute right-3 top-1/2 flex size-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 sf-focus">
                        <span class="sr-only">{{ __('Next image') }}</span><i class="bi bi-chevron-right text-xl" aria-hidden="true"></i>
                    </button>
                @endif
            </div>
            @if($images->count() > 1)
                <ul class="no-scrollbar flex justify-center gap-2 overflow-x-auto px-4 py-4">
                    @foreach($images as $i => $src)
                        <li class="shrink-0">
                            <button type="button" @click="go({{ $i }})" class="flex size-14 items-center justify-center rounded-[var(--sf-radius-sm)] border-2 bg-white p-1 sf-focus" :class="active === {{ $i }} ? 'border-primary' : 'border-transparent opacity-60 hover:opacity-100'">
                                <img src="{{ $src }}" alt="" class="size-full object-contain" loading="lazy"><span class="sr-only">{{ __('Image :n', ['n' => $i + 1]) }}</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('productPage', (cfg) => ({
            images: cfg.images,
            fallback: cfg.fallback,
            active: 0,
            lightbox: false,
            zoom: false,
            origin: '50% 50%',
            touchX: null,
            selected: { ...cfg.initial },
            qty: 1,
            adding: false,
            added: false,
            inWishlist: cfg.inWishlist,
            liked: cfg.liked,
            likes: cfg.likes,

            init() {
                window.addEventListener('wishlist-updated', (event) => {
                    if (String(event.detail?.productId) === String(cfg.id)) this.inWishlist = event.detail.status === 'added';
                });
                this.$watch('lightbox', (open) => document.body.classList.toggle('sf-locked', open));
            },

            // ── Variants ──
            matches(variant, selection) {
                return cfg.attributes.every((group) => String(variant.attributes[group.id]) === String(selection[group.id]));
            },
            get variant() {
                return cfg.variants.find((v) => this.matches(v, this.selected)) ?? null;
            },
            get current() {
                return cfg.variants.length ? this.variant : this.fallback;
            },
            get unavailable() {
                return cfg.variants.length > 0 && !this.variant;
            },
            get stock() {
                return this.current ? this.current.stock : 0;
            },
            get canBuy() {
                return !this.unavailable && this.stock > 0;
            },
            get maxQty() {
                return Math.max(1, Math.min(this.stock, 99));
            },
            available(groupId, valueId) {
                return cfg.variants.some((v) => v.stock > 0 && this.matches(v, { ...this.selected, [groupId]: valueId }));
            },
            valueName(groupId) {
                const group = cfg.attributes.find((g) => g.id === groupId);
                return group?.values.find((v) => String(v.id) === String(this.selected[groupId]))?.value ?? '';
            },
            select(groupId, valueId) {
                this.selected = { ...this.selected, [groupId]: valueId };
                this.qty = Math.min(this.qty, this.maxQty);
            },

            // ── Gallery ──
            go(index) {
                const count = this.images.length;
                if (count) this.active = (index + count) % count;
            },
            openLightbox() {
                this.zoom = false;
                this.lightbox = true;
            },
            zoomStart() {
                this.zoom = window.matchMedia('(hover: hover) and (min-width: 1024px)').matches;
            },
            zoomMove(event) {
                const rect = event.currentTarget.getBoundingClientRect();
                this.origin = `${((event.clientX - rect.left) / rect.width) * 100}% ${((event.clientY - rect.top) / rect.height) * 100}%`;
            },
            touchStart(event) {
                this.touchX = event.touches[0].clientX;
            },
            touchEnd(event) {
                if (this.touchX === null) return;
                const delta = event.changedTouches[0].clientX - this.touchX;
                if (Math.abs(delta) > 40) this.go(this.active + (delta < 0 ? 1 : -1));
                this.touchX = null;
            },

            // ── Buying ──
            inc() { if (this.qty < this.maxQty) this.qty++; },
            dec() { if (this.qty > 1) this.qty--; },
            async add() {
                if (!this.canBuy || this.adding) return;
                this.adding = true;
                const body = new FormData(this.$refs.form);
                body.set('quantity', this.qty);
                body.set('variant_id', this.variant ? this.variant.id : '');
                try {
                    const response = await fetch(this.$refs.form.action, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body,
                    });
                    const data = await response.json().catch(() => null);
                    if (!response.ok || !data) {
                        Alpine.store('toast').push(data?.message || window.StoreConfig.i18n.addFailed, 'error');
                        return;
                    }
                    Alpine.store('cart').set(data);
                    window.fbTrack?.(data.fb_event);
                    this.added = true;
                    setTimeout(() => (this.added = false), 2000);
                    Alpine.store('toast').push(window.StoreConfig.i18n.addedToCart.replace(':name', cfg.name), 'success', { label: window.StoreConfig.i18n.viewCart, url: window.StoreConfig.routes.cart });
                } catch (e) {
                    this.$refs.form.submit();
                } finally {
                    this.adding = false;
                }
            },
            buyNow() {
                if (!this.canBuy) return;
                const form = this.$refs.form;
                const flag = Object.assign(document.createElement('input'), { type: 'hidden', name: 'buy_now', value: '1' });
                form.appendChild(flag);
                form.submit();
            },
            notify() {
                window.dispatchEvent(new CustomEvent('open-chat', { detail: { message: cfg.i18n.notify } }));
            },

            // ── Engagement ──
            toggleWishlist() {
                Alpine.store('wishlist').toggle(cfg.id);
            },
            async like() {
                if (!cfg.auth) { window.location.href = cfg.routes.login; return; }
                const response = await fetch(cfg.routes.react, { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' } });
                const data = await response.json().catch(() => null);
                if (data?.success) { this.liked = data.liked; this.likes = data.count; }
            },
            async share() {
                const url = window.location.href.split('#')[0];
                if (navigator.share) {
                    try { await navigator.share({ title: cfg.name, url }); } catch (e) {}
                    return;
                }
                await navigator.clipboard?.writeText(url);
                Alpine.store('toast').push(cfg.i18n.copied, 'success');
            },
        }));
    });
</script>
@endpush
