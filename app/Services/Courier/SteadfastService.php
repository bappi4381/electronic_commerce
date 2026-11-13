<?php

namespace App\Services\Courier;

use App\Enums\OrderStatus;
use App\Exceptions\CourierException;
use App\Models\Order;
use App\Models\OrderCourier;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Steadfast (Packzy) merchant API: Api-Key / Secret-Key headers, JSON bodies.
 */
class SteadfastService implements CourierProvider
{
    public function createParcel(Order $order, array $options): array
    {
        $response = $this->client()->post('/create_order', [
            'invoice'           => $order->order_id,
            'recipient_name'    => mb_substr($order->customerDisplayName(), 0, 100),
            'recipient_phone'   => $this->phone($order),
            'recipient_address' => mb_substr(trim(implode(', ', array_filter([$order->shipping_address, $order->region, $order->city]))), 0, 250),
            'cod_amount'        => round((float) ($options['cod_amount'] ?? 0), 2),
            'note'              => $options['note'] ?? $order->order_notes,
        ]);

        $data = $this->decode($response, 'create the parcel');
        $consignment = $data['consignment'] ?? null;

        if (!is_array($consignment) || empty($consignment['consignment_id'])) {
            throw new CourierException('Steadfast did not return a consignment: ' . ($data['message'] ?? 'unknown response'));
        }

        return [
            'consignment_id'  => (string) $consignment['consignment_id'],
            'tracking_code'   => $consignment['tracking_code'] ?? null,
            'status'          => (string) ($consignment['status'] ?? 'in_review'),
            'shipping_charge' => 0.0,
            'response'        => $data,
        ];
    }

    public function fetchStatus(OrderCourier $parcel): string
    {
        $data = $this->decode($this->client()->get('/status_by_cid/' . urlencode($parcel->consignment_id)), 'fetch the parcel status');

        if (empty($data['delivery_status'])) {
            throw new CourierException('Steadfast did not return a delivery status.');
        }

        return (string) $data['delivery_status'];
    }

    public function orderStatusFor(string $courierStatus): ?OrderStatus
    {
        return match (strtolower($courierStatus)) {
            'delivered', 'partial_delivered' => OrderStatus::Delivered,
            'cancelled'                      => OrderStatus::Returned,
            default                          => null,
        };
    }

    private function client(): PendingRequest
    {
        $key = config('services.steadfast.api_key');
        $secret = config('services.steadfast.secret_key');

        if (!$key || !$secret) {
            throw new CourierException('Steadfast is not configured. Set STEADFAST_API_KEY and STEADFAST_SECRET_KEY.');
        }

        return Http::baseUrl(rtrim(config('services.steadfast.base_url'), '/'))
            ->withHeaders(['Api-Key' => $key, 'Secret-Key' => $secret])
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    private function decode(Response $response, string $action): array
    {
        $data = $response->json() ?? [];

        if ($response->failed() || (isset($data['status']) && (int) $data['status'] !== 200)) {
            $message = $data['message'] ?? $data['errors'] ?? ('HTTP ' . $response->status());
            throw new CourierException("Steadfast could not {$action}: " . (is_array($message) ? json_encode($message) : $message));
        }

        return $data;
    }

    private function phone(Order $order): string
    {
        $phone = preg_replace('/\D+/', '', (string) $order->customerDisplayPhone());
        $phone = str_starts_with($phone, '880') ? substr($phone, 2) : $phone;

        if (strlen($phone) !== 11) {
            throw new CourierException('Steadfast needs an 11-digit recipient phone number; this order has "' . $order->customerDisplayPhone() . '".');
        }

        return $phone;
    }
}
