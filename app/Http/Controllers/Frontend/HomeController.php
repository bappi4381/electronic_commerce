<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        // Get latest 8 products for the main tab
        $latestProducts = Product::with('images')->latest()->take(8)->get();

        // Get Best Selling products (Manual)
        $bestSellers = Product::where('is_best_seller', true)->with('images')->latest()->take(8)->get();

        // Get Featured products (Manual)
        $featuredProducts = Product::where('is_featured', true)->with('images')->latest()->take(8)->get();

        // Get top 4 categories with their latest 4 products for "New Arrivals" section
        $arrivalCategories = Category::where('type', 'product')
            ->whereHas('products')
            ->with(['products' => function($q) {
                $q->with('images')->latest()->take(4);
            }])
            ->take(4)
            ->get();

        // Get latest 3 articles
        $articles = \App\Models\Article::where('status', 'published')->latest()->take(3)->get();

        // Get user wishlist IDs if logged in
        $wishlistIds = Auth::check() ? Wishlist::where('user_id', Auth::id())->pluck('product_id')->toArray() : [];

        // Get banners
        $heroBanner = \App\Models\Banner::where('type', 'hero')->where('status', true)->orderBy('order', 'asc')->first();
        $subBanners = \App\Models\Banner::where('type', 'sub_banner')->where('status', true)->orderBy('order', 'asc')->take(2)->get();
        $promoBanners = \App\Models\Banner::where('type', 'promo')->where('status', true)->orderBy('order', 'asc')->get();

        return view('frontend.pages.home', compact('latestProducts', 'bestSellers', 'featuredProducts', 'arrivalCategories', 'articles', 'wishlistIds', 'heroBanner', 'subBanners', 'promoBanners'));
    }
    public function products(Request $request)
    {
        $categories = Category::where('type', 'product')->get();

        $products = Product::query();

        // Search
        if ($request->search) {
            $products->where(function($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('brand', 'like', '%' . $request->search . '%')
                      ->orWhere('model', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->category) {
            $products->where('category_id', $request->category);
        }

        if ($request->min_price) {
            $products->where('price', '>=', $request->min_price);
        }

        if ($request->max_price) {
            $products->where('price', '<=', $request->max_price);
        }

        // Sorting
        $sort = $request->get('sort', 'newest');
        switch ($sort) {
            case 'price_low':
                $products->orderBy('price', 'asc');
                break;
            case 'price_high':
                $products->orderBy('price', 'desc');
                break;
            case 'newest':
            default:
                $products->latest();
                break;
        }

        $products = $products->paginate(10);

        return view('frontend.pages.products', compact('categories', 'products'));
    }
    public function show($id)
    {
        $product = Product::with(['images', 'category', 'comments.user', 'reactions'])->findOrFail($id);
        
        // Related products from the same category
        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $id)
            ->with('images')
            ->take(4)
            ->get();

        return view('frontend.pages.singleProduct', compact('product', 'relatedProducts'));
    }

    /**
     * Product Engagement Methods
     */
    public function storeProductComment(Request $request, $id)
    {
        if (!Auth::check()) {
            return back()->with('error', 'You must be logged in to comment.');
        }

        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        \App\Models\ProductComment::create([
            'product_id' => $id,
            'user_id' => Auth::id(),
            'comment' => $request->comment,
        ]);

        return back()->with('success', 'Your comment has been posted!');
    }

    public function toggleProductReaction(Request $request, $id)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $reaction = \App\Models\ProductReaction::where('product_id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if ($reaction) {
            $reaction->delete();
            $liked = false;
        } else {
            \App\Models\ProductReaction::create([
                'product_id' => $id,
                'user_id' => Auth::id(),
                'type' => 'like',
            ]);
            $liked = true;
        }

        $count = \App\Models\ProductReaction::where('product_id', $id)->count();

        return response()->json([
            'success' => true,
            'liked' => $liked,
            'count' => $count
        ]);
    }

    public function updateProductComment(Request $request, $id)
    {
        $comment = \App\Models\ProductComment::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $request->validate([
            'comment' => 'required|string|max:1000',
        ]);

        $comment->update([
            'comment' => $request->comment
        ]);

        return back()->with('success', 'Comment updated successfully!');
    }

    public function deleteProductComment($id)
    {
        $comment = \App\Models\ProductComment::withoutGlobalScopes()
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $comment->delete();

        return back()->with('success', 'Comment deleted successfully!');
    }
    
    public function flashDeals()
    {
        // Fetch products with discount > 0 or whatever logic
        $products = Product::whereNotNull('discount')->orWhere('discount', '>', 0)->with('images')->paginate(12);
        return view('frontend.pages.flash-deals', compact('products'));
    }

    public function contact()
    {
        return view('frontend.pages.contact');
    }

    public function fetchChat() {
        $sessionId = session()->getId();
        $userId = auth()->check() ? auth()->id() : null;

        $messages = \App\Models\Message::where(function($q) use ($sessionId, $userId) {
            $q->where('session_id', $sessionId);
            if ($userId) {
                $q->orWhere('user_id', $userId);
            }
        })->orderBy('created_at', 'asc')->get();

        return response()->json($messages);
    }

    public function sendChat(\Illuminate\Http\Request $request) {
        $request->validate(['message' => 'required|string']);
        $sessionId = session()->getId();
        $userId = auth()->check() ? auth()->id() : null;

        $message = \App\Models\Message::create([
            'user_id' => $userId,
            'session_id' => $userId ? null : $sessionId,
            'is_admin' => false,
            'message' => $request->message,
        ]);

        try {
            \Illuminate\Support\Facades\Log::info('Broadcasting message from Guest to Admin');
            broadcast(new \App\Events\MessageSent($message));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Pusher Broadcast Failed: ' . $e->getMessage());
        }

        return response()->json(['status' => 'Message Sent!', 'message' => $message]);
    }

    public function markChatAsRead() {
        $sessionId = session()->getId();
        $userId = auth()->check() ? auth()->id() : null;

        \App\Models\Message::where('is_admin', true)
            ->where('is_read', false)
            ->where(function($q) use ($sessionId, $userId) {
                if ($userId) {
                    $q->where('user_id', $userId);
                } else {
                    $q->where('session_id', $sessionId);
                }
            })->update(['is_read' => true]);

        return response()->json(['status' => 'Success']);
    }
}
