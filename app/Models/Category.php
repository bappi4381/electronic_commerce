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
}
