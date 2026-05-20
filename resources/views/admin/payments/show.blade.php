@extends('admin.layouts')
@section('title', 'Payment Details')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold text-uppercase mb-0" style="letter-spacing: 1px; color:#6f4e37;">
            <i class="bi bi-receipt-cutoff me-2"></i> Payment Details
        </h4>
        <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left-circle me-1"></i> Back to List
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h6 class="fw-bold">Payment Information</h6>
                    <p><strong>Payment ID:</strong> {{ $payment->id }}</p>
                    <p><strong>Order ID:</strong> {{ $payment->order->order_id ?? '-' }}</p>
                    <p><strong>Amount:</strong> {{ number_format($payment->amount, 2) }} TK</p>
                    <p><strong>Status:</strong> 
                        <span class="badge 
                            {{ $payment->status == 'completed' ? 'bg-success' : 
                               ($payment->status == 'pending' ? 'bg-warning text-dark' : 'bg-danger') }}">
                            {{ ucfirst($payment->status) }}
                        </span>
                    </p>
                    <p><strong>Method:</strong> {{ ucfirst($payment->method) }}</p>
                    <p><strong>Transaction ID:</strong> {{ $payment->transaction_id ?? '-' }}</p>
                </div>

                <div class="col-md-6">
                    <h6 class="fw-bold">User Information</h6>
                    <p><strong>Name:</strong> {{ $payment->user->name ?? '-' }}</p>
                    <p><strong>Email:</strong> {{ $payment->user->email ?? '-' }}</p>
                    <p><strong>Phone:</strong> {{ $payment->user->phone ?? '-' }}</p>
                    <p><strong>Payment Date:</strong> {{ $payment->created_at->format('d M Y, h:i A') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .btn-primary {
        background-color: #A75E30;
        border-color: #A75E30;
    }
    .btn-primary:hover {
        background-color: #B46A3B;
        border-color: #B46A3B;
    }
</style>
@endsection
