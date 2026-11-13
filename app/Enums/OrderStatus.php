<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Returned = 'returned';
    case Refunded = 'refunded';

    /**
     * Allowed next statuses. Anything not listed here is rejected by OrderStatusService.
     *
     * @return list<self>
     */
    public function transitions(): array
    {
        return match ($this) {
            self::Pending    => [self::Confirmed, self::Processing, self::Cancelled],
            self::Confirmed  => [self::Processing, self::Cancelled],
            self::Processing => [self::Shipped, self::Cancelled],
            self::Shipped    => [self::Delivered, self::Returned],
            self::Delivered  => [self::Returned, self::Refunded],
            self::Returned   => [self::Refunded],
            self::Cancelled  => [self::Pending, self::Refunded],
            self::Refunded   => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->transitions(), true);
    }

    /**
     * Stock is reserved and the goods are still in the warehouse.
     */
    public function holdsStock(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed, self::Processing], true);
    }

    /**
     * Items and charges may still be edited (subject to the order not being paid).
     */
    public function isEditable(): bool
    {
        return $this->holdsStock();
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Pending',
            self::Confirmed  => 'Confirmed',
            self::Processing => 'Processing',
            self::Shipped    => 'Shipped',
            self::Delivered  => 'Delivered',
            self::Cancelled  => 'Cancelled',
            self::Returned   => 'Returned',
            self::Refunded   => 'Refunded',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending    => 'bg-amber-50 text-amber-600 border-amber-100',
            self::Confirmed  => 'bg-sky-50 text-sky-600 border-sky-100',
            self::Processing => 'bg-blue-50 text-blue-600 border-blue-100',
            self::Shipped    => 'bg-indigo-50 text-indigo-600 border-indigo-100',
            self::Delivered  => 'bg-emerald-50 text-emerald-600 border-emerald-100',
            self::Cancelled  => 'bg-rose-50 text-rose-600 border-rose-100',
            self::Returned   => 'bg-orange-50 text-orange-600 border-orange-100',
            self::Refunded   => 'bg-slate-100 text-slate-600 border-slate-200',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending    => 'bi-clock-fill',
            self::Confirmed  => 'bi-patch-check-fill',
            self::Processing => 'bi-box-seam-fill',
            self::Shipped    => 'bi-truck',
            self::Delivered  => 'bi-house-check-fill',
            self::Cancelled  => 'bi-x-circle-fill',
            self::Returned   => 'bi-arrow-return-left',
            self::Refunded   => 'bi-cash-coin',
        };
    }

    /**
     * Sentence used in the customer's status-change email; null means no email is sent.
     */
    public function customerMessage(): ?string
    {
        return match ($this) {
            self::Pending    => null,
            self::Confirmed  => 'Your order has been confirmed and will be prepared shortly.',
            self::Processing => 'We are picking and packing your items.',
            self::Shipped    => 'Your order has been handed to the courier and is on its way.',
            self::Delivered  => 'Your order has been delivered. Thank you for shopping with us!',
            self::Cancelled  => 'Your order has been cancelled.',
            self::Returned   => 'Your order has been marked as returned.',
            self::Refunded   => 'Your order has been refunded.',
        };
    }

    /**
     * Customer-facing progress steps, in order.
     *
     * @return list<self>
     */
    public static function fulfilmentSteps(): array
    {
        return [self::Pending, self::Confirmed, self::Processing, self::Shipped, self::Delivered];
    }

    /**
     * @return array<string, string> value => label
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
