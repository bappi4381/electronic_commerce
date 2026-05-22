<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $coupons = Coupon::latest()->get();
        $search = $request->input('search');

        // Get all products to manage their individual discounts with search support
        $products = Product::with('category')
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(20)
            ->appends(['search' => $search]);

        $discountedProductsCount = Product::where('discount', '>', 0)->count();
        
        return view('admin.marketing.index', compact('coupons', 'products', 'discountedProductsCount'));
    }

    public function updateProductDiscount(Request $request, Product $product)
    {
        $request->validate([
            'discount' => 'required|numeric|min:0|max:100',
            'is_flash_deal' => 'nullable|boolean',
        ]);

        $product->update([
            'discount' => $request->discount,
            'is_flash_deal' => $request->has('is_flash_deal'),
            'discounted_price' => $product->price - ($product->price * ($request->discount / 100))
        ]);

        return back()->with('success', 'Discount updated for ' . $product->name);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|unique:coupons,code',
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
            'expiry_date' => 'nullable|date|after:today',
        ]);

        Coupon::create($request->all());

        return back()->with('success', 'Coupon created successfully.');
    }

    public function update(Request $request, Coupon $coupon)
    {
        $request->validate([
            'code' => 'required|unique:coupons,code,' . $coupon->id,
            'type' => 'required|in:fixed,percent',
            'value' => 'required|numeric|min:0',
            'expiry_date' => 'nullable|date',
        ]);

        $coupon->update($request->all());

        return back()->with('success', 'Coupon updated successfully.');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return back()->with('success', 'Coupon deleted successfully.');
    }

    public function toggleStatus(Coupon $coupon)
    {
        $coupon->update(['status' => !$coupon->status]);
        return back()->with('success', 'Coupon status updated.');
    }
}
