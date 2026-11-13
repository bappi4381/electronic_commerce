<?php

namespace App\Services\Courier;

use App\Enums\OrderStatus;
use App\Exceptions\CourierException;
use App\Models\Order;
use App\Models\OrderCourier;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Pathao Courier merchant API (OAuth password grant, Bearer token).
 */
class PathaoService implements CourierProvider
{
    private const TOKEN_CACHE_KEY = 'pathao.access_token';

    public function createParcel(Order $order, array $options): array
    {
        $storeId = $options['store_id'] ?? config('services.pathao.store_id');

        if (!$storeId) {
            throw new CourierException('Pathao store is not configured. Set PATHAO_STORE_ID.');
        }

        $payload = array_filter([
            'store_id'            => (int) $storeId,
            'merchant_order_id'   => $order->order_id,
            'recipient_name'      => mb_substr($order->customerDisplayName(), 0, 100),
            'recipient_phone'     => $this->phone($order),
            'recipient_address'   => mb_substr(trim(implode(', ', array_filter([$order->shipping_address, $order->region, $order->city]))), 0, 220),
            'recipient_city'      => $options['city_id'] ?? null,
            'recipient_zone'      => $options['zone_id'] ?? null,
            'recipient_area'      => $options['area_id'] ?? null,
            'delivery_type'       => 48,
            'item_type'           => 2,
            'special_instruction' => $options['note'] ?? $order->order_notes,
            'item_quantity'       => max(1, (int) $order->orderItems->sum('quantity')),
            'item_weight'         => (float) ($options['item_weight'] ?? 0.5),
            'amount_to_collect'   => (int) round((float) ($options['cod_amount'] ?? 0)),
            'item_description'    => mb_substr($order->orderItems->map(fn ($i) => ($i->product?->name ?? 'Item') . ' x' . $i->quantity)->implode(', '), 0, 250),
        ], fn ($value) => $value !== null && $value !== '');

        $data = $this->request('post', '/aladdin/api/v1/orders', $payload, 'create the parcel');
        $parcel = $data['data'] ?? [];

        if (empty($parcel['consignment_id'])) {
            throw new CourierException('Pathao did not return a consignment: ' . ($data['message'] ?? 'unknown response'));
        }

        return [
            'consignment_id'  => (string) $parcel['consignment_id'],
            'tracking_code'   => (string) $parcel['consignment_id'],
            'status'          => (string) ($parcel['order_status'] ?? 'Pending'),
            'shipping_charge' => (float) ($parcel['delivery_fee'] ?? 0),
            'response'        => $data,
        ];
    }

    public function fetchStatus(OrderCourier $parcel): string
    {
        $data = $this->request('get', '/aladdin/api/v1/orders/' . urlencode($parcel->consignment_id) . '/info', [], 'fetch the parcel status');
        $status = $data['data']['order_status'] ?? null;

        if (!$status) {
            throw new CourierException('Pathao did not return an order status.');
        }

        return (string) $status;
    }

    public function orderStatusFor(string $courierStatus): ?OrderStatus
    {
        return match (strtolower(str_replace(['_', '-'], ' ', $courierStatus))) {
            'delivered', 'partial delivery' => OrderStatus::Delivered,
            'returned', 'return'            => OrderStatus::Returned,
            default                         => null,
        };
    }

    /**
     * @return list<array{city_id: int, city_name: string}>
     */
    public function cities(): array
    {
        return Cache::remember('pathao.cities', now()->addDay(), fn () => $this->request('get', '/aladdin/api/v1/city-list', [], 'load cities')['data']['data'] ?? []);
    }

    /**
     * @return list<array{zone_id: int, zone_name: string}>
     */
    public function zones(int $cityId): array
    {
        return Cache::remember("pathao.zones.{$cityId}", now()->addDay(), fn () => $this->request('get', "/aladdin/api/v1/cities/{$cityId}/zone-list", [], 'load zones')['data']['data'] ?? []);
    }

    private function request(string $method, string $path, array $payload, string $action, bool $retried = false): array
    {
        $response = Http::baseUrl($this->baseUrl())
            ->withToken($this->token())
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->{$method}($path, $payload);

        if ($response->status() === 401 && !$retried) {
            Cache::forget(self::TOKEN_CACHE_KEY);

            return $this->request($method, $path, $payload, $action, true);
        }

        return $this->decode($response, $action);
    }

    private function token(): string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);
        if ($cached) {
            return $cached;
        }

        foreach (['client_id', 'client_secret', 'username', 'password'] as $key) {
            if (!config("services.pathao.{$key}")) {
                throw new CourierException('Pathao is not configured. Set PATHAO_CLIENT_ID, PATHAO_CLIENT_SECRET, PATHAO_USERNAME and PATHAO_PASSWORD.');
            }
        }

        $response = Http::baseUrl($this->baseUrl())->acceptJson()->asJson()->timeout(30)->post('/aladdin/api/v1/issue-token', [
            'client_id'     => config('services.pathao.client_id'),
            'client_secret' => config('services.pathao.client_secret'),
            'username'      => config('services.pathao.username'),
            'password'      => config('services.pathao.password'),
            'grant_type'    => 'password',
        ]);

        $data = $this->decode($response, 'authenticate');

        if (empty($data['access_token'])) {
            throw new CourierException('Pathao did not return an access token.');
        }

        Cache::put(self::TOKEN_CACHE_KEY, $data['access_token'], now()->addSeconds(max(60, (int) ($data['expires_in'] ?? 3600) - 300)));

        return $data['access_token'];
    }

    private function decode(Response $response, string $action): array
    {
        $data = $response->json() ?? [];

        if ($response->failed()) {
            $message = $data['message'] ?? ('HTTP ' . $response->status());
            if (!empty($data['errors'])) {
                $message .= ' ' . json_encode($data['errors']);
            }

            throw new CourierException("Pathao could not {$action}: {$message}");
        }

        return $data;
    }

    private function baseUrl(): string
    {
        return rtrim(config('services.pathao.base_url'), '/');
    }

    private function phone(Order $order): string
    {
        $phone = preg_replace('/\D+/', '', (string) $order->customerDisplayPhone());
        $phone = str_starts_with($phone, '880') ? substr($phone, 2) : $phone;

        if (strlen($phone) !== 11) {
            throw new CourierException('Pathao needs an 11-digit recipient phone number; this order has "' . $order->customerDisplayPhone() . '".');
        }

        return $phone;
    }
}
