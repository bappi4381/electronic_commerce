<?php

namespace App\Services;

use App\Exceptions\CouponException;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\User;

class CouponService
{
    /**
     * @throws CouponException
     */
    public function findValid(string $code, ?User $user, float $subtotal, bool $lock = false): Coupon
    {
        $code = strtoupper(trim($code));

        $coupon = Coupon::where('code', $code)->when($lock, fn ($query) => $query->lockForUpdate())->first();

        if (!$coupon || !$coupon->status) {
            throw new CouponException("Coupon \"{$code}\" is not valid.");
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            throw new CouponException("Coupon \"{$code}\" is not active yet.");
        }

        if ($coupon->ends_at && $coupon->ends_at->isPast()) {
            throw new CouponException("Coupon \"{$code}\" has expired.");
        }

        if ($coupon->min_order_amount && $subtotal < (float) $coupon->min_order_amount) {
            throw new CouponException("Coupon \"{$code}\" needs a minimum order of Tk " . number_format((float) $coupon->min_order_amount, 0) . '.');
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            throw new CouponException("Coupon \"{$code}\" has reached its usage limit.");
        }

        if ($coupon->per_user_limit !== null) {
            if (!$user) {
                throw new CouponException("Please log in to use coupon \"{$code}\".");
            }

            $used = CouponUsage::where('coupon_id', $coupon->id)->where('user_id', $user->id)->count();
            if ($used >= $coupon->per_user_limit) {
                throw new CouponException("You have already used coupon \"{$code}\" the maximum number of times.");
            }
        }

        return $coupon;
    }

    public function discountFor(Coupon $coupon, float $subtotal): float
    {
        $discount = $coupon->type === 'percent'
            ? $subtotal * ((float) $coupon->value / 100)
            : (float) $coupon->value;

        if ($coupon->type === 'percent' && $coupon->max_discount) {
            $discount = min($discount, (float) $coupon->max_discount);
        }

        return round(max(0.0, min($discount, $subtotal)), 2);
    }

    /**
     * Non-throwing check for showing the cart/checkout summary.
     *
     * @return array{coupon: ?Coupon, discount: float, error: ?string}
     */
    public function preview(?string $code, ?User $user, float $subtotal): array
    {
        if (!$code) {
            return ['coupon' => null, 'discount' => 0.0, 'error' => null];
        }

        try {
            $coupon = $this->findValid($code, $user, $subtotal);

            return ['coupon' => $coupon, 'discount' => $this->discountFor($coupon, $subtotal), 'error' => null];
        } catch (CouponException $e) {
            return ['coupon' => null, 'discount' => 0.0, 'error' => $e->getMessage()];
        }
    }

    /**
     * Locks the coupon, re-validates limits and records the usage. Call inside the order transaction.
     *
     * @throws CouponException
     */
    public function redeem(string $code, User $user, Order $order, float $subtotal): Coupon
    {
        $coupon = $this->findValid($code, $user, $subtotal, lock: true);
        $discount = $this->discountFor($coupon, $subtotal);

        CouponUsage::create([
            'coupon_id'       => $coupon->id,
            'user_id'         => $user->id,
            'order_id'        => $order->id,
            'discount_amount' => $discount,
        ]);

        $coupon->increment('used_count');

        $order->forceFill([
            'coupon_id'       => $coupon->id,
            'coupon_code'     => $coupon->code,
            'discount_amount' => $discount,
        ]);

        return $coupon;
    }
}
