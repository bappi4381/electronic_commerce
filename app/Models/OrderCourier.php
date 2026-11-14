<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderCourier extends Model
{
    protected $guarded = [];

    public const COURIERS = [
        'steadfast' => 'Steadfast',
        'pathao'    => 'Pathao',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'status_checked_at' => 'datetime',
        'shipping_charge' => 'decimal:2',
    ];

    /** The courier accepted the parcel (there is a consignment). */
    public function isBooked(): bool
    {
        return filled($this->consignment_id);
    }

    /** The last attempt to send was rejected and nothing is booked yet. */
    public function hasFailed(): bool
    {
        return !$this->isBooked() && $this->status === \App\Services\Courier\CourierStatus::FAILED;
    }

    public function statusLabel(): string
    {
        return \App\Services\Courier\CourierStatus::label($this->status);
    }

    public function statusTone(): string
    {
        return \App\Services\Courier\CourierStatus::tone($this->status);
    }

    public function order()
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function courierLabel(): string
    {
        return self::COURIERS[$this->courier_name] ?? ucfirst((string) $this->courier_name);
    }

    /**
     * Public tracking page for the customer, built from config/services.php templates.
     */
    public function trackingUrl(): ?string
    {
        $template = config("services.{$this->courier_name}.tracking_url");
        $code = $this->tracking_code ?: $this->consignment_id;

        if (!$template || !$code) {
            return null;
        }

        return str_replace(
            ['{code}', '{consignment_id}', '{phone}'],
            [urlencode($code), urlencode((string) $this->consignment_id), urlencode((string) $this->order?->customerDisplayPhone())],
            $template
        );
    }
}
