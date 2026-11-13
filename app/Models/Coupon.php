<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Coupon extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'max_discount',
        'usage_limit',
        'per_user_limit',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'max_discount' => 'decimal:2',
        'usage_limit' => 'integer',
        'per_user_limit' => 'integer',
        'used_count' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'status' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Coupon $coupon) {
            $coupon->code = strtoupper(trim($coupon->code));
        });
    }

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function summary(): string
    {
        $value = $this->type === 'percent'
            ? rtrim(rtrim(number_format((float) $this->value, 2), '0'), '.') . '% off'
            : 'Tk ' . number_format((float) $this->value, 0) . ' off';

        if ($this->type === 'percent' && $this->max_discount) {
            $value .= ' (max Tk ' . number_format((float) $this->max_discount, 0) . ')';
        }

        return $value;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('marketing')
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
