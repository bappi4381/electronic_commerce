@extends('admin.layouts')

@section('title', 'Edit User')

@section('content')
<div class="container mt-4">
    <h2>Edit User</h2>

    <form action="{{ route('users.update', $user->id) }}" method="POST">
        @csrf
        @method('PUT')
        @include('admin.users.form', ['user' => $user])
        <button type="submit" class="btn btn-success">Update</button>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>
@endsection
