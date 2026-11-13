@extends('admin.layouts')
@section('title', 'Edit role · ' . $role->name)
@section('ui-v2', '1')

@section('content')
@php($protected = $role->name === 'Super Admin')

<x-admin.page-header :title="'Edit ' . $role->name" description="Change the role name and what it can access."
                     :breadcrumbs="['Roles' => route('roles.index'), $role->name => null]" />

<form method="POST" action="{{ route('roles.update', $role) }}" class="space-y-6">
    @csrf @method('PUT')
    <x-admin.card title="Role">
        <div class="max-w-xl">
            <x-admin.input name="name" label="Role name" required :value="$role->name" :readonly="$protected"
                           :hint="$protected ? 'The system role “Super Admin” cannot be renamed.' : null" />
        </div>
    </x-admin.card>

    <x-admin.card title="Permissions">
        @include('admin.roles._permissions', ['selected' => $rolePermissions])
    </x-admin.card>

    <div class="flex justify-end gap-2">
        <x-admin.button variant="secondary" :href="route('roles.index')">Cancel</x-admin.button>
        <x-admin.button type="submit" icon="bi-check-lg">Save role</x-admin.button>
    </div>
</form>
@endsection
