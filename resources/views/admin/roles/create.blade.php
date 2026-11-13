@extends('admin.layouts')
@section('title', 'New role')
@section('ui-v2', '1')

@section('content')
<x-admin.page-header title="New role" description="A named set of permissions you can assign to admin users."
                     :breadcrumbs="['Roles' => route('roles.index'), 'New role' => null]" />

<form method="POST" action="{{ route('roles.store') }}" class="space-y-6">
    @csrf
    <x-admin.card title="Role">
        <div class="max-w-xl">
            <x-admin.input name="name" label="Role name" required placeholder="e.g. Content moderator, Support agent" />
        </div>
    </x-admin.card>

    <x-admin.card title="Permissions">
        @include('admin.roles._permissions')
    </x-admin.card>

    <div class="flex justify-end gap-2">
        <x-admin.button variant="secondary" :href="route('roles.index')">Cancel</x-admin.button>
        <x-admin.button type="submit" icon="bi-check-lg">Create role</x-admin.button>
    </div>
</form>
@endsection
