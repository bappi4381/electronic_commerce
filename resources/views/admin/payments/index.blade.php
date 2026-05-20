@extends('admin.layouts')
@section('title', 'Payment List')

@section('content')
<div class="container py-4">
    <div class="row align-items-center mb-4">
        <!-- Left: Payment List Title -->
        <div class="col-3">
            <h4 class="fw-bold text-uppercase mb-0" style="letter-spacing: 1px; color:#6f4e37;">
                <i class="bi bi-cash-stack me-2"></i> Payment List
            </h4>
        </div>

        <!-- Middle: Search -->
        <div class="col-6">
            <form action="{{ route('payments.index') }}" method="GET" class="d-flex">
                <input type="text" name="search" class="form-control rounded-start shadow-sm" 
                    placeholder="Search by order ID, user name..." 
                    value="{{ request('search') }}" autocomplete="off" style="height:45px;">
                <button type="submit" class="btn btn-primary rounded-end shadow-sm ms-1" style="height:45px;">
                    <i class="bi bi-search"></i>
                </button>
                @if(request('search'))
                    <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary ms-1 shadow-sm" style="height:45px;">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <!-- Right: Export Button -->
        <div class="col-3 text-end">
            <a href="{{ route('payments.export',['type' => 'xlsx']) }}" class="btn btn-success shadow-sm">
                <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
            </a>
        </div>
    </div>

    {{-- Success message --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Payments Table --}}
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark text-center">
                <tr>
                    <th>Payment ID</th>
                    <th>Order ID</th>
                    <th>User</th>
                    <th>Amount (TK)</th>
                    <th>Status</th>
                    <th>Method</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payments as $payment)
                    <tr class="text-center">
                        <td>{{ $payment->id }}</td>
                        <td>{{ $payment->order->order_id ?? '-' }}</td>
                        <td>{{ $payment->user->name ?? '-' }}</td>
                        <td>{{ number_format($payment->amount, 2) }}</td>
                        <td>
                            <span class="badge 
                                {{ $payment->status == 'completed' ? 'bg-success' : 
                                   ($payment->status == 'pending' ? 'bg-warning text-dark' : 'bg-danger') }}">
                                {{ ucfirst($payment->status) }}
                            </span>
                        </td>
                        <td>{{ ucfirst($payment->method) }}</td>
                        <td>{{ $payment->created_at->format('d M Y, h:i A') }}</td>
                        <td>
                            <a href="{{ route('payments.show', $payment) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">No payments found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-3">
        {{ $payments->links() }}
    </div>
</div>

<style>
    .table td, .table th { vertical-align: middle; }
    .btn-primary { background-color: #A75E30; border-color: #A75E30; }
    .btn-primary:hover { background-color: #B46A3B; border-color: #B46A3B; }
</style>
@endsection
