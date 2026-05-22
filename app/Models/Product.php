<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id', 'subcategory_id', 'name', 'description',
        'price', 'discount','discounted_price', 'stock', 'product_id',
        'brand', 'model', 'ram', 'storage', 'battery_capacity', 'screen_size', 'operating_system', 'color', 'warranty_period',
        'specifications', 'is_featured', 'is_best_seller', 'is_flash_deal'
    ];

    protected $casts = [
        'specifications' => 'array',
        'is_featured' => 'boolean',
        'is_best_seller' => 'boolean',
        'is_flash_deal' => 'boolean'
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

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
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

    public function isLikedBy($user)
    {
        if (!$user) return false;
        return $this->reactions()->where('user_id', $user->id)->exists();
    }
}
