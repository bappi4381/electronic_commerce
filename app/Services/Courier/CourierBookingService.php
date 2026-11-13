<?php

namespace App\Services\Courier;

use App\Enums\OrderStatus;
use App\Exceptions\CourierException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidOrderTransitionException;
use App\Models\CourierLog;
use App\Models\Order;
use App\Models\OrderCourier;
use App\Services\OrderStatusService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CourierBookingService
{
    public function __construct(private OrderStatusService $orderStatus)
    {
    }

    public function provider(string $courier): CourierProvider
    {
        return match ($courier) {
            'steadfast' => app(SteadfastService::class),
            'pathao'    => app(PathaoService::class),
            default     => throw new CourierException("Unknown courier \"{$courier}\"."),
        };
    }

    /**
     * Book the parcel with the courier, store the consignment and mark the order shipped.
     *
     * @throws CourierException
     */
    public function book(Order $order, string $courier, array $options, ?int $adminId): OrderCourier
    {
        $order->loadMissing('orderItems.product', 'user', 'orderCourier');

        if ($order->orderCourier) {
            throw new CourierException("This order is already booked with {$order->orderCourier->courierLabel()} (consignment {$order->orderCourier->consignment_id}).");
        }

        if ($order->statusEnum() !== OrderStatus::Processing) {
            throw new CourierException('Only orders in "Processing" can be handed to a courier.');
        }

        // The API call happens outside the DB transaction; a failure leaves nothing half-saved.
        $result = $this->provider($courier)->createParcel($order, $options);

        return DB::transaction(function () use ($order, $courier, $result, $adminId) {
            $parcel = OrderCourier::create([
                'order_id'        => $order->id,
                'admin_id'        => $adminId,
                'courier_name'    => $courier,
                'consignment_id'  => $result['consignment_id'],
                'tracking_code'   => $result['tracking_code'],
                'status'          => $result['status'],
                'shipping_charge' => $result['shipping_charge'],
            ]);

            CourierLog::create([
                'order_id'    => $order->id,
                'status_from' => null,
                'status_to'   => $result['status'],
                'payload'     => $result['response'],
                'remarks'     => "Parcel booked with {$parcel->courierLabel()}",
            ]);

            $this->orderStatus->transition(
                $order,
                OrderStatus::Shipped,
                $adminId,
                "Handed to {$parcel->courierLabel()} (consignment {$parcel->consignment_id})"
            );

            return $parcel;
        });
    }

    /**
     * Pull the latest status from the courier API.
     *
     * @throws CourierException
     */
    public function refresh(OrderCourier $parcel, ?int $adminId): OrderCourier
    {
        $status = $this->provider($parcel->courier_name)->fetchStatus($parcel);

        $this->applyStatus($parcel, $status, ['source' => 'manual refresh'], "{$parcel->courierLabel()} status refreshed", $adminId);

        return $parcel->fresh();
    }

    /**
     * Record a courier status update (from refresh or webhook) and move the order when it implies delivery/return.
     */
    public function applyStatus(OrderCourier $parcel, string $newStatus, array $payload, string $remarks, ?int $adminId = null): void
    {
        $oldStatus = $parcel->status;

        DB::transaction(function () use ($parcel, $newStatus, $oldStatus, $payload, $remarks) {
            $parcel->update(['status' => $newStatus]);

            CourierLog::create([
                'order_id'    => $parcel->order_id,
                'status_from' => $oldStatus,
                'status_to'   => $newStatus,
                'payload'     => $payload,
                'remarks'     => $remarks,
            ]);
        });

        $target = $this->provider($parcel->courier_name)->orderStatusFor($newStatus);
        $order = $parcel->order;

        if (!$target || !$order || $order->trashed() || $order->status === $target->value) {
            return;
        }

        try {
            $this->orderStatus->transition($order, $target, $adminId, "{$parcel->courierLabel()} reported \"{$newStatus}\"");
        } catch (InvalidOrderTransitionException|InsufficientStockException $e) {
            Log::warning('Courier status could not be applied to order', [
                'order_id' => $order->order_id,
                'courier_status' => $newStatus,
                'reason' => $e->getMessage(),
            ]);
        }
    }
}
