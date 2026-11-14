@extends(auth()->check() ? 'user.layout' : 'frontend.layout')

@section('title', __('My wishlist'))

@auth
    @section('account')
    <div class="space-y-5">
        <header class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="font-display text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">{{ __('My wishlist') }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ trans_choice(':count saved item|:count saved items', $wishlistItems->count(), ['count' => localized_number($wishlistItems->count())]) }}</p>
            </div>
            <a href="{{ route('products.index') }}" class="flex min-h-10 items-center gap-1.5 rounded text-sm font-semibold text-brand-700 hover:underline sf-focus">{{ __('Continue shopping') }} <i class="bi bi-chevron-right text-xs" aria-hidden="true"></i></a>
        </header>

        @if($wishlistItems->isNotEmpty())
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:gap-4 xl:grid-cols-4">
                @foreach($wishlistItems as $item)
                    <x-store.product-card :product="$item->product" :wishlist-ids="$wishlistItems->pluck('product_id')->all()" />
                @endforeach
            </div>
        @else
            <div class="rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white px-6 py-12 text-center">
                <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-rose-50 text-3xl text-rose-500" aria-hidden="true"><i class="bi bi-heart"></i></span>
                <h2 class="mt-4 text-lg font-semibold text-slate-900">{{ __('Your wishlist is empty') }}</h2>
                <p class="mx-auto mt-1 max-w-sm text-sm text-slate-500">{{ __('Tap the heart on any product to save it here for later.') }}</p>
                <a href="{{ route('products.index') }}" class="mt-5 inline-flex h-11 items-center gap-2 rounded-full bg-navy-900 px-6 text-sm font-semibold text-white hover:bg-navy-800 sf-focus">{{ __('Browse products') }}</a>
            </div>
        @endif
    </div>
    @endsection
@else
    @section('content')
    <div class="mx-auto max-w-lg px-4 py-14 text-center md:py-20">
        <span class="mx-auto flex size-16 items-center justify-center rounded-full bg-rose-50 text-3xl text-rose-500" aria-hidden="true"><i class="bi bi-heart"></i></span>
        <h1 class="mt-4 font-display text-2xl font-bold text-slate-900">{{ __('Save your favourites') }}</h1>
        <p class="mt-2 text-sm text-slate-500">{{ __('Log in to keep a wishlist across your phone and computer.') }}</p>
        <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
            <a href="{{ route('user.auth.login') }}" class="flex h-12 items-center justify-center rounded-full bg-brand-600 px-7 text-sm font-semibold text-white hover:bg-brand-700 sf-focus">{{ __('Log in') }}</a>
            <a href="{{ route('user.auth.register') }}" class="flex h-12 items-center justify-center rounded-full border border-slate-300 px-7 text-sm font-semibold text-slate-800 hover:border-brand-600 sf-focus">{{ __('Create account') }}</a>
        </div>
    </div>
    @endsection
@endauth
