<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;

class TrackingController extends Controller
{
    public function index()
    {
        return view('frontend.tracking.index');
    }

    public function track(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string',
            'phone'    => 'required|string'
        ]);

        $phone = trim($request->phone);

        $order = Order::with(['orderCourier', 'statusHistories', 'courierLogs' => function($q) {
            $q->orderBy('created_at', 'desc');
        }, 'user'])
        ->where('order_id', trim($request->order_id))
        ->where(function ($query) use ($phone) {
            $query->where('phone', $phone)
                ->orWhere('alt_phone', $phone)
                ->orWhereHas('user', fn ($q) => $q->where('phone', $phone));
        })
        ->first();

        if (!$order) {
            return redirect()->back()->with('error', 'No order found with the provided Order ID and Phone Number.');
        }

        return view('frontend.tracking.index', compact('order'));
    }
}
