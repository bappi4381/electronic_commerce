<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Replaces the fixed Dhaka/outside shipping charges with zones, adds payment
 * gateway toggles, and removes keys nothing reads.
 */
return new class extends Migration
{
    public function up(): void
    {
        $value = fn (string $key) => DB::table('settings')->where('key', $key)->value('value');
        $set = fn (string $key, $v) => DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $v, 'updated_at' => now(), 'created_at' => now()]);

        $dhaka = $value('shipping_charge_dhaka');
        $outside = $value('shipping_charge_outside');

        $set('shipping_zones', json_encode([
            ['name' => 'Dhaka', 'charge' => (float) ($dhaka ?? 80)],
        ]));
        $set('shipping_default_charge', (string) (float) ($outside ?? 110));
        $set('free_shipping_threshold', null);
        $set('payment_cod_enabled', '1');
        $set('payment_online_enabled', '1');

        DB::table('settings')->whereIn('key', ['shipping_charge_dhaka', 'shipping_charge_outside', 'shipping_fee', 'currency'])->delete();

        Cache::forget(Setting::CACHE_KEY);
    }

    public function down(): void
    {
        $zones = json_decode((string) DB::table('settings')->where('key', 'shipping_zones')->value('value'), true) ?: [];
        $default = DB::table('settings')->where('key', 'shipping_default_charge')->value('value');

        DB::table('settings')->updateOrInsert(['key' => 'shipping_charge_dhaka'], ['value' => (string) ($zones[0]['charge'] ?? 80)]);
        DB::table('settings')->updateOrInsert(['key' => 'shipping_charge_outside'], ['value' => (string) ($default ?? 110)]);
        DB::table('settings')->whereIn('key', ['shipping_zones', 'shipping_default_charge', 'free_shipping_threshold', 'payment_cod_enabled', 'payment_online_enabled'])->delete();

        Cache::forget(Setting::CACHE_KEY);
    }
};
