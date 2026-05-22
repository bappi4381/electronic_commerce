<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Clean existing products and images to ensure clean seed
        ProductImage::query()->delete();
        Product::withTrashed()->forceDelete();

        // 2. Prepare paths for image copies
        $frontendPath = public_path('frontend/images/product');
        $storagePath = storage_path('app/public/products');

        if (!File::exists($storagePath)) {
            File::makeDirectory($storagePath, 0755, true);
        }

        // Get all available product images from frontend folder
        $availableImages = [];
        if (File::exists($frontendPath)) {
            $files = File::files($frontendPath);
            foreach ($files as $file) {
                $availableImages[] = $file->getRealPath();
            }
        }

        // 3. Ensure we have the proper categories & subcategories
        $smartphoneCat = Category::updateOrCreate(
            ['name' => 'Smartphone', 'type' => 'product'],
            ['image' => null]
        );

        $laptopCat = Category::updateOrCreate(
            ['name' => 'Laptops', 'type' => 'product'],
            ['image' => 'categories/laptop.jpg']
        );

        // Ensure subcategories exist
        $iphoneSub = Subcategory::updateOrCreate(
            ['category_id' => $smartphoneCat->id, 'name' => 'Iphone']
        );

        $androidSub = Subcategory::updateOrCreate(
            ['category_id' => $smartphoneCat->id, 'name' => 'Android Phones']
        );

        $gamingLaptopSub = Subcategory::updateOrCreate(
            ['category_id' => $laptopCat->id, 'name' => 'Gaming Laptops']
        );

        $ultrabookSub = Subcategory::updateOrCreate(
            ['category_id' => $laptopCat->id, 'name' => 'Ultrabooks']
        );

        $businessLaptopSub = Subcategory::updateOrCreate(
            ['category_id' => $laptopCat->id, 'name' => 'Business Laptops']
        );

        $studentLaptopSub = Subcategory::updateOrCreate(
            ['category_id' => $laptopCat->id, 'name' => 'Student Laptops']
        );

        // Brands lists
        $phoneBrands = ['Apple', 'Samsung', 'Google', 'OnePlus', 'Xiaomi', 'Sony', 'Asus'];
        $laptopBrands = ['Apple', 'Razer', 'Asus', 'MSI', 'HP', 'Dell', 'Lenovo', 'Acer'];

        // Models lists to generate varied names
        $phoneModels = [
            'Pro Max Ultra', 'Flagship Edition', 'Neo Plus', 'Supreme 5G', 'Fold Pro',
            'Flip Pocket', 'Cyber Edition', 'Max Prime', 'Lite Edition', 'Alpha Z'
        ];

        $laptopModels = [
            'Blade Extreme', 'ROG Zephyrus', 'Raider Super', 'Spectre Fold', 'XPS Titanium',
            'ThinkPad X1 Carbon', 'MacBook Pro Max', 'Predator Helios', 'ZenBook Duo', 'Yoga Elite'
        ];

        // Seed 100 products
        for ($i = 1; $i <= 100; $i++) {
            $isLaptop = $i % 2 === 0;

            if ($isLaptop) {
                $category = $laptopCat;
                // Random subcategory for laptops
                $subcategories = [$gamingLaptopSub, $ultrabookSub, $businessLaptopSub, $studentLaptopSub];
                $subcategory = $subcategories[array_rand($subcategories)];
                $brand = $laptopBrands[array_rand($laptopBrands)];
                $model = $laptopModels[array_rand($laptopModels)] . " " . rand(14, 18);
                $name = "{$brand} {$model} Series v{$i}";

                $price = rand(65000, 390000);
                $discount = rand(0, 4) * 5; // 0%, 5%, 10%, 15%, 20%
                $discounted_price = $price - ($price * ($discount / 100));

                $ram = ['8GB', '16GB', '32GB', '64GB'][rand(0, 3)] . " DDR5";
                $storage = ['512GB SSD', '1TB NVMe', '2TB NVMe'][rand(0, 2)];
                $battery = rand(60, 99) . "Wh";
                $screen = rand(13, 17) . "." . rand(3, 9) . "-inch OLED 120Hz";
                $os = ['Windows 11 Home', 'Windows 11 Pro', 'macOS Sequoia'][rand(0, 2)];
                $color = ['Carbon Black', 'Mercury Silver', 'Titanium Grey', 'Cosmic Blue'][rand(0, 3)];

                $specifications = [
                    'OS' => $os,
                    'RAM' => $ram,
                    'Storage' => $storage,
                    'Processor' => ['Intel Core i7 14th Gen', 'Intel Core i9 14th Gen', 'AMD Ryzen 7 7840HS', 'AMD Ryzen 9 8945HS', 'Apple M3 Max'][rand(0, 4)],
                    'Graphics' => ['NVIDIA RTX 4060 8GB', 'NVIDIA RTX 4070 8GB', 'NVIDIA RTX 4080 12GB', 'Intel Iris Xe', 'Apple 30-Core GPU'][rand(0, 4)],
                    'Display' => $screen,
                    'Weight' => rand(12, 25)/10 . " kg"
                ];

            } else {
                $category = $smartphoneCat;
                $subcategory = rand(0, 1) === 0 ? $iphoneSub : $androidSub;
                $brand = $subcategory->id === $iphoneSub->id ? 'Apple' : $phoneBrands[rand(1, count($phoneBrands)-1)];
                $model = $phoneModels[array_rand($phoneModels)];
                $name = "{$brand} {$model} Flagship Series {$i}";

                $price = rand(25000, 185000);
                $discount = rand(0, 4) * 5; // 0%, 5%, 10%, 15%, 20%
                $discounted_price = $price - ($price * ($discount / 100));

                $ram = ['8GB', '12GB', '16GB'][rand(0, 2)];
                $storage = ['128GB UFS', '256GB UFS', '512GB UFS', '1TB'][rand(0, 3)];
                $battery = rand(4200, 5500) . " mAh";
                $screen = "6." . rand(1, 8) . "-inch Super AMOLED 120Hz";
                $os = $brand === 'Apple' ? 'iOS 18' : ['Android 14', 'OxygenOS 14', 'One UI 6.1'][rand(0, 2)];
                $color = ['Titanium Natural', 'Obsidian Black', 'Porcelain White', 'Emerald Green'][rand(0, 3)];

                $specifications = [
                    'OS' => $os,
                    'RAM' => $ram,
                    'Storage' => $storage,
                    'Processor' => $brand === 'Apple' ? 'A18 Pro Bionic' : ['Snapdragon 8 Gen 3', 'Dimensity 9300', 'Google Tensor G4'][rand(0, 2)],
                    'Camera' => ['50MP Triple Camera', '108MP Quad Camera', '200MP Ultra Vision'][rand(0, 2)],
                    'Display' => $screen,
                    'Battery' => $battery
                ];
            }

            // Create product
            $product = Product::create([
                'category_id' => $category->id,
                'subcategory_id' => $subcategory->id,
                'name' => $name,
                'description' => "Experience elite performance and premium craftsmanship with the all-new {$name}. Built with cutting-edge technology, it is fully optimized to provide a robust user experience, gorgeous visual output, and stunning style.",
                'brand' => $brand,
                'model' => $model,
                'ram' => $ram,
                'storage' => $storage,
                'battery_capacity' => $battery,
                'screen_size' => $screen,
                'operating_system' => $os,
                'color' => $color,
                'warranty_period' => rand(1, 2) . " Years Brand Warranty",
                'price' => $price,
                'discount' => $discount,
                'discounted_price' => $discounted_price,
                'stock' => rand(10, 85),
                'is_featured' => rand(1, 10) <= 3, // 30% featured
                'is_best_seller' => rand(1, 10) <= 2, // 20% best seller
                'is_flash_deal' => rand(1, 10) <= 2, // 20% flash deal
                'specifications' => $specifications
            ]);

            // Assign 1 to 2 random images from frontend product images
            if (!empty($availableImages)) {
                $imagesToAssign = array_rand($availableImages, min(count($availableImages), rand(1, 2)));
                if (!is_array($imagesToAssign)) {
                    $imagesToAssign = [$imagesToAssign];
                }

                foreach ($imagesToAssign as $imgIndex) {
                    $sourceImgPath = $availableImages[$imgIndex];
                    $extension = File::extension($sourceImgPath);
                    $newFileName = 'seeded_' . Str::random(20) . '.' . $extension;
                    $destPath = $storagePath . '/' . $newFileName;

                    // Copy the file to storage/app/public/products
                    File::copy($sourceImgPath, $destPath);

                    // Create database record
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image' => 'products/' . $newFileName
                    ]);
                }
            }
        }
    }
}
