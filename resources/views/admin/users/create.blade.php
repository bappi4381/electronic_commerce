@extends('admin.layouts')
@section('title', 'Add customer')
@section('ui-v2', '1')

@section('content')
<x-admin.page-header title="Add customer" description="Create a customer account."
                     :breadcrumbs="['Customers' => route('users.index'), 'Add customer' => null]" />

<form method="POST" action="{{ route('users.store') }}" class="mx-auto max-w-4xl">
    @csrf
    <x-admin.card title="Account details">
        @include('admin.users.form')
        <x-slot:footer>
            <x-admin.button variant="secondary" :href="route('users.index')">Cancel</x-admin.button>
            <x-admin.button type="submit" icon="bi-check-lg">Create customer</x-admin.button>
        </x-slot:footer>
    </x-admin.card>
</form>
@endsection
