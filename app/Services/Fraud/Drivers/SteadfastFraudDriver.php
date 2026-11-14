<?php

namespace App\Services\Fraud\Drivers;

use App\Services\Fraud\FraudCheckException;
use Illuminate\Support\Facades\Http;

/**
 * Steadfast merchant fraud check with the Api-Key / Secret-Key from Settings › Courier.
 * The endpoint path is configurable (config/fraud.php) in case Steadfast changes it.
 */
class SteadfastFraudDriver extends HttpDriver
{
    public function name(): string
    {
        return 'steadfast';
    }

    protected function label(): string
    {
        return 'Steadfast';
    }

    public function lookup(string $phone, ?int $excludeOrderId = null): array
    {
        if (!config('services.steadfast.api_key') || !config('services.steadfast.secret_key')) {
            throw new FraudCheckException('Steadfast API keys are not set (Settings › Courier).');
        }

        $url = rtrim((string) config('services.steadfast.base_url'), '/') . str_replace('{phone}', $phone, (string) config('fraud.drivers.steadfast.path'));

        $data = $this->send(fn () => Http::withHeaders([
            'Api-Key' => config('services.steadfast.api_key'),
            'Secret-Key' => config('services.steadfast.secret_key'),
        ])->acceptJson()->timeout($this->timeout())->connectTimeout(10)->get($url));

        $stats = isset($data['data']) && is_array($data['data']) ? $data['data'] : $data;
        $delivered = $this->number($stats, ['total_delivered', 'delivered', 'success_parcel', 'success']);
        $cancelled = $this->number($stats, ['total_cancelled', 'cancelled', 'cancelled_parcel', 'cancel']);
        $total = $this->number($stats, ['total_parcels', 'total_parcel', 'total']);

        if ($delivered === null && $cancelled === null && $total === null) {
            throw new FraudCheckException('Steadfast returned an unexpected response.');
        }

        $delivered ??= max(0, (int) $total - (int) $cancelled);
        $cancelled ??= max(0, (int) $total - $delivered);
        $total ??= $delivered + $cancelled;

        return [
            'total' => $total,
            'delivered' => $delivered,
            'cancelled' => $cancelled,
            'breakdown' => ['Steadfast' => compact('total', 'delivered', 'cancelled')],
            'raw' => compact('total', 'delivered', 'cancelled'),
        ];
    }
}
