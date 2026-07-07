<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

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
            'name'  => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'type'  => 'nullable|in:blog,product',
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
        }

        // Save name as translatable JSON (EN + BN)
        Category::create([
            'name'  => ['en' => $request->name, 'bn' => $request->name_bn ?? $request->name],
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
        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }

    // Update category
    public function update(Request $request, $id)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $category = Category::findOrFail($id);

        // Update translatable name, preserving existing BN if not sent
        $existingName = is_array($category->getTranslations('name')) ? $category->getTranslations('name') : ['en' => $category->name, 'bn' => ''];
        $category->setTranslation('name', 'en', $request->name);
        if ($request->filled('name_bn')) {
            $category->setTranslation('name', 'bn', $request->name_bn);
        }

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
            $category->image = $imagePath;
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
