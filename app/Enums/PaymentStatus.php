<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case PartiallyRefunded = 'partially_refunded';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid            => 'Unpaid',
            self::Pending           => 'Pending',
            self::Paid              => 'Paid',
            self::Failed            => 'Failed',
            self::Cancelled         => 'Cancelled',
            self::PartiallyRefunded => 'Partially refunded',
            self::Refunded          => 'Refunded',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Paid              => 'bg-emerald-50 text-emerald-600 border-emerald-100',
            self::Pending           => 'bg-amber-50 text-amber-600 border-amber-100',
            self::PartiallyRefunded => 'bg-orange-50 text-orange-600 border-orange-100',
            self::Refunded          => 'bg-slate-100 text-slate-600 border-slate-200',
            default                 => 'bg-rose-50 text-rose-600 border-rose-100',
        };
    }

    /**
     * Money has been received for the order (refunds may have been issued since).
     */
    public function isCollected(): bool
    {
        return in_array($this, [self::Paid, self::PartiallyRefunded, self::Refunded], true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }

    public static function badge(?string $value): string
    {
        return self::tryFrom((string) $value)?->badgeClasses() ?? 'bg-slate-50 text-slate-500 border-slate-100';
    }
}
