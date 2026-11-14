@extends('frontend.auth.layout')

@section('title', __('Set a new password'))
@section('auth_aside_title', __('Almost done. Choose a new password.'))

@section('auth')
<span class="flex size-12 items-center justify-center rounded-full bg-brand-50 text-xl text-brand-600" aria-hidden="true"><i class="bi bi-shield-lock"></i></span>
<h1 class="mt-4 font-display text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">{{ __('Set a new password') }}</h1>
<p class="mt-1 text-sm text-slate-500">{{ __('For :email', ['email' => $email ?? old('email')]) }}</p>

<form action="{{ route('password.update') }}" method="POST" class="mt-6 space-y-4" x-data="{ busy: false }" @submit="busy = true">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <input type="hidden" name="email" value="{{ $email ?? old('email') }}">
    @error('email')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror

    <x-auth.password name="password" :label="__('New password')" required autocomplete="new-password" autofocus :hint="__('At least 6 characters.')" />
    <x-auth.password name="password_confirmation" :label="__('Confirm new password')" required autocomplete="new-password" />

    <button type="submit" :disabled="busy"
            class="flex h-12 w-full items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] bg-brand-600 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-70 sf-focus">
        <i class="bi" :class="busy ? 'bi-arrow-repeat animate-spin' : 'bi-check-lg'" aria-hidden="true"></i> {{ __('Update password') }}
    </button>
</form>
@endsection
