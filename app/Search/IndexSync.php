<?php

namespace App\Search;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Search\Engines\DatabaseEngine;

/**
 * Keeps search in sync with catalog changes. Product saves are handled by Scout's Searchable trait;
 * stock, price-per-variant, image, brand and category changes re-index the affected products here.
 * Scout queues the Meilisearch writes (SCOUT_QUEUE=true).
 *
 * created/updated/deleted are used rather than saved because increment()/decrement() (stock moves)
 * only fire "updated".
 */
class IndexSync
{
    private const EVENTS = ['created', 'updated', 'deleted'];

    public static function register(): void
    {
        $flush = fn () => DatabaseEngine::flush();

        foreach ([...self::EVENTS, 'restored'] as $event) {
            Product::{$event}($flush);
        }

        foreach ([ProductVariant::class, ProductImage::class] as $model) {
            foreach (self::EVENTS as $event) {
                $model::{$event}(function ($child) use ($flush) {
                    $flush();
                    static::reindex(Product::whereKey($child->product_id));
                });
            }
        }

        foreach ([Brand::class, Category::class] as $model) {
            foreach (self::EVENTS as $event) {
                $model::{$event}(function ($item) use ($flush, $event) {
                    $flush();
                    SearchService::flushLookups();
                    if ($event === 'updated' && ($item->wasChanged('name') || $item->wasChanged('parent_id'))) {
                        static::reindex($item instanceof Brand
                            ? Product::where('brand_id', $item->id)
                            : Product::where('category_id', $item->id)->orWhereIn('category_id', Category::where('parent_id', $item->id)->select('id')));
                    }
                });
            }
        }
    }

    private static function reindex($query): void
    {
        if (config('scout.driver') === 'meilisearch') {
            $query->searchable();
        }
    }
}

