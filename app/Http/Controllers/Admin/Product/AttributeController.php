<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    public function index(Request $request)
    {
        $attributes = Attribute::with(['values', 'categories'])->withCount('values')->orderBy('name')->get();
        $categories = Category::whereNull('parent_id')->with('children.attributes', 'attributes')->orderBy('name')->get();
        return view('admin.product.attributes.index', compact('attributes', 'categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:attributes,name',
            'type' => 'required|in:select,radio,text',
            'is_filterable' => 'boolean',
        ]);

        Attribute::create([
            'name'          => $request->name,
            'type'          => $request->type,
            'is_filterable' => $request->boolean('is_filterable', true),
        ]);

        return redirect()->route('admin.attributes.index')
            ->with('success', '"' . $request->name . '" attribute created successfully!');
    }

    public function update(Request $request, Attribute $attribute)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:attributes,name,' . $attribute->id,
            'type' => 'required|in:select,radio,text',
            'is_filterable' => 'boolean',
        ]);

        $attribute->update([
            'name'          => $request->name,
            'type'          => $request->type,
            'is_filterable' => $request->boolean('is_filterable', true),
        ]);

        return redirect()->route('admin.attributes.index')
            ->with('success', 'Attribute updated successfully!');
    }

    public function destroy(Attribute $attribute)
    {
        $attribute->delete();
        return redirect()->route('admin.attributes.index')
            ->with('success', 'Attribute deleted successfully!');
    }

    // ---- Attribute Values ----

    public function storeValue(Request $request, Attribute $attribute)
    {
        $request->validate([
            'value' => 'required|string|max:100',
        ]);

        $attribute->values()->create(['value' => $request->value]);

        return redirect()->route('admin.attributes.index')
            ->with('success', '"' . $request->value . '" added to ' . $attribute->name);
    }

    public function destroyValue(AttributeValue $value)
    {
        $value->delete();
        return redirect()->route('admin.attributes.index')
            ->with('success', 'Attribute value removed.');
    }

    // Get attributes for a specific category
    public function getByCategory(Request $request, $category_id)
    {
        $attributes = Attribute::with('values')
            ->whereHas('categories', function ($q) use ($category_id) {
                $q->where('category_id', $category_id);
            })
            ->get();

        return response()->json($attributes);
    }

    // Attach attributes to a category
    public function attachToCategory(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'attribute_ids' => 'required|array',
            'attribute_ids.*' => 'exists:attributes,id',
        ]);

        $category = Category::find($request->category_id);
        $category->attributes()->sync($request->attribute_ids);

        return redirect()->back()->with('success', 'Attributes assigned to category successfully!');
    }
}
