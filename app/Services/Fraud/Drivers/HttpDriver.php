<?php

namespace App\Services\Fraud\Drivers;

use App\Services\Fraud\FraudCheckException;
use App\Services\Fraud\FraudDriver;
use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;

abstract class HttpDriver implements FraudDriver
{
    protected function timeout(): int
    {
        return (int) config('fraud.timeout', 15);
    }

    /** Runs the request, turning timeouts and error responses into FraudCheckException. */
    protected function send(Closure $request): array
    {
        try {
            /** @var Response $response */
            $response = $request();
        } catch (ConnectionException) {
            throw new FraudCheckException("{$this->label()} did not answer within {$this->timeout()} seconds.");
        }

        $data = $response->json();

        if ($response->failed() || !is_array($data)) {
            $message = is_array($data) ? ($data['message'] ?? $data['error'] ?? null) : null;
            throw new FraudCheckException("{$this->label()} returned HTTP {$response->status()}" . ($message ? ": {$message}" : '.'));
        }

        return $data;
    }

    /** First numeric value among the given keys. */
    protected function number(array $data, array $keys): ?int
    {
        foreach ($keys as $key) {
            if (isset($data[$key]) && is_numeric($data[$key])) {
                return (int) $data[$key];
            }
        }

        return null;
    }

    abstract protected function label(): string;
}
