<?php

namespace App\Services\Courier;

use Illuminate\Support\Str;

/**
 * Courier parcel statuses, stored normalised (lower snake case: "in_review", "partial_delivered").
 * Steadfast: pending, in_review, hold, delivered, partial_delivered, cancelled, unknown and the
 * "*_approval_pending" variants. Pathao statuses ("Delivered", "Partial Delivery", …) normalise the same way.
 * "failed" is ours: the booking request was rejected and nothing was sent.
 */
final class CourierStatus
{
    public const FAILED = 'failed';

    /** Statuses after which the parcel no longer changes, so polling stops. */
    public const FINAL = ['delivered', 'partial_delivered', 'partial_delivery', 'cancelled', 'returned', 'return', self::FAILED];

    private const LABELS = [
        'pending' => 'Pending',
        'in_review' => 'In Review',
        'hold' => 'Hold',
        'delivered' => 'Delivered',
        'partial_delivered' => 'Partial Delivered',
        'partial_delivery' => 'Partial Delivered',
        'cancelled' => 'Cancelled',
        'returned' => 'Returned',
        'delivered_approval_pending' => 'Delivered (approval pending)',
        'partial_delivered_approval_pending' => 'Partial Delivered (approval pending)',
        'cancelled_approval_pending' => 'Cancelled (approval pending)',
        'unknown_approval_pending' => 'Unknown (approval pending)',
        'unknown' => 'Unknown',
        self::FAILED => 'Failed to send',
    ];

    private const TONES = [
        'pending' => 'yellow',
        'in_review' => 'blue',
        'hold' => 'orange',
        'delivered' => 'green',
        'partial_delivered' => 'indigo',
        'partial_delivery' => 'indigo',
        'cancelled' => 'red',
        'returned' => 'red',
        self::FAILED => 'red',
    ];

    public static function normalize(?string $status): string
    {
        return Str::of((string) $status)->trim()->lower()->replaceMatches('/[\s\-]+/', '_')->toString();
    }

    public static function label(?string $status): string
    {
        $status = self::normalize($status);

        return self::LABELS[$status] ?? ($status === '' ? 'Unknown' : Str::headline($status));
    }

    public static function tone(?string $status): string
    {
        $status = self::normalize($status);

        return self::TONES[$status] ?? (str_ends_with($status, '_approval_pending') ? 'yellow' : 'gray');
    }

    public static function isFinal(?string $status): bool
    {
        return in_array(self::normalize($status), self::FINAL, true);
    }
}
