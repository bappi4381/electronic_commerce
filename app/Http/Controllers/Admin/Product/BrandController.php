<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $brands = Brand::withCount(['products' => fn ($q) => $q->withTrashed()])
            ->when($search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.product.brands.index', compact('brands', 'search'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        }

        Brand::create($data);

        return back()->with('success', "Brand \"{$data['name']}\" created.");
    }

    public function update(Request $request, Brand $brand)
    {
        $data = $this->validated($request, $brand);

        if ($request->hasFile('logo')) {
            if ($brand->logo) {
                Storage::disk('public')->delete($brand->logo);
            }
            $data['logo'] = $request->file('logo')->store('brands', 'public');
        } elseif ($request->boolean('remove_logo') && $brand->logo) {
            Storage::disk('public')->delete($brand->logo);
            $data['logo'] = null;
        }

        $brand->update($data);

        return back()->with('success', "Brand \"{$brand->name}\" updated.");
    }

    public function destroy(Brand $brand)
    {
        $count = $brand->products()->withTrashed()->count();

        if ($count > 0) {
            return back()->with('error', "\"{$brand->name}\" is used by {$count} product(s), including archived ones. Reassign them or deactivate the brand instead.");
        }

        if ($brand->logo) {
            Storage::disk('public')->delete($brand->logo);
        }

        $brand->delete();

        return back()->with('success', 'Brand deleted.');
    }

    private function validated(Request $request, ?Brand $brand = null): array
    {
        $data = $request->validate([
            'name'   => ['required', 'string', 'max:100', Rule::unique('brands', 'name')->ignore($brand?->id)],
            'logo'   => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'status' => 'nullable|boolean',
        ]);

        $data['status'] = $request->boolean('status');
        unset($data['logo']);

        return $data;
    }
}
