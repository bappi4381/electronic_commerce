<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductsImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;
    public int $skipped  = 0;
    public array $errors = [];

    /**
     * Process the full collection. Each row = one variant.
     * Required columns: name_en, price, category, stock
     * Optional: name_bn, description_en, description_bn, brand, model,
     *           sku, variant_price, attribute_[AttributeName] (e.g. attribute_color, attribute_size)
     */
    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {
            // Cache for reuse within the import
            $categoryCache   = [];
            $attributeCache  = [];

            // Group rows by name_en (each unique name_en = one product, rows are its variants)
            $grouped = $rows->groupBy('name_en');

            foreach ($grouped as $nameEn => $variantRows) {
                if (empty($nameEn)) {
                    $this->skipped++;
                    continue;
                }

                $firstRow = $variantRows->first();

                // Resolve or create category
                $categoryName = trim($firstRow['category'] ?? '');
                if ($categoryName) {
                    if (!isset($categoryCache[$categoryName])) {
                        $cat = Category::where('name->en', $categoryName)->first();
                        if (!$cat) {
                            $cat = Category::create(['name' => ['en' => $categoryName, 'bn' => '']]);
                        }
                        $categoryCache[$categoryName] = $cat->id;
                    }
                    $categoryId = $categoryCache[$categoryName];
                } else {
                    $categoryId = null;
                }

                // Create or find the base product (match by name_en)
                $product = Product::where('name->en', $nameEn)->first();

                if (!$product) {
                    $price = (float) ($firstRow['price'] ?? 0);
                    $discount = (float) ($firstRow['discount'] ?? 0);

                    $product = Product::create([
                        'name'        => ['en' => $nameEn, 'bn' => $firstRow['name_bn'] ?? ''],
                        'description' => ['en' => $firstRow['description_en'] ?? '', 'bn' => $firstRow['description_bn'] ?? ''],
                        'category_id' => $categoryId,
                        'price'       => $price,
                        'discount'    => $discount,
                        'discounted_price' => $price - ($price * $discount / 100),
                        'brand'       => $firstRow['brand'] ?? null,
                        'model'       => $firstRow['model'] ?? null,
                        'warranty_period' => $firstRow['warranty_period'] ?? null,
                        'video_link'  => $firstRow['video_link'] ?? null,
                        'low_stock_threshold' => (int) ($firstRow['low_stock_threshold'] ?? 5),
                    ]);
                    $this->imported++;
                }

                // Create variants for each row
                foreach ($variantRows as $row) {
                    $variant = $product->variants()->create([
                        'sku'   => $row['sku'] ?? null,
                        'price' => isset($row['variant_price']) && $row['variant_price'] !== '' ? (float) $row['variant_price'] : null,
                        'stock' => (int) ($row['stock'] ?? 0),
                    ]);

                    // Handle attribute columns: attribute_color, attribute_size, etc.
                    $valueIds = [];
                    foreach ($row->toArray() as $key => $value) {
                        if (!str_starts_with($key, 'attribute_') || empty($value)) continue;

                        $attrName = str_replace('attribute_', '', $key);
                        $attrName = ucfirst(str_replace('_', ' ', $attrName));

                        // Cache attribute lookup
                        if (!isset($attributeCache[$attrName])) {
                            $attr = Attribute::firstOrCreate(['name' => $attrName], ['type' => 'select', 'is_filterable' => true]);
                            $attributeCache[$attrName] = $attr;
                        }

                        $attr    = $attributeCache[$attrName];
                        $attrVal = AttributeValue::firstOrCreate(['attribute_id' => $attr->id, 'value' => trim($value)]);
                        $valueIds[] = $attrVal->id;
                    }

                    if (!empty($valueIds)) {
                        $variant->attributeValues()->sync($valueIds);
                    }
                }
            }
        });
    }
}
