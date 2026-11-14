{{--
    Flash sale block shared by the homepage (layout="rail") and the Deals page (layout="grid").
    campaign: FlashSale::campaign() result; products: FlashSale::products(). Renders nothing without a running campaign
    and removes itself when the countdown reaches zero.
--}}
@props(['campaign' => null, 'products', 'wishlistIds' => [], 'layout' => 'rail', 'viewAll' => false])

@if($campaign && $products->isNotEmpty())
    <section x-data="{ ended: false }" x-show="!ended" @countdown-ended="ended = true" data-section="flash-sale"
             aria-labelledby="flash-title" {{ $attributes }}>
        <div class="overflow-hidden rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white shadow-[var(--sf-shadow-sm)]">
            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 bg-gradient-to-r from-sale to-[#f97316] px-4 py-3 text-white sm:px-6">
                <div class="flex min-w-0 items-center gap-2">
                    <i class="bi bi-lightning-charge-fill text-xl text-yellow-300" aria-hidden="true"></i>
                    <h2 id="flash-title" class="truncate font-display text-lg font-bold sm:text-2xl">{{ $campaign['title'] }}</h2>
                </div>
                <div class="flex items-center gap-3">
                    @if($campaign['ends_at'])
                        <span class="text-xs text-white/85 sm:text-sm">{{ __('Ends in') }}</span>
                        <x-store.countdown :end="$campaign['ends_at']" tone="dark" />
                    @endif
                    @if($viewAll)
                        <a href="{{ route('flash-deals') }}" class="hidden min-h-11 items-center gap-1 rounded text-sm font-semibold text-white hover:underline sm:flex sf-focus">{{ __('View all flash deals') }} <i class="bi bi-chevron-right text-xs" aria-hidden="true"></i></a>
                    @endif
                </div>
            </div>

            @if($layout === 'grid')
                <ul class="grid grid-cols-2 gap-3 p-3 sm:grid-cols-3 sm:p-4 md:gap-4 lg:grid-cols-4 xl:grid-cols-6">
                    @foreach($products as $product)
                        <li><x-store.product-card :product="$product" :wishlist-ids="$wishlistIds" :sold="(int) $product->sold_quantity" /></li>
                    @endforeach
                </ul>
            @else
                <div x-data="scrollRow" class="relative p-3 sm:p-4">
                    <ul x-ref="track" @scroll.debounce.60ms="update()" class="no-scrollbar sf-snap-row flex gap-3 overflow-x-auto pb-1">
                        @foreach($products as $product)
                            <li class="w-[150px] shrink-0 sm:w-[190px] lg:w-[calc((100%-3rem)/5)]">
                                <x-store.product-card :product="$product" :wishlist-ids="$wishlistIds" variant="rail" :sold="(int) $product->sold_quantity" />
                            </li>
                        @endforeach
                    </ul>
                    <button type="button" @click="scroll(-1)" x-show="!atStart" x-cloak class="absolute left-1 top-1/2 hidden size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white text-slate-700 shadow-[var(--sf-shadow)] ring-1 ring-black/5 hover:text-brand-700 lg:flex sf-focus">
                        <span class="sr-only">{{ __('Previous') }}</span><i class="bi bi-chevron-left" aria-hidden="true"></i>
                    </button>
                    <button type="button" @click="scroll(1)" x-show="!atEnd" class="absolute right-1 top-1/2 hidden size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white text-slate-700 shadow-[var(--sf-shadow)] ring-1 ring-black/5 hover:text-brand-700 lg:flex sf-focus">
                        <span class="sr-only">{{ __('Next') }}</span><i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </button>
                    @if($viewAll)
                        <a href="{{ route('flash-deals') }}" class="mt-2 flex min-h-11 items-center justify-center gap-1 rounded-[var(--sf-radius-sm)] bg-sale-soft text-sm font-semibold text-sale sm:hidden sf-focus">{{ __('View all flash deals') }} <i class="bi bi-chevron-right text-xs" aria-hidden="true"></i></a>
                    @endif
                </div>
            @endif
        </div>
    </section>
@endif
