<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Category extends Model
{
     use HasFactory, \Spatie\Translatable\HasTranslations;

    protected $fillable = ['parent_id', 'name', 'image', 'type', 'icon', 'color'];

    public $translatable = ['name'];

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
    
    public function articles()
    {
        return $this->hasMany(Article::class);
    }
    
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class, 'category_attribute')
                    ->withTimestamps();
    }

    /**
     * Reason this category cannot be deleted, or null when it is safe to delete.
     * Archived products count because they still reference the category.
     */
    public function deletionBlocker(): ?string
    {
        $name = $this->getTranslation('name', 'en');

        if ($count = $this->children()->count()) {
            return "\"{$name}\" has {$count} subcategor" . ($count === 1 ? 'y' : 'ies') . '. Move or delete them first.';
        }

        if ($count = $this->products()->withTrashed()->count()) {
            return "\"{$name}\" still has {$count} product(s), including archived ones. Move them to another category first.";
        }

        if ($count = $this->articles()->count()) {
            return "\"{$name}\" still has {$count} article(s). Move them to another category first.";
        }

        return null;
    }
}
