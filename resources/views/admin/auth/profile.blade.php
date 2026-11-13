@extends('admin.layouts')
@section('title', 'Your profile')
@section('ui-v2', '1')

@section('content')
@php
    $avatar = $admin->avatar ? asset('storage/' . $admin->avatar) : null;
    $roles = method_exists($admin, 'getRoleNames') ? $admin->getRoleNames() : collect();
@endphp

<x-admin.page-header title="Your profile" description="Your name, email, photo and password." />

<form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data"
      class="grid grid-cols-1 gap-6 lg:grid-cols-3" x-data="{ preview: @js($avatar) }">
    @csrf @method('PUT')

    <x-admin.card class="self-start">
        <div class="flex flex-col items-center text-center">
            <div class="relative mb-4 size-28 overflow-hidden rounded-full border-4 border-white bg-brand-50 shadow-sm ring-1 ring-gray-200 dark:border-gray-900 dark:bg-brand-500/10 dark:ring-gray-800">
                <template x-if="preview"><img :src="preview" alt="{{ $admin->name }}" class="size-full object-cover"></template>
                <template x-if="!preview">
                    <span class="flex size-full items-center justify-center text-3xl font-semibold text-brand-700 dark:text-brand-300" aria-hidden="true">{{ mb_strtoupper(mb_substr($admin->name, 0, 1)) }}</span>
                </template>
            </div>
            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $admin->name }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $admin->email }}</p>
            @if($roles->isNotEmpty())
                <div class="mt-3 flex flex-wrap justify-center gap-1">
                    @foreach($roles as $role)
                        <x-admin.status-badge :tone="$role === 'Super Admin' ? 'brand' : 'gray'" :dot="false" :label="$role" />
                    @endforeach
                </div>
            @endif
            <label for="avatar" class="mt-6 inline-flex h-9 cursor-pointer items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 text-sm font-medium text-gray-700 shadow-xs hover:bg-gray-50 focus-within:ring-2 focus-within:ring-brand-500/40 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
                <i class="bi bi-camera" aria-hidden="true"></i> Change photo
                <input type="file" name="avatar" id="avatar" accept="image/*" class="sr-only"
                       @change="const f = $event.target.files[0]; if (f) { const r = new FileReader(); r.onload = e => preview = e.target.result; r.readAsDataURL(f); }">
            </label>
            @error('avatar')<p class="mt-2 text-xs font-medium text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
        </div>
    </x-admin.card>

    <div class="space-y-6 lg:col-span-2">
        <x-admin.card title="Account">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-admin.input name="name" id="name" label="Full name" required :value="$admin->name" />
                <x-admin.input name="email" id="email" type="email" label="Email" required :value="$admin->email" />
            </div>
        </x-admin.card>

        <x-admin.card title="Change password" description="Leave both fields empty to keep your current password.">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <x-admin.password name="password" id="password" label="New password" autocomplete="new-password" />
                <x-admin.password name="password_confirmation" id="password_confirmation" label="Confirm new password" autocomplete="new-password" />
            </div>
            <x-slot:footer>
                <x-admin.button type="submit" icon="bi-check-lg">Save changes</x-admin.button>
            </x-slot:footer>
        </x-admin.card>
    </div>
</form>
@endsection
