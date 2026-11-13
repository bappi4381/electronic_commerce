<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        Coupon::query()->delete();

        $coupons = [
            [
                'code' => 'EID2026',
                'type' => 'percent',
                'value' => 20,
                'max_discount' => 2000,
                'min_order_amount' => 1000,
                'ends_at' => now()->addMonths(1),
                'status' => 1,
            ],
            [
                'code' => 'FIRSTORDER',
                'type' => 'fixed',
                'value' => 500,
                'min_order_amount' => 3000,
                'per_user_limit' => 1,
                'ends_at' => now()->addMonths(6),
                'status' => 1,
            ],
            [
                'code' => 'FLASH10',
                'type' => 'percent',
                'value' => 10,
                'usage_limit' => 100,
                'ends_at' => now()->addDays(2),
                'status' => 1,
            ],
        ];

        foreach ($coupons as $coupon) {
            Coupon::create($coupon);
        }
    }
}
