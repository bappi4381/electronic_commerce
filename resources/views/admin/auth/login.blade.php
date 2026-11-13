@extends('admin.auth.layout')

@section('title', 'Sign in')

@section('card')
    <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">Sign in to your account</h1>
    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Welcome back. Enter your admin credentials to continue.</p>

    @if(session('status'))
        <x-admin.alert tone="success" class="mt-6">{{ session('status') }}</x-admin.alert>
    @endif

    <form method="POST" action="{{ route('admin.login.submit') }}" class="mt-8 space-y-5">
        @csrf

        <x-admin.input name="email" type="email" label="Email address" placeholder="you@company.com" autocomplete="username" required autofocus />

        <x-admin.password name="password" label="Password" autocomplete="current-password" required />

        <div class="flex items-center justify-between gap-4">
            <x-admin.checkbox name="remember" label="Remember me" />
            <a href="{{ route('admin.password.request') }}" class="rounded text-sm font-medium text-brand-600 hover:underline focus-ring dark:text-brand-400">Forgot password?</a>
        </div>

        <x-admin.button type="submit" size="lg" class="w-full">Sign in</x-admin.button>
    </form>

    <p class="mt-8 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
        <i class="bi bi-shield-lock" aria-hidden="true"></i>
        Repeated failed attempts are temporarily blocked to protect your account.
    </p>
@endsection
