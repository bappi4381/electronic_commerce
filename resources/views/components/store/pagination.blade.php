{{-- Storefront pagination: page numbers on tablet and up, Prev / "Page x of y" / Next on phones. --}}
@props(['paginator'])

@if($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $pages = collect([1, $last])->merge(range(max(1, $current - 1), min($last, $current + 1)))->unique()->sort()->values();
        $step = 'flex h-11 items-center justify-center gap-1.5 rounded-full border border-[var(--sf-border)] bg-white px-4 text-sm font-semibold sm:w-11 sm:px-0 sf-focus';
    @endphp

    <nav {{ $attributes->merge(['class' => 'flex items-center justify-between gap-3 sm:justify-center']) }} aria-label="{{ __('Page navigation') }}">
        @if($paginator->onFirstPage())
            <span class="{{ $step }} cursor-not-allowed text-slate-300" aria-disabled="true">
                <i class="bi bi-chevron-left text-xs" aria-hidden="true"></i><span class="sm:sr-only">{{ __('Previous') }}</span>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $step }} text-slate-700 hover:border-brand-300 hover:text-brand-700">
                <i class="bi bi-chevron-left text-xs" aria-hidden="true"></i><span class="sm:sr-only">{{ __('Previous') }}</span>
            </a>
        @endif

        <p class="text-sm text-slate-600 sm:hidden">{{ __('Page :current of :last', ['current' => localized_number($current), 'last' => localized_number($last)]) }}</p>

        <ul class="hidden items-center gap-1 sm:flex">
            @foreach($pages as $i => $page)
                @if($i > 0 && $page - $pages[$i - 1] > 1)
                    <li class="flex size-11 items-center justify-center text-slate-400" aria-hidden="true">…</li>
                @endif
                <li>
                    @if($page === $current)
                        <span aria-current="page" class="flex size-11 items-center justify-center rounded-full bg-navy-900 text-sm font-semibold text-white">{{ localized_number($page) }}</span>
                    @else
                        <a href="{{ $paginator->url($page) }}" class="flex size-11 items-center justify-center rounded-full text-sm font-medium text-slate-700 hover:bg-white hover:text-brand-700 hover:shadow-[var(--sf-shadow-sm)] sf-focus"
                           aria-label="{{ __('Page :page', ['page' => $page]) }}">{{ localized_number($page) }}</a>
                    @endif
                </li>
            @endforeach
        </ul>

        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $step }} text-slate-700 hover:border-brand-300 hover:text-brand-700">
                <span class="sm:sr-only">{{ __('Next') }}</span><i class="bi bi-chevron-right text-xs" aria-hidden="true"></i>
            </a>
        @else
            <span class="{{ $step }} cursor-not-allowed text-slate-300" aria-disabled="true">
                <span class="sm:sr-only">{{ __('Next') }}</span><i class="bi bi-chevron-right text-xs" aria-hidden="true"></i>
            </span>
        @endif
    </nav>
@endif
