<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Services\ProductAttributeService;
use App\Models\StockMovement;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search      = $request->input('search');
        $stockStatus = $request->input('stock_status');

        $products = Product::with(['category', 'images', 'variants'])
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
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
            ->appends(['search' => $search, 'stock_status' => $stockStatus]);

        return view('admin.product.index', compact('products', 'search', 'stockStatus'));
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

        $latest = Product::latest('id')->first();
        $nextProductId = $latest ? intval(substr($latest->product_id ?? 'PROD-0000', 5)) + 1 : 1;
        $nextProductId = 'PROD-' . str_pad($nextProductId, 4, '0', STR_PAD_LEFT);

        return view('admin.product.create', compact('categories', 'attributes', 'selectedCategoryAttributes', 'attributePayload', 'nextProductId'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_en'     => 'required|string|max:255',
            'name_bn'     => 'nullable|string|max:255',
            'description_en' => 'nullable|string',
            'description_bn' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'price'       => 'required|numeric|min:0',
            'brand'       => 'nullable|string|max:100',
            'model'       => 'nullable|string|max:100',
            'video_link'  => 'nullable|url',
            'low_stock_threshold' => 'nullable|integer|min:0',
        ]);

        DB::transaction(function () use ($request) {
            $product = Product::create([
                'name'        => ['en' => $request->name_en, 'bn' => $request->name_bn ?? ''],
                'description' => ['en' => $request->description_en ?? '', 'bn' => $request->description_bn ?? ''],
                'category_id' => $request->category_id,
                'price'       => $request->price,
                'discount'    => $request->input('discount', 0),
                'discounted_price' => $request->price - ($request->price * ($request->input('discount', 0) / 100)),
                'brand'       => $request->brand,
                'model'       => $request->model,
                'warranty_period' => $request->warranty_period,
                'video_link'  => $request->video_link,
                'low_stock_threshold' => $request->input('low_stock_threshold', 5),
                'is_featured'   => $request->boolean('is_featured'),
                'is_best_seller' => $request->boolean('is_best_seller'),
                'is_flash_deal' => $request->boolean('is_flash_deal'),
            ]);

            // Save product images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('products', 'public');
                    $product->images()->create(['image' => $path]);
                }
            }

            // Save variants
            if ($request->has('variants')) {
                foreach ($request->input('variants') as $variantData) {
                    if (empty($variantData['attribute_value_ids'])) continue;

                    $variant = $product->variants()->create([
                        'sku'   => $variantData['sku'] ?? null,
                        'price' => $variantData['price'] ?? null,
                        'stock' => $variantData['stock'] ?? 0,
                    ]);

                    // Sync the attribute values (Color=Red, Size=M)
                    $variant->attributeValues()->sync($variantData['attribute_value_ids']);

                    // Record initial stock movement if stock > 0
                    $stock = intval($variantData['stock'] ?? 0);
                    if ($stock !== 0) {
                        StockMovement::create([
                            'variant_id' => $variant->id,
                            'change' => $stock,
                            'type' => 'initial_stock',
                            'reason' => 'Initial stock on product create',
                            'source_type' => \App\Models\Product::class,
                            'source_id' => $product->id,
                            'admin_id' => null,
                        ]);
                    }
                }
            }
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

        return view('admin.product.create', compact('product', 'categories', 'attributes', 'selectedCategoryAttributes', 'attributePayload'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name_en'     => 'required|string|max:255',
            'name_bn'     => 'nullable|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'price'       => 'required|numeric|min:0',
            'video_link'  => 'nullable|url',
        ]);

        DB::transaction(function () use ($request, $product) {
            $product->update([
                'name'        => ['en' => $request->name_en, 'bn' => $request->name_bn ?? ''],
                'description' => ['en' => $request->description_en ?? '', 'bn' => $request->description_bn ?? ''],
                'category_id' => $request->category_id,
                'price'       => $request->price,
                'discount'    => $request->input('discount', 0),
                'discounted_price' => $request->price - ($request->price * ($request->input('discount', 0) / 100)),
                'brand'       => $request->brand,
                'model'       => $request->model,
                'warranty_period' => $request->warranty_period,
                'video_link'  => $request->video_link,
                'low_stock_threshold' => $request->input('low_stock_threshold', 5),
                'is_featured'    => $request->boolean('is_featured'),
                'is_best_seller' => $request->boolean('is_best_seller'),
                'is_flash_deal'  => $request->boolean('is_flash_deal'),
            ]);

            // Handle new images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('products', 'public');
                    $product->images()->create(['image' => $path]);
                }
            }

            // Update variants: remove old and recreate
            foreach ($product->variants as $old) {
                $old->attributeValues()->detach();
                $old->delete();
            }

            if ($request->has('variants')) {
                foreach ($request->input('variants') as $variantData) {
                    if (empty($variantData['attribute_value_ids'])) continue;

                    $variant = $product->variants()->create([
                        'sku'   => $variantData['sku'] ?? null,
                        'price' => $variantData['price'] ?? null,
                        'stock' => $variantData['stock'] ?? 0,
                    ]);

                    $variant->attributeValues()->sync($variantData['attribute_value_ids']);

                    // Record initial stock movement for recreated variant
                    $stock = intval($variantData['stock'] ?? 0);
                    if ($stock !== 0) {
                        StockMovement::create([
                            'variant_id' => $variant->id,
                            'change' => $stock,
                            'type' => 'initial_stock',
                            'reason' => 'Initial stock on product update (recreate)',
                            'source_type' => \App\Models\Product::class,
                            'source_id' => $product->id,
                            'admin_id' => null,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully!');
    }

    public function destroy(Product $product)
    {
        DB::transaction(function () use ($product) {
            foreach ($product->images as $img) {
                Storage::disk('public')->delete($img->image);
                $img->delete();
            }
            foreach ($product->variants as $variant) {
                $variant->attributeValues()->detach();
                $variant->delete();
            }
            $product->delete();
        });

        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
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
}
