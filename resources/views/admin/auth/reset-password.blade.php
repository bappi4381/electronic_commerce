@extends('admin.auth.layout')

@section('title', 'Choose a new password')

@section('card')
    <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">Choose a new password</h1>
    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Use at least 8 characters. You'll be signed in with it from now on.</p>

    <form method="POST" action="{{ route('admin.password.update') }}" class="mt-8 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <x-admin.input name="email" type="email" label="Email address" :value="$email" autocomplete="username" required />
        <x-admin.password name="password" label="New password" hint="At least 8 characters." autocomplete="new-password" required autofocus />
        <x-admin.password name="password_confirmation" label="Confirm new password" autocomplete="new-password" required />

        <x-admin.button type="submit" size="lg" class="w-full">Reset password</x-admin.button>
    </form>

    <p class="mt-8 text-sm text-gray-500 dark:text-gray-400">
        Remembered it? <a href="{{ route('admin.login') }}" class="rounded font-medium text-brand-600 hover:underline focus-ring dark:text-brand-400">Back to sign in</a>
    </p>
@endsection
