<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PaymentsExport;

class PaymentController extends Controller
{
    public const STATUSES = ['pending', 'success', 'failed', 'partially_refunded', 'refunded'];
    public const GATEWAYS = ['sslcommerz' => 'SSLCommerz', 'cod' => 'Cash on delivery'];
    private const EXPORT_TYPES = ['xlsx' => \Maatwebsite\Excel\Excel::XLSX, 'csv' => \Maatwebsite\Excel\Excel::CSV];

    public function index(Request $request)
    {
        $filters = $this->filters($request);

        $payments = $this->query($filters)
            ->with(['order', 'user'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.payments.index', [
            'payments' => $payments,
            'filters' => $filters,
            'statuses' => self::STATUSES,
            'gateways' => self::GATEWAYS,
            'totalAmount' => (clone $this->query($filters))->sum('amount'),
        ]);
    }

    public function show($id)
    {
        $payment = Payment::with(['order', 'user'])->findOrFail($id);

        return view('admin.payments.show', compact('payment'));
    }

    public function export(Request $request, string $type)
    {
        abort_unless(isset(self::EXPORT_TYPES[$type]), 404);

        $fileName = 'payments_' . now()->format('Y_m_d_H_i') . '.' . $type;

        return Excel::download(new PaymentsExport($this->query($this->filters($request))), $fileName, self::EXPORT_TYPES[$type]);
    }

    private function filters(Request $request): array
    {
        return $request->validate([
            'search'    => 'nullable|string|max:100',
            'status'    => 'nullable|in:' . implode(',', self::STATUSES),
            'gateway'   => 'nullable|in:' . implode(',', array_keys(self::GATEWAYS)),
            'from_date' => 'nullable|date',
            'to_date'   => 'nullable|date|after_or_equal:from_date',
        ]);
    }

    private function query(array $filters): Builder
    {
        return Payment::query()
            ->when($filters['status'] ?? null, fn (Builder $q, $status) => $q->where('status', $status))
            ->when($filters['gateway'] ?? null, fn (Builder $q, $gateway) => $q->where('payment_method', $gateway))
            ->when($filters['from_date'] ?? null, fn (Builder $q, $date) => $q->whereDate('payment_date', '>=', $date))
            ->when($filters['to_date'] ?? null, fn (Builder $q, $date) => $q->whereDate('payment_date', '<=', $date))
            ->when($filters['search'] ?? null, function (Builder $q, string $search) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';

                // Grouped so the OR conditions cannot bypass the other filters.
                $q->where(function (Builder $q) use ($like) {
                    $q->where('transaction_id', 'like', $like)
                        ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', $like)->orWhere('email', 'like', $like))
                        ->orWhereHas('order', fn (Builder $o) => $o->where('order_id', 'like', $like));
                });
            });
    }
}
