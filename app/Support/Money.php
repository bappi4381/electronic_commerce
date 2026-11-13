<?php

namespace App\Support;

/**
 * The one place prices are turned into text: "৳1,234" (or "৳১,২৩৪" on /bn when enabled).
 */
class Money
{
    private const BANGLA_DIGITS = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

    public static function format(float|int|string|null $amount, ?string $locale = null, int $decimals = 0): string
    {
        $value = number_format(round((float) $amount, $decimals), $decimals);

        return config('store.currency.symbol', '৳') . static::localizeDigits($value, $locale);
    }

    public static function localizeDigits(string $value, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        if ($locale !== 'bn' || !config('store.currency.bangla_digits')) {
            return $value;
        }

        return strtr($value, array_combine(range(0, 9), self::BANGLA_DIGITS));
    }

    /** Whole-number percentage saved, or 0 when there is no real discount. */
    public static function discountPercent(float|int|string|null $price, float|int|string|null $salePrice): int
    {
        $price = (float) $price;
        $salePrice = (float) $salePrice;

        return $price > 0 && $salePrice > 0 && $salePrice < $price ? (int) round(100 - ($salePrice / $price * 100)) : 0;
    }
}
