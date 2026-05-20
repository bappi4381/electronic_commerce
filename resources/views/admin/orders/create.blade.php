@extends('admin.layouts')

@section('title', 'Create Order')

@section('content')
<div class="container mt-4">
    <h4 class="mb-3">🛒 Create New Order</h4>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form action="{{ route('orders.store') }}" method="POST">
        @csrf

        <div class="card p-3 mb-3">
            <h5>Customer Information</h5>
            <div class="row">
                <div class="col-md-6 mb-2">
                    <label>Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
                </div>
                <div class="col-md-6 mb-2">
                    <label>Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                </div>
                <div class="col-md-6 mb-2">
                    <label>Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-2">
                    <label>Address</label>
                    <input type="text" name="address" value="{{ old('address') }}" class="form-control">
                </div>
            </div>
        </div>

        <div class="card p-3 mb-3">
            <h5>Select Products <span class="text-danger">*</span></h5>
            <div class="row">
                @foreach($products as $product)
                    <div class="col-md-4">
                        <div class="form-check">
                            <input type="checkbox" name="products[]" value="{{ $product->id }}" class="form-check-input" id="p{{ $product->id }}">
                            <label for="p{{ $product->id }}" class="form-check-label">
                                {{ $product->name }} (৳{{ number_format($product->price, 2) }})
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card p-3 mb-3">
            <h5>Payment Method</h5>
            <select name="payment_method" class="form-select" required>
                <option value="cod">Cash on Delivery</option>
                <option value="online">Online Payment</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Create Order</button>
    </form>
</div>
@endsection
