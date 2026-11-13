@extends('admin.layouts')
@section('title', 'Permission · ' . $permission->name)
@section('ui-v2', '1')

@section('content')
@php
    $isCore = in_array($permission->name, \App\Support\AdminPermissions::all(), true);
    $assigned = $roles->where('has_permission', true);
@endphp

<x-admin.page-header :title="$permission->name" :description="$isCore ? 'Core permission' : 'Custom permission'"
                     :breadcrumbs="['Roles' => route('roles.index'), 'Permissions' => route('permissions.index'), $permission->name => null]" />

@if($isCore)
    <x-admin.alert tone="warning" title="System-protected permission" icon="bi-shield-lock-fill" class="mb-6">
        Core permission names can't be changed. You can see which roles have it below.
    </x-admin.alert>
@endif

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <form method="POST" action="{{ route('permissions.update', $permission) }}" x-data="{ name: @js(old('name', $permission->name)) }">
            @csrf @method('PUT')
            <x-admin.card title="Permission">
                <x-admin.input name="name" label="Identifier" required prefix="bi-key" autocomplete="off" class="font-mono" :readonly="$isCore"
                               x-model="name" @input="name = name.toLowerCase().replace(/[\s_]+/g, '-')"
                               :hint="$isCore ? 'Core permission names are immutable.' : 'Lowercase letters, numbers, dots and hyphens.'" />
                @unless($isCore)
                    <x-slot:footer>
                        <x-admin.button variant="secondary" :href="route('permissions.index')">Cancel</x-admin.button>
                        <x-admin.button type="submit" icon="bi-check-lg">Save changes</x-admin.button>
                    </x-slot:footer>
                @endunless
            </x-admin.card>
        </form>

        <x-admin.card title="Roles with this permission" :description="$assigned->count() . ' ' . \Illuminate\Support\Str::plural('role', $assigned->count())" :padding="false">
            <ul class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($assigned as $role)
                    <li class="flex items-center justify-between gap-4 px-6 py-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-green-50 text-green-600 dark:bg-green-500/10 dark:text-green-400" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
                            <span class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $role->name }}</span>
                        </div>
                        @can('roles.edit')
                            <x-admin.button variant="link" :href="route('roles.edit', $role)" icon-right="bi-arrow-right">Edit role</x-admin.button>
                        @endcan
                    </li>
                @empty
                    <li class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Not assigned to any role yet.</li>
                @endforelse
            </ul>
        </x-admin.card>
    </div>

    <div class="space-y-6">
        <x-admin.card title="Details">
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">ID</dt><dd class="font-medium">#{{ $permission->id }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">Guard</dt><dd class="font-medium">{{ $permission->guard_name }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">Created</dt><dd class="font-medium">{{ $permission->created_at?->format('d M Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500 dark:text-gray-400">Roles</dt><dd class="font-medium tabular-nums">{{ $assigned->count() }}</dd></div>
            </dl>
        </x-admin.card>

        @if(!$isCore)
            @can('roles.delete')
                <x-admin.card title="Danger zone" class="border-red-200 dark:border-red-500/30">
                    <p class="text-sm text-gray-600 dark:text-gray-300">Deleting removes this permission from every role immediately.</p>
                    <form method="POST" action="{{ route('permissions.destroy', $permission) }}" class="mt-4"
                          data-confirm="It's removed from every role that has it. This can't be undone." data-confirm-title="Delete {{ $permission->name }}?" data-confirm-button="Delete">
                        @csrf @method('DELETE')
                        <x-admin.button type="submit" variant="danger" icon="bi-trash" class="w-full">Delete permission</x-admin.button>
                    </form>
                </x-admin.card>
            @endcan
        @endif
    </div>
</div>
@endsection
