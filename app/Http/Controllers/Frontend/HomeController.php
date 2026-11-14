<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Wishlist;
use App\Services\FlashSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /** Relations every product card needs (avoids N+1 queries). */
    private const CARD_RELATIONS = ['images', 'brand', 'category.parent', 'variants'];

    /** Initial size of the "Just For You" grid. */
    private const FEED_SIZE = 15;

    /** Price the customer actually pays: the discounted price when set, otherwise the list price. */
    private const EFFECTIVE_PRICE = 'COALESCE(NULLIF(discounted_price, 0), price)';

    private const CATALOG_SORTS = [
        'newest' => 'Newest arrivals',
        'popular' => 'Best selling',
        'price_low' => 'Price: low to high',
        'price_high' => 'Price: high to low',
        'rating' => 'Top rated',
        'discount' => 'Biggest discount',
    ];

    /** Deals page discount chips: query value => inclusive [min %, max %] of the rounded discount. */
    private const DISCOUNT_BANDS = [
        'upto-20' => [1, 20],
        'upto-40' => [1, 40],
        '60-plus' => [60, 100],
    ];

    private const DEALS_PER_PAGE = 20;

    public function index(FlashSale $flashSale)
    {
        $wishlistIds = Auth::check() ? Wishlist::where('user_id', Auth::id())->pluck('product_id')->toArray() : [];

        $latestProducts = Product::with(self::CARD_RELATIONS)->latest()->take(self::FEED_SIZE)->get();
        $feedHasMore = Product::count() > self::FEED_SIZE;

        $flashCampaign = $flashSale->campaign();
        $flashDealProducts = $flashSale->products();

        $heroBanners = \App\Models\Banner::where('type', 'hero')->where('status', true)->orderBy('order')->get();
        $subBanners = \App\Models\Banner::where('type', 'sub_banner')->where('status', true)->orderBy('order')->take(2)->get();

        $departments = collect(\App\View\Composers\StorefrontComposer::localizedDepartments())->filter(fn ($d) => $d['count'] > 0)->values();
        $feedTabs = $departments->sortByDesc('count')->take(5)->values();

        return view('frontend.pages.home', compact(
            'latestProducts',
            'feedHasMore',
            'wishlistIds',
            'heroBanners',
            'subBanners',
            'flashDealProducts',
            'flashCampaign',
            'departments',
            'feedTabs',
        ));
    }

    public function products(Request $request)
    {
        $search = trim((string) $request->get('search'));
        $sort = array_key_exists($request->get('sort'), self::CATALOG_SORTS) ? $request->get('sort') : 'newest';
        $minPrice = is_numeric($request->get('min_price')) ? max(0, (float) $request->get('min_price')) : null;
        $maxPrice = is_numeric($request->get('max_price')) ? max(0, (float) $request->get('max_price')) : null;
        $inStock = $request->boolean('in_stock');
        $onSale = $request->boolean('on_sale');

        $departments = Category::nested('product')->filter(fn ($c) => $c->total_count > 0)->values();

        $activeCategory = $request->filled('category')
            ? Category::where('type', 'product')->with('parent.parent.parent')->find((int) $request->get('category'))
            : null;
        $categoryIds = $activeCategory?->selfAndDescendantIds();

        $activeBrand = $request->filled('brand') ? \App\Models\Brand::where('slug', $request->get('brand'))->first() : null;

        // Every filter except brand, so the brand list can show counts for the current selection.
        $applyFilters = function ($query) use ($search, $categoryIds, $minPrice, $maxPrice, $inStock, $onSale) {
            return $query
                ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                    ->whereRaw('JSON_EXTRACT(name, "$.en") LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('JSON_EXTRACT(name, "$.bn") LIKE ?', ["%{$search}%"])
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%{$search}%"))
                    ->orWhere('model', 'like', "%{$search}%")))
                ->when($categoryIds, fn ($q) => $q->whereIn('category_id', $categoryIds))
                ->when($minPrice !== null, fn ($q) => $q->whereRaw(self::EFFECTIVE_PRICE . ' >= ?', [$minPrice]))
                ->when($maxPrice !== null, fn ($q) => $q->whereRaw(self::EFFECTIVE_PRICE . ' <= ?', [$maxPrice]))
                ->when($inStock, fn ($q) => $q->whereHas('variants', fn ($v) => $v->where('stock', '>', 0)))
                ->when($onSale, fn ($q) => $q->whereNotNull('discounted_price')->where('discounted_price', '>', 0)->whereColumn('discounted_price', '<', 'price'));
        };

        $brands = \App\Models\Brand::where('status', true)
            ->withCount(['products' => fn ($q) => $applyFilters($q)])
            ->having('products_count', '>', 0)
            ->orderBy('name')
            ->get();

        $products = $applyFilters(Product::query())
            ->when($activeBrand, fn ($q) => $q->where('brand_id', $activeBrand->id))
            ->when($request->filled('brand') && !$activeBrand, fn ($q) => $q->whereRaw('1 = 0'))
            ->with(self::CARD_RELATIONS);

        match ($sort) {
            'popular' => $products->withSum('orderItems', 'quantity')->orderByDesc('order_items_sum_quantity')->latest(),
            'price_low' => $products->orderByRaw(self::EFFECTIVE_PRICE . ' asc'),
            'price_high' => $products->orderByRaw(self::EFFECTIVE_PRICE . ' desc'),
            'rating' => $products->orderByDesc('rating_avg')->orderByDesc('rating_count'),
            'discount' => $products->orderByRaw('(price - ' . self::EFFECTIVE_PRICE . ') / NULLIF(price, 0) desc')->latest(),
            default => $products->latest(),
        };

        $products = $products->paginate(24)->withQueryString();
        $priceBounds = Product::selectRaw('MIN(' . self::EFFECTIVE_PRICE . ') as min, MAX(' . self::EFFECTIVE_PRICE . ') as max')->first();
        $wishlistIds = Auth::check() ? Wishlist::where('user_id', Auth::id())->pluck('product_id')->toArray() : [];

        $filters = array_filter([
            'search' => $search !== '' ? $search : null,
            'category' => $activeCategory?->id,
            'brand' => $activeBrand?->slug,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'in_stock' => $inStock ?: null,
            'on_sale' => $onSale ?: null,
        ], fn ($v) => $v !== null);

        return view('frontend.pages.products', [
            'products' => $products,
            'departments' => $departments,
            'activeCategory' => $activeCategory,
            'brands' => $brands,
            'activeBrand' => $activeBrand,
            'filters' => $filters,
            'sort' => $sort,
            'sorts' => array_map(fn ($label) => __($label), self::CATALOG_SORTS),
            'priceBounds' => ['min' => (int) floor((float) $priceBounds?->min), 'max' => (int) ceil((float) $priceBounds?->max)],
            'wishlistIds' => $wishlistIds,
        ]);
    }
    public function show($id)
    {
        $product = Product::with(['images', 'category.parent.parent.parent', 'brand', 'variants.attributeValues.attribute'])->withCount('reactions')->findOrFail($id);

        // Public reviews are approved ones; authors also see their own pending/rejected reviews.
        $reviews = $product->comments()
            ->with('user')
            ->where(fn ($q) => $q->where('status', \App\Models\ProductComment::STATUS_APPROVED)
                ->when(Auth::id(), fn ($q, $userId) => $q->orWhere('user_id', $userId)))
            ->latest()
            ->get();
        $product->setRelation('comments', $reviews);
        
        // Same category first, topped up with the same brand.
        $relatedProducts = Product::whereIn('category_id', $product->category?->selfAndDescendantIds() ?? [$product->category_id])
            ->where('id', '!=', $product->id)
            ->with(self::CARD_RELATIONS)
            ->latest()
            ->take(10)
            ->get();
        if ($relatedProducts->count() < 10 && $product->brand_id) {
            $relatedProducts = $relatedProducts->concat(Product::where('brand_id', $product->brand_id)
                ->whereNotIn('id', $relatedProducts->pluck('id')->push($product->id))
                ->with(self::CARD_RELATIONS)
                ->latest()
                ->take(10 - $relatedProducts->count())
                ->get());
        }

        $ratingCounts = $product->approvedReviews()->whereNotNull('rating')
            ->selectRaw('rating, COUNT(*) as total')->groupBy('rating')->pluck('total', 'rating');

        $checkout = app(\App\Services\CheckoutOptions::class);
        $flashCampaign = $product->is_flash_deal ? app(FlashSale::class)->campaign() : null;

        app(\App\Services\FacebookTracking::class)->viewContent($product, (float) ($product->discounted_price ?: $product->price), request());

        return view('frontend.pages.singleProduct', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'wishlistIds' => Auth::check() ? Wishlist::where('user_id', Auth::id())->pluck('product_id')->toArray() : [],
            'liked' => Auth::check() && $product->reactions()->where('user_id', Auth::id())->exists(),
            'ratingCounts' => $ratingCounts,
            'deliveryOptions' => $checkout->deliveryOptions(),
            'freeShippingThreshold' => $checkout->freeShippingThreshold(),
            'codAvailable' => in_array('cod', $checkout->paymentMethods(), true),
            'flashDealEnd' => $flashCampaign['ends_at'] ?? null,
        ]);
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
    
    /**
     * Deals page: the running flash sale (same data as the homepage) and every discounted product,
     * biggest discount first, with discount-range and department filters.
     */
    public function flashDeals(Request $request, FlashSale $flashSale)
    {
        $band = array_key_exists((string) $request->query('off'), self::DISCOUNT_BANDS) ? (string) $request->query('off') : null;
        $percent = 'ROUND((price - ' . self::EFFECTIVE_PRICE . ') * 100.0 / price)';
        $onSale = fn ($query) => $query->where('discount', '>', 0)->where('price', '>', 0)->whereRaw(self::EFFECTIVE_PRICE . ' < price');

        $departments = Category::nested('product');
        $activeCategory = $request->filled('category') ? Category::findNested($departments, (int) $request->query('category')) : null;

        $discounts = $onSale(Product::query())
            ->when($activeCategory, fn ($q) => $q->whereIn('category_id', $activeCategory->loadedIds()))
            ->when($band, fn ($q) => $q->whereRaw("{$percent} BETWEEN ? AND ?", self::DISCOUNT_BANDS[$band]))
            ->with(self::CARD_RELATIONS)
            ->orderByRaw("{$percent} DESC")
            ->orderByDesc('id')
            ->paginate(self::DEALS_PER_PAGE)
            ->withQueryString();

        // Department filter options: only departments that have something on sale.
        $countsByCategory = $onSale(Product::query())->selectRaw('category_id, COUNT(*) as total')->groupBy('category_id')->pluck('total', 'category_id');
        $categories = $departments
            ->map(fn (Category $department) => [
                'id' => $department->id,
                'name' => $department->name,
                'count' => (int) $countsByCategory->only($department->loadedIds())->sum(),
            ])
            ->filter(fn ($department) => $department['count'] > 0)
            ->values();

        return view('frontend.pages.flash-deals', [
            'campaign' => $flashSale->campaign(),
            'flashProducts' => $flashSale->products(),
            'discounts' => $discounts,
            'categories' => $categories,
            'activeCategory' => $activeCategory?->id,
            'band' => $band,
            'wishlistIds' => Auth::check() ? Wishlist::where('user_id', Auth::id())->pluck('product_id')->toArray() : [],
        ]);
    }

    /** "Just For You" pages as card HTML: ?skip=&take=&category= (category includes its subcategories). */
    public function loadMoreProducts(Request $request)
    {
        $skip = max(0, (int) $request->get('skip', self::FEED_SIZE));
        $take = min(20, max(1, (int) $request->get('take', 5)));

        $query = Product::query();
        if ($category = (int) $request->get('category')) {
            $query->whereIn('category_id', Category::branchIds($category));
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
