@extends('admin.auth.layout')

@section('title', 'Forgot password')

@section('card')
    <a href="{{ route('admin.login') }}" class="mb-8 inline-flex items-center gap-1.5 rounded text-sm font-medium text-gray-500 hover:text-gray-900 focus-ring dark:text-gray-400 dark:hover:text-white">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to sign in
    </a>

    <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">Reset your password</h1>
    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Enter the email address of your admin account and we'll send you a link to choose a new password.</p>

    @if(session('status'))
        <x-admin.alert tone="success" class="mt-6">{{ session('status') }}</x-admin.alert>
    @endif

    <form method="POST" action="{{ route('admin.password.email') }}" class="mt-8 space-y-5">
        @csrf
        <x-admin.input name="email" type="email" label="Email address" placeholder="you@company.com" autocomplete="username" required autofocus />
        <x-admin.button type="submit" size="lg" class="w-full" icon="bi-envelope">Send reset link</x-admin.button>
    </form>
@endsection
