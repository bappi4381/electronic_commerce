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
    /** Relations every product card needs (avoids N+1 queries). */
    private const CARD_RELATIONS = ['images', 'brand', 'category.parent', 'variants'];

    /** Initial size of the "Just For You" grid. */
    private const FEED_SIZE = 15;

    public function index()
    {
        $wishlistIds = Auth::check() ? Wishlist::where('user_id', Auth::id())->pluck('product_id')->toArray() : [];

        $latestProducts = Product::with(self::CARD_RELATIONS)->latest()->take(self::FEED_SIZE)->get();
        $feedHasMore = Product::count() > self::FEED_SIZE;

        // Flash deals: discounted, flagged and in stock; units sold drive the progress bar.
        $flashDealProducts = Product::where('is_flash_deal', true)
            ->where('discount', '>', 0)
            ->whereHas('variants', fn ($q) => $q->where('stock', '>', 0))
            ->with(self::CARD_RELATIONS)
            ->withSum('orderItems', 'quantity')
            ->latest()
            ->take(12)
            ->get();

        $flashDealTitle = \App\Models\Setting::get('flash_deal_title', __('Flash Sale'));
        $flashDealEndTime = \App\Models\Setting::get('flash_deal_end_time');

        $heroBanners = \App\Models\Banner::where('type', 'hero')->where('status', true)->orderBy('order')->get();
        $subBanners = \App\Models\Banner::where('type', 'sub_banner')->where('status', true)->orderBy('order')->take(2)->get();

        $departments = collect(\App\View\Composers\StorefrontComposer::localizedDepartments())->filter(fn ($d) => $d['count'] > 0)->values();
        $feedTabs = $departments->sortByDesc('count')->take(5)->values();
        $brands = $this->featuredBrands();

        return view('frontend.pages.home', compact(
            'latestProducts',
            'feedHasMore',
            'wishlistIds',
            'heroBanners',
            'subBanners',
            'flashDealProducts',
            'flashDealTitle',
            'flashDealEndTime',
            'departments',
            'feedTabs',
            'brands',
        ));
    }

    /** Brands from config('store.featured_brands') that have products, topped up with the largest brands. */
    private function featuredBrands()
    {
        $featured = array_map('mb_strtolower', config('store.featured_brands', []));

        $brands = \App\Models\Brand::where('status', true)->whereHas('products')->withCount('products')->get();
        $picked = $brands->filter(fn ($b) => in_array(mb_strtolower($b->name), $featured, true))
            ->sortBy(fn ($b) => array_search(mb_strtolower($b->name), $featured, true));

        return $picked->concat($brands->whereNotIn('id', $picked->pluck('id'))->sortByDesc('products_count'))->take(12)->values();
    }
    public function products(Request $request)
    {
        $categories = Category::where('type', 'product')->withCount('products')->get();
        $brands = \App\Models\Brand::where('status', true)->whereHas('products')->withCount('products')->orderBy('name')->get();

        $products = Product::query()->with(self::CARD_RELATIONS);

        // Search (name is now JSON, use JSON_EXTRACT or LIKE on raw value)
        if ($request->search) {
            $s = $request->search;
            $products->where(function($query) use ($s) {
                $query->whereRaw('JSON_EXTRACT(name, "$.en") LIKE ?', ["%{$s}%"])
                      ->orWhereRaw('JSON_EXTRACT(name, "$.bn") LIKE ?', ["%{$s}%"])
                      ->orWhereHas('brand', fn ($brand) => $brand->where('name', 'like', "%{$s}%"))
                      ->orWhere('model', 'like', "%{$s}%");
            });
        }

        if ($request->category) {
            $products->where('category_id', $request->category);
        }

        if ($request->brand) {
            $products->whereHas('brand', fn ($brand) => $brand->where('slug', $request->brand));
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

        $products = $products->paginate(12)->withQueryString();
        $wishlistIds = Auth::check() ? Wishlist::where('user_id', Auth::id())->pluck('product_id')->toArray() : [];

        return view('frontend.pages.products', compact('categories', 'brands', 'products', 'wishlistIds'));
    }
    public function show($id)
    {
        $product = Product::with(['images', 'category', 'brand', 'reactions', 'variants.attributeValues.attribute'])->findOrFail($id);

        // Public reviews are approved ones; authors also see their own pending/rejected reviews.
        $reviews = $product->comments()
            ->with('user')
            ->where(fn ($q) => $q->where('status', \App\Models\ProductComment::STATUS_APPROVED)
                ->when(Auth::id(), fn ($q, $userId) => $q->orWhere('user_id', $userId)))
            ->latest()
            ->get();
        $product->setRelation('comments', $reviews);
        
        // Related products from the same category
        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $id)
            ->with(self::CARD_RELATIONS)
            ->take(4)
            ->get();

        $wishlistIds = Auth::check() ? Wishlist::where('user_id', Auth::id())->pluck('product_id')->toArray() : [];

        return view('frontend.pages.singleProduct', compact('product', 'relatedProducts', 'wishlistIds'));
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
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        Product::findOrFail($id);

        if (\App\Models\ProductComment::where('product_id', $id)->where('user_id', Auth::id())->exists()) {
            return back()->with('error', 'You have already reviewed this product. You can edit your existing review instead.');
        }

        $review = \App\Models\ProductComment::create([
            'product_id' => $id,
            'user_id' => Auth::id(),
            'rating' => $request->rating,
            'comment' => $request->comment,
            'status' => \App\Models\ProductComment::STATUS_PENDING,
        ]);

        app(\App\Services\AdminNotifier::class)->newReview($review);

        return back()->with('success', 'Thanks! Your review will appear once it has been approved.');
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
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ]);

        // Edited reviews go back through moderation.
        $comment->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
            'status' => \App\Models\ProductComment::STATUS_PENDING,
            'moderated_by' => null,
            'moderated_at' => null,
        ]);

        return back()->with('success', 'Review updated. It will reappear once approved.');
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
        $products = Product::where('discount', '>', 0)->with(self::CARD_RELATIONS)->latest()->paginate(12);
        return view('frontend.pages.flash-deals', compact('products'));
    }

    /** "Just For You" pages as card HTML: ?skip=&take=&category= (category includes its subcategories). */
    public function loadMoreProducts(Request $request)
    {
        $skip = max(0, (int) $request->get('skip', self::FEED_SIZE));
        $take = min(20, max(1, (int) $request->get('take', 5)));

        $query = Product::query();
        if ($category = (int) $request->get('category')) {
            $query->whereIn('category_id', Category::where('id', $category)->orWhere('parent_id', $category)->pluck('id'));
        }

        $total = (clone $query)->count();
        $products = $query->with(self::CARD_RELATIONS)->latest()->skip($skip)->take($take)->get();
        $wishlistIds = Auth::check() ? Wishlist::where('user_id', Auth::id())->pluck('product_id')->toArray() : [];

        $html = $products->map(fn ($product) => view('frontend.partials.product-card', ['product' => $product, 'wishlistIds' => $wishlistIds])->render())->implode('');

        return response()->json([
            'html' => $html,
            'has_more' => $total > $skip + $take,
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

        $hadUnread = \App\Models\Message::where('is_admin', false)->where('is_read', false)
            ->when($userId, fn ($q) => $q->where('user_id', $userId), fn ($q) => $q->where('session_id', $sessionId))
            ->exists();

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

        // One bell notification per conversation until an admin reads it.
        if (!$hadUnread) {
            app(\App\Services\AdminNotifier::class)->newChatMessage($message);
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
