<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    // Show categories & subcategories
    public function index()
    {
        $categories = Category::with('subcategories')->withCount('products')->get();
        return view('admin.product.category_subcategory', compact('categories'));
    }

    // Store category
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:categories,name',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'type'  => 'nullable|in:blog,product', 
        ]);

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
        }

        Category::create([
            'name'  => $request->name,
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
            'name' => 'required|unique:categories,name,' . $id,
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $category = Category::findOrFail($id);
        $category->name = $request->name;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('categories', 'public');
            $category->image = $imagePath;
        }

        $category->icon = $request->icon ?? $category->icon;
        $category->color = $request->color ?? $category->color;
        $category->save();

        return back()->with('success', 'Category updated successfully.');
    }
    public function getByType($type)
    {
        $categories = Category::where('type', $type)->get();
        return response()->json($categories);
    }
}
