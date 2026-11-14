<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoriesController extends Controller
{
    // Show categories & subcategories (only top-level parent categories)
    public function index()
    {
        $categories = Category::with('children')
            ->withCount('products')
            ->whereNull('parent_id')
            ->get();
        return view('admin.product.category_subcategory', compact('categories'));
    }

    // Store category
    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'name_bn' => 'nullable|string|max:255',
            'image'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'type'    => 'nullable|in:blog,product',
            'icon'    => 'nullable|string|max:60',
            'color'   => 'nullable|string|max:30',
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
        }

        // Save name as translatable JSON (EN + BN)
        Category::create([
            'name'  => ['en' => $request->name, 'bn' => $request->filled('name_bn') ? $request->name_bn : $request->name],
            'type'  => $request->type ?? 'product',
            'image' => $imagePath,
            'icon'  => $request->icon ?? 'bi-tag',
            'color' => $request->color ?? 'slate-600',
        ]);

        return back()->with('success', 'Category created successfully.');
    }

    // Delete category
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        if ($reason = $category->deletionBlocker()) {
            return back()->with('error', $reason);
        }

        $category->delete();

        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        return back()->with('success', 'Category deleted successfully.');
    }

    // Update category
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'name_bn' => 'nullable|string|max:255',
            'image'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'icon'    => 'nullable|string|max:60',
            'color'   => 'nullable|string|max:30',
        ]);

        $category = Category::findOrFail($id);

        // Update translatable name, preserving existing BN if not sent
        $category->setTranslation('name', 'en', $request->name);
        if ($request->filled('name_bn')) {
            $category->setTranslation('name', 'bn', $request->name_bn);
        }

        if ($request->hasFile('image')) {
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $category->image = $request->file('image')->store('categories', 'public');
        }

        $category->icon  = $request->icon  ?? $category->icon;
        $category->color = $request->color ?? $category->color;
        $category->save();

        return back()->with('success', 'Category updated successfully.');
    }

    public function getByType($type)
    {
        $categories = Category::where('type', $type)->whereNull('parent_id')->get();
        return response()->json($categories);
    }
}
