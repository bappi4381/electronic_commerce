@extends('frontend.layout')

@section('title', $query !== '' ? __('Search: :query', ['query' => $query]) : __('Search'))

@section('content')
@php
    $sortLabels = [
        'relevance' => __('Best match'),
        'popularity' => __('Most popular'),
        'price_asc' => __('Price: low to high'),
        'price_desc' => __('Price: high to low'),
        'rating' => __('Top rated'),
        'newest' => __('Newest'),
    ];
    $activeCategory = $filters['category'] ?? null;
    $activeBrand = $filters['brand'] ?? null;
    $hasFilters = collect($filters)->except([])->isNotEmpty();
    $base = array_filter(['q' => $query, 'sort' => $sort !== 'relevance' ? $sort : null]);
    $clearUrl = route('search.index', $base);
    $control = 'h-11 w-full rounded-[var(--sf-radius-sm)] border border-[var(--sf-border)] bg-white px-3 text-sm text-slate-800 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30';
    $categoryName = $activeCategory ? \App\Models\Category::flatten($categories)->firstWhere('id', (int) $activeCategory)?->name : null;
    $countOf = function ($node) use (&$countOf, $categoryCounts) {
        $total = (int) ($categoryCounts[$node->id] ?? 0);
        foreach ($node->children as $child) {
            $total += $countOf($child);
        }
        return $total;
    };
    $brandName = $activeBrand ? $brands->firstWhere('id', $activeBrand)?->name : null;
    $heading = $query !== '' ? __('Results for “:query”', ['query' => $query]) : ($categoryName ?? $brandName ?? __('All products'));
@endphp

<div class="mx-auto max-w-7xl px-4 py-5 md:py-8" x-data="{ filtersOpen: false }">
    <nav aria-label="{{ __('Breadcrumb') }}" class="mb-3 text-xs text-slate-500">
        <ol class="flex flex-wrap items-center gap-1.5">
            <li><a href="{{ route('home') }}" class="hover:text-brand-700">{{ __('Home') }}</a></li>
            <li aria-hidden="true">/</li>
            <li aria-current="page" class="text-slate-700">{{ __('Search') }}</li>
        </ol>
    </nav>

    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <h1 class="font-display text-xl font-bold tracking-tight text-slate-900 md:text-3xl">{{ $heading }}</h1>
            <p class="mt-1 text-sm text-slate-500" role="status">{{ trans_choice(':count product found|:count products found', $total, ['count' => localized_number($total)]) }}</p>
            @if($correction)
                <p class="mt-2 text-sm text-slate-600">
                    {{ __('Did you mean') }}
                    <a href="{{ route('search.index', array_merge($filters, ['q' => $correction])) }}" class="font-semibold text-[var(--sf-accent-strong)] underline-offset-2 hover:underline">{{ $correction }}</a>?
                </p>
            @endif
        </div>
        <div class="flex w-full items-center gap-2 sm:w-auto">
            <button type="button" @click="filtersOpen = true" class="flex h-11 flex-1 items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] border border-[var(--sf-border)] bg-white px-4 text-sm font-semibold text-slate-700 lg:hidden sf-focus">
                <i class="bi bi-sliders" aria-hidden="true"></i> {{ __('Filters') }}
                @if($hasFilters)<span class="rounded-full bg-primary px-1.5 text-[11px] text-white">{{ count($filters) }}</span>@endif
            </button>
            <form method="GET" action="{{ route('search.index') }}" class="flex-1 sm:flex-none">
                @foreach(array_merge(['q' => $query ?: null], $filters) as $key => $value)
                    @if($value !== null && $value !== '')<input type="hidden" name="{{ $key }}" value="{{ $value === true ? 1 : $value }}">@endif
                @endforeach
                <label for="sort" class="sr-only">{{ __('Sort by') }}</label>
                <select id="sort" name="sort" onchange="this.form.submit()" class="{{ $control }} min-w-48 pr-9">
                    @foreach($sortLabels as $value => $label)
                        @continue($value === 'relevance' && $query === '')
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <div class="lg:grid lg:grid-cols-[260px_1fr] lg:gap-8">
        {{-- Filters: sidebar on desktop, drawer on mobile --}}
        <div x-show="filtersOpen" x-cloak x-transition.opacity @click="filtersOpen = false" class="fixed inset-0 z-[var(--sf-z-drawer)] bg-navy-950/50 lg:hidden" aria-hidden="true"></div>
        <aside :style="filtersOpen ? 'translate: 0 0' : ''"
               class="fixed inset-y-0 left-0 z-[var(--sf-z-drawer)] flex w-[85%] max-w-sm flex-col bg-white shadow-2xl transition-[translate] max-lg:-translate-x-full lg:static lg:z-auto lg:w-auto lg:max-w-none lg:bg-transparent lg:shadow-none"
               aria-label="{{ __('Filters') }}" @keydown.escape="filtersOpen = false">
            <div class="flex items-center justify-between border-b border-[var(--sf-border)] px-4 py-3 lg:hidden">
                <p class="font-semibold text-slate-900">{{ __('Filters') }}</p>
                <button type="button" @click="filtersOpen = false" class="flex size-11 items-center justify-center rounded-full text-slate-500 sf-focus"><span class="sr-only">{{ __('Close') }}</span><i class="bi bi-x-lg" aria-hidden="true"></i></button>
            </div>
            <form method="GET" action="{{ route('search.index') }}" class="flex-1 space-y-6 overflow-y-auto p-4 lg:sticky lg:top-24 lg:max-h-[calc(100vh-7rem)] lg:rounded-[var(--sf-radius)] lg:border lg:border-[var(--sf-border)] lg:bg-white lg:p-5">
                @if($query !== '')<input type="hidden" name="q" value="{{ $query }}">@endif
                @if($sort !== 'relevance')<input type="hidden" name="sort" value="{{ $sort }}">@endif

                <fieldset>
                    <legend class="mb-2 text-sm font-semibold text-slate-900">{{ __('Category') }}</legend>
                    <div class="max-h-64 space-y-0.5 overflow-y-auto pr-1">
                        <label class="flex min-h-10 cursor-pointer items-center gap-2.5 rounded px-1 text-sm text-slate-700 hover:bg-slate-50">
                            <input type="radio" name="category" value="" @checked(!$activeCategory) class="size-4 border-slate-300 text-primary focus:ring-primary/40"> {{ __('All categories') }}
                        </label>
                        @include('frontend.partials.search-category', ['nodes' => $categories, 'depth' => 0])
                    </div>
                </fieldset>

                @if($brands->isNotEmpty())
                    <fieldset>
                        <legend class="mb-2 text-sm font-semibold text-slate-900">{{ __('Brand') }}</legend>
                        <div class="max-h-56 space-y-0.5 overflow-y-auto pr-1">
                            <label class="flex min-h-10 cursor-pointer items-center gap-2.5 rounded px-1 text-sm text-slate-700 hover:bg-slate-50">
                                <input type="radio" name="brand" value="" @checked(!$activeBrand) class="size-4 border-slate-300 text-primary focus:ring-primary/40"> {{ __('All brands') }}
                            </label>
                            @foreach($brands as $brand)
                                <label class="flex min-h-10 cursor-pointer items-center gap-2.5 rounded px-1 text-sm text-slate-700 hover:bg-slate-50">
                                    <input type="radio" name="brand" value="{{ $brand->id }}" @checked($activeBrand == $brand->id) class="size-4 border-slate-300 text-primary focus:ring-primary/40">
                                    <span class="flex-1">{{ $brand->name }}</span>
                                    @if(isset($brandCounts[$brand->id]))<span class="text-xs tabular-nums text-slate-400">{{ $brandCounts[$brand->id] }}</span>@endif
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endif

                <fieldset>
                    <legend class="mb-2 text-sm font-semibold text-slate-900">{{ __('Price (৳)') }}</legend>
                    <div class="flex items-center gap-2">
                        <label for="min_price" class="sr-only">{{ __('Minimum price') }}</label>
                        <input id="min_price" type="number" name="min_price" min="0" step="1" inputmode="numeric" value="{{ $filters['min_price'] ?? '' }}" placeholder="{{ __('From') }}" class="{{ $control }}">
                        <span class="text-slate-400" aria-hidden="true">–</span>
                        <label for="max_price" class="sr-only">{{ __('Maximum price') }}</label>
                        <input id="max_price" type="number" name="max_price" min="0" step="1" inputmode="numeric" value="{{ $filters['max_price'] ?? '' }}" placeholder="{{ __('To') }}" class="{{ $control }}">
                    </div>
                </fieldset>

                <label class="flex min-h-11 cursor-pointer items-center justify-between gap-3 text-sm font-medium text-slate-800">
                    {{ __('In stock only') }}
                    <input type="checkbox" name="in_stock" value="1" @checked(!empty($filters['in_stock'])) class="size-5 rounded border-slate-300 text-primary focus:ring-primary/40">
                </label>

                <div class="flex gap-2 border-t border-[var(--sf-border)] pt-4">
                    <button type="submit" class="h-11 flex-1 rounded-full bg-navy-900 text-sm font-semibold text-white hover:bg-navy-800 sf-focus">{{ __('Apply filters') }}</button>
                    @if($hasFilters)
                        <a href="{{ $clearUrl }}" class="flex h-11 items-center rounded-full px-4 text-sm font-semibold text-slate-600 hover:bg-slate-100 sf-focus">{{ __('Clear') }}</a>
                    @endif
                </div>
            </form>
        </aside>

        <div class="min-w-0">
            @if($products->count())
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:gap-4 xl:grid-cols-4">
                    @foreach($products as $product)
                        <x-store.product-card :product="$product" :wishlist-ids="$wishlistIds" :eager="$loop->index < 2" />
                    @endforeach
                </div>
                @if($products->hasPages())
                    <div class="mt-8">{{ $products->onEachSide(1)->links() }}</div>
                @endif
            @else
                <div class="rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white p-6 text-center md:p-10">
                    <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-brand-50 text-2xl text-brand-600" aria-hidden="true"><i class="bi bi-search"></i></span>
                    <h2 class="mt-4 text-lg font-semibold text-slate-900">{{ $query !== '' ? __('No results for “:query”', ['query' => $query]) : __('No products match these filters') }}</h2>
                    <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">{{ __('Check the spelling, try a more general word, or search in English, বাংলা or Banglish.') }}</p>
                    @if($hasFilters)
                        <a href="{{ $clearUrl }}" class="mt-4 inline-flex h-11 items-center rounded-full bg-navy-900 px-6 text-sm font-semibold text-white sf-focus">{{ __('Clear filters') }}</a>
                    @endif
                    @if($trending)
                        <div class="mt-6">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('Trending searches') }}</p>
                            <ul class="flex flex-wrap justify-center gap-2">
                                @foreach($trending as $term)
                                    <li><a href="{{ route('search.index', ['q' => $term]) }}" class="inline-flex min-h-10 items-center rounded-full bg-slate-100 px-3 text-sm text-slate-700 hover:bg-brand-50 hover:text-brand-700 sf-focus">{{ $term }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                @if($popular->isNotEmpty())
                    <section class="mt-8" aria-labelledby="popular-title">
                        <x-store.section-header :title="__('Popular right now')" id="popular-title" />
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:gap-4 xl:grid-cols-4">
                            @foreach($popular as $product)
                                <x-store.product-card :product="$product" :wishlist-ids="$wishlistIds" />
                            @endforeach
                        </div>
                    </section>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
