<?php

namespace App\Services\Fraud;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Support\BdPhone;
use Illuminate\Support\Facades\Log;

/**
 * Checks run synchronously at checkout, before the order is created (all are cheap database queries):
 * the same products from the same phone within the duplicate window, and orders per IP per hour.
 */
class CheckoutGuard
{
    public const FLAG_IP_VELOCITY = 'ip_velocity';

    /** Same products (quantities ignored, so "one more of the same" still counts as a duplicate). */
    public function cartHash(array $cart): string
    {
        $lines = collect($cart)
            ->map(fn ($item) => ($item['product_id'] ?? '') . ':' . ($item['variant_id'] ?? ''))
            ->unique()
            ->sort()
            ->implode('|');

        return hash('sha256', $lines);
    }

    /**
     * @return list<string> flags to store on the order
     *
     * @throws OrderBlockedException
     */
    public function inspect(string $phone, string $cartHash, ?string $ip, ?int $userId = null): array
    {
        $flags = [];
        $minutes = FraudSettings::duplicateMinutes();

        if ($minutes > 0) {
            $duplicate = Order::query()
                ->where('phone_normalized', $phone)
                ->where('cart_hash', $cartHash)
                ->where('created_at', '>=', now()->subMinutes($minutes))
                ->where('status', '!=', OrderStatus::Cancelled->value)
                ->value('order_id');

            if ($duplicate) {
                Log::notice('Checkout blocked: duplicate order', ['phone' => BdPhone::mask($phone), 'previous_order' => $duplicate, 'user_id' => $userId]);

                throw new OrderBlockedException(__('Your order is already being processed. You placed the same order a few minutes ago — check My orders, or contact us if you need to change it.'), 'duplicate');
            }
        }

        $max = FraudSettings::ipMaxPerHour();

        if ($max > 0 && $ip) {
            $recent = Order::where('placed_ip', $ip)->where('created_at', '>=', now()->subHour())->count();

            if ($recent >= $max) {
                if (FraudSettings::ipAction() === 'block') {
                    Log::notice('Checkout blocked: too many orders from one IP', ['orders_last_hour' => $recent, 'user_id' => $userId]);

                    throw new OrderBlockedException(__('Too many orders were placed from your network in the last hour. Please try again later, or call us to order.'), 'ip_limit');
                }

                Log::notice('Order flagged: many orders from one IP', ['orders_last_hour' => $recent, 'user_id' => $userId]);
                $flags[] = self::FLAG_IP_VELOCITY;
            }
        }

        return $flags;
    }
}
