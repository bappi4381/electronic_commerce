<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Admin\Product\StoreProductRequest;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $products = Product::with(['category', 'subcategory', 'images'])
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhereHas('category', fn($q) => $q->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('subcategory', fn($q) => $q->where('name', 'like', "%{$search}%"));
            })
            ->paginate(10)
            ->appends(['search' => $search]);

        return view('admin.product.index', compact('products'));
    }

    public function show(Product $product)
    {
        $product->load(['category', 'subcategory', 'images', 'comments.user']);
        return view('admin.product.show', compact('product'));
    }

    public function destroyComment($id)
    {
        $comment = \App\Models\ProductComment::withoutGlobalScopes()->findOrFail($id);
        $comment->delete();

        return back()->with('success', 'Comment deleted successfully.');
    }

    public function create(Request $request)
    {
        $categories = Category::where('type', 'product')->get();
        $selectedCategory = $request->input('category_id');
        $subcategories = $selectedCategory
            ? Subcategory::where('category_id', $selectedCategory)->get()
            : collect();

        // Generate next product ID for display
        $latest = Product::latest('id')->first();
        $nextProductId = $latest ? intval(substr($latest->product_id ?? 'PROD-0000', 5)) + 1 : 1;
        $nextProductId = 'PROD-' . str_pad($nextProductId, 4, '0', STR_PAD_LEFT);

        return view('admin.product.create', compact('categories', 'subcategories', 'selectedCategory', 'nextProductId'));
    }

    public function store(StoreProductRequest $request)
    {
        DB::transaction(function () use ($request) {
            $data = $request->only([
                'name', 'category_id', 'subcategory_id', 'description', 
                'price', 'stock',
                'brand', 'model', 'ram', 'storage', 'battery_capacity', 'screen_size', 'operating_system', 'color', 'warranty_period', 'specifications',
                'is_featured', 'is_best_seller'
            ]);

            $data['discount'] = 0;
            $data['discounted_price'] = $data['price'];

            $product = Product::create($data);

            // Save images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    $path = $file->store('products', 'public');
                    $product->images()->create([
                        'image' => $path,
                    ]);
                }
            }
        });

        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }


    public function edit(Product $product)
    {
        // Get all categories for the dropdown
        $categories = Category::where('type', 'product')->get();

        // Get subcategories for the selected category
        $subcategories = Subcategory::where('category_id', $product->category_id)->get();

        // Load relations (category, subcategory, images)
        $product->load(['category', 'subcategory', 'images']);

        // Pass data to the create/edit view
        return view('admin.product.create', compact('product', 'categories', 'subcategories'));
    }


    public function update(StoreProductRequest $request, Product $product)
    {
        DB::transaction(function () use ($request, $product) {
            $data = $request->only([
                'name', 'category_id', 'subcategory_id', 'description', 
                'price', 'stock',
                'brand', 'model', 'ram', 'storage', 'battery_capacity', 'screen_size', 'operating_system', 'color', 'warranty_period', 'specifications',
                'is_featured', 'is_best_seller'
            ]);

            $product->update($data);

            // Replace images if new ones uploaded
            if ($request->hasFile('images')) {
                // Delete old images
                foreach ($product->images as $img) {
                    Storage::disk('public')->delete($img->image);
                    $img->delete();
                }
                // Save new images
                foreach ($request->file('images') as $file) {
                    $path = $file->store('products', 'public');
                    $product->images()->create([
                        'image' => $path,
                    ]);
                }
            }
        });

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        DB::transaction(function () use ($product) {
            // Delete images
            foreach ($product->images as $img) {
                Storage::disk('public')->delete($img->image);
                $img->delete();
            }

            // Delete the product
            $product->delete();
        });

        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }
}
