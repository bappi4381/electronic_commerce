@props([
    'title',
    'description' => null,
    'icon' => 'bi-inbox',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-6 py-12 text-center']) }}>
    <span class="mb-4 flex size-12 items-center justify-center rounded-full bg-gray-100 text-xl text-gray-500 dark:bg-gray-800 dark:text-gray-400" aria-hidden="true">
        <i class="bi {{ $icon }}"></i>
    </span>
    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
    @if($description)
        <p class="mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
    @endif
    @if($slot->isNotEmpty())
        <div class="mt-6 flex flex-wrap justify-center gap-2">{{ $slot }}</div>
    @endif
</div>
