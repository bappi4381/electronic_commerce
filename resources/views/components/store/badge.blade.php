{{-- Small pill. tone: sale | new | info | dark --}}
@props(['tone' => 'sale'])

@php
    $tones = [
        'sale' => 'bg-sale text-white',
        'new' => 'bg-navy-900 text-white',
        'info' => 'bg-brand-50 text-brand-700',
        'dark' => 'bg-white/15 text-white backdrop-blur',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[11px] font-bold leading-4 tabular-nums ' . ($tones[$tone] ?? $tones['sale'])]) }}>{{ $slot }}</span>
