<?php

namespace App\Http\Controllers\Admin\Order;

use App\Exceptions\CourierException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderCourier;
use App\Services\Courier\CourierBookingService;
use App\Services\Courier\PathaoService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderCourierController extends Controller
{
    public function __construct(private CourierBookingService $couriers)
    {
    }

    public function store(Request $request, Order $order)
    {
        $data = $request->validate([
            'courier'     => ['required', Rule::in(array_keys(OrderCourier::COURIERS))],
            'cod_amount'  => 'required|numeric|min:0|max:999999',
            'note'        => 'nullable|string|max:250',
            'city_id'     => 'nullable|integer',
            'zone_id'     => 'nullable|integer',
            'area_id'     => 'nullable|integer',
            'item_weight' => 'nullable|numeric|min:0.1|max:50',
        ]);

        try {
            $parcel = $this->couriers->book($order, $data['courier'], $data, auth('admin')->id());
        } catch (CourierException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', "Booked with {$parcel->courierLabel()}. Consignment {$parcel->consignment_id}" . ($parcel->tracking_code ? ", tracking {$parcel->tracking_code}." : '.'));
    }

    public function refresh(Order $order)
    {
        $parcel = $order->orderCourier;

        if (!$parcel) {
            return back()->with('error', 'This order has no courier booking.');
        }

        try {
            $parcel = $this->couriers->refresh($parcel, auth('admin')->id());
        } catch (CourierException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$parcel->courierLabel()} status: {$parcel->status}.");
    }

    public function pathaoCities(PathaoService $pathao)
    {
        try {
            return response()->json($pathao->cities());
        } catch (CourierException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function pathaoZones(PathaoService $pathao, int $city)
    {
        try {
            return response()->json($pathao->zones($city));
        } catch (CourierException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
