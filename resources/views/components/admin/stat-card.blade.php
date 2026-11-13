{{-- KPI tile. trend is a percentage (null = no comparison available); positive is good unless :invert. --}}
@props([
    'label',
    'value',
    'icon' => null,
    'trend' => null,
    'trendLabel' => null,
    'hint' => null,
    'href' => null,
    'invert' => false,
])

@php
    $good = $trend !== null && ($invert ? $trend <= 0 : $trend >= 0);
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'block rounded-xl border border-gray-200 bg-white p-6 shadow-xs dark:border-gray-800 dark:bg-gray-900' . ($href ? ' transition-colors hover:border-gray-300 focus-ring dark:hover:border-gray-700' : '')]) }}>
    <div class="flex items-start justify-between gap-4">
        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
        @if($icon)
            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400" aria-hidden="true">
                <i class="bi {{ $icon }}"></i>
            </span>
        @endif
    </div>
    <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-900 tabular-nums dark:text-white">{{ $value }}</p>
    @if($trend !== null || $trendLabel || $hint)
        <p class="mt-2 flex flex-wrap items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
            @if($trend !== null)
                <span @class([
                    'inline-flex items-center gap-0.5 rounded-md px-1.5 py-0.5 font-medium',
                    'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400' => $good,
                    'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400' => !$good,
                ])>
                    <i class="bi {{ $trend >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}" aria-hidden="true"></i>
                    <span class="sr-only">{{ $trend >= 0 ? 'Up' : 'Down' }}</span>{{ abs($trend) }}%
                </span>
            @endif
            {{ $trendLabel ?? $hint }}
        </p>
    @endif
</{{ $tag }}>
