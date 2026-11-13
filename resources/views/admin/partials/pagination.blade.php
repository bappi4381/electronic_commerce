@php
    $item = 'inline-flex h-9 min-w-9 items-center justify-center rounded-lg px-3 text-sm font-medium focus-ring';
    $idle = 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800';
    $muted = 'text-gray-300 cursor-not-allowed dark:text-gray-600';
    $isLength = $paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
@endphp

<nav role="navigation" aria-label="Pagination" class="flex items-center justify-between gap-4">
    <p class="text-sm text-gray-500 dark:text-gray-400">
        @if($paginator->count())
            Showing <span class="font-medium text-gray-900 dark:text-white">{{ $paginator->firstItem() }}</span>–<span class="font-medium text-gray-900 dark:text-white">{{ $paginator->lastItem() }}</span>
            @if($isLength) of <span class="font-medium text-gray-900 dark:text-white">{{ number_format($paginator->total()) }}</span>@endif
        @else
            No results
        @endif
    </p>

    @if($paginator->hasPages())
        <div class="flex items-center gap-1">
            @if($paginator->onFirstPage())
                <span class="{{ $item }} {{ $muted }}" aria-disabled="true"><i class="bi bi-chevron-left" aria-hidden="true"></i><span class="sr-only">Previous</span></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $item }} {{ $idle }}"><i class="bi bi-chevron-left" aria-hidden="true"></i><span class="sr-only">Previous</span></a>
            @endif

            @isset($elements)
                <div class="hidden items-center gap-1 sm:flex">
                    @foreach($elements as $element)
                        @if(is_string($element))
                            <span class="{{ $item }} text-gray-400" aria-hidden="true">…</span>
                        @endif
                        @if(is_array($element))
                            @foreach($element as $page => $url)
                                @if($page == $paginator->currentPage())
                                    <span aria-current="page" class="{{ $item }} bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-600/20 dark:bg-brand-500/10 dark:text-brand-300">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="{{ $item }} {{ $idle }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                </div>
                <span class="px-2 text-sm text-gray-500 sm:hidden dark:text-gray-400">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
            @endisset

            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $item }} {{ $idle }}"><i class="bi bi-chevron-right" aria-hidden="true"></i><span class="sr-only">Next</span></a>
            @else
                <span class="{{ $item }} {{ $muted }}" aria-disabled="true"><i class="bi bi-chevron-right" aria-hidden="true"></i><span class="sr-only">Next</span></span>
            @endif
        </div>
    @endif
</nav>
