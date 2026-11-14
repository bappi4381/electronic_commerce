@extends('frontend.auth.layout')

@section('title', __('Create account'))
@section('auth_aside_title', __('Create an account and check out faster every time.'))

@section('auth')
@php($legal = \App\Models\Page::footerLinks()->keyBy('slug'))

<h1 class="font-display text-2xl font-bold tracking-tight text-slate-900 md:text-3xl">{{ __('Create account') }}</h1>
<p class="mt-1 text-sm text-slate-500">{{ __('It takes less than a minute.') }}</p>

<form action="{{ route('auth.register') }}" method="POST" class="mt-6 space-y-4" x-data="{ busy: false }" @submit="busy = true">
    @csrf
    <x-checkout.field name="name" :label="__('Full name')" required autocomplete="name" autofocus maxlength="100" />
    <x-checkout.field name="email" type="email" :label="__('Email')" required autocomplete="email" maxlength="255" />
    <x-checkout.field name="phone" type="tel" :label="__('Mobile number')" optional autocomplete="tel" inputmode="tel" maxlength="20" :hint="__('Used for delivery updates.')" />
    <div class="grid gap-4 sm:grid-cols-2">
        <x-auth.password name="password" :label="__('Password')" required autocomplete="new-password" :hint="__('At least 8 characters, with letters and numbers.')" />
        <x-auth.password name="password_confirmation" :label="__('Confirm password')" required autocomplete="new-password" />
    </div>

    <label class="flex cursor-pointer items-start gap-2.5 text-sm text-slate-700">
        <input type="checkbox" name="terms" value="1" required class="mt-0.5 size-[18px] shrink-0 rounded border-slate-300 accent-brand-600">
        <span>
            {{ __('I agree to the') }}
            @if($legal->has('terms'))<a href="{{ route('pages.show', 'terms') }}" target="_blank" class="font-semibold text-brand-700 hover:underline">{{ __('Terms of service') }}</a>@else{{ __('Terms of service') }}@endif
            {{ __('and') }}
            @if($legal->has('privacy'))<a href="{{ route('pages.show', 'privacy') }}" target="_blank" class="font-semibold text-brand-700 hover:underline">{{ __('Privacy policy') }}</a>@else{{ __('Privacy policy') }}@endif.
        </span>
    </label>

    <button type="submit" :disabled="busy"
            class="flex h-12 w-full items-center justify-center gap-2 rounded-[var(--sf-radius-sm)] bg-brand-600 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-70 sf-focus">
        <i class="bi" :class="busy ? 'bi-arrow-repeat animate-spin' : 'bi-person-plus'" aria-hidden="true"></i> {{ __('Create account') }}
    </button>
</form>

@include('frontend.auth.partials.social', ['label' => __('or sign up with')])

<p class="mt-8 text-center text-sm text-slate-600">
    {{ __('Already have an account?') }}
    <a href="{{ route('user.auth.login') }}" class="font-semibold text-brand-700 hover:underline">{{ __('Log in') }}</a>
</p>
@endsection
