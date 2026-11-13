<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
     * Steadfast delivery-status webhook: {notification_type, consignment_id, invoice, status, tracking_message, ...}
     */
    public function steadfastWebhook(Request $request)
    {
        $payload = $request->all();
        Log::info('Steadfast Webhook Received', $payload);

        if (empty($payload['consignment_id']) || empty($payload['status'])) {
            return response()->json(['message' => 'Invalid Payload'], 400);
        }

        return $this->apply('steadfast', (string) $payload['consignment_id'], (string) $payload['status'], $payload,
            'Steadfast webhook' . (!empty($payload['tracking_message']) ? ': ' . $payload['tracking_message'] : ''));
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

        return $this->apply('pathao', (string) $consignmentId, (string) $newStatus, $payload, 'Pathao webhook');
    }

    private function apply(string $courier, string $consignmentId, string $status, array $payload, string $remarks)
    {
        $parcel = OrderCourier::where('consignment_id', $consignmentId)->where('courier_name', $courier)->first();

        if (!$parcel) {
            Log::warning('Courier webhook for unknown consignment', compact('courier', 'consignmentId', 'status'));

            return response()->json(['message' => 'Unknown consignment'], 202);
        }

        $this->couriers->applyStatus($parcel, $status, $payload, $remarks);

        return response()->json(['message' => 'Success']);
    }
}
