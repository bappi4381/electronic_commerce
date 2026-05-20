@extends('admin.layouts')

@section('title', 'Create User')

@section('content')
<div class="container mt-4">
    <h2>Create User</h2>

    <form action="{{ route('users.store') }}" method="POST">
        @csrf
        @include('admin.users.form')
        <button type="submit" class="btn btn-success">Create</button>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>
@endsection
