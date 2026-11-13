<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Search\BanglaText;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Dev check: flags products whose name, brand, category or image look inconsistent. Reports only;
 * never changes data.
 */
class CatalogAudit extends Command
{
    protected $signature = 'catalog:audit {--json : Output machine-readable JSON}';

    protected $description = 'Report products with name/brand/category mismatches and missing or broken images (read-only)';

    /** Product lines that belong to one manufacturer. */
    private const PRODUCT_LINES = [
        'macbook' => 'Apple', 'iphone' => 'Apple', 'ipad' => 'Apple', 'airpods' => 'Apple',
        'galaxy' => 'Samsung', 'redmi' => 'Xiaomi', 'poco' => 'Xiaomi',
        'rog' => 'Asus', 'zenbook' => 'Asus', 'vivobook' => 'Asus', 'zephyrus' => 'Asus',
        'yoga' => 'Lenovo', 'ideapad' => 'Lenovo', 'thinkpad' => 'Lenovo', 'legion' => 'Lenovo',
        'xps' => 'Dell', 'inspiron' => 'Dell', 'alienware' => 'Dell',
        'spectre' => 'HP', 'pavilion' => 'HP', 'envy' => 'HP', 'omen' => 'HP',
        'predator' => 'Acer', 'aspire' => 'Acer', 'nitro' => 'Acer', 'helios' => 'Acer',
        'raider' => 'MSI', 'blade' => 'Razer', 'pixel' => 'Google',
    ];

    /** Category keywords that only fit one brand. */
    private const CATEGORY_BRAND = ['iphone' => 'Apple'];

    private const PHONE_WORDS = ['phone', 'smartphone', 'iphone', 'android'];
    private const LAPTOP_WORDS = ['laptop', 'laptops', 'ultrabook', 'ultrabooks', 'notebook'];

    public function handle(): int
    {
        $issues = [];
        $hashes = [];
        $disk = Storage::disk('public');

        Product::with(['brand', 'category', 'images'])->orderBy('id')->chunk(200, function ($products) use (&$issues, &$hashes, $disk) {
            foreach ($products as $product) {
                $name = (string) $product->getTranslation('name', 'en', false);
                $words = BanglaText::words($name);
                $brand = (string) $product->brand?->name;
                $category = (string) $product->category?->getTranslation('name', 'en', false);
                $categoryWords = BanglaText::words($category);
                $add = function (string $type, string $detail) use (&$issues, $product, $name, $brand, $category) {
                    $issues[] = ['id' => $product->id, 'name' => $name, 'brand' => $brand, 'category' => $category, 'type' => $type, 'detail' => $detail];
                };

                foreach (self::PRODUCT_LINES as $line => $owner) {
                    if (in_array($line, $words, true) && strcasecmp($owner, $brand) !== 0) {
                        $add('name/brand', "\"{$line}\" is a {$owner} product line but the brand is {$brand}");
                    }
                }

                if ($brand !== '' && $words && strcasecmp($words[0], BanglaText::words($brand)[0] ?? '') !== 0) {
                    $first = ucfirst($words[0]);
                    if (in_array($first, array_unique(array_values(self::PRODUCT_LINES)), true)) {
                        $add('name/brand', "name starts with {$first} but the brand is {$brand}");
                    }
                }

                foreach (self::CATEGORY_BRAND as $keyword => $owner) {
                    if (in_array($keyword, $categoryWords, true) && strcasecmp($owner, $brand) !== 0) {
                        $add('category/brand', "category \"{$category}\" only fits {$owner}, brand is {$brand}");
                    }
                }

                $isPhoneCategory = (bool) array_intersect($categoryWords, self::PHONE_WORDS);
                $isLaptopCategory = (bool) array_intersect($categoryWords, self::LAPTOP_WORDS);
                $laptopName = array_intersect($words, ['macbook', 'zenbook', 'vivobook', 'xps', 'spectre', 'ideapad', 'thinkpad', 'yoga', 'laptop', 'predator', 'zephyrus', 'raider', 'helios']);
                $phoneName = array_intersect($words, ['5g', 'fold', 'smartphone', 'iphone', 'galaxy']);
                if ($isPhoneCategory && str_contains(mb_strtolower($name), 'pro max') && strcasecmp($brand, 'Apple') !== 0) {
                    $add('name/brand', "\"Pro Max\" is iPhone naming but the brand is {$brand}; check the image");
                }
                if ($isPhoneCategory && $laptopName) {
                    $add('name/category', 'laptop-style name (' . implode(', ', $laptopName) . ") in phone category \"{$category}\"");
                }
                if ($isLaptopCategory && $phoneName && !$laptopName) {
                    $add('name/category', 'phone-style name (' . implode(', ', $phoneName) . ") in laptop category \"{$category}\"");
                }

                if ($product->images->isEmpty()) {
                    $add('image', 'no images');
                }
                foreach ($product->images as $image) {
                    $path = (string) $image->image;
                    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                        continue;
                    }
                    if ($path === '' || !$disk->exists($path)) {
                        $add('image', "file missing: {$path}");
                        continue;
                    }
                    $hashes[md5_file($disk->path($path))][$product->id] = ['name' => $name, 'brand' => $brand, 'category' => $category];
                }

                $bn = (string) $product->getTranslation('name', 'bn', false);
                if ($bn !== '' && $bn === $name) {
                    $add('translation', 'Bangla name is a copy of the English name');
                }
            }
        });

        // The same picture on products of different brands almost always means a wrong image.
        foreach ($hashes as $owners) {
            if (count(array_unique(array_map('mb_strtolower', array_column($owners, 'brand')))) < 2) {
                continue;
            }
            foreach ($owners as $id => $owner) {
                $others = collect($owners)->except($id)->map(fn ($o, $otherId) => "#{$otherId} {$o['brand']}")->take(4)->implode(', ');
                $issues[] = ['id' => $id, 'name' => $owner['name'], 'brand' => $owner['brand'], 'category' => $owner['category'], 'type' => 'image/brand', 'detail' => "same image file as {$others}"];
            }
        }

        if ($this->option('json')) {
            $this->line(json_encode($issues, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        if (!$issues) {
            $this->info('No catalog issues found.');

            return self::SUCCESS;
        }

        $summary = collect($issues)->countBy('type');
        $this->table(['ID', 'Product', 'Brand', 'Category', 'Issue', 'Detail'], collect($issues)->map(fn ($i) => [
            $i['id'], mb_strimwidth($i['name'], 0, 42, '…'), $i['brand'], $i['category'], $i['type'], $i['detail'],
        ])->all());
        $this->warn(collect($issues)->pluck('id')->unique()->count() . ' products flagged · ' . $summary->map(fn ($n, $type) => "{$type}: {$n}")->implode(', '));
        $this->line('Nothing was changed. Fix the products in the admin panel.');

        return self::SUCCESS;
    }
}
