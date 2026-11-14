@extends('frontend.auth.layout')

@section('title', __('Forgot password'))
@section('auth_aside_title', __('Locked out? We will get you back in.'))

@section('auth')
<a href="{{ route('user.auth.login') }}" class="inline-flex min-h-10 items-center gap-1.5 rounded text-sm font-medium text-slate-600 hover:text-brand-700 sf-focus">
    <i class="bi bi-arrow-left" aria-hidden="true"></i> {{ __('Back to log in') }}
</a>

<span class="mt-4 flex size-12 items-center justify-center rounded-full bg-brand-50 text-xl text-brand-600" aria-hidden="true"><i class="bi bi-key"></i></span>
<h1 class="mt-4 font-display text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">{{ __('Forgot your password?') }}</h1>
<p class="mt-1 text-sm text-slate-500">{{ __('Enter the email you signed up with and we will send you a link to set a new password.') }}</p>

<form action="{{ route('password.email') }}" method="POST" class="mt-6 space-y-4" x-data="{ busy: false }" @submit="busy = true">
    @csrf
    <x-checkout.field name="email" type="email" :label="__('Email')" required autocomplete="email" autofocus maxlength="255" />
    <button type="submit" :disabled="busy"
            class="flex h-12 w-full items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] bg-brand-600 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-70 sf-focus">
        <i class="bi" :class="busy ? 'bi-arrow-repeat animate-spin' : 'bi-envelope'" aria-hidden="true"></i> {{ __('Send reset link') }}
    </button>
</form>

<p class="mt-6 text-center text-xs text-slate-500">{{ __('The link expires after :minutes minutes. Check your spam folder if you do not see it.', ['minutes' => \App\Services\CustomerPasswordReset::EXPIRE_MINUTES]) }}</p>
@endsection
