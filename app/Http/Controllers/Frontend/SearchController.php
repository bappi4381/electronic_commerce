<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Wishlist;
use App\Search\SearchService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class SearchController extends Controller
{
    private const SORTS = ['relevance', 'price_asc', 'price_desc', 'popularity', 'rating', 'newest'];
    private const PER_PAGE = 20;

    public function index(Request $request, SearchService $search)
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:100',
            'category' => 'nullable|integer|min:1',
            'brand' => 'nullable|integer|min:1',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'in_stock' => 'nullable|boolean',
            'sort' => 'nullable|in:' . implode(',', self::SORTS),
            'page' => 'nullable|integer|min:1|max:200',
        ]);

        $query = trim((string) ($data['q'] ?? ''));
        $sort = $data['sort'] ?? 'relevance';
        $page = (int) ($data['page'] ?? 1);
        $filters = array_filter([
            'category' => $data['category'] ?? null,
            'brand' => $data['brand'] ?? null,
            'min_price' => $data['min_price'] ?? null,
            'max_price' => $data['max_price'] ?? null,
            'in_stock' => !empty($data['in_stock']) ? true : null,
        ], fn ($v) => $v !== null && $v !== '');

        $plan = $search->plan($query);
        $browsing = $plan->isEmpty();

        if ($browsing) {
            // No text: list the filtered catalog (category/brand chips from the suggest panel land here).
            $products = $this->browse($filters, $sort, $page);
            $total = $products->total();
            $facets = [];
            $correction = null;
        } else {
            $result = $search->run($plan, $filters, $sort === 'relevance' ? null : $sort, ($page - 1) * self::PER_PAGE, self::PER_PAGE, true);
            $items = $search->products($result->ids());
            $total = $result->total;
            $products = new LengthAwarePaginator($items, $total, self::PER_PAGE, $page, [
                'path' => $request->url(),
                'query' => $request->except('page'),
            ]);
            $facets = $result->facets;
            $correction = $result->correctedQuery ?? ($page === 1 ? $search->correction($plan, $items) : null);

            if ($page === 1 && !$request->hasHeader('X-Prefetch')) {
                $search->log($plan, $total, $request, 'results');
            }
        }

        $categoryCounts = $facets['category_ids'] ?? [];
        $brandCounts = $facets['brand_id'] ?? [];

        $categories = Category::where('type', 'product')->whereNull('parent_id')->with('children')->get();
        $brands = Brand::where('status', true)
            ->when($brandCounts, fn ($q) => $q->whereIn('id', array_keys($brandCounts)), fn ($q) => $q->whereHas('products'))
            ->orderBy('name')->get();

        $popular = $total === 0 ? $search->products($search->popularIds(10)) : collect();
        $wishlistIds = Auth::check() ? Wishlist::where('user_id', Auth::id())->pluck('product_id')->all() : [];

        return view('frontend.pages.search', [
            'query' => $query,
            'products' => $products,
            'total' => $total,
            'filters' => $filters,
            'sort' => $sort,
            'sorts' => self::SORTS,
            'categories' => $categories,
            'brands' => $brands,
            'categoryCounts' => $categoryCounts,
            'brandCounts' => $brandCounts,
            'correction' => $correction && $correction !== $plan->normalized ? $correction : null,
            'popular' => $popular,
            'trending' => $total === 0 ? $search->trending() : [],
            'wishlistIds' => $wishlistIds,
        ]);
    }

    private function browse(array $filters, string $sort, int $page): LengthAwarePaginator
    {
        $query = \App\Models\Product::query()->with(['images', 'brand', 'category', 'variants']);

        if (!empty($filters['category'])) {
            $ids = Category::where('id', $filters['category'])->orWhere('parent_id', $filters['category'])->pluck('id');
            $query->whereIn('category_id', $ids);
        }
        if (!empty($filters['brand'])) {
            $query->where('brand_id', $filters['brand']);
        }
        $price = 'COALESCE(NULLIF(discounted_price, 0), price)';
        if (isset($filters['min_price'])) {
            $query->whereRaw("{$price} >= ?", [$filters['min_price']]);
        }
        if (isset($filters['max_price'])) {
            $query->whereRaw("{$price} <= ?", [$filters['max_price']]);
        }
        if (!empty($filters['in_stock'])) {
            $query->whereHas('variants', fn ($q) => $q->where('stock', '>', 0));
        }

        match ($sort) {
            'price_asc' => $query->orderByRaw("{$price} asc"),
            'price_desc' => $query->orderByRaw("{$price} desc"),
            'rating' => $query->orderByDesc('rating_avg'),
            'popularity' => $query->withSum('orderItems', 'quantity')->orderByDesc('order_items_sum_quantity'),
            default => $query->latest(),
        };

        return $query->paginate(self::PER_PAGE, ['*'], 'page', $page)->withQueryString();
    }
}
