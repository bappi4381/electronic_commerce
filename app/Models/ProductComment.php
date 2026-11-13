<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * A customer review: optional 1–5 star rating plus text, shown publicly once approved.
 */
class ProductComment extends Model
{
    use HasFactory, LogsActivity;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUSES = [self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED];

    protected $fillable = ['product_id', 'user_id', 'rating', 'comment', 'status', 'moderated_by', 'moderated_at'];

    protected $casts = [
        'rating' => 'integer',
        'moderated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function moderator()
    {
        return $this->belongsTo(Admin::class, 'moderated_by');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    protected static function booted()
    {
        static::addGlobalScope('active_user', function ($builder) {
            $builder->whereHas('user', function ($query) {
                $query->where('status', 'active');
            });
        });

        // Keep the product's cached average in sync with approved reviews.
        $refresh = function (ProductComment $review) {
            if ($review->wasChanged(['status', 'rating']) || $review->wasRecentlyCreated || !$review->exists) {
                $review->product?->recalculateRating();
            }
        };
        static::saved($refresh);
        static::deleted($refresh);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('products')
            ->logOnly(['product_id', 'rating', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
