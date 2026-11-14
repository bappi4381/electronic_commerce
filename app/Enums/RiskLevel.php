<?php

namespace App\Enums;

enum RiskLevel: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case New = 'new';
    case Unknown = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Low risk',
            self::Medium => 'Medium risk',
            self::High => 'High risk',
            self::New => 'New customer',
            self::Unknown => 'Risk unknown',
        };
    }

    /** Colour for x-admin.status-badge. */
    public function tone(): string
    {
        return match ($this) {
            self::Low => 'green',
            self::Medium => 'yellow',
            self::High => 'red',
            self::New => 'blue',
            self::Unknown => 'gray',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Low => 'Delivers at least 80% of parcels.',
            self::Medium => 'Delivers 50–79% of parcels.',
            self::High => 'Delivers under half of parcels, or has cancelled several.',
            self::New => 'No courier history for this number yet.',
            self::Unknown => 'The check could not be completed.',
        };
    }

    /** @return array<string, string> value => label */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $level) => [$level->value => $level->label()])->all();
    }
}
