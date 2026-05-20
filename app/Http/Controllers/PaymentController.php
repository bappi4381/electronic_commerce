<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PaymentsExport;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['order', 'user']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('gateway')) {
            $query->where('payment_method', $request->gateway);
        }

        
        if ($request->filled('from_date') && $request->filled('to_date')) {
            $query->whereBetween('payment_date', [$request->from_date, $request->to_date]);
        }

       
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhereHas('order', function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%");
            });
        }

        
        $payments = $query->latest()->paginate(20);

        return view('admin.payments.index', compact('payments'));
    }

    public function show($id)
    {
        $payment = Payment::with(['order', 'user'])->findOrFail($id);

        return view('admin.payments.show', compact('payment'));
    }

    public function export($type)
    {
        $fileName = 'payments_' . now()->format('Y_m_d_H_i') . '.' . $type;
        return Excel::download(new PaymentsExport, $fileName);
    }
}
