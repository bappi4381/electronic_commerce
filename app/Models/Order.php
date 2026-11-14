<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Order extends Model
{
    use SoftDeletes, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'stock_released_at' => 'datetime',
        'discount_amount' => 'decimal:2',
        'marketing_opt_in' => 'boolean',
        'risk_flags' => 'array',
        'risk_checked_at' => 'datetime',
        'risk_reviewed_at' => 'datetime',
        'advance_amount' => 'decimal:2',
    ];

    public function riskLevelEnum(): ?\App\Enums\RiskLevel
    {
        return \App\Enums\RiskLevel::tryFrom((string) $this->risk_level);
    }

    public function riskLogs()
    {
        return $this->hasMany(CustomerRiskLog::class)->latest('checked_at')->latest('id');
    }

    public function riskReviewer()
    {
        return $this->belongsTo(Admin::class, 'risk_reviewed_by');
    }

    public function hasRiskFlag(string $flag): bool
    {
        return in_array($flag, $this->risk_flags ?? [], true);
    }

    /** Unambiguous characters (no 0/O, 1/I/L) so IDs are easy to read back over the phone. */
    private const ID_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderCourier()
    {
        return $this->hasOne(OrderCourier::class);
    }

    public function courierLogs()
    {
        return $this->hasMany(CourierLog::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at')->orderBy('id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class)->latest();
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function statusEnum(): ?OrderStatus
    {
        return OrderStatus::tryFrom((string) $this->status);
    }

    public function paymentStatusEnum(): ?PaymentStatus
    {
        return PaymentStatus::tryFrom((string) $this->payment_status);
    }

    /**
     * @return list<OrderStatus>
     */
    public function allowedTransitions(): array
    {
        return $this->statusEnum()?->transitions() ?? [];
    }

    public function itemsEditable(): bool
    {
        return (bool) $this->statusEnum()?->isEditable() && !$this->paymentStatusEnum()?->isCollected();
    }

    public function itemsSubtotal(): float
    {
        return (float) $this->orderItems->sum('subtotal');
    }

    public function customerDisplayName(): string
    {
        return $this->customer_name ?: ($this->user->name ?? 'Guest');
    }

    public function customerDisplayEmail(): ?string
    {
        return $this->customer_email ?: $this->user?->email;
    }

    public function customerDisplayPhone(): ?string
    {
        return $this->phone ?: ($this->user?->phone ?: $this->alt_phone);
    }

    /**
     * ORD-YYYYMMDD-XXXXXX with a cryptographically random suffix (~887M combinations per day),
     * checked against all orders including soft-deleted ones. The unique index is the final guard.
     */
    public static function generateOrderId(): string
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $suffix = '';
            for ($i = 0; $i < 6; $i++) {
                $suffix .= self::ID_ALPHABET[random_int(0, strlen(self::ID_ALPHABET) - 1)];
            }

            $orderId = 'ORD-' . now()->format('Ymd') . '-' . $suffix;

            if (!static::withTrashed()->where('order_id', $orderId)->exists()) {
                return $orderId;
            }
        }

        throw new RuntimeException('Could not generate a unique order ID.');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_id)) {
                $order->order_id = static::generateOrderId();
            }
        });

        // Fraud checks and duplicate detection look orders up by the normalised number.
        static::saving(function ($order) {
            if ($order->isDirty('phone') || !$order->exists) {
                $order->phone_normalized = \App\Support\BdPhone::normalize($order->phone) ?? $order->phone_normalized;
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('orders')
            ->logOnly([
                'status', 'payment_status', 'total_price', 'delivery_charge', 'discount_amount',
                'shipping_address', 'city', 'region', 'postal_code', 'phone', 'alt_phone', 'order_notes',
                'risk_level', 'advance_amount',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
