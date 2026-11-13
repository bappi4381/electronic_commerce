<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Shipping zones, free-shipping threshold and payment gateway toggles, as configured in Global Settings.
 */
class CheckoutOptions
{
    /** City value used for addresses outside every configured zone. */
    public const OTHER_ZONE = 'Other';

    public const PAYMENT_METHODS = [
        'cod'    => 'payment_cod_enabled',
        'online' => 'payment_online_enabled',
    ];

    /**
     * @return list<array{name: string, charge: float}>
     */
    public function zones(): array
    {
        $zones = json_decode((string) Setting::get('shipping_zones', '[]'), true);

        return collect(is_array($zones) ? $zones : [])
            ->filter(fn ($zone) => is_array($zone) && trim((string) ($zone['name'] ?? '')) !== '')
            ->map(fn (array $zone) => ['name' => trim($zone['name']), 'charge' => max(0, (float) ($zone['charge'] ?? 0))])
            ->values()
            ->all();
    }

    public function defaultCharge(): float
    {
        return max(0, (float) Setting::get('shipping_default_charge', 110));
    }

    public function freeShippingThreshold(): ?float
    {
        $value = Setting::get('free_shipping_threshold');

        return is_numeric($value) && (float) $value > 0 ? (float) $value : null;
    }

    /**
     * Options for the checkout city select: zone name => charge, with "Other" last.
     *
     * @return array<string, float>
     */
    public function cityOptions(): array
    {
        return collect($this->zones())
            ->mapWithKeys(fn (array $zone) => [$zone['name'] => $zone['charge']])
            ->put(self::OTHER_ZONE, $this->defaultCharge())
            ->all();
    }

    /**
     * @param  float  $orderValue  Items subtotal after discounts.
     */
    public function deliveryCharge(?string $city, float $orderValue): float
    {
        $threshold = $this->freeShippingThreshold();

        if ($threshold !== null && $orderValue >= $threshold) {
            return 0.0;
        }

        foreach ($this->zones() as $zone) {
            if (strcasecmp($zone['name'], trim((string) $city)) === 0) {
                return $zone['charge'];
            }
        }

        return $this->defaultCharge();
    }

    /**
     * Enabled payment methods. Cash on delivery is the fallback if every gateway is switched off.
     *
     * @return list<string>
     */
    public function paymentMethods(): array
    {
        $enabled = array_keys(array_filter(self::PAYMENT_METHODS, fn (string $key) => Setting::bool($key, true)));

        return $enabled ?: ['cod'];
    }
}
