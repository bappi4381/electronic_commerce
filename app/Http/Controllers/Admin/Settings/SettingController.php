<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\CheckoutOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    /** Plain keys saved as submitted; an empty field clears the value. */
    private const TEXT_KEYS = [
        'site_name', 'contact_email', 'contact_phone', 'contact_address',
        'facebook_url', 'twitter_url', 'instagram_url', 'footer_text',
        'flash_deal_title', 'flash_deal_end_time',
        'shipping_default_charge', 'free_shipping_threshold',
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
        $data = $request->validate([
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
            'flash_deal_title' => 'nullable|string|max:255',
            'flash_deal_end_time' => 'nullable|date',

            'shipping_zones' => 'nullable|array|max:50',
            'shipping_zones.*.name' => ['nullable', 'string', 'max:60', 'distinct:ignore_case', 'not_in:' . CheckoutOptions::OTHER_ZONE . ',' . strtolower(CheckoutOptions::OTHER_ZONE)],
            'shipping_zones.*.charge' => 'nullable|required_with:shipping_zones.*.name|numeric|min:0|max:100000',
            'shipping_default_charge' => 'required|numeric|min:0|max:100000',
            'free_shipping_threshold' => 'nullable|numeric|min:0|max:10000000',

            'payment_cod_enabled' => 'required|boolean',
            'payment_online_enabled' => 'required|boolean',
        ], [
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

        $zones = collect($data['shipping_zones'] ?? [])
            ->filter(fn ($zone) => trim((string) ($zone['name'] ?? '')) !== '')
            ->map(fn ($zone) => ['name' => trim($zone['name']), 'charge' => round((float) $zone['charge'], 2)])
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
}
