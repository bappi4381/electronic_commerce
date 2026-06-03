<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'site_name' => 'ONEMALL Electronics',
            'contact_email' => 'support@onemall.com',
            'flash_deal_title' => 'MEGA MONSOON FLASH SALE 2026',
            'flash_deal_end_time' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'shipping_fee' => '100',
            'currency' => 'TK',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
