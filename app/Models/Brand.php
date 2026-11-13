<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Stringable;

class Brand extends Model implements Stringable
{
    use LogsActivity;

    protected $fillable = ['name', 'slug', 'logo', 'status'];

    protected $casts = [
        'status' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Brand $brand) {
            $brand->name = trim(preg_replace('/\s+/', ' ', $brand->name));
            if (!$brand->slug || $brand->isDirty('name')) {
                $brand->slug = static::uniqueSlug($brand->name, $brand->id);
            }
        });
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Find a brand by name (case-insensitive) or create it; used by imports and seeders.
     */
    public static function resolve(?string $name): ?self
    {
        $name = trim(preg_replace('/\s+/', ' ', (string) $name));

        if ($name === '') {
            return null;
        }

        return static::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first() ?? static::create(['name' => $name]);
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'brand';
        $slug = $base;

        for ($i = 2; static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        return $slug;
    }

    /**
     * Lets views keep printing {{ $product->brand }}.
     */
    public function __toString(): string
    {
        return (string) $this->name;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('products')
            ->logOnly(['name', 'slug', 'logo', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
