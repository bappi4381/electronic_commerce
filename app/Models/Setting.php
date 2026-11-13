<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Setting extends Model
{
    use LogsActivity;

    public const CACHE_KEY = 'settings.all';

    protected $fillable = ['key', 'value'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * All settings as key => value, cached until a setting is saved or deleted.
     *
     * @return array<string, string|null>
     */
    public static function allCached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
    }

    /**
     * Get a setting value by key; empty values fall back to the default.
     */
    public static function get($key, $default = null)
    {
        $value = static::allCached()[$key] ?? null;

        return $value === null || $value === '' ? $default : $value;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = static::allCached()[$key] ?? null;

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Set/Update a setting value. Null clears it.
     */
    public static function set($key, $value)
    {
        return self::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('settings')
            ->logOnly(['key', 'value'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $event) => "Setting \"{$this->key}\" {$event}");
    }
}
