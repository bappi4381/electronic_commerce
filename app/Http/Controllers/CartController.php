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

    public function index(\App\Services\CartPricer $pricer)
    {
        $cartChanges = array_merge(session('cart_changes', []), $pricer->refreshSession());
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
            'cartChanges' => $cartChanges,
            'total' => (float) $total,
            'coupon' => $coupon['error'] ? null : $coupon['coupon'],
            'discount' => (float) $coupon['discount'],
            'freeShippingThreshold' => app(\App\Services\CheckoutOptions::class)->freeShippingThreshold(),
            'recommendations' => app(\App\Services\CartRecommendations::class)->forCart($cart, 4),
            'wishlistIds' => Auth::check() ? \App\Models\Wishlist::where('user_id', Auth::id())->pluck('product_id')->toArray() : [],
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
            return $this->couponRedirect($request)->withInput()->with('coupon_error', $e->getMessage());
        }

        session(['coupon_code' => $coupon->code]);

        return $this->couponRedirect($request)->with('success', "Coupon {$coupon->code} applied: you save tk. " . number_format($this->coupons->discountFor($coupon, $subtotal), 0) . '.');
    }

    public function removeCoupon(Request $request)
    {
        session()->forget('coupon_code');

        return $this->couponRedirect($request)->with('success', 'Coupon removed.');
    }

    /** Coupons can be applied from the cart or the checkout page; send the customer back where they were. */
    private function couponRedirect(Request $request)
    {
        return redirect()->route($request->input('return') === 'checkout' ? 'checkout.index' : 'cart.index');
    }

    public function add(Request $request)
    {
        $product = Product::with('images')->findOrFail($request->product_id);
        $cart = session()->get('cart', []);

        $variantId = $request->variant_id;
        $quantity = max(1, min(99, (int) $request->input('quantity', 1)));

        // Cart key distinguishes between different variants of the same product
        $cartKey = $variantId ? $product->id . '-' . $variantId : $product->id;

        if (isset($cart[$cartKey])) {
            $stock = $cart[$cartKey]['stock'] ?? null;
            $wanted = $cart[$cartKey]['quantity'] + $quantity;
            $cart[$cartKey]['quantity'] = $stock !== null ? max(1, min($wanted, (int) $stock)) : $wanted;
        } else {
            $price = $product->unitPrice();
            $variantName = null;
            $stock = $product->stock;

            if ($variantId) {
                $variant = $product->variants()->with('attributeValues.attribute')->findOrFail($variantId);
                $variant->setRelation('product', $product);
                $price = $variant->unitPrice();
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
                'quantity'   => min($quantity, (int) $stock),
                'price'      => $price,
                'image'      => $image,
                'stock'      => $stock,
            ];
        }

        session()->put('cart', $cart);

        // Same event_id for the Pixel and the Conversions API. fetch() callers fire the returned event;
        // form posts and "Buy now" redirect, so the event waits in the session for the next page.
        $fbEvent = app(\App\Services\FacebookTracking::class)
            ->addToCart($product, (float) $cart[$cartKey]['price'], $quantity, $request, nextPage: !$request->expectsJson());

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
                'fb_event' => $fbEvent,
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
