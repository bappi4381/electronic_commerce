<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class AttributeValue extends Model
{
    use HasFactory;

    protected $fillable = ['attribute_id', 'value'];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * Variants (including deactivated ones that keep order history) using any of these values.
     * Deleting a used value would cascade-remove it from those variants and from old orders' option names.
     */
    public static function variantUsage(iterable $valueIds): int
    {
        $ids = collect($valueIds)->all();

        return $ids ? DB::table('variant_attribute_values')->whereIn('attribute_value_id', $ids)->distinct()->count('variant_id') : 0;
    }
}
