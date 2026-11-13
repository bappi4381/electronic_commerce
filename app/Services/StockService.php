<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Single entry point for variant stock changes. Every change locks the variant row
 * and writes a StockMovement in the same transaction.
 */
class StockService
{
    public function __construct(private AdminNotifier $notifier)
    {
    }

    /**
     * Take stock for a sale. Fails when the variant is inactive (unless $allowInactive) or stock is insufficient.
     */
    public function reserve(ProductVariant|int $variant, int $quantity, ?Model $source, string $reason, ?int $adminId = null, bool $allowInactive = false): ProductVariant
    {
        return DB::transaction(function () use ($variant, $quantity, $source, $reason, $adminId, $allowInactive) {
            $locked = $this->lock($variant);

            if (!$allowInactive && (!$locked->is_active || !$locked->product)) {
                throw InsufficientStockException::unavailable($locked, $quantity);
            }

            if ($locked->stock < $quantity) {
                throw new InsufficientStockException($locked, $quantity);
            }

            return $this->apply($locked, -$quantity, 'order_reserved', $source, $reason, $adminId);
        });
    }

    public function release(ProductVariant|int $variant, int $quantity, ?Model $source, string $reason, ?int $adminId = null): ProductVariant
    {
        return DB::transaction(fn () => $this->apply($this->lock($variant), $quantity, 'order_released', $source, $reason, $adminId));
    }

    /**
     * Manual correction or initial stock. Rejects changes that would make stock negative.
     */
    public function adjust(ProductVariant|int $variant, int $change, ?Model $source, string $reason, ?int $adminId = null, string $type = 'adjustment'): ProductVariant
    {
        return DB::transaction(function () use ($variant, $change, $source, $reason, $adminId, $type) {
            $locked = $this->lock($variant);

            if ($locked->stock + $change < 0) {
                throw new InsufficientStockException($locked, -$change);
            }

            return $this->apply($locked, $change, $type, $source, $reason, $adminId);
        });
    }

    /**
     * Records that a reservation became a sale; stock was already taken at reserve time.
     */
    public function commit(ProductVariant|int $variant, ?Model $source, string $reason, ?int $adminId = null): void
    {
        $this->apply($this->resolve($variant), 0, 'order_committed', $source, $reason, $adminId);
    }

    public function reserveOrder(Order $order, string $reason, ?int $adminId = null): void
    {
        DB::transaction(function () use ($order, $reason, $adminId) {
            foreach ($order->orderItems as $item) {
                if ($variant = $this->variantFor($item)) {
                    $this->reserve($variant, (int) $item->quantity, $order, $reason, $adminId, allowInactive: true);
                }
            }
        });
    }

    public function releaseOrder(Order $order, string $reason, ?int $adminId = null): void
    {
        DB::transaction(function () use ($order, $reason, $adminId) {
            foreach ($order->orderItems as $item) {
                if ($variant = $this->variantFor($item)) {
                    $this->release($variant, (int) $item->quantity, $order, $reason, $adminId);
                }
            }
        });
    }

    public function commitOrder(Order $order, string $reason, ?int $adminId = null): void
    {
        foreach ($order->orderItems as $item) {
            if ($variant = $this->variantFor($item)) {
                $this->commit($variant, $order, $reason, $adminId);
            }
        }
    }

    /**
     * Legacy order items may lack a variant_id; those fall back to the product's first variant.
     */
    public function variantFor(OrderItem $item): ?ProductVariant
    {
        return $item->variant ?? $item->product?->allVariants()->orderBy('id')->first();
    }

    private function lock(ProductVariant|int $variant): ProductVariant
    {
        return ProductVariant::with('product')
            ->whereKey($variant instanceof ProductVariant ? $variant->getKey() : $variant)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function resolve(ProductVariant|int $variant): ProductVariant
    {
        return $variant instanceof ProductVariant ? $variant : ProductVariant::findOrFail($variant);
    }

    private function apply(ProductVariant $variant, int $change, string $type, ?Model $source, string $reason, ?int $adminId): ProductVariant
    {
        StockMovement::create([
            'variant_id'  => $variant->id,
            'change'      => $change,
            'type'        => $type,
            'reason'      => $reason,
            'source_type' => $source ? $source::class : null,
            'source_id'   => $source?->getKey(),
            'admin_id'    => $adminId,
        ]);

        if ($change !== 0) {
            $before = (int) $variant->stock;
            $variant->increment('stock', $change);
            $this->alertIfLow($variant, $before, (int) $variant->stock);
        }

        return $variant;
    }

    /**
     * Notifies once when stock crosses the product's threshold (or runs out), after the transaction commits.
     */
    private function alertIfLow(ProductVariant $variant, int $before, int $after): void
    {
        $threshold = $variant->product?->low_stock_threshold;

        if ($threshold === null || !$variant->is_active || $after >= $before) {
            return;
        }

        $crossedThreshold = $before > $threshold && $after <= $threshold;
        $ranOut = $before > 0 && $after <= 0;

        if ($crossedThreshold || $ranOut) {
            DB::afterCommit(fn () => $this->notifier->lowStock($variant, $after, (int) $threshold));
        }
    }
}
