@extends('frontend.layout')

@use('App\Support\Storefront')

@php
    $search = $filters['search'] ?? null;
    $heading = $search !== null
        ? __('Results for “:query”', ['query' => $search])
        : ($activeCategory?->name ?? $activeBrand?->name ?? __('All products'));

    $sortParam = $sort !== 'newest' ? $sort : null;
    $catalogUrl = fn (array $overrides = []) => route('products.index', array_filter(
        array_merge($filters, ['sort' => $sortParam], $overrides),
        fn ($value) => $value !== null && $value !== '' && $value !== false
    ));
    $clearUrl = route('products.index', array_filter(['sort' => $sortParam]));

    $hasPrice = isset($filters['min_price']) || isset($filters['max_price']);
    $priceLabel = match (true) {
        isset($filters['min_price'], $filters['max_price']) => money($filters['min_price']) . ' – ' . money($filters['max_price']),
        isset($filters['min_price']) => __('From :amount', ['amount' => money($filters['min_price'])]),
        isset($filters['max_price']) => __('Up to :amount', ['amount' => money($filters['max_price'])]),
        default => null,
    };

    $activeChips = array_values(array_filter([
        $search !== null ? ['label' => '“' . $search . '”', 'url' => $catalogUrl(['search' => null])] : null,
        $activeCategory ? ['label' => $activeCategory->name, 'url' => $catalogUrl(['category' => null])] : null,
        $activeBrand ? ['label' => $activeBrand->name, 'url' => $catalogUrl(['brand' => null])] : null,
        $hasPrice ? ['label' => $priceLabel, 'url' => $catalogUrl(['min_price' => null, 'max_price' => null])] : null,
        !empty($filters['in_stock']) ? ['label' => __('In stock'), 'url' => $catalogUrl(['in_stock' => null])] : null,
        !empty($filters['on_sale']) ? ['label' => __('On sale'), 'url' => $catalogUrl(['on_sale' => null])] : null,
    ]));

    // Chips follow the current branch: its children when it has some, otherwise its siblings.
    $activeNode = $activeCategory ? \App\Models\Category::findNested($departments, $activeCategory->id) : null;
    $chipRoot = $activeNode && $activeNode->children->contains(fn ($child) => $child->total_count > 0)
        ? $activeNode
        : ($activeNode?->parent_id ? \App\Models\Category::findNested($departments, (int) $activeNode->parent_id) : null);
    $subcategoryChips = $chipRoot ? collect([['id' => $chipRoot->id, 'name' => __('All :category', ['category' => $chipRoot->name]), 'count' => $chipRoot->total_count]])
        ->concat($chipRoot->children->where('total_count', '>', 0)->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'count' => $c->total_count]))
        ->all() : [];

    $priceRanges = [
        [null, 1000, __('Under :amount', ['amount' => money(1000)])],
        [1000, 5000, money(1000) . ' – ' . money(5000)],
        [5000, 20000, money(5000) . ' – ' . money(20000)],
        [20000, 50000, money(20000) . ' – ' . money(50000)],
        [50000, null, __('Over :amount', ['amount' => money(50000)])],
    ];

    $control = 'h-11 w-full rounded-[var(--sf-radius-sm)] border border-[var(--sf-border)] bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30';
    $option = 'flex min-h-10 cursor-pointer items-center gap-2.5 rounded-[var(--sf-radius-sm)] px-2 text-sm text-slate-700 hover:bg-slate-50 has-[:checked]:font-semibold has-[:checked]:text-brand-700';
    $summary = 'flex min-h-11 cursor-pointer list-none items-center justify-between gap-2 text-sm font-semibold text-slate-900 sf-focus [&::-webkit-details-marker]:hidden';
    $chevron = 'bi bi-chevron-down text-xs text-slate-400 transition-transform group-open:rotate-180';
@endphp

@section('title', $heading)

@section('content')
<div class="mx-auto max-w-7xl px-4 pb-10 pt-4 md:pb-16 md:pt-6"
     x-data="{ filtersOpen: false, desktop: window.matchMedia('(min-width: 1024px)').matches }"
     x-init="window.matchMedia('(min-width: 1024px)').addEventListener('change', e => { desktop = e.matches; if (desktop) filtersOpen = false })"
     x-effect="document.body.classList.toggle('sf-locked', filtersOpen)"
     @keydown.escape.window="filtersOpen = false">

    {{-- Breadcrumb + heading --}}
    <nav aria-label="{{ __('Breadcrumb') }}" class="text-xs text-slate-500">
        <ol class="flex flex-wrap items-center gap-1.5">
            <li><a href="{{ route('home') }}" class="rounded hover:text-brand-700 sf-focus">{{ __('Home') }}</a></li>
            <li aria-hidden="true"><i class="bi bi-chevron-right text-[10px]"></i></li>
            @if($activeCategory)
                <li><a href="{{ route('products.index') }}" class="rounded hover:text-brand-700 sf-focus">{{ __('Shop') }}</a></li>
                @foreach($activeCategory->lineage() as $crumb)
                    <li aria-hidden="true"><i class="bi bi-chevron-right text-[10px]"></i></li>
                    @if($loop->last)
                        <li aria-current="page" class="font-medium text-slate-700">{{ $crumb->name }}</li>
                    @else
                        <li><a href="{{ route('products.index', ['category' => $crumb->id]) }}" class="rounded hover:text-brand-700 sf-focus">{{ $crumb->name }}</a></li>
                    @endif
                @endforeach
            @else
                <li aria-current="page" class="font-medium text-slate-700">{{ __('Shop') }}</li>
            @endif
        </ol>
    </nav>

    <header class="mt-3 flex items-center gap-3 md:mt-4 md:gap-4">
        @if($activeCategory)
            <span class="flex size-11 shrink-0 items-center justify-center rounded-[var(--sf-radius)] bg-brand-50 text-xl text-brand-600 md:size-14 md:text-2xl" aria-hidden="true">
                <i class="bi {{ Storefront::categoryIcon($activeCategory) }}"></i>
            </span>
        @endif
        <div class="min-w-0">
            <h1 class="truncate font-display text-xl font-bold tracking-tight text-slate-900 md:text-3xl">{{ $heading }}</h1>
            <p class="mt-0.5 text-sm text-slate-500" role="status">
                {{ trans_choice(':count product|:count products', $products->total(), ['count' => localized_number($products->total())]) }}
            </p>
        </div>
    </header>

    {{-- Quick category navigation --}}
    @if($subcategoryChips || (!$activeCategory && $departments->isNotEmpty()))
        <nav aria-label="{{ __('Categories') }}" @class(['mt-4', 'lg:hidden' => !$activeCategory])>
            <ul class="no-scrollbar sf-snap-row -mx-4 flex gap-2 overflow-x-auto px-4 pb-1 lg:mx-0 lg:flex-wrap lg:px-0">
                @if($activeCategory)
                    @foreach($subcategoryChips as $chip)
                        @php($current = $activeCategory->id === $chip['id'])
                        <li class="shrink-0">
                            <a href="{{ $catalogUrl(['category' => $chip['id'], 'brand' => null]) }}" @if($current) aria-current="page" @endif
                               @class(['flex min-h-10 items-center gap-1.5 rounded-full border px-4 text-sm font-medium transition-colors sf-focus',
                                       'border-navy-900 bg-navy-900 text-white' => $current,
                                       'border-[var(--sf-border)] bg-white text-slate-700 hover:border-slate-300' => !$current])>
                                {{ $chip['name'] }}
                                <span @class(['text-xs tabular-nums', 'text-white/70' => $current, 'text-slate-400' => !$current])>{{ localized_number($chip['count']) }}</span>
                            </a>
                        </li>
                    @endforeach
                @else
                    @foreach($departments as $department)
                        <li class="shrink-0">
                            <a href="{{ $catalogUrl(['category' => $department->id, 'brand' => null]) }}"
                               class="flex min-h-11 items-center gap-2 rounded-full border border-[var(--sf-border)] bg-white py-1.5 pl-1.5 pr-4 text-sm font-medium text-slate-700 shadow-[var(--sf-shadow-sm)] active:bg-slate-50 sf-focus">
                                <span class="flex size-8 items-center justify-center rounded-full bg-brand-50 text-brand-600" aria-hidden="true"><i class="bi {{ Storefront::categoryIcon($department) }}"></i></span>
                                {{ $department->name }}
                            </a>
                        </li>
                    @endforeach
                @endif
            </ul>
        </nav>
    @endif

    <div class="mt-4 md:mt-6 lg:grid lg:grid-cols-[280px_minmax(0,1fr)] lg:items-start lg:gap-8">
        {{-- Filters: sticky sidebar on desktop, bottom sheet on mobile --}}
        <div x-show="filtersOpen" x-cloak x-transition.opacity @click="filtersOpen = false"
             class="fixed inset-0 z-[var(--sf-z-drawer)] bg-navy-950/50 backdrop-blur-[2px] lg:hidden" aria-hidden="true"></div>

        <aside id="catalog-filters" aria-labelledby="filters-title"
               :inert="!desktop && !filtersOpen"
               :style="filtersOpen ? 'translate: 0 0' : ''"
               class="fixed inset-x-0 bottom-0 z-[var(--sf-z-drawer)] flex max-h-[88dvh] flex-col rounded-t-[20px] bg-white shadow-[var(--sf-shadow-lg)] transition-[translate] duration-300 ease-out max-lg:translate-y-full lg:sticky lg:inset-x-auto lg:bottom-auto lg:top-24 lg:z-auto lg:max-h-[calc(100vh-7rem)] lg:rounded-[var(--sf-radius)] lg:border lg:border-[var(--sf-border)] lg:shadow-[var(--sf-shadow-sm)]">
            <div class="mx-auto mt-2 h-1 w-10 shrink-0 rounded-full bg-slate-200 lg:hidden" aria-hidden="true"></div>
            <div class="flex shrink-0 items-center justify-between gap-3 border-b border-[var(--sf-border)] px-4 py-2 lg:px-5 lg:py-3">
                <h2 id="filters-title" class="flex items-center gap-2 font-display text-base font-bold text-slate-900">
                    <i class="bi bi-sliders2 text-brand-600" aria-hidden="true"></i> {{ __('Filters') }}
                </h2>
                <div class="flex items-center gap-1">
                    @if($activeChips)
                        <a href="{{ $clearUrl }}" class="hidden min-h-9 items-center rounded px-1 text-sm font-semibold text-[var(--sf-accent-strong)] hover:underline lg:flex sf-focus">{{ __('Clear all') }}</a>
                    @endif
                    <button type="button" @click="filtersOpen = false" class="flex size-11 items-center justify-center rounded-full text-slate-500 hover:bg-slate-100 lg:hidden sf-focus">
                        <span class="sr-only">{{ __('Close') }}</span><i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <form method="GET" action="{{ route('products.index') }}" class="flex min-h-0 flex-1 flex-col"
                  @change="if (desktop && $event.target.name && $event.target.type !== 'number') $el.requestSubmit()"
                  @formdata="[...$event.formData].forEach(([key, value]) => value === '' && $event.formData.delete(key))">
                @if($search !== null)<input type="hidden" name="search" value="{{ $search }}">@endif
                @if($sortParam)<input type="hidden" name="sort" value="{{ $sortParam }}">@endif

                <div class="scrollbar-thin min-h-0 flex-1 divide-y divide-[var(--sf-border)] overflow-y-auto overscroll-contain px-4 lg:px-5">
                    {{-- Category --}}
                    <details class="group py-3" open>
                        <summary class="{{ $summary }}">{{ __('Category') }} <i class="{{ $chevron }}" aria-hidden="true"></i></summary>
                        <div class="mt-1 space-y-0.5">
                            <label class="{{ $option }}">
                                <input type="radio" name="category" value="" @checked(!$activeCategory) class="size-4 shrink-0 accent-brand-600">
                                <span class="flex-1">{{ __('All categories') }}</span>
                            </label>
                            @include('frontend.partials.catalog-category', ['nodes' => $departments, 'depth' => 0])
                        </div>
                    </details>

                    {{-- Brand --}}
                    @if($brands->isNotEmpty() || $activeBrand)
                        @php($collapsedAfter = 8)
                        <details class="group py-3" open>
                            <summary class="{{ $summary }}">{{ __('Brand') }} <i class="{{ $chevron }}" aria-hidden="true"></i></summary>
                            <div class="mt-1" x-data="{ q: '', all: false }">
                                @if($brands->count() > $collapsedAfter)
                                    <div class="relative mb-2">
                                        <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" aria-hidden="true"></i>
                                        <label for="brand-search" class="sr-only">{{ __('Search brands') }}</label>
                                        <input id="brand-search" type="search" x-model="q" autocomplete="off" placeholder="{{ __('Search brands') }}" class="{{ $control }} h-10 pl-8">
                                    </div>
                                @endif
                                <div class="space-y-0.5">
                                    <label class="{{ $option }}" x-show="!q">
                                        <input type="radio" name="brand" value="" @checked(!$activeBrand) class="size-4 shrink-0 accent-brand-600">
                                        <span class="flex-1">{{ __('All brands') }}</span>
                                    </label>
                                    @foreach($brands as $brand)
                                        @php($pinned = $loop->index < $collapsedAfter || $activeBrand?->id === $brand->id)
                                        <label class="{{ $option }}"
                                               x-show="@js(mb_strtolower($brand->name)).includes(q.trim().toLowerCase()) && (q || all || @js($pinned))"
                                               @unless($pinned) style="display: none" @endunless>
                                            <input type="radio" name="brand" value="{{ $brand->slug }}" @checked($activeBrand?->id === $brand->id) class="size-4 shrink-0 accent-brand-600">
                                            <span class="flex-1 truncate">{{ $brand->name }}</span>
                                            <span class="text-xs font-normal tabular-nums text-slate-400">{{ localized_number($brand->products_count) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @if($brands->count() > $collapsedAfter)
                                    <button type="button" x-show="!q" @click="all = !all" class="mt-1 flex min-h-10 items-center gap-1 rounded px-2 text-sm font-semibold text-[var(--sf-accent-strong)] hover:underline sf-focus">
                                        <span x-text="all ? @js(__('Show fewer')) : @js(__('Show all :count brands', ['count' => localized_number($brands->count())]))">{{ __('Show all :count brands', ['count' => localized_number($brands->count())]) }}</span>
                                        <i class="bi text-xs" :class="all ? 'bi-chevron-up' : 'bi-chevron-down'" aria-hidden="true"></i>
                                    </button>
                                @endif
                            </div>
                        </details>
                    @endif

                    {{-- Price --}}
                    <details class="group py-3" open>
                        <summary class="{{ $summary }}">{{ __('Price') }} <i class="{{ $chevron }}" aria-hidden="true"></i></summary>
                        <div class="mt-1" x-data>
                            <div class="flex items-center gap-2">
                                <div class="relative flex-1">
                                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400" aria-hidden="true">৳</span>
                                    <label for="min_price" class="sr-only">{{ __('Minimum price') }}</label>
                                    <input id="min_price" x-ref="min" type="number" name="min_price" min="0" step="1" inputmode="numeric"
                                           value="{{ $filters['min_price'] ?? '' }}" placeholder="{{ __('Min') }}" class="{{ $control }} pl-7">
                                </div>
                                <span class="text-slate-400" aria-hidden="true">–</span>
                                <div class="relative flex-1">
                                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400" aria-hidden="true">৳</span>
                                    <label for="max_price" class="sr-only">{{ __('Maximum price') }}</label>
                                    <input id="max_price" x-ref="max" type="number" name="max_price" min="0" step="1" inputmode="numeric"
                                           value="{{ $filters['max_price'] ?? '' }}" placeholder="{{ __('Max') }}" class="{{ $control }} pl-7">
                                </div>
                            </div>
                            @if($priceBounds['max'] > 0)
                                <p class="mt-1.5 text-xs text-slate-500">{{ __('Prices range from :min to :max', ['min' => money($priceBounds['min']), 'max' => money($priceBounds['max'])]) }}</p>
                            @endif
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                @foreach($priceRanges as [$from, $to, $label])
                                    @php($selected = ($filters['min_price'] ?? null) == $from && ($filters['max_price'] ?? null) == $to)
                                    <button type="button" @click="$refs.min.value = @js($from ?? ''); $refs.max.value = @js($to ?? ''); desktop && $el.form.requestSubmit()"
                                            @class(['min-h-9 rounded-full border px-3 text-xs font-medium transition-colors sf-focus',
                                                    'border-brand-600 bg-brand-50 text-brand-700' => $selected,
                                                    'border-[var(--sf-border)] text-slate-600 hover:border-slate-300 hover:text-slate-900' => !$selected])>{{ $label }}</button>
                                @endforeach
                            </div>
                            <button type="submit" class="mt-3 hidden h-10 w-full items-center justify-center rounded-full border border-navy-900 text-sm font-semibold text-navy-900 transition-colors hover:bg-navy-900 hover:text-white lg:flex sf-focus">
                                {{ __('Apply price') }}
                            </button>
                        </div>
                    </details>

                    {{-- Availability --}}
                    <details class="group py-3" open>
                        <summary class="{{ $summary }}">{{ __('Availability') }} <i class="{{ $chevron }}" aria-hidden="true"></i></summary>
                        <div class="mt-1 space-y-1">
                            @foreach([['in_stock', __('In stock only'), 'bi-box-seam'], ['on_sale', __('On sale'), 'bi-tag']] as [$name, $label, $icon])
                                <label class="flex min-h-11 cursor-pointer items-center justify-between gap-3 rounded-[var(--sf-radius-sm)] px-2 text-sm text-slate-700 hover:bg-slate-50">
                                    <span class="flex items-center gap-2.5"><i class="bi {{ $icon }} text-slate-400" aria-hidden="true"></i> {{ $label }}</span>
                                    <input type="checkbox" name="{{ $name }}" value="1" @checked(!empty($filters[$name])) class="peer sr-only">
                                    <span class="relative h-6 w-10 shrink-0 rounded-full bg-slate-200 transition-colors after:absolute after:left-0.5 after:top-0.5 after:size-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-primary peer-checked:after:translate-x-4 peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-primary" aria-hidden="true"></span>
                                </label>
                            @endforeach
                        </div>
                    </details>
                </div>

                <div class="flex shrink-0 gap-2 border-t border-[var(--sf-border)] bg-white p-4 pb-[calc(1rem+var(--sf-safe-bottom))] lg:hidden">
                    <a href="{{ $clearUrl }}" class="flex h-12 flex-1 items-center justify-center rounded-full border border-[var(--sf-border)] text-sm font-semibold text-slate-700 active:bg-slate-50 sf-focus">{{ __('Clear all') }}</a>
                    <button type="submit" class="flex h-12 flex-[2] items-center justify-center gap-2 rounded-full bg-navy-900 text-sm font-semibold text-white active:bg-navy-800 sf-focus">
                        {{ __('Show results') }} <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </button>
                </div>
            </form>
        </aside>

        {{-- Results --}}
        <section class="min-w-0" aria-label="{{ __('Products') }}">
            <div class="flex items-center gap-2 rounded-[var(--sf-radius)] border border-[var(--sf-border)] bg-white p-2 shadow-[var(--sf-shadow-sm)] lg:py-2 lg:pl-4">
                <button type="button" @click="filtersOpen = true" aria-controls="catalog-filters" :aria-expanded="filtersOpen.toString()"
                        class="flex h-11 flex-1 items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] bg-slate-100 px-4 text-sm font-semibold text-slate-800 active:bg-slate-200 lg:hidden sf-focus">
                    <i class="bi bi-sliders2" aria-hidden="true"></i> {{ __('Filters') }}
                    @if($activeChips)
                        <span class="flex min-w-5 items-center justify-center rounded-full bg-primary px-1.5 text-[11px] font-bold leading-5 text-white">{{ localized_number(count($activeChips)) }}</span>
                    @endif
                </button>

                <p class="hidden flex-1 text-sm text-slate-500 lg:block">
                    @if($products->total())
                        {!! __('Showing :range of :total products', [
                            'range' => '<span class="font-semibold text-slate-900">' . localized_number($products->firstItem()) . '–' . localized_number($products->lastItem()) . '</span>',
                            'total' => '<span class="font-semibold text-slate-900">' . localized_number($products->total()) . '</span>',
                        ]) !!}
                    @else
                        {{ __('No products to show') }}
                    @endif
                </p>

                <form method="GET" action="{{ route('products.index') }}" class="flex flex-1 items-center gap-2 lg:flex-none">
                    @foreach($filters as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value === true ? 1 : $value }}">
                    @endforeach
                    <label for="catalog-sort" class="hidden whitespace-nowrap text-sm text-slate-500 xl:block">{{ __('Sort by') }}</label>
                    <div class="relative flex-1 lg:w-52 lg:flex-none">
                        <i class="bi bi-arrow-down-up pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-500 lg:hidden" aria-hidden="true"></i>
                        <select id="catalog-sort" name="sort" onchange="this.form.submit()" aria-label="{{ __('Sort by') }}"
                                class="h-11 w-full cursor-pointer appearance-none rounded-[var(--sf-radius-sm)] border border-[var(--sf-border)] bg-white pl-8 pr-9 text-sm font-medium text-slate-800 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30 lg:pl-3">
                            @foreach($sorts as $value => $label)
                                <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-500" aria-hidden="true"></i>
                    </div>
                    <noscript><button type="submit" class="h-11 rounded-[var(--sf-radius-sm)] bg-navy-900 px-3 text-sm font-semibold text-white">{{ __('Sort') }}</button></noscript>
                </form>
            </div>

            @if($activeChips)
                <ul class="mt-3 flex flex-wrap items-center gap-2" aria-label="{{ __('Active filters') }}">
                    @foreach($activeChips as $chip)
                        <li>
                            <a href="{{ $chip['url'] }}" class="group flex min-h-9 items-center gap-1.5 rounded-full border border-brand-200 bg-brand-50 pl-3 pr-2 text-xs font-medium text-brand-800 hover:border-brand-300 sf-focus">
                                {{ $chip['label'] }}
                                <span class="flex size-5 items-center justify-center rounded-full text-brand-600 group-hover:bg-white" aria-hidden="true"><i class="bi bi-x text-sm"></i></span>
                                <span class="sr-only">{{ __('Remove filter') }}</span>
                            </a>
                        </li>
                    @endforeach
                    <li><a href="{{ $clearUrl }}" class="flex min-h-9 items-center rounded px-2 text-xs font-semibold text-slate-600 underline-offset-2 hover:text-slate-900 hover:underline sf-focus">{{ __('Clear all') }}</a></li>
                </ul>
            @endif

            @if($products->count())
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 md:gap-4 xl:grid-cols-4">
                    @foreach($products as $product)
                        <x-store.product-card :product="$product" :wishlist-ids="$wishlistIds" :eager="$loop->index < 2" />
                    @endforeach
                </div>

                <x-store.pagination :paginator="$products" class="mt-8 md:mt-10" />
                @if($products->hasPages())
                    <p class="mt-3 text-center text-xs text-slate-500">
                        {{ __('Showing :from–:to of :total products', ['from' => localized_number($products->firstItem()), 'to' => localized_number($products->lastItem()), 'total' => localized_number($products->total())]) }}
                    </p>
                @endif
            @else
                <div class="mt-4 rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white px-6 py-12 text-center md:py-16">
                    <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-brand-50 text-2xl text-brand-600" aria-hidden="true"><i class="bi bi-search"></i></span>
                    <h2 class="mt-4 font-display text-lg font-bold text-slate-900 md:text-xl">{{ __('No products match these filters') }}</h2>
                    <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">{{ __('Try removing a filter, widening the price range or browsing another category.') }}</p>
                    @if($activeChips)
                        <a href="{{ $clearUrl }}" class="mt-5 inline-flex h-11 items-center gap-2 rounded-full bg-navy-900 px-6 text-sm font-semibold text-white hover:bg-navy-800 sf-focus">
                            <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> {{ __('Clear all filters') }}
                        </a>
                    @endif
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
