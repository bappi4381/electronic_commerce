<?php

namespace App\Http\Controllers\Admin\Order;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Refund;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RefundController extends Controller
{
    public function __construct(private RefundService $refunds)
    {
    }

    public function store(Request $request, Order $order)
    {
        $data = $request->validate([
            'amount'    => 'required|numeric|min:0.01',
            'reason'    => 'required|string|max:1000',
            'method'    => ['nullable', Rule::in(array_keys(Refund::METHODS))],
            'reference' => 'nullable|string|max:255',
            'restock'   => 'nullable|boolean',
            'complete'  => 'nullable|boolean',
        ]);

        $data['restock'] = $request->boolean('restock');
        $data['complete'] = $request->boolean('complete');

        $refund = $this->refunds->create($order, $data, auth('admin')->id());

        return back()->with('success', 'Refund of Tk ' . number_format((float) $refund->amount, 2) . ($refund->isPending() ? ' recorded as pending.' : ' completed.'));
    }

    public function complete(Refund $refund)
    {
        $this->refunds->complete($refund, auth('admin')->id());

        return back()->with('success', "Refund #{$refund->id} marked as completed.");
    }

    public function reject(Refund $refund)
    {
        $this->refunds->reject($refund, auth('admin')->id());

        return back()->with('success', "Refund #{$refund->id} rejected.");
    }
}
