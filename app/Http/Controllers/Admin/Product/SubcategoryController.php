<?php

namespace App\Http\Controllers\Admin\Product;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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
        $data = $this->validated($request);
        $parent = Category::findOrFail($data['category_id']);

        Category::create([
            'parent_id' => $parent->id,
            'name'      => ['en' => $data['name'], 'bn' => $data['name_bn'] ?? $data['name']],
            'type'      => $parent->type ?? 'product',
        ]);

        return back()->with('success', 'Subcategory created successfully.');
    }

    // Update child category
    public function update(Request $request, $id)
    {
        $subcategory = Category::findOrFail($id);
        $data = $this->validated($request, $subcategory);
        $parent = Category::findOrFail($data['category_id']);

        $subcategory->parent_id = $parent->id;
        $subcategory->type = $parent->type ?? $subcategory->type;
        $subcategory->setTranslation('name', 'en', $data['name']);
        if (filled($data['name_bn'] ?? null)) {
            $subcategory->setTranslation('name', 'bn', $data['name_bn']);
        }
        $subcategory->save();

        return back()->with('success', 'Subcategory updated successfully.');
    }

    // Delete child category
    public function destroy($id)
    {
        $subcategory = Category::findOrFail($id);

        if ($reason = $subcategory->deletionBlocker()) {
            return back()->with('error', $reason);
        }

        $subcategory->delete();

        if ($subcategory->image) {
            Storage::disk('public')->delete($subcategory->image);
        }

        return back()->with('success', 'Subcategory deleted successfully.');
    }

    /** A parent can be any category, up to Category::MAX_DEPTH, and never one of its own descendants. */
    private function validated(Request $request, ?Category $subcategory = null): array
    {
        $data = $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'name'        => 'required|string|max:255',
            'name_bn'     => 'nullable|string|max:255',
        ]);

        if ($reason = ($subcategory ?? new Category())->parentRejection((int) $data['category_id'])) {
            throw ValidationException::withMessages(['category_id' => $reason]);
        }

        return $data;
    }
}
