<?php

namespace App\Services\Courier;

use App\Models\CourierLog;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;

/**
 * Writes one courier_logs row per API call (request body, response body, HTTP status).
 * Credentials travel in headers and are never part of the logged request.
 */
trait LogsCourierCalls
{
    /** Seconds before an API call is abandoned. */
    protected int $timeout = 15;

    protected function logCall(?int $orderId, string $action, array $request, ?Response $response, ?string $error = null): void
    {
        if (!$orderId) {
            return;
        }

        CourierLog::create([
            'order_id' => $orderId,
            'action' => $action,
            'request_payload' => $request ?: null,
            'response_payload' => $response ? ($response->json() ?? ['body' => Str::limit($response->body(), 2000)]) : null,
            'status_code' => $response?->status(),
            'remarks' => $error ? Str::limit($error, 1000) : null,
        ]);
    }
}
