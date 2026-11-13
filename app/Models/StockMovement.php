<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StockMovement extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('stock')
            ->logOnly(['variant_id', 'change', 'type', 'reason', 'source_type', 'source_id'])
            ->setDescriptionForEvent(fn (string $event) => "Stock {$this->type} ({$this->change}) on variant #{$this->variant_id}");
    }

    protected $fillable = [
        'variant_id', 'change', 'type', 'reason', 'source_type', 'source_id', 'admin_id',
    ];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function source()
    {
        return $this->morphTo();
    }
}
