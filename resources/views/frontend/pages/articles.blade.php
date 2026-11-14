@extends('frontend.layout')

@php
    $storeName = \App\Models\Setting::get('site_name', 'ONEMALL');
    $imageOf = fn ($article) => $article->image ? asset('storage/' . $article->image) : null;
    $chip = 'inline-flex min-h-10 shrink-0 items-center gap-1.5 rounded-full border px-4 text-sm font-medium transition-colors sf-focus';
    $filtered = $activeCategory || $search !== '';
@endphp

@section('title', $activeCategory ? $activeCategory->name . ' – ' . __('Tech blog') : __('Tech blog'))
@section('meta_description', __('Buying guides, product comparisons and tips for phones, laptops and home appliances from the :store team.', ['store' => $storeName]))

@section('content')
<div class="mx-auto max-w-7xl px-4 pb-12 pt-4 md:pb-16 md:pt-6">
    <nav aria-label="{{ __('Breadcrumb') }}" class="text-xs text-slate-500">
        <ol class="flex items-center gap-1.5">
            <li><a href="{{ route('home') }}" class="rounded hover:text-brand-700 sf-focus">{{ __('Home') }}</a></li>
            <li aria-hidden="true"><i class="bi bi-chevron-right text-[10px]"></i></li>
            @if($activeCategory)
                <li><a href="{{ route('frontend.articles') }}" class="rounded hover:text-brand-700 sf-focus">{{ __('Tech blog') }}</a></li>
                <li aria-hidden="true"><i class="bi bi-chevron-right text-[10px]"></i></li>
                <li aria-current="page" class="font-medium text-slate-700">{{ $activeCategory->name }}</li>
            @else
                <li aria-current="page" class="font-medium text-slate-700">{{ __('Tech blog') }}</li>
            @endif
        </ol>
    </nav>

    <header class="mt-3 flex flex-col gap-4 md:mt-4 md:flex-row md:items-end md:justify-between">
        <div class="max-w-2xl">
            <h1 class="font-display text-2xl font-bold tracking-tight text-slate-900 md:text-4xl">{{ $activeCategory?->name ?? __('Tech blog') }}</h1>
            <p class="mt-2 text-sm text-slate-600 md:text-base">{{ __('Buying guides, comparisons and tips to help you choose the right gadget.') }}</p>
        </div>
        <form action="{{ route('frontend.articles') }}" method="GET" role="search" class="w-full md:w-80">
            @if($activeCategory)<input type="hidden" name="category" value="{{ $activeCategory->id }}">@endif
            <label for="blog-search" class="sr-only">{{ __('Search articles') }}</label>
            <div class="relative">
                <i class="bi bi-search pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                <input id="blog-search" type="search" name="search" value="{{ $search }}" maxlength="100" placeholder="{{ __('Search articles') }}"
                       class="block h-11 w-full rounded-full border border-slate-300 bg-white pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-primary/25">
            </div>
        </form>
    </header>

    @if($categories->isNotEmpty())
        <nav aria-label="{{ __('Topics') }}" class="-mx-4 mt-5 sm:mx-0">
            <ul class="no-scrollbar flex gap-2 overflow-x-auto px-4 pb-1 sm:flex-wrap sm:px-0">
                <li>
                    <a href="{{ route('frontend.articles', array_filter(['search' => $search ?: null])) }}" @if(!$activeCategory) aria-current="page" @endif
                       @class([$chip, 'border-slate-900 bg-slate-900 text-white' => !$activeCategory, 'border-slate-300 bg-white text-slate-700 hover:border-slate-400' => $activeCategory])>
                        {{ __('All') }} <span class="tabular-nums opacity-70">{{ localized_number($totalPublished) }}</span>
                    </a>
                </li>
                @foreach($categories as $category)
                    @php($current = $activeCategory?->id === $category->id)
                    <li>
                        <a href="{{ route('frontend.articles', array_filter(['category' => $category->id, 'search' => $search ?: null])) }}" @if($current) aria-current="page" @endif
                           @class([$chip, 'border-slate-900 bg-slate-900 text-white' => $current, 'border-slate-300 bg-white text-slate-700 hover:border-slate-400' => !$current])>
                            {{ $category->name }} <span class="tabular-nums opacity-70">{{ localized_number($category->articles_count) }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif

    @if($search !== '')
        <p class="mt-5 text-sm text-slate-600" role="status">
            {{ trans_choice(':count result for “:term”|:count results for “:term”', $articles->total(), ['count' => localized_number($articles->total()), 'term' => $search]) }}
            <a href="{{ route('frontend.articles', array_filter(['category' => $activeCategory?->id])) }}" class="ml-2 rounded font-semibold text-[var(--sf-accent-strong)] hover:underline sf-focus">{{ __('Clear search') }}</a>
        </p>
    @endif

    {{-- Featured story --}}
    @if($featured)
        <article class="group relative mt-6 grid overflow-hidden rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white md:grid-cols-2">
            <div class="aspect-[16/9] overflow-hidden bg-slate-100 md:aspect-auto md:min-h-80">
                @if($imageOf($featured))
                    <img src="{{ $imageOf($featured) }}" alt="" class="size-full object-cover transition-transform duration-500 group-hover:scale-[1.03]">
                @else
                    <div class="flex size-full items-center justify-center bg-gradient-to-br from-brand-50 to-slate-100 text-5xl text-brand-300" aria-hidden="true"><i class="bi bi-journal-richtext"></i></div>
                @endif
            </div>
            <div class="flex flex-col justify-center p-5 md:p-8 lg:p-10">
                <p class="flex flex-wrap items-center gap-2 text-xs font-semibold">
                    <span class="rounded-full bg-brand-50 px-2.5 py-1 text-brand-700">{{ __('Featured') }}</span>
                    <span class="text-[var(--sf-accent-strong)]">{{ $featured->category?->name }}</span>
                </p>
                <h2 class="mt-3 font-display text-xl font-bold leading-snug tracking-tight text-slate-900 md:text-3xl">
                    <a href="{{ route('frontend.articles.show', $featured->slug) }}" class="rounded after:absolute after:inset-0 group-hover:text-brand-700 sf-focus">{{ $featured->title }}</a>
                </h2>
                <p class="mt-3 line-clamp-3 text-sm text-slate-600 md:text-base">{{ $featured->excerpt(220) }}</p>
                <p class="mt-5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                    <span>{{ ($featured->published_at ?? $featured->created_at)->format('d M Y') }}</span>
                    <span aria-hidden="true">·</span>
                    <span>{{ __(':count min read', ['count' => localized_number($featured->readingMinutes())]) }}</span>
                    @if($featured->comments_count)
                        <span aria-hidden="true">·</span>
                        <span><i class="bi bi-chat" aria-hidden="true"></i> {{ localized_number($featured->comments_count) }}</span>
                    @endif
                </p>
            </div>
        </article>
    @endif

    {{-- Grid --}}
    @if($articles->isNotEmpty())
        <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($articles as $article)
                <article class="group relative flex flex-col overflow-hidden rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white transition-shadow hover:shadow-[var(--sf-shadow)]">
                    <div class="aspect-[16/10] overflow-hidden bg-slate-100">
                        @if($imageOf($article))
                            <img src="{{ $imageOf($article) }}" alt="" loading="lazy" class="size-full object-cover transition-transform duration-500 group-hover:scale-[1.04]">
                        @else
                            <div class="flex size-full items-center justify-center bg-gradient-to-br from-brand-50 to-slate-100 text-4xl text-brand-300" aria-hidden="true"><i class="bi bi-journal-richtext"></i></div>
                        @endif
                    </div>
                    <div class="flex flex-1 flex-col p-4 md:p-5">
                        <p class="text-xs font-semibold text-[var(--sf-accent-strong)]">{{ $article->category?->name }}</p>
                        <h2 class="mt-1.5 line-clamp-2 font-display text-base font-bold leading-snug text-slate-900 md:text-lg">
                            <a href="{{ route('frontend.articles.show', $article->slug) }}" class="rounded after:absolute after:inset-0 group-hover:text-brand-700 sf-focus">{{ $article->title }}</a>
                        </h2>
                        <p class="mt-2 line-clamp-2 text-sm text-slate-600">{{ $article->excerpt(140) }}</p>
                        <p class="mt-auto flex items-center gap-2 pt-4 text-xs text-slate-500">
                            <span>{{ ($article->published_at ?? $article->created_at)->format('d M Y') }}</span>
                            <span aria-hidden="true">·</span>
                            <span>{{ __(':count min read', ['count' => localized_number($article->readingMinutes())]) }}</span>
                            @if($article->comments_count)
                                <span class="ml-auto"><i class="bi bi-chat" aria-hidden="true"></i> {{ localized_number($article->comments_count) }}</span>
                            @endif
                        </p>
                    </div>
                </article>
            @endforeach
        </div>
        <x-store.pagination :paginator="$articles" class="mt-8 md:mt-10" />
    @elseif(!$featured)
        <div class="mt-8 flex flex-col items-center rounded-[var(--sf-radius-lg)] border border-dashed border-slate-300 bg-white px-6 py-14 text-center">
            <span class="flex size-14 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400" aria-hidden="true"><i class="bi bi-journal-x"></i></span>
            <h2 class="mt-4 font-display text-lg font-bold text-slate-900">{{ $filtered ? __('No articles match') : __('No articles yet') }}</h2>
            <p class="mt-1 max-w-sm text-sm text-slate-500">{{ $filtered ? __('Try another topic or a shorter search.') : __('Our first buying guides are on the way. Meanwhile, browse the latest products.') }}</p>
            <a href="{{ $filtered ? route('frontend.articles') : route('products.index') }}" class="mt-5 flex h-11 items-center rounded-[var(--sf-radius-sm)] bg-brand-600 px-5 text-sm font-semibold text-white hover:bg-brand-700 sf-focus">
                {{ $filtered ? __('See all articles') : __('Browse products') }}
            </a>
        </div>
    @endif
</div>
@endsection
