{{-- Shared shell for login, register and password recovery: brand panel on desktop, form card. Pages fill @section('auth'). --}}
@extends('frontend.layout')

@section('content')
@php
    $storeName = \App\Models\Setting::get('site_name', 'ONEMALL');
    $benefits = [
        ['bi-truck', __('Track every order live')],
        ['bi-lightning-charge', __('Faster checkout with saved details')],
        ['bi-heart', __('Wishlist on all your devices')],
        ['bi-chat-dots', __('Chat with support about any order')],
    ];
@endphp

<div class="mx-auto max-w-5xl px-4 py-6 md:py-12">
    <div class="overflow-hidden rounded-[var(--sf-radius-lg)] border border-[var(--sf-border)] bg-white shadow-[var(--sf-shadow)] lg:grid lg:grid-cols-[minmax(0,1fr)_minmax(0,1.15fr)]">
        <aside class="relative hidden overflow-hidden bg-navy-900 p-10 text-white lg:flex lg:flex-col">
            <div aria-hidden="true" class="pointer-events-none absolute -right-24 -top-24 size-72 rounded-full bg-primary/25 blur-3xl"></div>
            <div class="relative flex items-center gap-2.5">
                <span class="flex size-10 items-center justify-center rounded-[var(--sf-radius-sm)] bg-primary text-xl" aria-hidden="true"><i class="bi bi-lightning-charge-fill"></i></span>
                <span class="font-display text-lg font-extrabold uppercase tracking-tight">{{ $storeName }}</span>
            </div>
            <h2 class="relative mt-10 font-display text-3xl font-bold leading-tight">@yield('auth_aside_title', __('Shop smarter with your account'))</h2>
            <ul class="relative mt-8 space-y-4">
                @foreach($benefits as [$icon, $text])
                    <li class="flex items-center gap-3 text-sm text-slate-200">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-white/10 text-primary-light" aria-hidden="true"><i class="bi {{ $icon }}"></i></span>
                        {{ $text }}
                    </li>
                @endforeach
            </ul>
            <div class="relative mt-auto flex flex-wrap gap-2 pt-10 text-xs text-slate-300">
                <span class="flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5"><i class="bi bi-cash-coin" aria-hidden="true"></i> {{ __('Cash on delivery') }}</span>
                <span class="flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> {{ __(':days-day returns', ['days' => localized_number(config('store.return_days', 30))]) }}</span>
                <span class="flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1.5"><i class="bi bi-shield-lock" aria-hidden="true"></i> {{ __('Secure') }}</span>
            </div>
        </aside>

        <div class="px-5 py-8 sm:px-10 md:py-10">
            @foreach([
                'success' => ['border-emerald-200 bg-emerald-50 text-emerald-800', 'bi-check-circle-fill', 'status'],
                'status' => ['border-emerald-200 bg-emerald-50 text-emerald-800', 'bi-check-circle-fill', 'status'],
                'error' => ['border-rose-200 bg-rose-50 text-rose-800', 'bi-exclamation-triangle-fill', 'alert'],
            ] as $key => [$tone, $icon, $role])
                @if(session($key))
                    <div class="mb-5 flex gap-3 rounded-[var(--sf-radius-sm)] border p-3.5 text-sm {{ $tone }}" role="{{ $role }}">
                        <i class="bi {{ $icon }} mt-0.5" aria-hidden="true"></i><p>{{ session($key) }}</p>
                    </div>
                @endif
            @endforeach

            @yield('auth')
        </div>
    </div>
</div>
@endsection
