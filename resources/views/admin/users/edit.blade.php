@extends('admin.layouts')
@section('title', 'Edit ' . $user->name)
@section('ui-v2', '1')

@section('content')
<x-admin.page-header :title="'Edit ' . $user->name" description="Update account and address details."
                     :breadcrumbs="['Customers' => route('users.index'), $user->name => route('users.show', $user), 'Edit' => null]" />

<form method="POST" action="{{ route('users.update', $user->id) }}" class="mx-auto max-w-4xl">
    @csrf @method('PUT')
    <x-admin.card title="Account details">
        @include('admin.users.form', ['user' => $user])
        <x-slot:footer>
            <x-admin.button variant="secondary" :href="route('users.show', $user)">Cancel</x-admin.button>
            <x-admin.button type="submit" icon="bi-check-lg">Save changes</x-admin.button>
        </x-slot:footer>
    </x-admin.card>
</form>
@endsection
