<?php

namespace App\Services\Fraud;

/**
 * One source of courier delivery history (Steadfast, BD Courier, this store's own orders…).
 */
interface FraudDriver
{
    public function name(): string;

    /**
     * Delivery history for a normalised 01XXXXXXXXX number.
     *
     * @return array{total: int, delivered: int, cancelled: int, breakdown: array<string, array{total: int, delivered: int, cancelled: int}>, raw: array}
     *
     * @throws FraudCheckException when the source cannot answer (not configured, timeout, bad response)
     */
    public function lookup(string $phone, ?int $excludeOrderId = null): array;
}
