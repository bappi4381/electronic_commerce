<?php

namespace App\Exports;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PaymentsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    public function __construct(private Builder $query)
    {
    }

    public function query()
    {
        return $this->query->with(['order', 'user'])->orderByDesc('id');
    }

    public function headings(): array
    {
        return ['Payment #', 'Transaction ID', 'Order ID', 'Customer', 'Customer Email', 'Method', 'Status', 'Amount', 'Currency', 'Payment Date'];
    }

    /**
     * @param  Payment  $payment
     */
    public function map($payment): array
    {
        return [
            $payment->id,
            $payment->transaction_id,
            $payment->order?->order_id,
            $payment->user?->name ?? $payment->order?->customer_name,
            $payment->user?->email ?? $payment->order?->customer_email,
            $payment->payment_method,
            $payment->status,
            (float) $payment->amount,
            $payment->currency,
            optional($payment->payment_date ?? $payment->created_at)->format('Y-m-d H:i'),
        ];
    }
}
