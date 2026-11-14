@extends('frontend.layout')

@php
    $storeName = \App\Models\Setting::get('site_name', 'ONEMALL');
    $hasCampaign = $campaign && $flashProducts->isNotEmpty();
    $pageTitle = $hasCampaign
        ? __(':campaign – Deals & discounts', ['campaign' => $campaign['title']])
        : __('Deals & discounts');
    $metaDescription = $hasCampaign
        ? __('Shop :campaign at :store: limited-time flash prices plus every discount in the store. Cash on delivery across Bangladesh.', ['campaign' => $campaign['title'], 'store' => $storeName])
        : __('Every discounted product at :store, biggest savings first. Cash on delivery across Bangladesh.', ['store' => $storeName]);
    $bands = ['' => __('All'), 'upto-20' => __('Up to 20%'), 'upto-40' => __('Up to 40%'), '60-plus' => __('60%+ off')];
    $fragment = $hasCampaign ? '#all-discounts' : '';
    $dealsUrl = fn (array $overrides) => route('flash-deals', array_filter(array_merge(['off' => $band, 'category' => $activeCategory], $overrides))) . $fragment;
    $discounts->fragment($hasCampaign ? 'all-discounts' : null);
@endphp

@section('title', $pageTitle)
@section('meta_description', $metaDescription)

@section('content')
<div class="mx-auto max-w-7xl px-4 pb-12 pt-4 md:pb-16 md:pt-6">
    <nav aria-label="{{ __('Breadcrumb') }}" class="text-xs text-slate-500">
        <ol class="flex items-center gap-1.5">
            <li><a href="{{ route('home') }}" class="rounded hover:text-brand-700 sf-focus">{{ __('Home') }}</a></li>
            <li aria-hidden="true"><i class="bi bi-chevron-right text-[10px]"></i></li>
            <li aria-current="page" class="font-medium text-slate-700">{{ __('Deals') }}</li>
        </ol>
    </nav>

    <header class="mt-3 flex items-center gap-3 md:mt-4 md:gap-4">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-[var(--sf-radius)] bg-sale-soft text-xl text-sale md:size-14 md:text-2xl" aria-hidden="true"><i class="bi bi-tags-fill"></i></span>
        <div class="min-w-0">
            <h1 class="font-display text-xl font-bold tracking-tight text-slate-900 md:text-3xl">{{ $hasCampaign ? __('Deals') : __('Deals & discounts') }}</h1>
            <p class="mt-0.5 text-sm text-slate-500">
                {{ $hasCampaign ? __('Flash prices for a limited time, plus every discount in the store.') : __('Every discount in the store, biggest savings first.') }}
            </p>
        </div>
    </header>

    <x-store.flash-sale :campaign="$campaign" :products="$flashProducts" :wishlist-ids="$wishlistIds" layout="grid" class="mt-5 block md:mt-6" />

    <section id="all-discounts" class="mt-8 scroll-mt-28 md:mt-10" aria-labelledby="discounts-title">
        <div class="flex flex-wrap items-end justify-between gap-x-4 gap-y-2">
            <div>
                <h2 id="discounts-title" class="font-display text-lg font-bold tracking-tight text-slate-900 sm:text-2xl">{{ __('All discounts') }}</h2>
                <p class="mt-0.5 text-sm text-slate-500" role="status">{{ trans_choice(':count product on sale|:count products on sale', $discounts->total(), ['count' => localized_number($discounts->total())]) }}</p>
            </div>
            <a href="{{ route('products.index', ['on_sale' => 1, 'sort' => 'discount']) }}" class="flex min-h-10 items-center gap-1 rounded text-sm font-semibold text-[var(--sf-accent-strong)] hover:underline sf-focus">
                {{ __('Browse all on-sale products') }} <i class="bi bi-chevron-right text-xs" aria-hidden="true"></i>
            </a>
        </div>

        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <nav aria-label="{{ __('Discount range') }}" class="-mx-4 sm:mx-0">
                <ul class="no-scrollbar flex gap-2 overflow-x-auto px-4 pb-1 sm:px-0">
                    @foreach($bands as $value => $label)
                        @php($current = (string) $band === (string) $value)
                        <li class="shrink-0">
                            <a href="{{ $dealsUrl(['off' => $value ?: null]) }}" @if($current) aria-current="page" @endif
                               @class(['flex min-h-10 items-center rounded-full border px-4 text-sm font-medium transition-colors sf-focus',
                                       'border-navy-900 bg-navy-900 text-white' => $current,
                                       'border-[var(--sf-border)] bg-white text-slate-700 hover:border-slate-300' => !$current])>{{ $label }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>

            @if($categories->isNotEmpty())
                <form method="GET" action="{{ route('flash-deals') }}{{ $fragment }}" class="flex items-center gap-2">
                    @if($band)<input type="hidden" name="off" value="{{ $band }}">@endif
                    <label for="deals-category" class="whitespace-nowrap text-sm text-slate-500">{{ __('Category') }}</label>
                    <div class="relative flex-1 sm:w-56 sm:flex-none">
                        <select id="deals-category" name="category" onchange="this.form.submit()"
                                class="h-10 w-full cursor-pointer appearance-none rounded-[var(--sf-radius-sm)] border border-[var(--sf-border)] bg-white pl-3 pr-9 text-sm text-slate-800 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <option value="">{{ __('All categories') }}</option>
                            @foreach($categories as $category)
                                <option value="{{ $category['id'] }}" @selected($activeCategory === $category['id'])>{{ $category['name'] }} ({{ localized_number($category['count']) }})</option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-500" aria-hidden="true"></i>
                    </div>
                    <noscript><button type="submit" class="h-10 rounded-[var(--sf-radius-sm)] bg-navy-900 px-3 text-sm font-semibold text-white">{{ __('Apply') }}</button></noscript>
                </form>
            @endif
        </div>

        @if($discounts->count())
            <ul class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 md:gap-4 lg:grid-cols-4 xl:grid-cols-5" data-section="discounts">
                @foreach($discounts as $product)
                    <li><x-store.product-card :product="$product" :wishlist-ids="$wishlistIds" :eager="!$hasCampaign && $loop->index < 2" /></li>
                @endforeach
            </ul>
            <x-store.pagination :paginator="$discounts" class="mt-8 md:mt-10" />
        @else
            <div class="mt-4 rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white px-6 py-12 text-center">
                <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-sale-soft text-3xl text-sale" aria-hidden="true"><i class="bi bi-tag"></i></span>
                <h3 class="mt-4 text-lg font-semibold text-slate-900">{{ $band || $activeCategory ? __('No deals match this filter') : __('No discounts right now') }}</h3>
                <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">{{ $band || $activeCategory ? __('Try another discount range or category.') : __('New offers are added often. Check back soon.') }}</p>
                <a href="{{ $band || $activeCategory ? route('flash-deals') : route('products.index') }}" class="mt-5 inline-flex h-11 items-center rounded-full bg-navy-900 px-6 text-sm font-semibold text-white hover:bg-navy-800 sf-focus">
                    {{ $band || $activeCategory ? __('Show all deals') : __('Browse products') }}
                </a>
            </div>
        @endif
    </section>
</div>
@endsection
