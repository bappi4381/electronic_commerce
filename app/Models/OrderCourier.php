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
