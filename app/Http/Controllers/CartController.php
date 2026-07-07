<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductVariant;

class CartController extends Controller
{
    public function index()
    {
        $cart = session()->get('cart', []);
        $total = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);
        return view('frontend.cart.index', compact('cart', 'total'));
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
                $variant = ProductVariant::with('attributeValues.attribute')->findOrFail($variantId);
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
                return redirect()->back()->with('error', 'This item is out of stock.');
            }

            $image = $product->images->first()->image ?? 'frontend/images/default-book.jpg';

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
