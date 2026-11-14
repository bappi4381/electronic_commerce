<?php

namespace App\Http\Controllers\Admin\Product;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

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

    /** The parent must be a top-level category other than the subcategory itself (two levels only). */
    private function validated(Request $request, ?Category $subcategory = null): array
    {
        return $request->validate([
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->whereNull('parent_id'),
                Rule::notIn(array_filter([$subcategory?->id])),
            ],
            'name'    => 'required|string|max:255',
            'name_bn' => 'nullable|string|max:255',
        ], [
            'category_id.exists' => 'Choose a top-level category as the parent.',
            'category_id.not_in' => 'A category cannot be its own parent.',
        ]);
    }
}
