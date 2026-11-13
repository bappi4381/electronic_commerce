<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidOrderTransitionException;
use App\Mail\OrderStatusChanged;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Single entry point for order status changes: validates the transition against
 * OrderStatus::transitions(), applies stock effects, records history and queues
 * the customer email (after commit).
 */
class OrderStatusService
{
    public function __construct(private StockService $stock)
    {
    }

    public function recordInitial(Order $order, ?int $adminId, string $note): void
    {
        OrderStatusHistory::create([
            'order_id'    => $order->id,
            'from_status' => null,
            'to_status'   => $order->status,
            'admin_id'    => $adminId,
            'note'        => $note,
        ]);
    }

    /**
     * @throws InvalidOrderTransitionException
     * @throws InsufficientStockException
     */
    public function transition(Order $order, OrderStatus $to, ?int $adminId = null, ?string $note = null, bool $restock = false, bool $notify = true): Order
    {
        return DB::transaction(function () use ($order, $to, $adminId, $note, $restock, $notify) {
            $order = Order::with('orderItems.variant', 'orderItems.product', 'user')
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $from = $order->statusEnum();

            if ($from === $to) {
                return $order;
            }

            if (!$from || !$from->canTransitionTo($to)) {
                throw new InvalidOrderTransitionException($from, $to, $order->order_id);
            }

            $this->applyStockEffects($order, $from, $to, $adminId, $restock);

            if ($to === OrderStatus::Delivered && $order->payment_method === 'cod' && !$order->paymentStatusEnum()?->isCollected()) {
                $this->recordCashCollected($order);
            }

            $order->status = $to->value;
            $order->save();

            OrderStatusHistory::create([
                'order_id'    => $order->id,
                'from_status' => $from->value,
                'to_status'   => $to->value,
                'admin_id'    => $adminId,
                'note'        => $note,
            ]);

            if ($notify && $to->customerMessage() && ($email = $order->customerDisplayEmail())) {
                Mail::to($email)->queue(new OrderStatusChanged($order, $to->value, $note));
            }

            return $order;
        });
    }

    /**
     * Each order is transitioned in its own transaction so one failure does not block the rest.
     *
     * @param  iterable<Order>  $orders
     * @return array{updated: list<string>, failed: array<string, string>}
     */
    public function bulk(iterable $orders, OrderStatus $to, ?int $adminId, ?string $note = null): array
    {
        $result = ['updated' => [], 'failed' => []];

        foreach ($orders as $order) {
            try {
                $this->transition($order, $to, $adminId, $note);
                $result['updated'][] = $order->order_id;
            } catch (InvalidOrderTransitionException|InsufficientStockException $e) {
                $result['failed'][$order->order_id] = $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * Put goods that left the warehouse (shipped/delivered/returned) back into stock, once.
     */
    public function restockReturnedGoods(Order $order, string $reason, ?int $adminId): bool
    {
        $order->loadMissing('orderItems.variant', 'orderItems.product');
        $status = $order->statusEnum();

        if ($order->stock_released_at || !$status || $status->holdsStock() || $status === OrderStatus::Cancelled) {
            return false;
        }

        $this->stock->releaseOrder($order, $reason, $adminId);
        $order->forceFill(['stock_released_at' => now()])->save();

        return true;
    }

    private function applyStockEffects(Order $order, OrderStatus $from, OrderStatus $to, ?int $adminId, bool $restock): void
    {
        if ($to === OrderStatus::Cancelled && $from->holdsStock() && !$order->stock_released_at) {
            $this->stock->releaseOrder($order, 'Released due to order cancellation', $adminId);
            $order->stock_released_at = now();
        } elseif ($from === OrderStatus::Cancelled && $to === OrderStatus::Pending && $order->stock_released_at) {
            $this->stock->reserveOrder($order, 'Re-reserved when re-opening a cancelled order', $adminId);
            $order->stock_released_at = null;
        } elseif ($to === OrderStatus::Delivered) {
            $this->stock->commitOrder($order, 'Order delivered', $adminId);
        } elseif ($to === OrderStatus::Returned && $restock && !$order->stock_released_at) {
            $this->stock->releaseOrder($order, 'Returned goods restocked', $adminId);
            $order->stock_released_at = now();
        }
    }

    /**
     * Cash on delivery is collected when the parcel is delivered.
     */
    private function recordCashCollected(Order $order): void
    {
        $order->payment_status = PaymentStatus::Paid->value;

        Payment::firstOrCreate(
            ['transaction_id' => 'COD-' . $order->order_id],
            [
                'order_id'       => $order->id,
                'user_id'        => $order->user_id,
                'payment_method' => 'cod',
                'amount'         => $order->total_price,
                'currency'       => config('services.sslcommerz.currency', 'BDT'),
                'status'         => 'success',
                'payment_date'   => now(),
            ]
        );
    }
}
