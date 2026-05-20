<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
     use HasFactory;

    protected $fillable = ['title', 'content', 'slug','image','published_at','status', 'category_id'];

   
    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function comments()
    {
        return $this->hasMany(BlogComment::class);
    }

    public function reactions()
    {
        return $this->hasMany(BlogReaction::class);
    }

    public function isLikedBy($user)
    {
        if (!$user) return false;
        return $this->reactions()->where('user_id', $user->id)->exists();
    }
}
