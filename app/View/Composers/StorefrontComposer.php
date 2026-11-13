<?php

namespace App\View\Composers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Wishlist;
use App\Search\SearchService;
use App\Support\Storefront;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Data for the storefront chrome (header, mega menu, bottom nav, footer), computed once per request.
 */
class StorefrontComposer
{
    public const DEPARTMENTS_CACHE_KEY = 'store.departments.v1';

    private ?array $data = null;

    public function compose(View $view): void
    {
        $view->with($this->data ??= $this->build());
    }

    private function build(): array
    {
        $cart = Storefront::cart();
        $locale = app()->getLocale();

        return [
            'storeName' => Setting::get('site_name', 'ONEMALL'),
            'storeLogo' => Setting::get('logo') ? Storefront::imageUrl(Setting::get('logo')) : null,
            'storeHotline' => Setting::get('contact_phone') ?: config('store.hotline'),
            'storeDepartments' => collect(static::departments())->map(fn ($d) => $this->localize($d, $locale))->all(),
            'cartCount' => $cart['count'],
            'cartTotal' => $cart['total'],
            'wishlistCount' => Auth::check() ? Wishlist::where('user_id', Auth::id())->count() : 0,
            'freeShippingThreshold' => Storefront::freeShippingThreshold(),
            'searchTrending' => rescue(fn () => app(SearchService::class)->trending(), [], false),
        ];
    }

    /** Top-level product categories with subcategories, product counts and their leading brands. */
    public static function departments(): array
    {
        return Cache::remember(self::DEPARTMENTS_CACHE_KEY, 600, function () {
            $categories = Category::where('type', 'product')->whereNull('parent_id')->with('children')->orderBy('id')->get();

            return $categories->map(function (Category $category) {
                $ids = $category->children->pluck('id')->push($category->id);
                $count = Product::whereIn('category_id', $ids)->count();

                $brands = Brand::where('status', true)
                    ->whereHas('products', fn ($q) => $q->whereIn('category_id', $ids))
                    ->withCount(['products' => fn ($q) => $q->whereIn('category_id', $ids)])
                    ->orderByDesc('products_count')->take(4)->get();

                return [
                    'id' => $category->id,
                    'name' => $category->getTranslations('name'),
                    'icon' => Storefront::categoryIcon($category),
                    'image' => Storefront::imageUrl($category->image),
                    'count' => $count,
                    'children' => $category->children->map(fn ($child) => [
                        'id' => $child->id,
                        'name' => $child->getTranslations('name'),
                        'icon' => $child->icon ? Storefront::categoryIcon($child) : null,
                    ])->values()->all(),
                    'brands' => $brands->map(fn ($b) => ['id' => $b->id, 'name' => $b->name, 'slug' => $b->slug, 'logo' => Storefront::imageUrl($b->logo)])->all(),
                ];
            })->filter(fn ($d) => $d['count'] > 0 || $d['children'])->values()->all();
        });
    }

    /** Departments with names in the current language. */
    public static function localizedDepartments(): array
    {
        $locale = app()->getLocale();

        return array_map(fn ($d) => (new static())->localize($d, $locale), static::departments());
    }

    private function localize(array $department, string $locale): array
    {
        $pick = fn (array $names) => $names[$locale] ?? $names['en'] ?? reset($names) ?: '';
        $department['name'] = $pick($department['name']);
        $department['children'] = array_map(fn ($c) => ['name' => $pick($c['name'])] + $c, $department['children']);

        return $department;
    }
}
