<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

/**
 * Small bilingual catalog for the search acceptance tests (and handy in dev:
 * `php artisan db:seed --class=SearchDemoSeeder`). Safe to run twice: products are matched by English name.
 */
class SearchDemoSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'clothing' => ['en' => 'Clothing', 'bn' => 'পোশাক', 'icon' => 'bi-handbag'],
            'kitchen' => ['en' => 'Home & Kitchen', 'bn' => 'হোম ও কিচেন', 'icon' => 'bi-cup-hot'],
            'computers' => ['en' => 'Computers', 'bn' => 'কম্পিউটার', 'icon' => 'bi-laptop'],
            'sports' => ['en' => 'Sports', 'bn' => 'খেলাধুলা', 'icon' => 'bi-trophy'],
        ];

        $ids = [];
        foreach ($categories as $key => $c) {
            $category = Category::query()->where('type', 'product')->whereNull('parent_id')->get()
                ->first(fn ($cat) => $cat->getTranslation('name', 'en', false) === $c['en']);
            $category ??= Category::create(['name' => ['en' => $c['en'], 'bn' => $c['bn']], 'type' => 'product', 'icon' => $c['icon']]);
            $ids[$key] = $category->id;
        }

        $products = [
            ['Levi\'s T-Shirt - Slim Fit', 'Levi\'s টি-শার্ট', 'Levi\'s', 'clothing', 500, 5, 20],
            ['Aarong T-Shirt - Classic', 'Aarong টি-শার্ট', 'Aarong', 'clothing', 510, 0, 15],
            ['Walton Electric Kettle - WEK-18', 'Walton ইলেকট্রিক কেটলি', 'Walton', 'kitchen', 1760, 5, 12],
            ['Philips Electric Kettle - HD9350', 'Philips ইলেকট্রিক কেটলি', 'Philips', 'kitchen', 2270, 15, 3],
            ['Walton Rice Cooker - WRC-18', 'Walton রাইস কুকার', 'Walton', 'kitchen', 2950, 10, 8],
            ['Miyako Rice Cooker - MRC-18', 'Miyako রাইস কুকার', 'Miyako', 'kitchen', 2650, 0, 0],
            ['SanDisk Pen Drive Ultra Flair', 'SanDisk পেন ড্রাইভ', 'SanDisk', 'computers', 650, 8, 40],
            ['Kingston Pen Drive DataTraveler', 'Kingston পেন ড্রাইভ', 'Kingston', 'computers', 590, 0, 25],
            ['Nike Football - Pitch', 'Nike ফুটবল', 'Nike', 'sports', 1800, 0, 10],
        ];

        foreach ($products as [$en, $bn, $brandName, $categoryKey, $price, $discount, $stock]) {
            $existing = Product::query()->get()->first(fn ($p) => $p->getTranslation('name', 'en', false) === $en);
            if ($existing) {
                continue;
            }

            $product = Product::create([
                'name' => ['en' => $en, 'bn' => $bn],
                'description' => ['en' => "{$en}. Genuine product with warranty.", 'bn' => $bn],
                'category_id' => $ids[$categoryKey],
                'brand_id' => Brand::resolve($brandName)?->id,
                'price' => $price,
                'discount' => $discount,
                'discounted_price' => round($price * (100 - $discount) / 100),
            ]);

            ProductVariant::create([
                'product_id' => $product->id,
                'sku' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $en), 0, 6)) . '-' . $product->id,
                'price' => round($price * (100 - $discount) / 100),
                'stock' => $stock,
                'is_active' => true,
            ]);
        }
    }
}
