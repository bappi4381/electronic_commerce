<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Meta Conversions API: normalises and hashes customer data, then posts events to the Graph API.
 * Events normally go through SendFacebookEventJob; call sendEvent() directly only for diagnostics.
 */
class FacebookCapiService
{
    /** user_data keys Meta expects SHA-256 hashed. */
    private const HASHED_KEYS = ['em', 'ph', 'fn', 'ln', 'ct', 'st', 'zp', 'country', 'external_id'];

    // Credentials: Admin › Settings › Tracking wins; .env (config/services.php) is the fallback.

    public function pixelId(): ?string
    {
        return Setting::get('fb_pixel_id') ?: (config('services.facebook.pixel_id') ?: null);
    }

    /** Stored encrypted in settings; decrypted only when an event is sent. */
    public function accessToken(): ?string
    {
        $stored = Setting::get('fb_capi_access_token');
        if ($stored) {
            try {
                return Crypt::decryptString($stored);
            } catch (DecryptException) {
                Log::warning('Facebook CAPI access token in settings cannot be decrypted (APP_KEY changed?). Re-enter it in Admin › Settings › Tracking.');
            }
        }

        return config('services.facebook.capi_access_token') ?: null;
    }

    public function testEventCode(): ?string
    {
        return Setting::get('fb_test_event_code') ?: (config('services.facebook.test_event_code') ?: null);
    }

    /** Where a credential comes from, for the admin screen: "admin", "env" or null when unset. */
    public function source(string $key): ?string
    {
        $env = ['fb_pixel_id' => 'pixel_id', 'fb_capi_access_token' => 'capi_access_token', 'fb_test_event_code' => 'test_event_code'][$key];

        return match (true) {
            filled(Setting::get($key)) => 'admin',
            filled(config("services.facebook.{$env}")) => 'env',
            default => null,
        };
    }

    public function pixelEnabled(): bool
    {
        return (bool) $this->pixelId() && Setting::bool('fb_pixel_enabled', true);
    }

    public function enabled(): bool
    {
        return (bool) $this->pixelId()
            && (bool) $this->accessToken()
            && Setting::bool('fb_capi_enabled', true);
    }

    /**
     * Send one dummy event (fake customer, value 1000 BDT) straight to Meta, bypassing the queue.
     * Used by `php artisan fb:test-event` and the "Send test event" button in Admin › Settings › Tracking.
     * An empty $testEventCode sends live data; null uses the configured test code.
     */
    public function sendTestEvent(string $eventName = 'PageView', ?string $testEventCode = null): Response
    {
        $customData = $eventName === 'PageView' ? [] : array_filter([
            'value' => 1000,
            'currency' => config('store.currency.code', 'BDT'),
            'content_ids' => ['TEST-1'],
            'content_type' => 'product',
            'num_items' => 1,
            'order_id' => $eventName === 'Purchase' ? 'TEST-' . now()->format('His') : null,
        ]);

        return $this->sendEvent($eventName, 'test_' . Str::uuid(), $this->testUserData(), $customData, config('app.url'), time(), $testEventCode);
    }

    /** Hashed fake customer used by sendTestEvent(). */
    public function testUserData(): array
    {
        return $this->prepareUserData([
            'email' => 'test@example.com',
            'phone' => '01700000000',
            'first_name' => 'Test',
            'city' => 'Dhaka',
            'country' => 'BD',
            'external_id' => 'store-test',
            'client_ip_address' => '103.0.0.1',
            'client_user_agent' => 'Store test event (' . config('app.name') . ')',
        ]);
    }

    /**
     * Send one event. $userData may hold raw values (email, phone, first name, city, country) or already hashed ones;
     * anything not yet a SHA-256 hash is normalised and hashed here. Throws on a non-2xx response so jobs retry.
     *
     * @param  array<string, mixed>  $userData
     * @param  array<string, mixed>  $customData
     */
    public function sendEvent(string $eventName, string $eventId, array $userData, array $customData = [], ?string $sourceUrl = null, ?int $eventTime = null, ?string $testEventCode = null): Response
    {
        $token = $this->accessToken();
        if (!$this->pixelId() || !$token) {
            throw new RuntimeException('Facebook CAPI is not configured: add the Pixel ID and access token in Admin › Settings › Tracking.');
        }

        $event = array_filter([
            'event_name' => $eventName,
            'event_time' => $eventTime ?? time(),
            'event_id' => $eventId,
            'action_source' => 'website',
            'event_source_url' => $sourceUrl,
            'user_data' => $this->prepareUserData($userData),
            'custom_data' => $customData ?: null,
        ], fn ($value) => $value !== null);

        $payload = ['data' => [$event]];
        if ($code = $testEventCode ?? $this->testEventCode()) {
            $payload['test_event_code'] = $code;
        }

        $response = Http::timeout(10)
            ->acceptJson()
            ->post($this->endpoint(), $payload + ['access_token' => $token]);

        if ($response->failed()) {
            throw new RuntimeException("Facebook CAPI {$eventName} ({$eventId}) failed with HTTP {$response->status()}: " . $response->body());
        }

        return $response;
    }

    public function endpoint(): string
    {
        return sprintf('https://graph.facebook.com/%s/%s/events', config('services.facebook.graph_version', 'v19.0'), $this->pixelId());
    }

    /**
     * Normalise + hash the identifying fields, keep the technical ones (IP, user agent, fbp, fbc) as they are.
     * Accepts friendly keys (email, phone, first_name, last_name, city, country) or Meta's short keys.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function prepareUserData(array $data): array
    {
        $aliases = ['email' => 'em', 'phone' => 'ph', 'first_name' => 'fn', 'last_name' => 'ln', 'city' => 'ct', 'state' => 'st', 'postal_code' => 'zp'];
        foreach ($aliases as $friendly => $short) {
            if (array_key_exists($friendly, $data)) {
                $data[$short] ??= $data[$friendly];
                unset($data[$friendly]);
            }
        }

        $prepared = [];
        foreach ($data as $key => $value) {
            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            if (in_array($key, self::HASHED_KEYS, true)) {
                $normalised = $this->normalise($key, (string) $value);
                if ($normalised !== '') {
                    $prepared[$key] = $this->isHash($normalised) ? $normalised : hash('sha256', $normalised);
                }
            } else {
                $prepared[$key] = $value;
            }
        }

        return $prepared;
    }

    private function normalise(string $key, string $value): string
    {
        $value = trim($value);
        if ($this->isHash(strtolower($value))) {
            return strtolower($value);
        }

        return match ($key) {
            'em' => mb_strtolower($value),
            'ph' => $this->normalisePhone($value),
            'fn', 'ln' => mb_strtolower(preg_replace('/[\s\p{P}]+/u', '', $value) ?? ''),
            'ct', 'st' => mb_strtolower(preg_replace('/[^\p{L}\p{N}]+/u', '', $value) ?? ''),
            'zp' => mb_strtolower(preg_replace('/\s+/', '', $value) ?? ''),
            'country' => mb_strtolower(substr(preg_replace('/[^A-Za-z]/', '', $value) ?? '', 0, 2)),
            default => $value,
        };
    }

    /** Digits only, with Bangladesh's 880 country code: 01712345678 / +8801712345678 / 1712345678 → 8801712345678. */
    public function normalisePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        $digits = preg_replace('/^00/', '', $digits);

        return match (true) {
            str_starts_with($digits, '880') => $digits,
            str_starts_with($digits, '0') && strlen($digits) === 11 => '88' . $digits,
            strlen($digits) === 10 && str_starts_with($digits, '1') => '880' . $digits,
            default => $digits,
        };
    }

    private function isHash(string $value): bool
    {
        return (bool) preg_match('/^[a-f0-9]{64}$/', $value);
    }
}
