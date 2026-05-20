<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function index()
    {
        $wishlistItems = Wishlist::where('user_id', Auth::id())
            ->with([
                'product' => function ($q) {
                    $q->with('images');
                }
            ])
            ->latest()
            ->get();

        return view('frontend.pages.wishlist', compact('wishlistItems'));
    }

    public function fetchWishlist()
    {
        $wishlistItems = Wishlist::where('user_id', Auth::id())
            ->with(['product.images'])
            ->latest()
            ->get();

        return view('frontend.partials.wishlist-items', compact('wishlistItems'))->render();
    }

    public function toggle(Request $request, $productId)
    {
        if (!Auth::check()) {
            return response()->json([
                'status' => 'unauthenticated',
                'message' => 'Please login to manage your wishlist.'
            ], 401);
        }

        $wishlist = Wishlist::where('user_id', Auth::id())
            ->where('product_id', $productId)
            ->first();

        if ($wishlist) {
            $wishlist->delete();
            return response()->json([
                'status' => 'removed',
                'message' => 'Removed from wishlist',
                'count' => Wishlist::where('user_id', Auth::id())->count()
            ]);
        }

        Wishlist::create([
            'user_id' => Auth::id(),
            'product_id' => $productId
        ]);

        return response()->json([
            'status' => 'added',
            'message' => 'Added to wishlist',
            'count' => Wishlist::where('user_id', Auth::id())->count()
        ]);
    }

    public function remove($id)
    {
        $wishlist = Wishlist::where('user_id', Auth::id())->findOrFail($id);
        $wishlist->delete();

        return redirect()->back()->with('success', 'Product removed from wishlist.');
    }
}
