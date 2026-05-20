@extends('admin.layouts')

@section('title', 'Admin Profile')

@section('content')
<div class="container mt-4">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        {{-- Profile Info --}}
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <img src="{{ $admin->avatar 
                                ? asset('storage/' . $admin->avatar) 
                                : 'https://via.placeholder.com/100x100/007bff/ffffff?text=' . strtoupper(substr($admin->name, 0, 2)) }}" 
                         class="rounded-circle mb-3" 
                         alt="{{ $admin->name }}" 
                         width="100" height="100">
                    <h5 class="mb-0">{{ $admin->name }}</h5>
                    <small class="text-muted">{{ $admin->email }}</small>
                </div>
            </div>
        </div>

        {{-- Edit Profile --}}
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5>Update Profile</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        {{-- Name --}}
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" name="name" id="name" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   value="{{ old('name', $admin->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" name="email" id="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   value="{{ old('email', $admin->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Avatar --}}
                        <div class="mb-3">
                            <label for="avatar" class="form-label">Profile Picture</label>
                            <input type="file" name="avatar" id="avatar" 
                                   class="form-control @error('avatar') is-invalid @enderror">
                            @error('avatar')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password <small>(optional)</small></label>
                            <input type="password" name="password" id="password" 
                                   class="form-control @error('password') is-invalid @enderror">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Password Confirmation --}}
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirm New Password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control">
                        </div>

                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
