{{--
    Dialog opened with $dispatch('open-modal', 'name') and closed with $dispatch('close-modal', 'name'),
    Escape, the close button or a backdrop click. Focus is trapped while open and restored on close.
    Use :show="true" to open on load (e.g. after a validation error).
--}}
@props([
    'name',
    'title' => null,
    'description' => null,
    'maxWidth' => 'lg',
    'show' => false,
])

@php
    $width = ['sm' => 'sm:max-w-sm', 'md' => 'sm:max-w-md', 'lg' => 'sm:max-w-lg', 'xl' => 'sm:max-w-xl', '2xl' => 'sm:max-w-2xl', '4xl' => 'sm:max-w-4xl'][$maxWidth] ?? 'sm:max-w-lg';
    $titleId = 'modal-' . \Illuminate\Support\Str::slug($name) . '-title';
@endphp

<div x-data="modal(@js($name), @js((bool) $show))"
     x-on:open-modal.window="if (matches($event.detail)) open = true"
     x-on:close-modal.window="if (matches($event.detail)) open = false"
     x-on:keydown.escape.window="open = false"
     x-show="open" x-cloak
     class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" @if($title) aria-labelledby="{{ $titleId }}" @endif>
    <div x-show="open" x-transition.opacity class="fixed inset-0 bg-gray-950/50 backdrop-blur-[2px]" @click="open = false" aria-hidden="true"></div>

    <div class="flex min-h-full items-end justify-center p-4 sm:items-center">
        <div x-ref="panel" x-show="open" @keydown.tab="trap($event)"
             x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             {{ $attributes->merge(['class' => "relative w-full {$width} rounded-xl bg-white shadow-xl ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800"]) }}>
            @if($title)
                <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-6 py-4 dark:border-gray-800">
                    <div>
                        <h2 id="{{ $titleId }}" class="text-base font-semibold text-gray-900 dark:text-white">{{ $title }}</h2>
                        @if($description)<p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>@endif
                    </div>
                    <button type="button" @click="open = false" class="-m-1 rounded-lg p-1 text-gray-400 hover:text-gray-600 focus-ring dark:hover:text-gray-200">
                        <span class="sr-only">Close</span><i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
            @endif

            <div class="px-6 py-5">{{ $slot }}</div>

            @isset($footer)
                <div class="flex flex-col-reverse gap-2 rounded-b-xl border-t border-gray-200 bg-gray-50 px-6 py-3 sm:flex-row sm:justify-end dark:border-gray-800 dark:bg-gray-900/60">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
