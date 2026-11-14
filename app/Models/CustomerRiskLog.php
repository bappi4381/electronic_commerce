<?php

namespace App\Models;

use App\Enums\RiskLevel;
use Illuminate\Database\Eloquent\Model;

/**
 * One delivery-history lookup for a phone number (fresh API call or cached result applied to an order).
 */
class CustomerRiskLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'raw_response' => 'array',
        'success_rate' => 'decimal:2',
        'checked_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function levelEnum(): ?RiskLevel
    {
        return RiskLevel::tryFrom((string) $this->risk_level);
    }

    /** Per-courier rows stored with the response: [name => [total, delivered, cancelled]]. */
    public function breakdown(): array
    {
        return $this->raw_response['breakdown'] ?? [];
    }

    public function error(): ?string
    {
        return $this->raw_response['error'] ?? null;
    }

    public function wasCached(): bool
    {
        return (bool) ($this->raw_response['cached'] ?? false);
    }
}
