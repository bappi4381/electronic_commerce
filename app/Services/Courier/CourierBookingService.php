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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CourierBookingService
{
    /** Orders sent per bulk action; each call can take up to the 15 s API timeout. */
    public const BULK_LIMIT = 30;

    public function __construct(private OrderStatusService $orderStatus)
    {
    }

    public function provider(string $courier): CourierInterface
    {
        return match ($courier) {
            'steadfast' => app(SteadfastService::class),
            'pathao'    => app(PathaoService::class),
            default     => throw new CourierException("Unknown courier \"{$courier}\"."),
        };
    }

    /** Amount the rider should collect: nothing when the order is already paid, the order total otherwise. */
    public function defaultCodAmount(Order $order): float
    {
        return $order->paymentStatusEnum()?->isCollected() ? 0.0 : round((float) $order->total_price, 2);
    }

    /** Why the order cannot be sent right now, or null when it can. */
    public function blockReason(Order $order): ?string
    {
        if ($order->orderCourier?->isBooked()) {
            return "Already sent to {$order->orderCourier->courierLabel()} (tracking " . ($order->orderCourier->tracking_code ?: $order->orderCourier->consignment_id) . ').';
        }

        if ($order->statusEnum() !== OrderStatus::Processing) {
            return 'Only orders in "Processing" can be sent to a courier.';
        }

        return null;
    }

    /**
     * Book the parcel with the courier, store the consignment and mark the order shipped.
     * Safe against double clicks and parallel requests: one lock per order, a re-check inside it,
     * and a unique order_id on order_couriers. A rejected request marks the booking "failed".
     *
     * @throws CourierException
     */
    public function book(Order $order, string $courier, array $options, ?int $adminId): OrderCourier
    {
        $provider = $this->provider($courier);
        if (!$provider->configured()) {
            $label = OrderCourier::COURIERS[$courier] ?? $courier;
            throw new CourierException("{$label} is not connected yet. Add its API keys in Settings › Courier and try again.");
        }

        $lock = Cache::lock("courier-book:{$order->id}", 60);
        if (!$lock->get()) {
            throw new CourierException('This order is being sent to the courier right now. Refresh the page in a moment.');
        }

        try {
            $order = Order::with('orderItems.product', 'user', 'orderCourier')->findOrFail($order->id);

            if ($reason = $this->blockReason($order)) {
                throw new CourierException($reason);
            }

            $options['cod_amount'] ??= $this->defaultCodAmount($order);

            // The API call happens outside the DB transaction; a failure leaves nothing half-saved.
            try {
                $result = $provider->createParcel($order, $options);
            } catch (CourierException $e) {
                $this->recordFailure($order, $courier, $e->getMessage(), $adminId);

                throw $e;
            }

            $parcel = DB::transaction(function () use ($order, $courier, $result, $adminId) {
                $parcel = OrderCourier::updateOrCreate(['order_id' => $order->id], [
                    'admin_id'          => $adminId,
                    'courier_name'      => $courier,
                    'consignment_id'    => $result['consignment_id'],
                    'tracking_code'     => $result['tracking_code'],
                    'status'            => $result['status'],
                    'shipping_charge'   => $result['shipping_charge'],
                    'sent_at'           => now(),
                    'failed_at'         => null,
                    'last_error'        => null,
                    'status_checked_at' => now(),
                ]);

                CourierLog::create([
                    'order_id'    => $order->id,
                    'action'      => 'booked',
                    'status_from' => null,
                    'status_to'   => $result['status'],
                    'payload'     => $result['response'],
                    'remarks'     => "Parcel booked with {$parcel->courierLabel()}",
                ]);

                return $parcel;
            });

            // The parcel is booked at the courier either way; a refused transition must not lose that record.
            try {
                $this->orderStatus->transition($order, OrderStatus::Shipped, $adminId, "Handed to {$parcel->courierLabel()} (consignment {$parcel->consignment_id})");
            } catch (InvalidOrderTransitionException|InsufficientStockException $e) {
                Log::warning('Parcel booked but the order could not be marked shipped', ['order_id' => $order->order_id, 'reason' => $e->getMessage()]);
            }

            return $parcel;
        } finally {
            $lock->release();
        }
    }

    /**
     * Send several orders with one courier. Orders that cannot be sent are skipped with the reason.
     *
     * @param  Collection<int, Order>  $orders
     * @return array{sent: array<string, string>, failed: array<string, string>}
     */
    public function bookMany(Collection $orders, string $courier, ?int $adminId): array
    {
        $result = ['sent' => [], 'failed' => []];

        foreach ($orders as $order) {
            try {
                $parcel = $this->book($order, $courier, [], $adminId);
                $result['sent'][$order->order_id] = $parcel->tracking_code ?: $parcel->consignment_id;
            } catch (CourierException $e) {
                $result['failed'][$order->order_id] = $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * Pull the latest status from the courier API.
     *
     * @throws CourierException
     */
    public function refresh(OrderCourier $parcel, ?int $adminId, string $source = 'manual refresh'): OrderCourier
    {
        if (!$parcel->isBooked()) {
            throw new CourierException('This order has not been booked with a courier yet.');
        }

        $status = $this->provider($parcel->courier_name)->getStatus($parcel);
        $this->applyStatus($parcel, $status, ['source' => $source], "{$parcel->courierLabel()} status ({$source})", $adminId);

        return $parcel->fresh();
    }

    /**
     * Record a courier status update (refresh, poll or webhook) and move the order when it implies delivery/return.
     * Unchanged statuses only update the "last checked" time.
     *
     * @param  array<string, mixed>  $attributes  extra parcel fields to store, e.g. shipping_charge from a webhook
     */
    public function applyStatus(OrderCourier $parcel, string $newStatus, array $payload, string $remarks, ?int $adminId = null, array $attributes = []): void
    {
        $newStatus = CourierStatus::normalize($newStatus);
        $oldStatus = $parcel->status;

        if ($newStatus === $oldStatus) {
            $parcel->update(['status_checked_at' => now()] + $attributes);

            return;
        }

        DB::transaction(function () use ($parcel, $newStatus, $oldStatus, $payload, $remarks, $attributes) {
            $parcel->update(['status' => $newStatus, 'status_checked_at' => now()] + $attributes);

            CourierLog::create([
                'order_id'    => $parcel->order_id,
                'action'      => 'status_changed',
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
            $this->orderStatus->transition($order, $target, $adminId, "{$parcel->courierLabel()} reported \"" . CourierStatus::label($newStatus) . '"');
        } catch (InvalidOrderTransitionException|InsufficientStockException $e) {
            Log::warning('Courier status could not be applied to order', [
                'order_id' => $order->order_id,
                'courier_status' => $newStatus,
                'reason' => $e->getMessage(),
            ]);
        }
    }

    /** Remember the rejected attempt on the order so admins see "Failed to send" and the reason. */
    private function recordFailure(Order $order, string $courier, string $message, ?int $adminId): void
    {
        OrderCourier::updateOrCreate(['order_id' => $order->id], [
            'admin_id'       => $adminId,
            'courier_name'   => $courier,
            'consignment_id' => null,
            'tracking_code'  => null,
            'status'         => CourierStatus::FAILED,
            'failed_at'      => now(),
            'last_error'     => Str::limit($message, 1000),
        ]);

        Log::warning('Courier booking failed', ['order_id' => $order->order_id, 'courier' => $courier, 'error' => $message]);
    }
}
