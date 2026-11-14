<?php

namespace App\Services\Courier;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * Courier credentials entered in Admin › Settings › Courier. A saved value overrides config/services.php
 * (.env); an empty one falls back to it. Secrets are stored encrypted with APP_KEY.
 */
final class CourierSettings
{
    /** setting key => [config path, encrypted] */
    public const FIELDS = [
        'courier_steadfast_api_key'        => ['services.steadfast.api_key', false],
        'courier_steadfast_secret_key'     => ['services.steadfast.secret_key', true],
        'courier_steadfast_webhook_secret' => ['services.steadfast.webhook_secret', true],
        'courier_pathao_client_id'         => ['services.pathao.client_id', false],
        'courier_pathao_client_secret'     => ['services.pathao.client_secret', true],
        'courier_pathao_username'          => ['services.pathao.username', false],
        'courier_pathao_password'          => ['services.pathao.password', true],
        'courier_pathao_store_id'          => ['services.pathao.store_id', false],
        'courier_pathao_webhook_secret'    => ['services.pathao.webhook_secret', true],
    ];

    public const PATHAO_MODES = [
        'live'    => 'https://api-hermes.pathao.com',
        'sandbox' => 'https://courier-api-sandbox.pathao.com',
    ];

    /** Copy saved settings into config so every courier class, middleware and command uses them. */
    public static function apply(): void
    {
        try {
            $settings = Setting::allCached();
        } catch (\Throwable) {
            return; // settings table not migrated yet
        }

        foreach (self::FIELDS as $key => [$path, $encrypted]) {
            $value = $settings[$key] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            if ($encrypted) {
                try {
                    $value = Crypt::decryptString($value);
                } catch (DecryptException) {
                    Log::warning("Courier setting {$key} cannot be decrypted (APP_KEY changed?). Re-enter it in Admin › Settings › Courier.");
                    continue;
                }
            }

            config([$path => $value]);
        }

        if ($url = self::PATHAO_MODES[$settings['courier_pathao_mode'] ?? ''] ?? null) {
            config(['services.pathao.base_url' => $url]);
        }
    }

    /** "admin", "env" or null when unset; for the settings screen. */
    public static function source(string $key): ?string
    {
        return match (true) {
            filled(Setting::get($key)) => 'admin',
            filled(config(self::FIELDS[$key][0])) => 'env',
            default => null,
        };
    }

    public static function pathaoMode(): string
    {
        return array_search(rtrim((string) config('services.pathao.base_url'), '/'), self::PATHAO_MODES, true) ?: 'live';
    }
}
