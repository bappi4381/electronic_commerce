<?php

namespace App\Support;

use App\Models\Category;
use App\Models\Setting;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class Storefront
{
    /** Public URL for a stored image path (or an absolute URL as-is); null when there is none. */
    public static function imageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://') ? $path : Storage::disk('public')->url($path);
    }

    public static function categoryIcon(?Category $category): string
    {
        $icon = $category?->icon ?: ($category?->relationLoaded('parent') ? $category->parent?->icon : null);
        if ($icon) {
            return str_starts_with($icon, 'bi-') ? $icon : 'bi-' . $icon;
        }

        $root = $category?->relationLoaded('parent') && $category->parent ? $category->parent : $category;
        $name = mb_strtolower((string) $root?->getTranslation('name', 'en', false));

        return config('store.category_icons')[$name] ?? 'bi-box-seam';
    }

    /** Free-delivery threshold in BDT: the admin setting wins, config/store.php is the fallback. 0 = off. */
    public static function freeShippingThreshold(): float
    {
        $setting = Setting::get('free_shipping_threshold');

        return (float) ($setting !== null && $setting !== '' ? $setting : config('store.free_shipping_threshold', 0));
    }

    /** The current page in another language, keeping route parameters and the query string. */
    public static function localeUrl(string $locale): string
    {
        $route = request()->route();
        $name = $route?->getName();

        if (!$name || !Route::has($name)) {
            return url('/' . $locale);
        }

        $url = route($name, array_merge($route->originalParameters(), ['locale' => $locale]));
        $query = request()->getQueryString();

        return $query ? "{$url}?{$query}" : $url;
    }

    /** @return array{count:int, total:float} */
    public static function cart(): array
    {
        $cart = collect(session('cart', []));

        return [
            'count' => (int) $cart->sum('quantity'),
            'total' => (float) $cart->sum(fn ($item) => (float) $item['price'] * (int) $item['quantity']),
        ];
    }
}
