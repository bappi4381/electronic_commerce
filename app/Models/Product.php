<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes, \Spatie\Translatable\HasTranslations;

    protected $fillable = [
        'category_id', 'name', 'description',
        'price', 'discount', 'discounted_price', 'product_id', 'brand', 'model', 'warranty_period',
        'specifications', 'video_link', 'low_stock_threshold', 'is_flash_deal'
    ];

    public $translatable = ['name', 'description'];

    protected $casts = [
        'specifications'  => 'array',
        'is_flash_deal'   => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            $latest = Product::latest('id')->first();
            $number = $latest ? intval(substr($latest->product_id ?? 'PROD-0000', 5)) + 1 : 1;
            $product->product_id = 'PROD-' . str_pad($number, 4, '0', STR_PAD_LEFT);
        });
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

    public function comments()
    {
        return $this->hasMany(ProductComment::class);
    }

    public function reactions()
    {
        return $this->hasMany(ProductReaction::class);
    }

    public function variants()
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
