@extends('frontend.auth.layout')

@section('title', __('Log in'))
@section('auth_aside_title', __('Welcome back! Your orders and wishlist are waiting.'))

@section('auth')
@php($fromCheckout = str_contains((string) session('url.intended'), '/checkout'))

<h1 class="font-display text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">{{ __('Log in') }}</h1>
<p class="mt-1 text-sm text-slate-500">{{ __('Use the email and password for your account.') }}</p>

@if($fromCheckout)
    <div class="mt-5 flex gap-3 rounded-[var(--sf-radius-sm)] border border-brand-200 bg-brand-50 p-3.5 text-sm text-brand-800" role="status">
        <i class="bi bi-bag-check mt-0.5" aria-hidden="true"></i>
        <p>{{ __('Log in or create an account to finish your order. Your cart is saved.') }}</p>
    </div>
@endif

<form action="{{ route('auth.login') }}" method="POST" class="mt-6 space-y-4" x-data="{ busy: false }" @submit="busy = true">
    @csrf
    <x-checkout.field name="email" type="email" :label="__('Email')" required autocomplete="email" autofocus maxlength="255" />
    <x-auth.password name="password" :label="__('Password')" required autocomplete="current-password" />

    <div class="flex items-center justify-between gap-3">
        <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-700">
            <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="size-[18px] rounded border-slate-300 accent-brand-600">
            {{ __('Remember me') }}
        </label>
        <a href="{{ route('password.request') }}" class="rounded text-sm font-semibold text-brand-700 hover:underline sf-focus">{{ __('Forgot password?') }}</a>
    </div>

    <button type="submit" :disabled="busy"
            class="flex h-12 w-full items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] bg-brand-600 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-70 sf-focus">
        <i class="bi" :class="busy ? 'bi-arrow-repeat animate-spin' : 'bi-box-arrow-in-right'" aria-hidden="true"></i> {{ __('Log in') }}
    </button>
</form>

@include('frontend.auth.partials.social', ['label' => __('or log in with')])

<p class="mt-8 text-center text-sm text-slate-600">
    {{ __('New here?') }}
    <a href="{{ route('user.auth.register') }}" class="font-semibold text-brand-700 hover:underline">{{ __('Create an account') }}</a>
</p>
@endsection
