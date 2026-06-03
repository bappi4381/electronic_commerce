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
                'expiry_date' => now()->addMonths(1),
                'status' => 1
            ],
            [
                'code' => 'FIRSTORDER',
                'type' => 'fixed',
                'value' => 500,
                'expiry_date' => now()->addMonths(6),
                'status' => 1
            ],
            [
                'code' => 'FLASH10',
                'type' => 'percent',
                'value' => 10,
                'expiry_date' => now()->addDays(2),
                'status' => 1
            ]
        ];

        foreach ($coupons as $coupon) {
            Coupon::create($coupon);
        }
    }
}
