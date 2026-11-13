<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundService
{
    public function __construct(private OrderStatusService $orderStatus)
    {
    }

    /**
     * Collected amount minus refunds that are pending or completed.
     */
    public function refundableAmount(Order $order): float
    {
        if (!$order->paymentStatusEnum()?->isCollected()) {
            return 0.0;
        }

        $committed = (float) $order->refunds()
            ->whereIn('status', [Refund::STATUS_PENDING, Refund::STATUS_COMPLETED])
            ->sum('amount');

        return max(0.0, round((float) $order->total_price - $committed, 2));
    }

    /**
     * @param  array{amount: float|string, reason: string, method?: ?string, reference?: ?string, restock?: bool, complete?: bool}  $data
     */
    public function create(Order $order, array $data, ?int $adminId): Refund
    {
        return DB::transaction(function () use ($order, $data, $adminId) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $amount = round((float) $data['amount'], 2);
            $available = $this->refundableAmount($order);

            if ($available <= 0) {
                throw ValidationException::withMessages(['amount' => 'There is nothing left to refund on this order.']);
            }

            if ($amount <= 0 || $amount > $available) {
                throw ValidationException::withMessages(['amount' => 'The refund amount must be between Tk 0.01 and Tk ' . number_format($available, 2) . '.']);
            }

            $refund = $order->refunds()->create([
                'payment_id' => Payment::where('order_id', $order->id)->latest('id')->value('id'),
                'admin_id'   => $adminId,
                'amount'     => $amount,
                'reason'     => $data['reason'],
                'method'     => $data['method'] ?? null,
                'reference'  => $data['reference'] ?? null,
                'restock'    => (bool) ($data['restock'] ?? false),
                'status'     => Refund::STATUS_PENDING,
            ]);

            if (!empty($data['complete'])) {
                $this->complete($refund, $adminId);
            }

            return $refund->fresh();
        });
    }

    public function complete(Refund $refund, ?int $adminId): Refund
    {
        return DB::transaction(function () use ($refund, $adminId) {
            $refund = Refund::whereKey($refund->id)->lockForUpdate()->firstOrFail();

            if (!$refund->isPending()) {
                throw ValidationException::withMessages(['refund' => 'Only pending refunds can be completed.']);
            }

            $refund->fill(['status' => Refund::STATUS_COMPLETED, 'processed_at' => now()]);

            $order = Order::whereKey($refund->order_id)->lockForUpdate()->firstOrFail();

            if ($refund->restock && $this->orderStatus->restockReturnedGoods($order, "Restocked by refund #{$refund->id}", $adminId)) {
                $refund->restocked_at = now();
            }

            $refund->save();
            $this->syncPaymentStatus($order->fresh(), $adminId);

            return $refund;
        });
    }

    public function reject(Refund $refund, ?int $adminId): Refund
    {
        if (!$refund->isPending()) {
            throw ValidationException::withMessages(['refund' => 'Only pending refunds can be rejected.']);
        }

        $refund->update(['status' => Refund::STATUS_REJECTED, 'processed_at' => now(), 'admin_id' => $refund->admin_id ?? $adminId]);

        return $refund;
    }

    private function syncPaymentStatus(Order $order, ?int $adminId): void
    {
        $refunded = (float) $order->refunds()->where('status', Refund::STATUS_COMPLETED)->sum('amount');
        $fully = $refunded >= round((float) $order->total_price, 2);
        $status = $fully ? PaymentStatus::Refunded : PaymentStatus::PartiallyRefunded;

        $order->update(['payment_status' => $status->value]);

        Payment::where('order_id', $order->id)
            ->whereIn('status', ['success', 'partially_refunded'])
            ->update(['status' => $fully ? 'refunded' : 'partially_refunded']);

        if ($fully && $order->statusEnum()?->canTransitionTo(OrderStatus::Refunded)) {
            $this->orderStatus->transition($order, OrderStatus::Refunded, $adminId, 'Order fully refunded');
        }
    }
}
