<?php

use App\Support\Money;

if (!function_exists('money')) {
    /** Format an amount in BDT, e.g. money(1234) => "৳1,234". */
    function money(float|int|string|null $amount, ?string $locale = null, int $decimals = 0): string
    {
        return Money::format($amount, $locale, $decimals);
    }
}

if (!function_exists('localized_number')) {
    /** Thousands-separated number with Bangla digits on /bn when enabled. */
    function localized_number(float|int|string|null $value, int $decimals = 0): string
    {
        return Money::localizeDigits(number_format((float) $value, $decimals));
    }
}
