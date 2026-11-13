{{-- Price block: current price bold, original struck through, optional "You save". size: sm | md | lg --}}
@props([
    'price',
    'sale' => null,
    'size' => 'md',
    'save' => false,
])

@php
    $sale = (float) ($sale ?: $price);
    $price = (float) $price;
    $discounted = $sale < $price;
    $sizes = ['sm' => 'text-sm', 'md' => 'text-base sm:text-lg', 'lg' => 'text-2xl'];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-baseline gap-x-1.5 gap-y-0.5 leading-tight']) }}>
    <span class="{{ $sizes[$size] ?? $sizes['md'] }} font-bold tracking-tight text-slate-900 tabular-nums">
        <span class="sr-only">{{ __('Price') }}:</span>{{ money($sale) }}
    </span>
    @if($discounted)
        <span class="text-xs text-slate-500 line-through tabular-nums"><span class="sr-only">{{ __('Original price') }}:</span>{{ money($price) }}</span>
        @if($save)
            <span class="hidden w-full text-xs font-semibold text-[var(--sf-success)] lg:block">{{ __('You save :amount', ['amount' => money($price - $sale)]) }}</span>
        @endif
    @endif
</div>
