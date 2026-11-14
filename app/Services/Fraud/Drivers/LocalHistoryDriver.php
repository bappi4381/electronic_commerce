<?php

namespace App\Services\Fraud\Drivers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Fraud\FraudDriver;

/**
 * This store's own orders for the number. Delivered counts as success; returned parcels and orders cancelled
 * after a courier was booked count as failed. Orders still in progress are ignored.
 */
class LocalHistoryDriver implements FraudDriver
{
    public function name(): string
    {
        return 'local';
    }

    public function lookup(string $phone, ?int $excludeOrderId = null): array
    {
        $orders = Order::query()
            ->where('phone_normalized', $phone)
            ->when($excludeOrderId, fn ($q) => $q->whereKeyNot($excludeOrderId));

        $delivered = (clone $orders)->where('status', OrderStatus::Delivered->value)->count();
        $cancelled = (clone $orders)
            ->where(fn ($q) => $q->where('status', OrderStatus::Returned->value)
                ->orWhere(fn ($q) => $q->where('status', OrderStatus::Cancelled->value)
                    ->whereHas('orderCourier', fn ($c) => $c->whereNotNull('consignment_id'))))
            ->count();
        $total = $delivered + $cancelled;

        return [
            'total' => $total,
            'delivered' => $delivered,
            'cancelled' => $cancelled,
            'breakdown' => $total ? ['This store' => compact('total', 'delivered', 'cancelled')] : [],
            'raw' => ['orders_with_number' => (clone $orders)->count()],
        ];
    }
}
