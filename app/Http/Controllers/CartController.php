<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

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

        if (isset($cart[$product->id])) {
            $cart[$product->id]['quantity']++;
        } else {
            $price = $product->discounted_price ?? $product->price;

            $image = $product->images->first()->image ?? 'frontend/images/default-book.jpg';

            $cart[$product->id] = [
                'name'     => $product->name,
                'quantity' => 1,
                'price'    => $price,
                'image'    => $image,
            ];
        }

        session()->put('cart', $cart);

        if ($request->has('buy_now')) {
            return redirect()->route('checkout.index');
        }

        return redirect()->back();
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
            $cart[$id]['quantity'] = $quantity;
            session()->put('cart', $cart);
        }

        return redirect()->route('cart.index')->with('success', 'Cart updated!');
    }
}
