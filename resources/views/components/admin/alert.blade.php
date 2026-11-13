{{-- Inline message. tone: info | success | warning | error --}}
@props([
    'tone' => 'info',
    'title' => null,
    'icon' => null,
])

@php
    $styles = [
        'info'    => ['bg-brand-50 text-brand-800 ring-brand-600/20 dark:bg-brand-500/10 dark:text-brand-200 dark:ring-brand-400/20', 'bi-info-circle-fill', 'text-brand-600 dark:text-brand-400'],
        'success' => ['bg-green-50 text-green-800 ring-green-600/20 dark:bg-green-500/10 dark:text-green-200 dark:ring-green-400/20', 'bi-check-circle-fill', 'text-green-600 dark:text-green-400'],
        'warning' => ['bg-yellow-50 text-yellow-800 ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-200 dark:ring-yellow-400/20', 'bi-exclamation-triangle-fill', 'text-yellow-500'],
        'error'   => ['bg-red-50 text-red-800 ring-red-600/20 dark:bg-red-500/10 dark:text-red-200 dark:ring-red-400/20', 'bi-x-circle-fill', 'text-red-600 dark:text-red-400'],
    ];
    [$box, $defaultIcon, $iconColor] = $styles[$tone] ?? $styles['info'];
@endphp

<div role="{{ $tone === 'error' ? 'alert' : 'status' }}" {{ $attributes->merge(['class' => "flex gap-3 rounded-lg p-4 text-sm ring-1 ring-inset {$box}"]) }}>
    <i class="bi {{ $icon ?? $defaultIcon }} {{ $iconColor }} mt-0.5" aria-hidden="true"></i>
    <div class="min-w-0 flex-1">
        @if($title)<p class="font-medium">{{ $title }}</p>@endif
        <div @class(['mt-1' => $title])>{{ $slot }}</div>
    </div>
</div>
