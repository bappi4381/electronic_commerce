{{-- White surface with optional header (title, description, actions slot) and footer slot. --}}
@props([
    'title' => null,
    'description' => null,
    'padding' => true,
])

<section {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900']) }}>
    @if($title || isset($actions))
        <header class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-200 px-6 py-4 dark:border-gray-800">
            <div class="min-w-0">
                @if($title)<h2 class="text-base font-semibold text-gray-900 dark:text-white">{{ $title }}</h2>@endif
                @if($description)<p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>@endif
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div @class(['p-6' => $padding])>
        {{ $slot }}
    </div>

    @isset($footer)
        <footer class="flex flex-wrap items-center justify-end gap-2 rounded-b-xl border-t border-gray-200 bg-gray-50 px-6 py-3 dark:border-gray-800 dark:bg-gray-900/60">
            {{ $footer }}
        </footer>
    @endisset
</section>
