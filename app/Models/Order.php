<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $guarded = [];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

     protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            // Generate a unique, date-based order ID: ORD-YYYYMMDD-XXXX
            $date = now()->format('Ymd');
            $lastOrder = self::whereDate('created_at', now()->toDateString())
                             ->latest('id')
                             ->first();

            $number = $lastOrder ? ((int) substr($lastOrder->order_id, -4)) + 1 : 1;
            $order->order_id = 'ORD-' . $date . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
        });
    }
}
