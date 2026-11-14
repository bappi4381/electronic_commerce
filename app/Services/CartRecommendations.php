<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * "Frequently bought together": in-stock products from the cart's categories that are not in the cart yet,
 * falling back to flash deals when the cart's categories have nothing else to offer.
 */
class CartRecommendations
{
    public function forCart(array $cart, int $limit = 3): Collection
    {
        $productIds = collect($cart)->pluck('product_id')->filter()->unique();
        $categoryIds = Product::whereIn('id', $productIds)->pluck('category_id')->filter()->unique();

        $query = fn () => Product::whereNotIn('id', $productIds)
            ->whereHas('variants', fn ($q) => $q->where('stock', '>', 0))
            ->with(['images', 'brand', 'category.parent', 'variants'])
            ->latest()
            ->take($limit);

        $products = $categoryIds->isNotEmpty() ? $query()->whereIn('category_id', $categoryIds)->get() : collect();

        return $products->isNotEmpty() ? $products : $query()->where('is_flash_deal', true)->get();
    }
}
