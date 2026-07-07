<?php

namespace App\Http\Controllers\Admin\Product;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

/**
 * SubcategoryController
 *
 * Manages child categories (subcategories) using the self-referencing
 * Category model with `parent_id`. The old `Subcategory` model has been
 * replaced by Category children.
 */
class SubcategoryController extends Controller
{
    // Store subcategory as a child Category
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name'        => 'required|string|max:255',
        ]);

        Category::create([
            'parent_id' => $request->category_id,
            'name'      => $request->name,
            'type'      => 'product',
        ]);

        return back()->with('success', 'Subcategory created successfully.');
    }

    // Update child category
    public function update(Request $request, $id)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name'        => 'required|string|max:255',
        ]);

        $subcategory = Category::findOrFail($id);
        $subcategory->update([
            'parent_id' => $request->category_id,
            'name'      => $request->name,
        ]);

        return back()->with('success', 'Subcategory updated successfully.');
    }

    // Delete child category
    public function destroy($id)
    {
        $subcategory = Category::findOrFail($id);
        $subcategory->delete();

        return back()->with('success', 'Subcategory deleted successfully.');
    }

    // AJAX: get child categories by parent category ID
    public function getSubcategories(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
        ]);

        $subcategories = Category::where('parent_id', $request->category_id)->get();

        return response()->json($subcategories);
    }

    // AJAX: get categories by type
    public function getCategoriesByType(Request $request)
    {
        $request->validate([
            'type' => 'required|in:blog,product',
        ]);

        $categories = Category::where('type', $request->type)
                               ->whereNull('parent_id')
                               ->get();

        return response()->json($categories);
    }
}
