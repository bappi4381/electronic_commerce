<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ProductVariant extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Stock is deliberately excluded: every stock change is logged through StockMovement.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('products')
            ->logOnly(['product_id', 'sku', 'price', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = ['product_id', 'sku', 'price', 'stock', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'variant_attribute_values', 'variant_id', 'attribute_value_id');
    }

    public function stockMovements()
    {
        return $this->hasMany(\App\Models\StockMovement::class, 'variant_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'variant_id');
    }

    /**
     * A variant referenced by orders or stock history must be kept (deactivated) rather than deleted.
     */
    public function hasHistory(): bool
    {
        return $this->orderItems()->exists() || $this->stockMovements()->exists();
    }

    /**
     * Selling price: the variant's own price, else the product's discounted price when it is a real discount.
     */
    public function unitPrice(): float
    {
        if ((float) $this->price > 0) {
            return (float) $this->price;
        }

        $product = $this->product;
        $price = (float) ($product?->price ?? 0);
        $discounted = (float) ($product?->discounted_price ?? 0);

        return $discounted > 0 && $discounted < $price ? $discounted : $price;
    }

    /**
     * Shape used by the admin searchable variant picker. Expects "product" loaded withTrashed (and "brand" if wanted).
     */
    public function toPickerArray(): array
    {
        $product = $this->product;

        return [
            'id'     => $this->id,
            'label'  => ($product?->name ?? 'Product #' . $this->product_id) . ($this->sku ? ' — ' . $this->sku : ''),
            'meta'   => collect([
                $product?->product_id,
                $product?->relationLoaded('brand') ? (string) ($product->brand ?? '') : null,
                $product?->trashed() ? 'archived' : null,
                $this->is_active ? null : 'inactive',
            ])->filter()->implode(' · '),
            'stock'  => (int) $this->stock,
            'price'  => $this->unitPrice(),
            'active' => (bool) $this->is_active && $product && !$product->trashed(),
        ];
    }

    public function label(): string
    {
        $name = Product::withTrashed()->find($this->product_id)?->name ?? 'Product #' . $this->product_id;

        return $this->sku ? "{$name} ({$this->sku})" : $name;
    }
}
