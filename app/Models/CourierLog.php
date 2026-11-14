<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    /** Parcel movements only; API call and webhook records are for admins. */
    public function scopeCustomerVisible($query)
    {
        return $query->whereNotNull('status_to')
            ->where('status_to', '!=', \App\Services\Courier\CourierStatus::FAILED)
            ->where(fn ($q) => $q->whereNull('action')->orWhereIn('action', ['booked', 'status_changed']));
    }

    /** Readable name of the logged action, e.g. "create_parcel" → "Create parcel". */
    public function actionLabel(): string
    {
        return $this->action ? \Illuminate\Support\Str::of($this->action)->replace('_', ' ')->ucfirst()->toString() : 'Status update';
    }

    public function order()
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }
}
