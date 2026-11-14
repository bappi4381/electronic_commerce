<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourierLog;
use App\Models\Order;
use App\Models\OrderCourier;
use App\Services\Courier\CourierBookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CourierWebhookController extends Controller
{
    public function __construct(private CourierBookingService $couriers)
    {
    }

    /**
     * Steadfast sends two notification types (Bearer token checked by courier.webhook:steadfast):
     *  delivery_status: {consignment_id, invoice, cod_amount, status, delivery_charge, tracking_message, updated_at}
     *  tracking_update: {consignment_id, invoice, tracking_message, updated_at}
     */
    public function steadfastWebhook(Request $request)
    {
        $payload = $request->all();
        $type = (string) ($payload['notification_type'] ?? (isset($payload['status']) ? 'delivery_status' : 'tracking_update'));

        if (empty($payload['consignment_id']) && empty($payload['invoice'])) {
            return response()->json(['status' => 'error', 'message' => 'consignment_id or invoice is required.'], 422);
        }
        if ($type === 'delivery_status' && empty($payload['status'])) {
            return response()->json(['status' => 'error', 'message' => 'status is required for delivery_status.'], 422);
        }

        $parcel = $this->findSteadfastParcel($payload);

        if (!$parcel) {
            Log::warning('Steadfast webhook for unknown consignment', [
                'consignment_id' => $payload['consignment_id'] ?? null,
                'invoice' => $payload['invoice'] ?? null,
                'type' => $type,
            ]);

            // 200 so Steadfast does not keep retrying a parcel this shop never booked.
            return response()->json(['status' => 'success', 'message' => 'Webhook received; consignment not found.']);
        }

        $message = trim((string) ($payload['tracking_message'] ?? ''));

        if ($type === 'delivery_status') {
            $attributes = isset($payload['delivery_charge']) && is_numeric($payload['delivery_charge'])
                ? ['shipping_charge' => (float) $payload['delivery_charge']]
                : [];

            $this->couriers->applyStatus($parcel, (string) $payload['status'], $payload,
                'Steadfast webhook' . ($message !== '' ? ': ' . $message : ''), null, $attributes);
        }

        CourierLog::create([
            'order_id' => $parcel->order_id,
            'action' => 'webhook_' . $type,
            'status_to' => $type === 'delivery_status' ? $parcel->fresh()->status : null,
            'request_payload' => $payload,
            'remarks' => $message !== '' ? mb_substr($message, 0, 1000) : null,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Webhook received successfully.']);
    }

    /**
     * Pathao webhook: {consignment_id, merchant_order_id, order_status | event: "order.delivered", ...}
     */
    public function pathaoWebhook(Request $request)
    {
        $payload = $request->all();
        Log::info('Pathao Webhook Received', $payload);

        $consignmentId = $payload['consignment_id'] ?? null;
        $newStatus = $payload['order_status'] ?? (isset($payload['event']) ? str_replace('order.', '', (string) $payload['event']) : null);

        if (!$consignmentId || !$newStatus) {
            return response()->json(['message' => 'Invalid Payload'], 400);
        }

        $parcel = OrderCourier::where('consignment_id', (string) $consignmentId)->where('courier_name', 'pathao')->first();

        if (!$parcel) {
            Log::warning('Courier webhook for unknown consignment', ['courier' => 'pathao', 'consignmentId' => $consignmentId, 'status' => $newStatus]);

            return response()->json(['message' => 'Unknown consignment'], 202);
        }

        $this->couriers->applyStatus($parcel, (string) $newStatus, $payload, 'Pathao webhook');

        return response()->json(['message' => 'Success']);
    }

    private function findSteadfastParcel(array $payload): ?OrderCourier
    {
        $query = OrderCourier::where('courier_name', 'steadfast')->whereNotNull('consignment_id');

        if (!empty($payload['consignment_id'])) {
            return (clone $query)->where('consignment_id', (string) $payload['consignment_id'])->first();
        }

        $orderId = Order::withTrashed()->where('order_id', (string) $payload['invoice'])->value('id');

        return $orderId ? $query->where('order_id', $orderId)->first() : null;
    }
}
