<?php

namespace App\Http\Controllers;

use App\Exceptions\CouponException;
use App\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
class CartController extends Controller
{
    public function __construct(private CouponService $coupons)
    {
    }

    public function index()
    {
        $cart = session()->get('cart', []);
        $total = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);
        $coupon = $this->coupons->preview(session('coupon_code'), Auth::user(), $total);

        // The cart may have changed since the coupon was applied (e.g. below the minimum now).
        if ($coupon['error']) {
            session()->forget('coupon_code');
            session()->now('error', $coupon['error'] . ' It has been removed.');
        }

        return view('frontend.cart.index', [
            'cart' => $cart,
            'total' => $total,
            'coupon' => $coupon['coupon'],
            'discount' => $coupon['discount'],
        ]);
    }

    public function applyCoupon(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|max:50']);
        $subtotal = collect(session('cart', []))->sum(fn ($item) => $item['price'] * $item['quantity']);

        if ($subtotal <= 0) {
            return redirect()->route('cart.index')->with('error', 'Add items to your cart before applying a coupon.');
        }

        try {
            $coupon = $this->coupons->findValid($data['code'], Auth::user(), $subtotal);
        } catch (CouponException $e) {
            return redirect()->route('cart.index')->withInput()->with('error', $e->getMessage());
        }

        session(['coupon_code' => $coupon->code]);

        return redirect()->route('cart.index')->with('success', "Coupon {$coupon->code} applied: you save tk. " . number_format($this->coupons->discountFor($coupon, $subtotal), 0) . '.');
    }

    public function removeCoupon()
    {
        session()->forget('coupon_code');

        return redirect()->route('cart.index')->with('success', 'Coupon removed.');
    }

    public function add(Request $request)
    {
        $product = Product::with('images')->findOrFail($request->product_id);
        $cart = session()->get('cart', []);

        $variantId = $request->variant_id;
        
        // Cart key distinguishes between different variants of the same product
        $cartKey = $variantId ? $product->id . '-' . $variantId : $product->id;

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity']++;
        } else {
            $price = $product->discounted_price ?? $product->price;
            $variantName = null;
            $stock = $product->stock;

            if ($variantId) {
                $variant = $product->variants()->with('attributeValues.attribute')->findOrFail($variantId);
                if ($variant->price) {
                    // Variant specific price, apply discount if needed or just use as is
                    // Assuming variant price is already final or base price
                    $price = $variant->price; 
                }
                $stock = $variant->stock;
                
                // Build variant string (e.g. "Color: Red | Size: M")
                $attrs = [];
                foreach ($variant->attributeValues as $av) {
                    $attrs[] = $av->attribute->name . ': ' . $av->value;
                }
                $variantName = implode(' | ', $attrs);
            }

            if ($stock <= 0) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => __('This item is out of stock.')], 422);
                }

                return redirect()->back()->with('error', 'This item is out of stock.');
            }

            $image = $product->images->first()?->image ?? 'frontend/images/default-book.jpg';

            $cart[$cartKey] = [
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'name'       => is_array($product->name) ? ($product->name['en'] ?? $product->name['bn']) : $product->name,
                'variant_name'=> $variantName,
                'quantity'   => 1,
                'price'      => $price,
                'image'      => $image,
                'stock'      => $stock,
            ];
        }

        session()->put('cart', $cart);

        if ($request->has('buy_now')) {
            return redirect()->route('checkout.index');
        }

        // Product cards add via fetch() and update the header badge from this payload.
        if ($request->expectsJson()) {
            $summary = \App\Support\Storefront::cart();

            return response()->json([
                'message' => __('Added to cart successfully.'),
                'count' => $summary['count'],
                'total' => $summary['total'],
                'total_text' => money($summary['total']),
            ]);
        }

        return redirect()->back()->with('success', 'Added to cart successfully.');
    }

    public function remove($id)
    {
        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart', $cart);
        }

        return redirect()->route('cart.index')->with('success', 'Product removed from cart!');
    }

    public function update(Request $request, $id)
    {
        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            $quantity = max(1, intval($request->quantity)); // prevent 0 quantity
            
            // Check stock limit
            if (isset($cart[$id]['stock']) && $quantity > $cart[$id]['stock']) {
                return redirect()->route('cart.index')->with('error', 'Requested quantity exceeds available stock.');
            }

            $cart[$id]['quantity'] = $quantity;
            session()->put('cart', $cart);
        }

        return redirect()->route('cart.index')->with('success', 'Cart updated!');
    }
}
