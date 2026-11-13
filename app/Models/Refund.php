<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Refund extends Model
{
    use LogsActivity;

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';

    public const METHODS = [
        'cash'    => 'Cash',
        'bkash'   => 'bKash',
        'nagad'   => 'Nagad',
        'bank'    => 'Bank transfer',
        'gateway' => 'Payment gateway',
    ];

    protected $fillable = [
        'order_id', 'payment_id', 'admin_id', 'amount', 'reason', 'status',
        'method', 'reference', 'restock', 'restocked_at', 'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'restock' => 'boolean',
        'restocked_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function badgeClasses(): string
    {
        return match ($this->status) {
            self::STATUS_COMPLETED => 'bg-emerald-50 text-emerald-600 border-emerald-100',
            self::STATUS_REJECTED  => 'bg-rose-50 text-rose-600 border-rose-100',
            default                => 'bg-amber-50 text-amber-600 border-amber-100',
        };
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('orders')
            ->logOnly(['order_id', 'amount', 'status', 'method', 'reference', 'restock'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
