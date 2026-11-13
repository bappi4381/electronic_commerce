<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    use SoftDeletes, \Spatie\Translatable\HasTranslations, LogsActivity, Searchable;

    // ── Search (Scout / Meilisearch) ────────────────────────

    public function searchableAs(): string
    {
        return config('search.meilisearch.index', 'products');
    }

    public function toSearchableArray(): array
    {
        return app(\App\Search\ProductDocument::class)->make($this);
    }

    public function shouldBeSearchable(): bool
    {
        return !$this->trashed();
    }

    protected function makeAllSearchableUsing($query)
    {
        return $query->with(['brand', 'category.parent', 'images', 'allVariants'])->withSum('orderItems', 'quantity');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('products')
            ->logOnly([
                'product_id', 'category_id', 'brand_id', 'name', 'price', 'discount', 'discounted_price',
                'model', 'warranty_period', 'video_link', 'low_stock_threshold', 'is_flash_deal', 'deleted_at',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'category_id', 'brand_id', 'name', 'description',
        'price', 'discount', 'discounted_price', 'product_id', 'model', 'warranty_period',
        'specifications', 'video_link', 'low_stock_threshold', 'is_flash_deal'
    ];

    public $translatable = ['name', 'description'];

    /** Storefront lists print the brand on every card. */
    protected $with = ['brand'];

    protected $casts = [
        'specifications'  => 'array',
        'is_flash_deal'   => 'boolean',
        'rating_avg'      => 'decimal:2',
        'rating_count'    => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            $product->product_id = static::nextProductId();
        });
    }

    /**
     * Archived (soft-deleted) products keep their unique product_id, so they must be counted.
     */
    public static function nextProductId(): string
    {
        $latest = static::withTrashed()->latest('id')->value('product_id');
        $number = $latest ? intval(substr($latest, 5)) + 1 : 1;

        return 'PROD-' . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    // ── Relationships ───────────────────────────────────────

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * All reviews regardless of moderation status (admin use).
     */
    public function comments()
    {
        return $this->hasMany(ProductComment::class);
    }

    public function approvedReviews()
    {
        return $this->hasMany(ProductComment::class)->where('status', ProductComment::STATUS_APPROVED);
    }

    /**
     * Cache the average of approved, rated reviews on the product for listings.
     */
    public function recalculateRating(): void
    {
        $stats = $this->approvedReviews()->whereNotNull('rating')->selectRaw('AVG(rating) as avg_rating, COUNT(*) as total')->first();

        $this->forceFill([
            'rating_avg'   => $stats?->total ? round((float) $stats->avg_rating, 2) : null,
            'rating_count' => (int) ($stats?->total ?? 0),
        ])->saveQuietly();
    }

    public function reactions()
    {
        return $this->hasMany(ProductReaction::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Sellable variants. Deactivated variants are kept only for order and stock history.
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class)->where('is_active', true);
    }

    public function allVariants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function specifications()
    {
        return $this->belongsToMany(AttributeValue::class, 'product_specifications', 'product_id', 'attribute_value_id');
    }

    // ── Helpers ─────────────────────────────────────────────

    public function isLikedBy($user)
    {
        if (!$user) return false;
        return $this->reactions()->where('user_id', $user->id)->exists();
    }

    /**
     * Total stock across all variants.
     */
    public function getTotalStockAttribute(): int
    {
        return $this->variants->sum('stock');
    }

    /**
     * Alias for total stock across all variants.
     */
    public function getStockAttribute(): int
    {
        return $this->variants->sum('stock');
    }

    /**
     * Whether any variant has stock > 0.
     */
    public function getInStockAttribute(): bool
    {
        return $this->variants->sum('stock') > 0;
    }
}
