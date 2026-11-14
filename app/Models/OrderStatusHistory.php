<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;

class OrderStatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['order_id', 'from_status', 'to_status', 'admin_id', 'note', 'created_at'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function toStatus(): ?OrderStatus
    {
        return OrderStatus::tryFrom($this->to_status);
    }

    public function fromStatus(): ?OrderStatus
    {
        return $this->from_status ? OrderStatus::tryFrom($this->from_status) : null;
    }

    /** Putting an order on hold for risk, or releasing it back to pending: internal, hidden from customers. */
    public function isRiskReview(): bool
    {
        return $this->to_status === OrderStatus::OnHold->value
            || ($this->from_status === OrderStatus::OnHold->value && $this->to_status === OrderStatus::Pending->value);
    }
}
