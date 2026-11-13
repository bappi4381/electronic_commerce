<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasTranslations, LogsActivity;

    /** Linked from the storefront footer; they can be edited or unpublished but not deleted. */
    public const CORE_SLUGS = ['about', 'faq', 'privacy', 'terms', 'return-policy'];

    public $translatable = ['title', 'content', 'meta_description'];

    protected $fillable = ['slug', 'title', 'content', 'meta_description', 'is_published', 'sort_order'];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    public const FOOTER_CACHE_KEY = 'pages.footer';

    protected static function booted(): void
    {
        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget(self::FOOTER_CACHE_KEY));
        static::deleted(fn () => \Illuminate\Support\Facades\Cache::forget(self::FOOTER_CACHE_KEY));
    }

    /**
     * Published pages for the storefront footer, cached until a page changes.
     */
    public static function footerLinks()
    {
        return \Illuminate\Support\Facades\Cache::rememberForever(self::FOOTER_CACHE_KEY, fn () => static::published()->orderBy('sort_order')->get(['id', 'slug', 'title']));
    }

    public function isCore(): bool
    {
        return in_array($this->slug, self::CORE_SLUGS, true);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('settings')
            ->logOnly(['slug', 'title', 'is_published', 'sort_order'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
