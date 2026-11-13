{{--
    Shared product card. Eager-load images, brand, category and variants on the product to avoid N+1.
    variant: grid (default) | rail (narrow horizontal-scroll card, used by Flash Sale)
    sold: units sold, shows a "X sold" progress bar when given together with stock.
--}}
@props([
    'product',
    'wishlistIds' => [],
    'variant' => 'grid',
    'sold' => null,
    'eager' => false,
])

@use('App\Support\Money')
@use('App\Support\Storefront')

@php
    $name = (string) $product->name;
    $price = (float) $product->price;
    $sale = (float) ($product->discounted_price ?: $product->price);
    $discount = Money::discountPercent($price, $sale);
    $isNew = !$discount && $product->created_at?->gt(now()->subDays(7));
    $stock = $product->relationLoaded('variants') ? (int) $product->variants->sum('stock') : null;
    $inStock = $stock === null || $stock > 0;
    $lowStock = $stock !== null && $stock > 0 && $stock <= max(1, (int) ($product->low_stock_threshold ?: 5));
    $image = Storefront::imageUrl($product->relationLoaded('images') ? $product->images->first()?->image : $product->images()->value('image'));
    $icon = $product->relationLoaded('category') ? Storefront::categoryIcon($product->category) : 'bi-box-seam';
    $url = route('products.show', $product->id);
    $inWishlist = in_array($product->id, $wishlistIds ?? [], true);
    $soldPercent = $sold > 0 && $stock !== null ? (int) round($sold / ($sold + $stock) * 100) : null;
    $rail = $variant === 'rail';
@endphp

<article x-data="productCard(@js(['id' => $product->id, 'inWishlist' => $inWishlist, 'name' => $name]))"
         {{ $attributes->merge(['class' => 'group relative flex h-full flex-col rounded-[var(--sf-radius)] border border-[var(--sf-border)] bg-white p-2.5 shadow-[var(--sf-shadow-sm)] transition duration-200 focus-within:border-brand-300 sm:p-3 lg:hover:-translate-y-0.5 lg:hover:shadow-[var(--sf-shadow)]']) }}>
    <div class="relative">
        <x-store.image :src="$image" :alt="$name" :icon="$icon" :eager="$eager"
                       class="aspect-square rounded-[var(--sf-radius-sm)] bg-slate-50"
                       img-class="p-2 mix-blend-multiply transition-transform duration-500 group-hover:scale-105" />

        @if($discount)
            <x-store.badge tone="sale" class="absolute left-2 top-2">-{{ Money::localizeDigits((string) $discount) }}%</x-store.badge>
        @elseif($isNew)
            <x-store.badge tone="new" class="absolute left-2 top-2">{{ __('NEW') }}</x-store.badge>
        @endif

        <button type="button" @click="toggleWishlist()" :aria-pressed="inWishlist.toString()" aria-pressed="{{ $inWishlist ? 'true' : 'false' }}"
                class="absolute right-1 top-1 z-10 flex size-11 items-center justify-center rounded-full sf-focus"
                :title="inWishlist ? @js(__('Remove from wishlist')) : @js(__('Add to wishlist'))">
            <span class="flex size-8 items-center justify-center rounded-full bg-white/90 shadow-sm ring-1 ring-black/5 transition-colors"
                  :class="inWishlist ? 'text-sale' : 'text-slate-500 hover:text-sale'">
                <i class="bi" :class="inWishlist ? 'bi-heart-fill' : 'bi-heart'" aria-hidden="true"></i>
            </span>
            <span class="sr-only">{{ __('Wishlist') }}: {{ $name }}</span>
        </button>

        @if($inStock && !$rail)
            <form method="POST" action="{{ route('cart.add') }}" @submit.prevent="add($el)"
                  class="pointer-events-none absolute inset-x-2 bottom-2 z-10 hidden translate-y-1 opacity-0 transition lg:block lg:group-hover:pointer-events-auto lg:group-hover:translate-y-0 lg:group-hover:opacity-100 lg:group-focus-within:pointer-events-auto lg:group-focus-within:opacity-100">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <button type="submit" :disabled="adding" tabindex="-1" aria-hidden="true"
                        class="flex h-9 w-full items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] bg-navy-900/90 text-xs font-semibold text-white backdrop-blur hover:bg-navy-900">
                    <i class="bi bi-lightning-charge-fill text-primary" aria-hidden="true"></i> {{ __('Quick add') }}
                </button>
            </form>
        @endif
    </div>

    <div class="mt-2.5 flex flex-1 flex-col">
        @if($product->brand)
            <p class="truncate text-[11px] font-semibold uppercase tracking-wider text-[var(--sf-accent-strong)]">{{ $product->brand }}</p>
        @endif

        <h3 @class(['mt-0.5 line-clamp-2 font-medium leading-snug text-slate-800', 'text-[13px] min-h-[2.4rem]' => $rail, 'text-sm min-h-[2.6rem]' => !$rail])>
            <a href="{{ $url }}" class="rounded after:absolute after:inset-0 after:rounded-[var(--sf-radius)] after:content-[''] sf-focus">{{ $name }}</a>
        </h3>

        @if(!$rail)
            <x-store.rating :value="$product->rating_avg" :count="$product->rating_count" class="mt-1" />
        @endif

        @if($soldPercent !== null)
            <div class="mt-2">
                <div class="h-1.5 overflow-hidden rounded-full bg-sale-soft" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $soldPercent }}" aria-label="{{ __(':count sold', ['count' => $sold]) }}">
                    <div class="h-full rounded-full bg-sale" style="width: {{ max(6, $soldPercent) }}%"></div>
                </div>
                <p class="mt-1 text-[11px] font-medium text-slate-500">{{ __(':count sold', ['count' => localized_number($sold)]) }}</p>
            </div>
        @elseif($lowStock)
            <p class="mt-1 text-[11px] font-semibold text-[var(--sf-warning)]">{{ __('Only :count left', ['count' => localized_number($stock)]) }}</p>
        @endif

        <div class="mt-auto flex items-end justify-between gap-2 pt-2">
            <x-store.price :price="$price" :sale="$sale" :size="$rail ? 'sm' : 'md'" class="min-w-0" />

            @if($inStock)
                <form method="POST" action="{{ route('cart.add') }}" @submit.prevent="add($el)" class="relative z-10 shrink-0">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <button type="submit" :disabled="adding"
                            class="flex h-11 min-w-11 items-center justify-center gap-1.5 rounded-[var(--sf-radius-sm)] bg-brand-50 px-3 text-sm font-semibold text-brand-700 transition-colors hover:bg-primary hover:text-white disabled:opacity-60 sf-focus"
                            :class="added && '!bg-[var(--sf-success)] !text-white'"
                            aria-label="{{ __('Add :name to cart', ['name' => $name]) }}">
                        <i class="bi" :class="added ? 'bi-check-lg' : (adding ? 'bi-arrow-repeat animate-spin' : 'bi-cart-plus')" aria-hidden="true"></i>
                        @unless($rail)<span class="hidden xl:inline" x-text="added ? @js(__('Added')) : @js(__('Add'))">{{ __('Add') }}</span>@endunless
                    </button>
                </form>
            @endif
        </div>

        @if($discount && !$rail)
            <p class="mt-1 hidden text-xs font-semibold text-[var(--sf-success)] lg:block">{{ __('You save :amount', ['amount' => money($price - $sale)]) }}</p>
        @endif

        @unless($inStock)
            <div class="relative z-10 mt-2 flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-slate-500">{{ __('Out of stock') }}</span>
                <button type="button" @click="notify()" class="h-9 rounded-[var(--sf-radius-sm)] border border-[var(--sf-border)] px-3 text-xs font-semibold text-slate-700 hover:border-brand-300 hover:text-brand-700 sf-focus">
                    <i class="bi bi-bell" aria-hidden="true"></i> {{ __('Notify me') }}
                </button>
            </div>
        @endunless
    </div>
</article>
