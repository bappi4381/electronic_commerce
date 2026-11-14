<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttributeController extends Controller
{
    public function index(Request $request)
    {
        $attributes = Attribute::with(['values', 'categories'])->withCount('values')->orderBy('name')->get();
        $categories = Category::nested(null, ['attributes']);
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
        if ($used = AttributeValue::variantUsage($attribute->values()->pluck('id'))) {
            return redirect()->route('admin.attributes.index')->with('error',
                "\"{$attribute->name}\" is used by {$used} product " . Str::plural('variant', $used) . ' (including ones with past orders), so it can\'t be deleted. Remove it from those variants first, or simply stop using it.');
        }

        $attribute->delete();
        $this->audit($attribute, "Deleted attribute \"{$attribute->name}\"");

        return redirect()->route('admin.attributes.index')
            ->with('success', 'Attribute deleted successfully!');
    }

    // ---- Attribute Values ----

    public function storeValue(Request $request, Attribute $attribute)
    {
        $request->validate([
            'value' => 'required|string|max:100',
        ]);

        // Reported as a page message: every attribute card has its own "value" input.
        if ($attribute->values()->where('value', trim($request->value))->exists()) {
            return redirect()->route('admin.attributes.index')->with('error', "{$attribute->name} already has the value \"" . trim($request->value) . '".');
        }

        $attribute->values()->create(['value' => trim($request->value)]);

        return redirect()->route('admin.attributes.index')
            ->with('success', '"' . $request->value . '" added to ' . $attribute->name);
    }

    public function destroyValue(AttributeValue $value)
    {
        if ($used = AttributeValue::variantUsage([$value->id])) {
            return redirect()->route('admin.attributes.index')->with('error',
                "\"{$value->value}\" is used by {$used} product " . Str::plural('variant', $used) . ' (including ones with past orders), so it can\'t be removed.');
        }

        $value->delete();
        $this->audit($value->attribute, "Removed value \"{$value->value}\" from attribute \"{$value->attribute?->name}\"");

        return redirect()->route('admin.attributes.index')
            ->with('success', 'Attribute value removed.');
    }

    private function audit(?Attribute $attribute, string $description): void
    {
        activity('catalog')->causedBy(auth('admin')->user())->withProperties(['attribute_id' => $attribute?->id])->log($description);
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
