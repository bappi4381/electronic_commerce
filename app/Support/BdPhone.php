<?php

namespace App\Support;

/**
 * Bangladeshi mobile numbers in the local 11-digit form couriers expect: 01XXXXXXXXX.
 */
final class BdPhone
{
    /**
     * "+880 1712-345678", "8801712345678", "1712345678" and "01712345678" all become "01712345678".
     * Returns null when the result is not a valid mobile number (01 followed by an operator digit 3-9 and 8 digits).
     */
    public static function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';
        $digits = preg_replace('/^00/', '', $digits) ?? '';

        if (str_starts_with($digits, '880')) {
            $digits = '0' . substr($digits, 3);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '0' . $digits;
        }

        return preg_match('/^01[3-9]\d{8}$/', $digits) ? $digits : null;
    }

    /** For logs: "017*****678". Never log the full number. */
    public static function mask(?string $phone): string
    {
        $phone = (string) $phone;

        return strlen($phone) < 7 ? '***' : substr($phone, 0, 3) . str_repeat('*', strlen($phone) - 6) . substr($phone, -3);
    }
}
