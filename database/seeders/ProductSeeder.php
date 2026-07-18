<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\Category;
use App\Models\AttributeValue;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Clean existing products and images to ensure clean seed
        ProductImage::query()->delete();
        ProductVariant::query()->delete();
        StockMovement::query()->delete();
        Product::withTrashed()->forceDelete();

        // 2. Prepare paths for image copies
        $frontendPath = public_path('frontend/images/product');
        $storagePath = storage_path('app/public/products');

        if (!File::exists($storagePath)) {
            File::makeDirectory($storagePath, 0755, true);
        }

        $availableImages = [];
        if (File::exists($frontendPath)) {
            $files = File::files($frontendPath);
            foreach ($files as $file) {
                $availableImages[] = $file->getRealPath();
            }
        }

        // 3. Ensure we have the proper categories (using self-referencing parent_id for subcategories)
        $smartphoneCat = Category::firstOrCreate(
            ['type' => 'product', 'name->en' => 'Smartphone'], 
            ['name' => ['en' => 'Smartphone', 'bn' => 'স্মার্টফোন'], 'image' => null]
        );
        $laptopCat = Category::firstOrCreate(
            ['type' => 'product', 'name->en' => 'Laptops'], 
            ['name' => ['en' => 'Laptops', 'bn' => 'ল্যাপটপ'], 'image' => 'categories/laptop.jpg']
        );

        $iphoneSub = Category::firstOrCreate(['parent_id' => $smartphoneCat->id, 'name->en' => 'Iphone'], ['name' => ['en' => 'Iphone', 'bn' => 'আইফোন'], 'type' => 'product']);
        $androidSub = Category::firstOrCreate(['parent_id' => $smartphoneCat->id, 'name->en' => 'Android Phones'], ['name' => ['en' => 'Android Phones', 'bn' => 'অ্যান্ড্রয়েড ফোন'], 'type' => 'product']);
        
        $gamingLaptopSub = Category::firstOrCreate(['parent_id' => $laptopCat->id, 'name->en' => 'Gaming Laptops'], ['name' => ['en' => 'Gaming Laptops', 'bn' => 'গেমিং ল্যাপটপ'], 'type' => 'product']);
        $ultrabookSub = Category::firstOrCreate(['parent_id' => $laptopCat->id, 'name->en' => 'Ultrabooks'], ['name' => ['en' => 'Ultrabooks', 'bn' => 'আল্ট্রাবুক'], 'type' => 'product']);
        $businessLaptopSub = Category::firstOrCreate(['parent_id' => $laptopCat->id, 'name->en' => 'Business Laptops'], ['name' => ['en' => 'Business Laptops', 'bn' => 'বিজনেস ল্যাপটপ'], 'type' => 'product']);
        $studentLaptopSub = Category::firstOrCreate(['parent_id' => $laptopCat->id, 'name->en' => 'Student Laptops'], ['name' => ['en' => 'Student Laptops', 'bn' => 'স্টুডেন্ট ল্যাপটপ'], 'type' => 'product']);

        $phoneBrands = ['Apple', 'Samsung', 'Google', 'OnePlus', 'Xiaomi', 'Sony', 'Asus'];
        $laptopBrands = ['Apple', 'Razer', 'Asus', 'MSI', 'HP', 'Dell', 'Lenovo', 'Acer'];

        $phoneModels = ['Pro Max Ultra', 'Flagship Edition', 'Neo Plus', 'Supreme 5G', 'Fold Pro', 'Flip Pocket', 'Cyber Edition', 'Max Prime', 'Lite Edition', 'Alpha Z'];
        $laptopModels = ['Blade Extreme', 'ROG Zephyrus', 'Raider Super', 'Spectre Fold', 'XPS Titanium', 'ThinkPad X1 Carbon', 'MacBook Pro Max', 'Predator Helios', 'ZenBook Duo', 'Yoga Elite'];

        // Get some attribute values to attach to variants (if they exist)
        $attributeValues = AttributeValue::all();

        // Seed 50 products for testing
        for ($i = 1; $i <= 50; $i++) {
            $isLaptop = $i % 2 === 0;

            if ($isLaptop) {
                $category = $laptopCat;
                $subcategories = [$gamingLaptopSub, $ultrabookSub, $businessLaptopSub, $studentLaptopSub];
                $subcategory = $subcategories[array_rand($subcategories)];
                $brand = $laptopBrands[array_rand($laptopBrands)];
                $model = $laptopModels[array_rand($laptopModels)] . " " . rand(14, 18);
                $name = "{$brand} {$model} Series v{$i}";

                $price = rand(65000, 390000);
                $ram = ['8GB', '16GB', '32GB', '64GB'][rand(0, 3)] . " DDR5";
                $storage = ['512GB SSD', '1TB NVMe', '2TB NVMe'][rand(0, 2)];
                $battery = rand(60, 99) . "Wh";
                $screen = rand(13, 17) . "." . rand(3, 9) . "-inch OLED 120Hz";
                $os = ['Windows 11 Home', 'Windows 11 Pro', 'macOS Sequoia'][rand(0, 2)];

                $specifications = [
                    'OS' => $os, 'RAM' => $ram, 'Storage' => $storage,
                    'Processor' => ['Intel Core i7 14th Gen', 'Intel Core i9 14th Gen', 'AMD Ryzen 7 7840HS', 'AMD Ryzen 9 8945HS', 'Apple M3 Max'][rand(0, 4)],
                    'Graphics' => ['NVIDIA RTX 4060 8GB', 'NVIDIA RTX 4070 8GB', 'NVIDIA RTX 4080 12GB', 'Intel Iris Xe', 'Apple 30-Core GPU'][rand(0, 4)],
                    'Display' => $screen, 'Weight' => rand(12, 25)/10 . " kg"
                ];
            } else {
                $category = $smartphoneCat;
                $subcategory = rand(0, 1) === 0 ? $iphoneSub : $androidSub;
                $brand = $subcategory->id === $iphoneSub->id ? 'Apple' : $phoneBrands[rand(1, count($phoneBrands)-1)];
                $model = $phoneModels[array_rand($phoneModels)];
                $name = "{$brand} {$model} Flagship Series {$i}";

                $price = rand(25000, 185000);
                $ram = ['8GB', '12GB', '16GB'][rand(0, 2)];
                $storage = ['128GB UFS', '256GB UFS', '512GB UFS', '1TB'][rand(0, 3)];
                $battery = rand(4200, 5500) . " mAh";
                $screen = "6." . rand(1, 8) . "-inch Super AMOLED 120Hz";
                $os = $brand === 'Apple' ? 'iOS 18' : ['Android 14', 'OxygenOS 14', 'One UI 6.1'][rand(0, 2)];

                $specifications = [
                    'OS' => $os, 'RAM' => $ram, 'Storage' => $storage,
                    'Processor' => $brand === 'Apple' ? 'A18 Pro Bionic' : ['Snapdragon 8 Gen 3', 'Dimensity 9300', 'Google Tensor G4'][rand(0, 2)],
                    'Camera' => ['50MP Triple Camera', '108MP Quad Camera', '200MP Ultra Vision'][rand(0, 2)],
                    'Display' => $screen, 'Battery' => $battery
                ];
            }

            $isFlashDeal = rand(1, 10) <= 3; 
            $discount = $isFlashDeal ? [30, 40, 50, 60, 70][rand(0, 4)] : rand(0, 4) * 5;
            $discounted_price = $price - ($price * ($discount / 100));

            $product = Product::create([
                'category_id' => $subcategory->id, // Subcategories are children, we assign product to it
                'name' => ['en' => $name, 'bn' => $name],
                'description' => ['en' => "Experience elite performance and premium craftsmanship with the all-new {$name}.", 'bn' => ""],
                'brand' => $brand,
                'model' => $model,
                'warranty_period' => rand(1, 2) . " Years Brand Warranty",
                'price' => $price,
                'discount' => $discount,
                'discounted_price' => $discounted_price,
                'is_featured' => rand(1, 10) <= 3, 
                'is_best_seller' => rand(1, 10) <= 2, 
                'is_flash_deal' => $isFlashDeal, 
                'specifications' => $specifications,
                'low_stock_threshold' => 5
            ]);

            // --- INVENTORY SEEDING (VARIANTS) ---
            $numVariants = rand(1, 4);
            for ($v = 0; $v < $numVariants; $v++) {
                // Mix of out of stock (10%), low stock (20%), and normal stock
                $stockRand = rand(1, 10);
                if ($stockRand === 1) $stock = 0; // Out of stock
                elseif ($stockRand <= 3) $stock = rand(1, 4); // Low stock (triggers threshold)
                else $stock = rand(10, 50); // Normal stock

                $variant = $product->variants()->create([
                    'sku' => strtoupper(Str::random(6)),
                    'price' => rand(0, 1) ? null : $price + rand(-1000, 2000), // Random price override
                    'stock' => $stock
                ]);

                // Record Stock Movement
                if ($stock > 0) {
                    StockMovement::create([
                        'variant_id' => $variant->id,
                        'change' => $stock,
                        'type' => 'initial_stock',
                        'reason' => 'Initial seeder stock',
                        'source_type' => Product::class,
                        'source_id' => $product->id,
                    ]);
                }

                // Randomly attach 1 or 2 attributes if they exist in DB
                if ($attributeValues->isNotEmpty() && rand(0, 1)) {
                    $randomAttrs = $attributeValues->random(rand(1, min(2, $attributeValues->count())))->pluck('id')->toArray();
                    $variant->attributeValues()->sync($randomAttrs);
                }
            }

            // Assign Images
            if (!empty($availableImages)) {
                $imagesToAssign = array_rand($availableImages, min(count($availableImages), rand(1, 2)));
                if (!is_array($imagesToAssign)) $imagesToAssign = [$imagesToAssign];

                foreach ($imagesToAssign as $imgIndex) {
                    $sourceImgPath = $availableImages[$imgIndex];
                    $extension = File::extension($sourceImgPath);
                    $newFileName = 'seeded_' . Str::random(20) . '.' . $extension;
                    $destPath = $storagePath . '/' . $newFileName;
                    File::copy($sourceImgPath, $destPath);
                    ProductImage::create(['product_id' => $product->id, 'image' => 'products/' . $newFileName]);
                }
            }
        }
    }
}
