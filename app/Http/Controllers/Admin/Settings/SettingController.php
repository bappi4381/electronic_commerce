<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Exceptions\CourierException;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\CheckoutOptions;
use App\Services\Courier\CourierSettings;
use App\Services\Courier\PathaoService;
use App\Services\Courier\SteadfastService;
use App\Services\Fraud\FraudSettings;
use App\Services\FacebookCapiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    /** Plain keys saved as submitted; an empty field clears the value. */
    private const TEXT_KEYS = [
        'site_name', 'contact_email', 'contact_phone', 'contact_address',
        'facebook_url', 'twitter_url', 'instagram_url', 'footer_text',
        'flash_deal_title', 'flash_deal_start_time', 'flash_deal_end_time',
        'shipping_default_charge', 'shipping_default_eta', 'free_shipping_threshold',
    ];

    public function index(CheckoutOptions $checkout)
    {
        $settings = Setting::allCached();
        $zones = $checkout->zones();
        $defaultCharge = $checkout->defaultCharge();
        $paymentMethods = $checkout->paymentMethods();

        return view('admin.settings.index', compact('settings', 'zones', 'defaultCharge', 'paymentMethods'));
    }

    public function update(Request $request)
    {
        $data = $request->validate(\App\Http\Controllers\Admin\CouponController::flashDealRules(titleRequired: false) + [
            'site_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:20',
            'contact_address' => 'nullable|string|max:1000',
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'instagram_url' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_logo' => 'nullable|boolean',
            'footer_text' => 'nullable|string|max:1000',
            'shipping_zones' => 'nullable|array|max:50',
            'shipping_zones.*.name' => ['nullable', 'string', 'max:60', 'distinct:ignore_case', 'not_in:' . CheckoutOptions::OTHER_ZONE . ',' . strtolower(CheckoutOptions::OTHER_ZONE)],
            'shipping_zones.*.charge' => 'nullable|required_with:shipping_zones.*.name|numeric|min:0|max:100000',
            'shipping_zones.*.eta' => 'nullable|string|max:40',
            'shipping_default_charge' => 'required|numeric|min:0|max:100000',
            'shipping_default_eta' => 'nullable|string|max:40',
            'free_shipping_threshold' => 'nullable|numeric|min:0|max:10000000',

            'payment_cod_enabled' => 'required|boolean',
            'payment_online_enabled' => 'required|boolean',

            'fb_pixel_enabled' => 'required|boolean',
            'fb_capi_enabled' => 'required|boolean',
            'fb_pixel_id' => ['nullable', 'regex:/^\d{5,25}$/'],
            'fb_capi_access_token' => 'nullable|string|min:20|max:1000',
            'remove_fb_capi_access_token' => 'nullable|boolean',
            'fb_test_event_code' => ['nullable', 'regex:/^[A-Za-z0-9_-]{1,50}$/'],

            'courier_steadfast_api_key' => ['nullable', 'string', 'max:255', 'regex:/^\S+$/'],
            'courier_steadfast_secret_key' => ['nullable', 'string', 'max:255', 'regex:/^\S+$/'],
            'courier_steadfast_webhook_secret' => ['nullable', 'string', 'min:16', 'max:255', 'regex:/^\S+$/'],
            'courier_pathao_mode' => ['nullable', Rule::in(array_keys(CourierSettings::PATHAO_MODES))],
            'courier_pathao_client_id' => ['nullable', 'string', 'max:255', 'regex:/^\S+$/'],
            'courier_pathao_client_secret' => ['nullable', 'string', 'max:255', 'regex:/^\S+$/'],
            'courier_pathao_username' => 'nullable|string|max:255',
            'courier_pathao_password' => 'nullable|string|max:255',
            'courier_pathao_store_id' => ['nullable', 'regex:/^\d{1,20}$/'],
            'courier_pathao_webhook_secret' => ['nullable', 'string', 'min:16', 'max:255', 'regex:/^\S+$/'],

            'fraud_duplicate_minutes' => 'nullable|integer|min:0|max:1440',
            'fraud_ip_max_per_hour' => 'nullable|integer|min:0|max:1000',
            'fraud_ip_action' => 'nullable|in:flag,block',
            'fraud_check_enabled' => 'nullable|boolean',
            'fraud_driver' => ['nullable', Rule::in(array_keys(FraudSettings::DRIVERS))],
            'fraud_bdcourier_api_key' => ['nullable', 'string', 'max:500', 'regex:/^\S+$/'],
            'remove_fraud_bdcourier_api_key' => 'nullable|boolean',
            'fraud_high_cancel_count' => 'nullable|integer|min:1|max:100',
            'fraud_hold_high_risk' => 'nullable|boolean',
            'fraud_advance_enabled' => 'nullable|boolean',
            'fraud_advance_type' => 'nullable|in:delivery_charge,fixed',
            'fraud_advance_amount' => 'nullable|required_if:fraud_advance_type,fixed|numeric|min:0|max:100000',
        ], [
            'fraud_bdcourier_api_key.regex' => 'Paste the API key without spaces.',
            'fraud_advance_amount.required_if' => 'Enter the fixed advance amount.',
            'courier_steadfast_api_key.regex' => 'Paste the key without spaces.',
            'courier_steadfast_secret_key.regex' => 'Paste the key without spaces.',
            'courier_steadfast_webhook_secret.regex' => 'The webhook token cannot contain spaces.',
            'courier_pathao_client_id.regex' => 'Paste the client ID without spaces.',
            'courier_pathao_client_secret.regex' => 'Paste the client secret without spaces.',
            'courier_pathao_store_id.regex' => 'The store ID is a number from Pathao merchant panel › Stores.',
            'courier_pathao_webhook_secret.regex' => 'The webhook secret cannot contain spaces.',
            'fb_pixel_id.regex' => 'The Pixel ID is a number, e.g. 1234567890123456.',
            'fb_test_event_code.regex' => 'Use the code exactly as shown in Events Manager › Test events, e.g. TEST12345.',
            'shipping_zones.*.name.not_in' => '"' . CheckoutOptions::OTHER_ZONE . '" is reserved for addresses outside every zone.',
            'shipping_zones.*.name.distinct' => 'Each shipping zone needs a different name.',
        ]);

        if (!$data['payment_cod_enabled'] && !$data['payment_online_enabled']) {
            throw ValidationException::withMessages(['payment_cod_enabled' => 'Keep at least one payment method enabled.']);
        }

        foreach (self::TEXT_KEYS as $key) {
            Setting::set($key, $data[$key] ?? null);
        }

        Setting::set('payment_cod_enabled', $data['payment_cod_enabled'] ? '1' : '0');
        Setting::set('payment_online_enabled', $data['payment_online_enabled'] ? '1' : '0');
        Setting::set('fb_pixel_enabled', $data['fb_pixel_enabled'] ? '1' : '0');
        Setting::set('fb_capi_enabled', $data['fb_capi_enabled'] ? '1' : '0');
        Setting::set('fb_pixel_id', $data['fb_pixel_id'] ?? null);
        Setting::set('fb_test_event_code', $data['fb_test_event_code'] ?? null);

        // The token is never sent back to the form: blank keeps the saved one, the checkbox removes it.
        if ($request->boolean('remove_fb_capi_access_token')) {
            Setting::set('fb_capi_access_token', null);
        } elseif (filled($data['fb_capi_access_token'] ?? null)) {
            Setting::set('fb_capi_access_token', Crypt::encryptString(trim($data['fb_capi_access_token'])));
        }

        $this->saveCourierSettings($request, $data);
        $this->saveFraudSettings($request, $data);

        if ($request->has('flash_deal_auto_end')) {
            Setting::set('flash_deal_auto_end', $request->boolean('flash_deal_auto_end') ? '1' : '0');
        }
        app(\App\Services\FlashSale::class)->sync();

        $zones = collect($data['shipping_zones'] ?? [])
            ->filter(fn ($zone) => trim((string) ($zone['name'] ?? '')) !== '')
            ->map(fn ($zone) => ['name' => trim($zone['name']), 'charge' => round((float) $zone['charge'], 2), 'eta' => trim((string) ($zone['eta'] ?? ''))])
            ->values()
            ->all();
        Setting::set('shipping_zones', json_encode($zones));

        $oldLogo = Setting::get('logo');
        if ($request->hasFile('logo') || $request->boolean('remove_logo')) {
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }
            Setting::set('logo', $request->hasFile('logo') ? $request->file('logo')->store('settings', 'public') : null);
        }

        return redirect()->back()->with('success', 'Settings updated successfully!');
    }

    /**
     * Plain fields save as submitted (empty clears). Secrets are never sent back to the form: blank keeps the saved
     * one, the "remove" checkbox clears it. Webhook tokens can also be generated here.
     */
    private function saveCourierSettings(Request $request, array $data): void
    {
        $before = Setting::allCached();

        foreach (CourierSettings::FIELDS as $key => [, $encrypted]) {
            $value = isset($data[$key]) ? trim((string) $data[$key]) : '';

            if (!$encrypted) {
                Setting::set($key, $value === '' ? null : $value);
            } elseif ($request->boolean("generate_{$key}")) {
                Setting::set($key, Crypt::encryptString(Str::random(48)));
            } elseif ($request->boolean("remove_{$key}")) {
                Setting::set($key, null);
            } elseif ($value !== '') {
                Setting::set($key, Crypt::encryptString($value));
            }
        }
        Setting::set('courier_pathao_mode', $data['courier_pathao_mode'] ?? null);

        $after = Setting::allCached();
        $changed = fn (string $courier) => collect($after)->filter(fn ($v, $k) => str_starts_with($k, "courier_{$courier}_"))
            != collect($before)->filter(fn ($v, $k) => str_starts_with($k, "courier_{$courier}_"));

        if ($changed('steadfast')) {
            Cache::forget('courier.steadfast.balance');
        }
        if ($changed('pathao')) {
            Cache::forget(PathaoService::TOKEN_CACHE_KEY);
        }
    }

    /** Only fields present in the request are saved, so other forms posting here leave these untouched. */
    private function saveFraudSettings(Request $request, array $data): void
    {
        $fields = ['fraud_duplicate_minutes', 'fraud_ip_max_per_hour', 'fraud_ip_action', 'fraud_driver', 'fraud_high_cancel_count', 'fraud_advance_type', 'fraud_advance_amount'];
        $toggles = ['fraud_check_enabled', 'fraud_hold_high_risk', 'fraud_advance_enabled'];
        $changes = [];

        foreach ($fields as $key) {
            if ($request->has($key)) {
                $value = $data[$key] ?? null;
                Setting::set($key, $value === null || $value === '' ? null : (string) $value);
                $changes[$key] = $value;
            }
        }
        foreach ($toggles as $key) {
            if ($request->has($key)) {
                Setting::set($key, $request->boolean($key) ? '1' : '0');
                $changes[$key] = $request->boolean($key);
            }
        }

        if ($request->boolean('remove_fraud_bdcourier_api_key')) {
            Setting::set('fraud_bdcourier_api_key', null);
        } elseif (filled($data['fraud_bdcourier_api_key'] ?? null)) {
            Setting::set('fraud_bdcourier_api_key', Crypt::encryptString(trim($data['fraud_bdcourier_api_key'])));
        }

        if ($changes) {
            activity('fraud')->causedBy(auth('admin')->user())->withProperties($changes)->log('Updated fraud protection settings');
        }
    }

    /** "Check connection" on the Courier tab: one harmless read call with the saved credentials. */
    public function courierTest(string $courier)
    {
        $back = redirect()->to(route('admin.settings.index') . '#courier');

        try {
            if ($courier === 'steadfast') {
                $steadfast = app(SteadfastService::class);
                if (!$steadfast->configured()) {
                    return $back->with('error', 'Save the Steadfast API key and secret key first.');
                }
                $balance = $steadfast->getBalance();
                Cache::forget('courier.steadfast.balance');

                return $back->with('success', 'Steadfast connected. Current balance: Tk ' . number_format($balance, 2) . '.');
            }

            $pathao = app(PathaoService::class);
            if (!$pathao->configured()) {
                return $back->with('error', 'Save the Pathao client ID, client secret, username, password and store ID first.');
            }
            Cache::forget(PathaoService::TOKEN_CACHE_KEY);
            $cities = $pathao->cities();

            return $back->with('success', 'Pathao connected (' . CourierSettings::pathaoMode() . '). ' . count($cities) . ' cities available.');
        } catch (CourierException $e) {
            return $back->with('error', $e->getMessage());
        }
    }

    /** "Send test event" on the Tracking tab: one dummy event to Meta with the saved test event code. */
    public function facebookTest(FacebookCapiService $capi)
    {
        $back = redirect()->to(route('admin.settings.index') . '#tracking');

        if (!$capi->pixelId() || !$capi->accessToken()) {
            return $back->with('error', 'Save the Pixel ID and CAPI access token first.');
        }
        if (!$capi->testEventCode()) {
            return $back->with('error', 'Add a test event code (Events Manager › Test events) so the test does not count as real data.');
        }

        try {
            $response = $capi->sendTestEvent('PageView');
        } catch (\Throwable $e) {
            return $back->with('error', 'Meta rejected the test event: ' . \Illuminate\Support\Str::limit($e->getMessage(), 300));
        }

        return $back->with('success', sprintf(
            'Meta received %d test event (fbtrace_id %s). Open Events Manager › Test events to see it.',
            (int) $response->json('events_received'),
            $response->json('fbtrace_id') ?? '—'
        ));
    }
}
