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
        // Get initial 12 products for Just For You section
        $latestProducts = Product::with('images')->latest()->take(15)->get();

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

        // Get Flash Deal products (Must have discount and at least one variant in stock)
        $flashDealProducts = Product::where('is_flash_deal', true)
            ->where('discount', '>', 0)
            ->whereHas('variants', fn($q) => $q->where('stock', '>', 0))
            ->with('images')
            ->latest()
            ->take(10)
            ->get();

        // Get Campaign Settings (Dynamic)
        $flashDealTitle = \App\Models\Setting::where('key', 'flash_deal_title')->first()?->value ?? "Cyber Monday Extreme Deals";
        $flashDealEndTime = \App\Models\Setting::where('key', 'flash_deal_end_time')->first()?->value ?? "2026-06-30 23:59:59";

        // Get banners
        $heroBanners = \App\Models\Banner::where('type', 'hero')->where('status', true)->orderBy('order', 'asc')->get();
        $subBanners = \App\Models\Banner::where('type', 'sub_banner')->where('status', true)->orderBy('order', 'asc')->take(2)->get();
        $promoBanners = \App\Models\Banner::where('type', 'promo')->where('status', true)->orderBy('order', 'asc')->get();

        // Get all categories for the horizontal scroll section (Latest First)
        $allCategories = Category::where('type', 'product')->latest()->get();

        return view('frontend.pages.home', compact(
            'latestProducts', 
            'arrivalCategories', 
            'articles', 
            'wishlistIds', 
            'heroBanners', 
            'subBanners', 
            'promoBanners', 
            'flashDealProducts',
            'flashDealTitle',
            'flashDealEndTime',
            'allCategories'
        ));
    }
    public function products(Request $request)
    {
        $categories = Category::where('type', 'product')->withCount('products')->get();

        $products = Product::query();

        // Search (name is now JSON, use JSON_EXTRACT or LIKE on raw value)
        if ($request->search) {
            $s = $request->search;
            $products->where(function($query) use ($s) {
                $query->whereRaw('JSON_EXTRACT(name, "$.en") LIKE ?', ["%{$s}%"])
                      ->orWhereRaw('JSON_EXTRACT(name, "$.bn") LIKE ?', ["%{$s}%"])
                      ->orWhere('brand', 'like', "%{$s}%")
                      ->orWhere('model', 'like', "%{$s}%");
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

        $products = $products->paginate(12);

        return view('frontend.pages.products', compact('categories', 'products'));
    }
    public function show($id)
    {
        $product = Product::with(['images', 'category', 'comments.user', 'reactions', 'variants.attributeValues.attribute'])->findOrFail($id);
        
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
        // Fetch all products with actual discounts (discount > 0)
        $products = Product::where('discount', '>', 0)->with('images')->latest()->paginate(12);
        return view('frontend.pages.flash-deals', compact('products'));
    }

    public function loadMoreProducts(Request $request)
    {
        $skip = $request->get('skip', 15);
        
        $products = Product::with('images')->latest()->skip($skip)->take(5)->get();
        
        $html = '';
        foreach($products as $product) {
            $html .= view('frontend.partials.product-card', compact('product'))->render();
        }
        
        return response()->json([
            'html' => $html,
            'has_more' => Product::count() > ($skip + 5)
        ]);
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
