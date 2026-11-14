<?php

namespace App\Services\Courier;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderCourier;

/**
 * One courier company (Steadfast, Pathao, later RedX…). Implementations must use a 15 s timeout,
 * log every call to courier_logs and throw CourierException with a message an admin can act on.
 */
interface CourierInterface
{
    /**
     * Book a parcel for the order.
     *
     * @param  array<string, mixed>  $options  cod_amount, note, plus provider-specific fields
     * @return array{consignment_id: string, tracking_code: ?string, status: string, shipping_charge: float, response: array}
     *
     * @throws \App\Exceptions\CourierException
     */
    public function createParcel(Order $order, array $options): array;

    /**
     * Current courier-side status of the parcel (normalised with CourierStatus::normalize()).
     *
     * @throws \App\Exceptions\CourierException
     */
    public function getStatus(OrderCourier $parcel): string;

    /**
     * Merchant account balance in BDT, or null when the courier has no balance API.
     *
     * @throws \App\Exceptions\CourierException
     */
    public function getBalance(): ?float;

    /**
     * Order status implied by a courier status, or null when it should not change the order.
     */
    public function orderStatusFor(string $courierStatus): ?OrderStatus;

    /** Credentials are present; when false nothing is sent and no order is marked failed. */
    public function configured(): bool;
}
