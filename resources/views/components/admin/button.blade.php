{{--
    Button or link. Submit buttons show a spinner and lock while the form submits (ui.js).
    variant: primary | secondary | danger | ghost | link     size: sm | md | lg
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'iconRight' => null,
    'loading' => null,
])

@php
    $variants = [
        'primary'   => 'bg-brand-600 text-white shadow-xs hover:bg-brand-700 dark:bg-brand-500 dark:text-gray-950 dark:hover:bg-brand-400',
        'secondary' => 'bg-white text-gray-700 border border-gray-300 shadow-xs hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:border-gray-700 dark:hover:bg-gray-700',
        'danger'    => 'bg-red-600 text-white shadow-xs hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-400',
        'ghost'     => 'text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100',
        'link'      => 'text-brand-600 hover:text-brand-700 hover:underline dark:text-brand-400 px-0! py-0! h-auto!',
    ];
    $sizes = [
        'sm' => 'h-8 px-3 text-xs gap-1.5',
        'md' => 'h-10 px-4 text-sm gap-2',
        'lg' => 'h-12 px-6 text-base gap-2',
    ];
    $classes = 'group/button relative inline-flex items-center justify-center rounded-lg font-medium whitespace-nowrap transition-colors focus-ring '
        . 'disabled:opacity-60 disabled:cursor-not-allowed aria-busy:cursor-wait '
        . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
    $showsLoading = $loading ?? ($type === 'submit' && !$href);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)<i class="bi {{ $icon }}" aria-hidden="true"></i>@endif
        {{ $slot }}
        @if($iconRight)<i class="bi {{ $iconRight }}" aria-hidden="true"></i>@endif
    </a>
@else
    <button type="{{ $type }}" @if($showsLoading) data-loading @endif {{ $attributes->merge(['class' => $classes]) }}>
        @if($showsLoading)
            <span class="absolute inset-0 hidden items-center justify-center group-aria-busy/button:flex" aria-hidden="true">
                <svg class="size-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-25"/><path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
            </span>
        @endif
        <span @class(['inline-flex items-center', 'gap-1.5' => $size === 'sm', 'gap-2' => $size !== 'sm', 'group-aria-busy/button:invisible' => $showsLoading])>
            @if($icon)<i class="bi {{ $icon }}" aria-hidden="true"></i>@endif
            {{ $slot }}
            @if($iconRight)<i class="bi {{ $iconRight }}" aria-hidden="true"></i>@endif
        </span>
    </button>
@endif
