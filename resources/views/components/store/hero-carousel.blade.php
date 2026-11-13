{{--
    Hero slider: swipe, arrows, dots, autoplay that pauses on hover/focus and is off under
    prefers-reduced-motion. Banners: title, subtitle, link, image.
--}}
@props(['banners'])

@use('App\Support\Storefront')

@php
    $slides = collect($banners)->map(fn ($b) => [
        'image' => Storefront::imageUrl($b->image),
        'title' => $b->title,
        'subtitle' => $b->subtitle,
        'link' => $b->link,
    ])->values();

    if ($slides->isEmpty()) {
        $slides = collect([[
            'image' => null,
            'title' => __('Gadgets, appliances & more, delivered across Bangladesh'),
            'subtitle' => __('Cash on delivery, bKash & Nagad. Genuine products with warranty.'),
            'link' => route('products.index'),
        ]]);
    }
@endphp

<section x-data="carousel({ count: {{ $slides->count() }}, interval: 6000 })"
         @mouseenter="pause()" @mouseleave="resume()" @focusin="pause()" @focusout="resume()"
         @touchstart.passive="touchStart($event)" @touchend="touchEnd($event)"
         {{ $attributes->merge(['class' => 'group relative overflow-hidden rounded-[var(--sf-radius-lg)] bg-navy-900']) }}
         aria-roledescription="carousel" aria-label="{{ __('Featured offers') }}">
    <div class="flex h-full transition-transform duration-500 ease-out motion-reduce:transition-none" :style="`transform: translateX(-${index * 100}%)`">
        @foreach($slides as $i => $slide)
            <div class="relative h-full w-full shrink-0" role="group" aria-roledescription="slide"
                 aria-label="{{ __(':current of :total', ['current' => $i + 1, 'total' => $slides->count()]) }}"
                 :aria-hidden="(index !== {{ $i }}).toString()" @if($i > 0) aria-hidden="true" @endif>
                @if($slide['image'])
                    <x-store.image :src="$slide['image']" :alt="$slide['title'] ?: __('Promotion')" fit="cover" icon="bi-image"
                                   :width="1280" :height="560" :eager="$i === 0" class="absolute inset-0 bg-navy-800" />
                @else
                    <div class="absolute inset-0 bg-[radial-gradient(120%_120%_at_85%_10%,#1e9fd4_0%,#0f3b63_42%,#0b1220_75%)]" aria-hidden="true">
                        <i class="bi bi-lightning-charge-fill absolute -right-6 top-1/2 -translate-y-1/2 text-[14rem] text-white/5"></i>
                    </div>
                @endif

                @if($slide['title'] || $slide['subtitle'])
                    <div class="absolute inset-0 bg-gradient-to-r from-navy-950/85 via-navy-950/45 to-transparent" aria-hidden="true"></div>
                    <div class="relative flex h-full max-w-xl flex-col justify-center gap-2 px-5 py-6 sm:gap-3 sm:px-10">
                        @if($slide['title'])
                            <p class="font-display text-xl font-bold leading-tight text-white sm:text-4xl">{{ $slide['title'] }}</p>
                        @endif
                        @if($slide['subtitle'])
                            <p class="line-clamp-2 text-xs text-white/80 sm:text-base">{{ $slide['subtitle'] }}</p>
                        @endif
                        @if($slide['link'])
                            <a href="{{ $slide['link'] }}" @if($i > 0) tabindex="-1" @endif :tabindex="index === {{ $i }} ? 0 : -1"
                               class="mt-1 inline-flex h-10 w-fit items-center gap-2 rounded-full bg-primary px-5 text-sm font-semibold text-white shadow-lg shadow-primary/30 hover:bg-primary-dark sm:mt-2 sm:h-12 sm:px-7 sf-focus">
                                {{ __('Shop now') }} <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </a>
                        @endif
                    </div>
                @elseif($slide['link'])
                    <a href="{{ $slide['link'] }}" class="absolute inset-0 sf-focus" @if($i > 0) tabindex="-1" @endif :tabindex="index === {{ $i }} ? 0 : -1"><span class="sr-only">{{ __('Shop now') }}</span></a>
                @endif
            </div>
        @endforeach
    </div>

    @if($slides->count() > 1)
        <button type="button" @click="prev()" class="absolute left-3 top-1/2 hidden size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-white opacity-0 backdrop-blur transition hover:bg-white/30 focus-visible:opacity-100 group-hover:opacity-100 md:flex sf-focus">
            <span class="sr-only">{{ __('Previous slide') }}</span><i class="bi bi-chevron-left" aria-hidden="true"></i>
        </button>
        <button type="button" @click="next()" class="absolute right-3 top-1/2 hidden size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-white opacity-0 backdrop-blur transition hover:bg-white/30 focus-visible:opacity-100 group-hover:opacity-100 md:flex sf-focus">
            <span class="sr-only">{{ __('Next slide') }}</span><i class="bi bi-chevron-right" aria-hidden="true"></i>
        </button>
        <div class="absolute inset-x-0 bottom-2 flex justify-center gap-1 sm:bottom-4">
            @foreach($slides as $i => $slide)
                <button type="button" @click="go({{ $i }})" class="flex size-6 items-center justify-center sf-focus rounded-full"
                        :aria-current="(index === {{ $i }}).toString()" aria-label="{{ __('Go to slide :n', ['n' => $i + 1]) }}">
                    <span class="h-1.5 rounded-full transition-all" :class="index === {{ $i }} ? 'w-6 bg-white' : 'w-1.5 bg-white/50'"></span>
                </button>
            @endforeach
        </div>
    @endif
</section>
