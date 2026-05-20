<?php

namespace App\Exports;

use App\Models\Payment;
use Maatwebsite\Excel\Concerns\FromCollection;

class PaymentsExport implements FromCollection
{
    public function collection()
    {
        return Payment::all(['id', 'order_id', 'user_id', 'amount', 'payment_method', 'status', 'payment_date']);
    }
    
}
