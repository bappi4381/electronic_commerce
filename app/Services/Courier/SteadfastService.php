<?php

namespace App\Services\Courier;

use App\Enums\OrderStatus;
use App\Exceptions\CourierException;
use App\Models\Order;
use App\Models\OrderCourier;
use App\Support\BdPhone;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Steadfast (Packzy) merchant API: Api-Key / Secret-Key headers, JSON bodies.
 * https://portal.packzy.com/api/v1 — create_order, status_by_cid/{id}, get_balance.
 */
class SteadfastService implements CourierInterface
{
    use LogsCourierCalls;

    public function createParcel(Order $order, array $options): array
    {
        $phone = BdPhone::normalize($order->customerDisplayPhone());
        if (!$phone) {
            throw new CourierException('Recipient phone "' . ($order->customerDisplayPhone() ?: 'missing') . '" is not a valid Bangladeshi mobile number (01XXXXXXXXX). Fix it in "Edit order" and send again.');
        }
        $altPhone = BdPhone::normalize($order->alt_phone);

        $payload = array_filter([
            'invoice'           => $order->order_id,
            'recipient_name'    => mb_substr($order->customerDisplayName(), 0, 100),
            'recipient_phone'   => $phone,
            'alternative_phone' => $altPhone !== $phone ? $altPhone : null,
            'recipient_email'   => $order->customerDisplayEmail(),
            'recipient_address' => mb_substr(trim(preg_replace('/\s+/', ' ', implode(', ', array_filter([$order->shipping_address, $order->region, $order->city === 'Other' ? null : $order->city, $order->postal_code])))), 0, 250),
            'cod_amount'        => round((float) ($options['cod_amount'] ?? 0), 2),
            'note'              => mb_substr((string) ($options['note'] ?? $order->order_notes), 0, 250) ?: null,
        ], fn ($value) => $value !== null && $value !== '');

        $data = $this->call('post', '/create_order', $payload, 'create_parcel', $order->id, 'create the parcel');
        $consignment = $data['consignment'] ?? null;

        if (!is_array($consignment) || empty($consignment['consignment_id'])) {
            throw new CourierException('Steadfast did not return a consignment: ' . ($data['message'] ?? 'unknown response'));
        }

        return [
            'consignment_id'  => (string) $consignment['consignment_id'],
            'tracking_code'   => $consignment['tracking_code'] ?? null,
            'status'          => CourierStatus::normalize($consignment['status'] ?? 'in_review'),
            'shipping_charge' => 0.0,
            'response'        => $data,
        ];
    }

    public function getStatus(OrderCourier $parcel): string
    {
        $data = $this->call('get', '/status_by_cid/' . urlencode($parcel->consignment_id), [], 'status_check', $parcel->order_id, 'fetch the parcel status');

        if (empty($data['delivery_status'])) {
            throw new CourierException('Steadfast did not return a delivery status.');
        }

        return CourierStatus::normalize($data['delivery_status']);
    }

    public function getBalance(): ?float
    {
        $data = $this->call('get', '/get_balance', [], 'balance', null, 'read the balance');

        if (!isset($data['current_balance']) || !is_numeric($data['current_balance'])) {
            throw new CourierException('Steadfast did not return a balance.');
        }

        return (float) $data['current_balance'];
    }

    public function orderStatusFor(string $courierStatus): ?OrderStatus
    {
        return match (CourierStatus::normalize($courierStatus)) {
            'delivered', 'partial_delivered' => OrderStatus::Delivered,
            'cancelled'                      => OrderStatus::Returned,
            default                          => null,
        };
    }

    public function configured(): bool
    {
        return filled(config('services.steadfast.api_key')) && filled(config('services.steadfast.secret_key'));
    }

    /** One API call: 15 s timeout, logged to courier_logs, errors turned into a readable CourierException. */
    private function call(string $method, string $uri, array $payload, string $action, ?int $orderId, string $description): array
    {
        try {
            $response = $this->client()->{$method}($uri, $payload);
        } catch (ConnectionException $e) {
            $message = "Steadfast did not answer within {$this->timeout} seconds while trying to {$description}. Please try again.";
            $this->logCall($orderId, $action, $payload, null, $message . ' (' . $e->getMessage() . ')');

            throw new CourierException($message);
        }

        try {
            $data = $this->decode($response, $description);
        } catch (CourierException $e) {
            $this->logCall($orderId, $action, $payload, $response, $e->getMessage());

            throw $e;
        }

        $this->logCall($orderId, $action, $payload, $response);

        return $data;
    }

    private function client(): PendingRequest
    {
        if (!$this->configured()) {
            throw new CourierException('Steadfast is not configured. Add the API key and secret key in Settings › Courier.');
        }

        return Http::baseUrl(rtrim((string) config('services.steadfast.base_url'), '/'))
            ->withHeaders(['Api-Key' => config('services.steadfast.api_key'), 'Secret-Key' => config('services.steadfast.secret_key')])
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->connectTimeout(10);
    }

    private function decode(Response $response, string $description): array
    {
        $data = $response->json() ?? [];

        if ($response->failed() || (isset($data['status']) && (int) $data['status'] !== 200)) {
            $message = $data['message'] ?? null;
            if (!empty($data['errors'])) {
                $message = trim(($message ? $message . ' ' : '') . collect($data['errors'])->flatten()->implode(' '));
            }

            throw new CourierException("Steadfast could not {$description}: " . ($message ?: 'HTTP ' . $response->status()));
        }

        return $data;
    }
}
