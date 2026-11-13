<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\ProductAttributeService;
use App\Services\StockService;

class ProductController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    public function index(Request $request)
    {
        $search      = $request->input('search');
        $stockStatus = $request->input('stock_status');
        $brandId     = $request->integer('brand') ?: null;
        $archived    = $stockStatus === 'archived';

        $products = Product::with(['category', 'brand', 'images', 'variants'])
            ->when($archived, fn ($query) => $query->onlyTrashed())
            ->when($brandId, fn ($query, $id) => $query->where('brand_id', $id))
            ->when($search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhereHas('brand', fn ($brand) => $brand->where('name', 'like', "%{$search}%"))
                        ->orWhere('model', 'like', "%{$search}%");
                });
            })
            ->when($stockStatus === 'low', function ($query) {
                // products whose ANY variant stock <= low_stock_threshold
                $query->whereHas('variants', fn($q) => $q->whereColumn('stock', '<=', 'products.low_stock_threshold'));
            })
            ->when($stockStatus === 'out', function ($query) {
                $query->whereHas('variants', fn($q) => $q->where('stock', '<=', 0));
            })
            ->latest()
            ->paginate(20)
            ->appends(['search' => $search, 'stock_status' => $stockStatus, 'brand' => $brandId]);

        $brands = Brand::orderBy('name')->get(['id', 'name']);

        return view('admin.product.index', compact('products', 'search', 'stockStatus', 'archived', 'brands', 'brandId'));
    }

    public function show(Product $product)
    {
        $product->load(['category', 'images', 'variants.attributeValues.attribute', 'comments.user']);
        return view('admin.product.show', compact('product'));
    }

    public function destroyComment($id)
    {
        $comment = \App\Models\ProductComment::withoutGlobalScopes()->findOrFail($id);
        $comment->delete();
        return back()->with('success', 'Comment deleted successfully.');
    }

    public function create()
    {
        $attributeService = new ProductAttributeService();
        $categories = $attributeService->getRootCategoriesWithAttributes();
        $attributes = $attributeService->getAllAttributesWithValues();

        $selectedCategoryId = old('category_id');
        $selectedCategoryAttributes = $attributeService->getAttributesForCategory($selectedCategoryId);

        $attributePayload = $attributeService->makeJsPayload($categories, $attributes, 0);

        $brands = Brand::orderBy('name')->get(['id', 'name', 'status']);

        return view('admin.product.create', compact('categories', 'attributes', 'selectedCategoryAttributes', 'attributePayload', 'brands'));
    }

    public function store(Request $request)
    {
        $request->validate($this->rules() + [
            'description_en' => 'nullable|string',
            'description_bn' => 'nullable|string',
            'model'       => 'nullable|string|max:100',
            'low_stock_threshold' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $product = Product::create($this->productAttributes($request));

            $this->storeImages($request, $product);
            $this->syncVariants($product, $request->input('variants', []));
        });

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully with variants!');
    }

    public function edit(Product $product)
    {
        $attributeService = new ProductAttributeService();
        $categories = $attributeService->getRootCategoriesWithAttributes();
        $attributes = $attributeService->getAllAttributesWithValues();
        $product->load(['category', 'images', 'variants.attributeValues']);

        $selectedCategoryId = old('category_id', $product->category_id);
        $selectedCategoryAttributes = $attributeService->getAttributesForCategory($selectedCategoryId);

        $attributePayload = $attributeService->makeJsPayload($categories, $attributes, $product->variants->count());

        $brands = Brand::orderBy('name')->get(['id', 'name', 'status']);

        return view('admin.product.create', compact('product', 'categories', 'attributes', 'selectedCategoryAttributes', 'attributePayload', 'brands'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate($this->rules());

        DB::transaction(function () use ($request, $product) {
            $product->update($this->productAttributes($request));

            $this->storeImages($request, $product);
            $this->syncVariants($product, $request->input('variants', []));
        });

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully!');
    }

    /**
     * Archive the product. Images, variants and stock history are kept so it can be restored.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product archived. You can restore it from the "Archived" filter.');
    }

    public function restore(int $id)
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        return redirect()->route('admin.products.index')->with('success', 'Product restored.');
    }

    /**
     * Remove a single product image via AJAX or redirect
     */
    public function destroyImage(ProductImage $image)
    {
        Storage::disk('public')->delete($image->image);
        $image->delete();
        return back()->with('success', 'Image removed.');
    }

    private function rules(): array
    {
        return [
            'name_en'     => 'required|string|max:255',
            'name_bn'     => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'brand_id'    => 'nullable|exists:brands,id',
            'price'       => 'required|numeric|min:0',
            'discount'    => 'nullable|numeric|min:0|max:100',
            'video_link'  => 'nullable|url',
            'images'      => 'nullable|array',
            'images.*'    => 'image|mimes:jpg,jpeg,png,webp|max:2048',
            'variants'                        => 'nullable|array',
            'variants.*.id'                   => 'nullable|integer',
            'variants.*.sku'                  => 'nullable|string|max:100|distinct',
            'variants.*.price'                => 'nullable|numeric|min:0',
            'variants.*.stock'                => 'required|integer|min:0',
            'variants.*.original_stock'       => 'nullable|integer|min:0',
            'variants.*.attribute_value_ids'  => 'nullable|array',
            'variants.*.attribute_value_ids.*'=> 'integer|exists:attribute_values,id',
        ];
    }

    private function productAttributes(Request $request): array
    {
        $price = (float) $request->price;
        $discount = (float) $request->input('discount', 0);

        return [
            'name'        => ['en' => $request->name_en, 'bn' => $request->name_bn ?? ''],
            'description' => ['en' => $request->description_en ?? '', 'bn' => $request->description_bn ?? ''],
            'category_id' => $request->category_id,
            'price'       => $price,
            'discount'    => $discount,
            'discounted_price' => $price - ($price * ($discount / 100)),
            'brand_id'    => $request->input('brand_id') ?: null,
            'model'       => $request->model,
            'warranty_period' => $request->warranty_period,
            'video_link'  => $request->video_link,
            'low_stock_threshold' => $request->input('low_stock_threshold', 5),
            'is_flash_deal' => $request->boolean('is_flash_deal'),
        ];
    }

    private function storeImages(Request $request, Product $product): void
    {
        foreach ($request->file('images', []) as $file) {
            $product->images()->create(['image' => $file->store('products', 'public')]);
        }
    }

    /**
     * Update submitted variants in place (matched by id), create new ones, and remove the ones
     * left out: deleted when nothing references them, otherwise deactivated to keep history.
     */
    private function syncVariants(Product $product, array $rows): void
    {
        $adminId = auth('admin')->id();
        $existing = $product->allVariants()->get()->keyBy('id');
        $keptIds = [];

        foreach ($rows as $index => $row) {
            $id = !empty($row['id']) ? (int) $row['id'] : null;
            $variant = $id ? $existing->get($id) : null;

            if ($id && !$variant) {
                throw ValidationException::withMessages([
                    "variants.{$index}.id" => 'A variant no longer belongs to this product. Reload the page and try again.',
                ]);
            }

            $sku = filled($row['sku'] ?? null) ? trim($row['sku']) : null;
            $this->assertSkuAvailable($sku, $variant, $index);

            $attributes = [
                'sku'       => $sku,
                'price'     => filled($row['price'] ?? null) ? $row['price'] : null,
                'is_active' => true,
            ];
            $targetStock = (int) ($row['stock'] ?? 0);

            if ($variant) {
                $variant->update($attributes);

                // Apply the edit as a delta from the stock shown in the form, so orders placed meanwhile are not overwritten.
                $baseline = isset($row['original_stock']) && is_numeric($row['original_stock'])
                    ? (int) $row['original_stock']
                    : (int) $variant->stock;
                $delta = $targetStock - $baseline;

                if ($delta !== 0) {
                    $this->stock->adjust($variant, $delta, $product, 'Stock edited on product update', $adminId);
                }
            } else {
                $variant = $product->allVariants()->create($attributes + ['stock' => 0]);

                if ($targetStock > 0) {
                    $this->stock->adjust($variant, $targetStock, $product, 'Initial stock for new variant', $adminId, 'initial_stock');
                }
            }

            $variant->attributeValues()->sync($row['attribute_value_ids'] ?? []);
            $keptIds[] = $variant->id;
        }

        foreach ($existing->except($keptIds) as $removed) {
            if (!$removed->is_active) {
                continue;
            }

            if ($removed->hasHistory()) {
                $removed->update(['is_active' => false]);
            } else {
                $removed->attributeValues()->detach();
                $removed->delete();
            }
        }
    }

    private function assertSkuAvailable(?string $sku, ?ProductVariant $variant, int|string $index): void
    {
        if ($sku === null) {
            return;
        }

        $owner = ProductVariant::where('sku', $sku)
            ->when($variant, fn ($query) => $query->whereKeyNot($variant->id))
            ->first();

        if ($owner) {
            throw ValidationException::withMessages([
                "variants.{$index}.sku" => "SKU {$sku} is already used by " . ($owner->is_active ? 'another variant' : 'an archived variant') . '.',
            ]);
        }
    }
}
