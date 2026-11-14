<?php

namespace App\Services\Fraud\Drivers;

use App\Services\Fraud\FraudCheckException;
use App\Services\Fraud\FraudSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * BD Courier (bdcourier.com): delivery history combined across Bangladeshi couriers.
 * Response: {courierData: {pathao: {total_parcel, success_parcel, cancelled_parcel, …}, …, summary: {…}}}
 */
class BdCourierDriver extends HttpDriver
{
    public function name(): string
    {
        return 'bdcourier';
    }

    protected function label(): string
    {
        return 'BD Courier';
    }

    public function lookup(string $phone, ?int $excludeOrderId = null): array
    {
        $key = FraudSettings::bdCourierApiKey();
        if (!$key) {
            throw new FraudCheckException('BD Courier API key is not set (Settings › Fraud).');
        }

        $data = $this->send(fn () => Http::withToken($key)
            ->acceptJson()
            ->timeout($this->timeout())
            ->connectTimeout(10)
            ->post((string) config('fraud.drivers.bdcourier.url'), ['phone' => $phone]));

        $couriers = $data['courierData'] ?? $data['data'] ?? null;
        if (!is_array($couriers)) {
            throw new FraudCheckException('BD Courier returned an unexpected response.');
        }

        // Only parcel counts are kept: names, addresses and fraud reports in the response are not stored.
        $breakdown = [];
        foreach ($couriers as $name => $row) {
            if ($name === 'summary' || !is_array($row)) {
                continue;
            }
            $total = (int) $this->number($row, ['total_parcel', 'total']);
            if ($total > 0) {
                $breakdown[$row['name'] ?? Str::headline($name)] = [
                    'total' => $total,
                    'delivered' => (int) $this->number($row, ['success_parcel', 'delivered']),
                    'cancelled' => (int) $this->number($row, ['cancelled_parcel', 'cancelled']),
                ];
            }
        }

        $summary = is_array($couriers['summary'] ?? null) ? $couriers['summary'] : [];
        $total = $this->number($summary, ['total_parcel', 'total']) ?? array_sum(array_column($breakdown, 'total'));
        $delivered = $this->number($summary, ['success_parcel', 'delivered']) ?? array_sum(array_column($breakdown, 'delivered'));
        $cancelled = $this->number($summary, ['cancelled_parcel', 'cancelled']) ?? array_sum(array_column($breakdown, 'cancelled'));

        return [
            'total' => $total,
            'delivered' => $delivered,
            'cancelled' => $cancelled,
            'breakdown' => $breakdown,
            'raw' => ['summary' => compact('total', 'delivered', 'cancelled'), 'couriers' => $breakdown],
        ];
    }
}
