<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
     use HasFactory, \Spatie\Translatable\HasTranslations;

    public $translatable = ['title', 'content'];

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

    /** Minutes to read at ~200 words a minute (whitespace split, so Bangla text counts too). */
    public function readingMinutes(): int
    {
        $text = trim(strip_tags((string) $this->content));

        return max(1, (int) ceil(($text === '' ? 0 : count(preg_split('/\s+/u', $text))) / 200));
    }

    /** Plain-text summary for cards and meta descriptions. */
    public function excerpt(int $limit = 160): string
    {
        // Tags become spaces so headings and paragraphs don't run together ("Bangladesh.1. Battery").
        $text = html_entity_decode(strip_tags(preg_replace('/<[^>]+>/', ' $0 ', (string) $this->content)));

        return \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/u', ' ', $text)), $limit);
    }

    public function isLikedBy($user)
    {
        if (!$user) return false;
        return $this->reactions()->where('user_id', $user->id)->exists();
    }
}
