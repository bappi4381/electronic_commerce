<?php

namespace App\Services\Fraud;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Fraud protection limits: Admin › Settings › Fraud first, config/fraud.php as the fallback.
 */
final class FraudSettings
{
    public const DRIVERS = [
        'local' => 'This store\'s order history',
        'steadfast' => 'Steadfast fraud check',
        'bdcourier' => 'BD Courier (all couriers)',
    ];

    public static function duplicateMinutes(): int
    {
        return max(0, (int) self::value('fraud_duplicate_minutes', 'duplicate_minutes'));
    }

    public static function ipMaxPerHour(): int
    {
        return max(0, (int) self::value('fraud_ip_max_per_hour', 'ip_max_per_hour'));
    }

    public static function ipAction(): string
    {
        return self::value('fraud_ip_action', 'ip_action') === 'block' ? 'block' : 'flag';
    }

    public static function checkEnabled(): bool
    {
        return self::bool('fraud_check_enabled', 'enabled');
    }

    public static function driver(): string
    {
        $driver = (string) self::value('fraud_driver', 'driver');

        return array_key_exists($driver, self::DRIVERS) ? $driver : 'local';
    }

    public static function highCancelCount(): int
    {
        return max(1, (int) self::value('fraud_high_cancel_count', 'high_cancel_count'));
    }

    public static function holdHighRisk(): bool
    {
        return self::bool('fraud_hold_high_risk', 'hold_high_risk');
    }

    public static function advanceEnabled(): bool
    {
        return self::bool('fraud_advance_enabled', 'advance_enabled');
    }

    public static function advanceType(): string
    {
        return self::value('fraud_advance_type', 'advance_type') === 'fixed' ? 'fixed' : 'delivery_charge';
    }

    public static function advanceAmount(): float
    {
        return max(0, (float) self::value('fraud_advance_amount', 'advance_amount'));
    }

    /** Stored encrypted; decrypted only when the API is called. */
    public static function bdCourierApiKey(): ?string
    {
        $stored = Setting::get('fraud_bdcourier_api_key');
        if ($stored) {
            try {
                return Crypt::decryptString($stored);
            } catch (DecryptException) {
                Log::warning('BD Courier API key in settings cannot be decrypted (APP_KEY changed?). Re-enter it in Admin › Settings › Fraud.');
            }
        }

        return config('fraud.drivers.bdcourier.api_key') ?: null;
    }

    private static function value(string $setting, string $config): mixed
    {
        $saved = Setting::get($setting);

        return $saved !== null ? $saved : config("fraud.{$config}");
    }

    private static function bool(string $setting, string $config): bool
    {
        return Setting::bool($setting, (bool) config("fraud.{$config}"));
    }
}
