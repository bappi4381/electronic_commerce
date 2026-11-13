{{-- Page title row. :breadcrumbs is label => url (null url for the current page); actions go in the slot. --}}
@props([
    'title',
    'description' => null,
    'breadcrumbs' => [],
])

<div {{ $attributes->merge(['class' => 'mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="min-w-0">
        @if($breadcrumbs)
            <nav aria-label="Breadcrumb" class="mb-2">
                <ol class="flex flex-wrap items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                    @foreach($breadcrumbs as $crumb => $url)
                        <li class="flex items-center gap-1.5">
                            @if(!$loop->first)<i class="bi bi-chevron-right text-[10px] text-gray-400" aria-hidden="true"></i>@endif
                            @if($url)
                                <a href="{{ $url }}" class="rounded hover:text-gray-900 focus-ring dark:hover:text-gray-100">{{ $crumb }}</a>
                            @else
                                <span aria-current="page" class="text-gray-700 dark:text-gray-300">{{ $crumb }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif
        <h1 class="truncate text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ $title }}</h1>
        @if($description)
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
        @endif
    </div>
    @if($slot->isNotEmpty())
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $slot }}</div>
    @endif
</div>
