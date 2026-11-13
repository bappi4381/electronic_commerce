<?php

namespace App\Services\Courier;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderCourier;

interface CourierProvider
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
     * Current courier-side status string for the parcel.
     *
     * @throws \App\Exceptions\CourierException
     */
    public function fetchStatus(OrderCourier $parcel): string;

    /**
     * Order status implied by a courier status, or null when it should not change the order.
     */
    public function orderStatusFor(string $courierStatus): ?OrderStatus;
}
