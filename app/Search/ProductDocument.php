<?php

namespace App\Search;

use App\Models\Product;
use App\Support\Money;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Builds the search document for a product (the Meilisearch record and the database engine's row).
 */
class ProductDocument
{
    public function __construct(private Dictionary $dictionary)
    {
    }

    public function make(Product $product): array
    {
        $product->loadMissing(['brand', 'category.parent.parent.parent', 'images', 'allVariants']);
        $activeVariants = $product->allVariants->where('is_active', true);

        $nameEn = (string) $product->getTranslation('name', 'en', false);
        $nameBn = (string) $product->getTranslation('name', 'bn', false);
        if ($nameBn === $nameEn) {
            $nameBn = '';
        }

        $brand = (string) ($product->brand?->name ?? '');
        $brandBn = implode(' ', $this->dictionary->banglaFor($brand));

        $categories = $product->category?->lineage() ?? collect();
        $categoryEn = $categories->map(fn ($c) => $c->getTranslation('name', 'en', false))->filter()->unique()->values();
        $categoryBn = $categories->map(fn ($c) => $c->getTranslation('name', 'bn', false))
            ->filter(fn ($n) => BanglaText::hasBangla($n))->unique()->values();

        $description = (string) $product->getTranslation('description', 'en', false);
        $price = (float) $product->price;
        $salePrice = (float) ($product->discounted_price ?: $product->price);
        $stock = (int) $activeVariants->sum('stock');
        $sold = (int) ($product->order_items_sum_quantity ?? $product->orderItems()->sum('quantity'));
        $image = $product->images->first()?->image;

        $banglaText = implode(' ', array_filter([$nameBn, $categoryBn->implode(' ')]));
        $banglish = trim(Transliterator::toLatin($banglaText) . ' ' . Transliterator::toLatin($brandBn));
        $englishText = implode(' ', array_filter([$nameEn, $brand, $categoryEn->implode(' ')]));

        return [
            'id' => $product->id,
            'name_en' => $nameEn,
            'name_bn' => $nameBn,
            'brand' => $brand,
            'brand_bn' => $brandBn,
            'brand_id' => $product->brand_id,
            'brand_slug' => $product->brand?->slug,
            'category' => $product->category_id,
            'category_ids' => $categories->pluck('id')->values()->all(),
            'category_names' => $categoryEn->merge($categoryBn)->values()->all(),
            'tags' => $this->tags($nameEn . ' ' . $brand . ' ' . $categoryEn->implode(' ')),
            'sku' => $product->allVariants->pluck('sku')->filter()->push($product->product_id)->filter()->unique()->values()->all(),
            'model' => (string) $product->model,
            'description_short' => Str::limit(trim(strip_tags($description)), 200, ''),
            'price' => $price,
            'sale_price' => $salePrice,
            'discount_percent' => Money::discountPercent($price, $salePrice),
            'in_stock' => $stock > 0,
            'stock' => $stock,
            'rating' => $product->rating_count ? (float) $product->rating_avg : 0,
            'rating_count' => (int) $product->rating_count,
            'sold' => $sold,
            'popularity' => $sold * 3 + (int) $product->rating_count * 2 + ($product->is_flash_deal ? 5 : 0) + ($stock > 0 ? 1 : 0),
            'image' => $image,
            'image_url' => $image ? Storage::disk('public')->url($image) : null,
            'created_at' => $product->created_at?->getTimestamp() ?? 0,
            'search_banglish' => $this->unique($banglish . ' ' . Transliterator::key($banglish)),
            'search_bn' => implode(' ', array_unique(array_merge($this->dictionary->banglaFor($englishText), [$brandBn]))),
            'search_compact' => $this->compactPairs([$nameEn, $nameBn, $banglish]),
        ];
    }

    /** Synonyms of words in the name/category so documents carry their own alternative spellings. */
    private function tags(string $text): array
    {
        return array_values(array_slice($this->dictionary->expand($text, 24), 0, 24));
    }

    /**
     * Single-token forms so "t shirt", "t-shirt" and "tshirt" meet: every adjacent word pair and every
     * hyphenated/apostrophised token with its separators removed.
     */
    private function compactPairs(array $texts): string
    {
        $out = [];
        foreach ($texts as $text) {
            $words = BanglaText::words($text);
            for ($i = 0; $i < count($words) - 1; $i++) {
                $out[] = $words[$i] . $words[$i + 1];
            }
            preg_match_all('/[\p{L}\p{M}\p{N}]+(?:[-\'.\/][\p{L}\p{M}\p{N}]+)+/u', BanglaText::normalize($text), $m);
            foreach ($m[0] as $joined) {
                $out[] = BanglaText::compact($joined);
            }
        }

        return implode(' ', array_values(array_unique(array_filter($out))));
    }

    private function unique(string $text): string
    {
        return implode(' ', array_values(array_unique(array_filter(explode(' ', $text)))));
    }
}
