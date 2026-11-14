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
            'cod_amount'  => 'nullable|numeric|min:0|max:999999',
            'note'        => 'nullable|string|max:250',
            'city_id'     => 'nullable|integer',
            'zone_id'     => 'nullable|integer',
            'area_id'     => 'nullable|integer',
            'item_weight' => 'nullable|numeric|min:0.1|max:50',
        ]);

        if (!isset($data['cod_amount']) || $data['cod_amount'] === '') {
            unset($data['cod_amount']);
        }

        try {
            $parcel = $this->couriers->book($order, $data['courier'], $data, auth('admin')->id());
        } catch (CourierException $e) {
            return back()->withInput()->with('error', "Order {$order->order_id} was not sent: {$e->getMessage()}");
        }

        return back()->with('success', "Order {$order->order_id} sent to {$parcel->courierLabel()}. Consignment {$parcel->consignment_id}" . ($parcel->tracking_code ? ", tracking {$parcel->tracking_code}." : '.'));
    }

    public function bulkStore(Request $request)
    {
        $data = $request->validate([
            'courier'     => ['required', Rule::in(['steadfast'])],
            'order_ids'   => 'required|array|min:1|max:' . CourierBookingService::BULK_LIMIT,
            'order_ids.*' => 'integer|distinct',
        ], [
            'order_ids.required' => 'Select at least one order.',
            'order_ids.max' => 'Send at most ' . CourierBookingService::BULK_LIMIT . ' orders at a time.',
        ]);

        $orders = Order::with('orderCourier')->whereIn('id', $data['order_ids'])->orderBy('id')->get();
        $result = $this->couriers->bookMany($orders, $data['courier'], auth('admin')->id());

        $sent = count($result['sent']);
        $failed = count($result['failed']);
        $label = OrderCourier::COURIERS[$data['courier']];

        $redirect = $failed ? back()->with('bulk_failures', $result['failed']) : back();

        if ($sent === 0) {
            return $redirect->with('error', "No orders were sent to {$label}." . ($failed ? " {$failed} could not be sent; see the reasons below." : ''));
        }

        return $redirect->with('success', "{$sent} " . str('order')->plural($sent) . " sent to {$label}." . ($failed ? " {$failed} could not be sent; see the reasons below." : ''));
    }

    public function refresh(Order $order)
    {
        $parcel = $order->orderCourier;

        if (!$parcel?->isBooked()) {
            return back()->with('error', 'This order has not been sent to a courier yet.');
        }

        try {
            $parcel = $this->couriers->refresh($parcel, auth('admin')->id());
        } catch (CourierException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$parcel->courierLabel()} status: {$parcel->statusLabel()}.");
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
