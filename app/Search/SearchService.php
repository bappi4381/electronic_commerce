<?php

namespace App\Search;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SearchLog;
use App\Search\Contracts\SearchEngine;
use App\Search\Engines\DatabaseEngine;
use App\Search\Engines\MeilisearchEngine;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SearchService
{
    public const LOOKUP_CACHE_KEY = 'search.lookups.v1';

    public function __construct(
        private Dictionary $dictionary,
        private DatabaseEngine $database,
        private ?MeilisearchEngine $meilisearch = null,
    ) {
    }

    public function plan(string $query): QueryPlan
    {
        return QueryPlan::make($query, $this->dictionary);
    }

    public function dictionary(): Dictionary
    {
        return $this->dictionary;
    }

    public function engine(): SearchEngine
    {
        $mode = config('search.engine', 'auto');

        if ($mode === 'database' || !$this->meilisearch) {
            return $this->database;
        }

        if ($mode === 'meilisearch' || $this->meilisearchAvailable()) {
            return $this->meilisearch;
        }

        return $this->database;
    }

    public function meilisearch(): ?MeilisearchEngine
    {
        return $this->meilisearch;
    }

    public function meilisearchAvailable(): bool
    {
        if (!$this->meilisearch || config('scout.driver') !== 'meilisearch') {
            return false;
        }

        return Cache::remember('search.meilisearch.healthy', 30, function () {
            try {
                return $this->meilisearch->client()->isHealthy();
            } catch (Throwable) {
                return false;
            }
        });
    }

    /** Runs a query on the active engine; if Meilisearch fails mid-request the database engine answers. */
    public function run(QueryPlan $plan, array $filters = [], ?string $sort = null, int $offset = 0, int $limit = 20, bool $withFacets = false): EngineResult
    {
        if ($plan->isEmpty() || $plan->isUnsupportedScript()) {
            return EngineResult::empty();
        }

        $engine = $this->engine();

        try {
            return $engine->search($plan, $filters, $sort, $offset, $limit, $withFacets);
        } catch (Throwable $e) {
            if ($engine === $this->database) {
                throw $e;
            }
            report($e);
            Cache::put('search.meilisearch.healthy', false, 30);

            return $this->database->search($plan, $filters, $sort, $offset, $limit, $withFacets);
        }
    }

    /**
     * Grouped auto-suggest payload for /api/search.
     */
    public function suggest(string $query, string $locale = 'en', array $filters = [], ?int $limit = null): array
    {
        $plan = $this->plan($query);
        $limit = max(1, min(12, $limit ?? config('search.suggest.products', 6)));

        $base = [
            'query' => $plan->raw,
            'normalized' => $plan->normalized,
            'script' => $plan->script,
            'products' => [],
            'categories' => [],
            'brands' => [],
            'did_you_mean' => null,
            'total' => 0,
            'see_all_url' => route('search.index', ['locale' => $locale, 'q' => $plan->raw]),
        ];

        if ($plan->isTooShort() || $plan->isUnsupportedScript()) {
            return $base + ['popular' => [], 'trending' => []];
        }

        $cacheKey = 'search.suggest.' . sha1(json_encode([$plan->normalized, $locale, $filters, $limit, $this->engine()->name()]));

        return Cache::remember($cacheKey, config('search.cache_ttl', 60), function () use ($plan, $locale, $filters, $limit, $base) {
            $result = $this->run($plan, $filters, null, 0, $limit, true);
            $products = $this->products($result->ids());
            $needles = $this->needles($plan);

            $categories = $this->matchCategories($plan);
            foreach (array_keys(array_slice($this->sortFacet($result->facets['category_ids'] ?? []), 0, 6, true)) as $categoryId) {
                $categories[$categoryId] ??= 0.1;
            }
            $brands = $this->matchBrands($plan);
            foreach (array_keys(array_slice($this->sortFacet($result->facets['brand_id'] ?? []), 0, 6, true)) as $brandId) {
                $brands[$brandId] ??= 0.1;
            }

            $correction = $result->correctedQuery ?? $this->correction($plan, $products);

            return array_merge($base, [
                'products' => $products->map(fn ($p) => $this->presentProduct($p, $locale, $needles))->values()->all(),
                'categories' => $this->presentCategories(array_slice(array_keys($categories), 0, config('search.suggest.categories', 3)), $locale, $needles),
                'brands' => $this->presentBrands(array_slice(array_keys($brands), 0, config('search.suggest.brands', 3)), $locale, $needles),
                'did_you_mean' => $correction && $correction !== $plan->normalized ? $correction : null,
                'total' => $result->total,
                'popular' => $result->total ? [] : $this->products($this->popularIds(6))->map(fn ($p) => $this->presentProduct($p, $locale, []))->values()->all(),
                'trending' => $result->total ? [] : $this->trending(),
            ]);
        });
    }

    /** Ordered product models for engine ids. */
    public function products(array $ids): Collection
    {
        if (!$ids) {
            return collect();
        }

        $products = Product::with(['images', 'brand', 'category.parent', 'variants'])->whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)->map(fn ($id) => $products->get($id))->filter()->values();
    }

    public function popularIds(int $limit = 6): array
    {
        try {
            return $this->engine()->popular($limit);
        } catch (Throwable $e) {
            report($e);

            return $this->database->popular($limit);
        }
    }

    /** Most searched queries that found something in the last 14 days (falls back to config). */
    public function trending(int $limit = 8): array
    {
        return Cache::remember('search.trending', 600, function () use ($limit) {
            $logged = SearchLog::query()
                ->where('results_count', '>', 0)
                ->where('created_at', '>=', now()->subDays(14))
                ->selectRaw('normalized, MAX(query) as query, COUNT(*) as hits')
                ->groupBy('normalized')
                ->orderByDesc('hits')
                ->limit($limit)
                ->pluck('query')
                ->all();

            return array_values(array_unique(array_merge($logged, config('search.default_trending', []))));
        });
    }

    public function log(QueryPlan $plan, int $results, Request $request, string $source): void
    {
        if ($plan->normalized === '') {
            return;
        }

        $session = $request->hasSession() ? $request->session()->getId() : substr(sha1((string) $request->ip()), 0, 40);

        // One row per visitor and query per 10 minutes, so typing and reloads don't inflate counts.
        if (!Cache::add('search.logged.' . sha1($session . '|' . $plan->normalized . '|' . $source), 1, 600)) {
            return;
        }

        try {
            SearchLog::create([
                'query' => mb_substr($plan->raw, 0, 191),
                'normalized' => mb_substr($plan->normalized, 0, 191),
                'results_count' => $results,
                'lang' => app()->getLocale(),
                'script' => $plan->script,
                'source' => $source,
                'user_id' => $request->user()?->id,
                'session_id' => $session,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function presentProduct(Product $product, string $locale, array $needles): array
    {
        $name = (string) ($product->getTranslation('name', $locale, false) ?: $product->getTranslation('name', 'en'));
        $price = (float) $product->price;
        $sale = (float) ($product->discounted_price ?: $product->price);
        $discount = Money::discountPercent($price, $sale);
        $image = $product->images->first()?->image;

        return [
            'id' => $product->id,
            'name' => $name,
            'name_html' => $this->highlight($name, $needles),
            'brand' => $product->brand?->name,
            'url' => route('products.show', ['locale' => $locale, 'id' => $product->id]),
            'image' => \App\Support\Storefront::imageUrl($image),
            'icon' => $this->categoryIcon($product->category),
            'price' => Money::format($sale, $locale),
            'old_price' => $discount ? Money::format($price, $locale) : null,
            'discount' => $discount,
            'in_stock' => $product->variants->sum('stock') > 0,
        ];
    }

    /** Escaped text with the parts that match the query wrapped in <mark>. */
    public function highlight(string $text, array $needles): string
    {
        if (!$needles) {
            return e($text);
        }

        $parts = preg_split('/([^\p{L}\p{M}\p{N}]+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $html = '';

        foreach ($parts as $part) {
            $normal = BanglaText::normalize($part);
            $length = 0;
            if (preg_match('/[\p{L}\p{N}]/u', $part)) {
                foreach ($needles as $needle) {
                    if ($normal === $needle || (mb_strlen($needle) >= 2 && str_starts_with($normal, $needle))) {
                        $length = max($length, mb_strlen($needle));
                    } elseif (($allowed = BanglaText::allowedTypos($needle)) && BanglaText::distance($normal, $needle, $allowed) <= $allowed) {
                        $length = mb_strlen($part);
                    }
                }
            }
            $html .= $length
                ? '<mark>' . e(mb_substr($part, 0, $length)) . '</mark>' . e(mb_substr($part, $length))
                : e($part);
        }

        return $html;
    }

    /** Lower-cased words the highlighter looks for: the query, its variants and synonym expansions. */
    public function needles(QueryPlan $plan): array
    {
        $words = [];
        foreach ($plan->variants as $variant) {
            $words = array_merge($words, BanglaText::words($variant['q']));
            foreach ($this->dictionary->expand($variant['q'], 8) as $alt) {
                $words = array_merge($words, BanglaText::words($alt));
            }
        }

        return array_values(array_unique(array_filter($words, fn ($w) => mb_strlen($w) >= (BanglaText::hasBangla($w) ? 1 : 2))));
    }

    /** "Did you mean": query words that only matched the top products through a typo, replaced by the real word. */
    public function correction(QueryPlan $plan, Collection $products): ?string
    {
        $words = BanglaText::words($plan->variants[0]['q'] ?? '');
        if (!$words || $products->isEmpty()) {
            return null;
        }

        $vocab = $products->take(3)->flatMap(fn ($p) => array_merge(
            BanglaText::words($p->getTranslation('name', 'en', false) . ' ' . $p->getTranslation('name', 'bn', false)),
            BanglaText::words((string) $p->brand?->name),
        ))->unique()->values();

        $expansions = collect($this->dictionary->expand($plan->normalized))->flatMap(fn ($alt) => BanglaText::words($alt));
        $changed = false;

        $fixed = array_map(function ($word) use ($vocab, $expansions, &$changed) {
            $allowed = BanglaText::allowedTypos($word);
            if (!$allowed || $vocab->contains(fn ($v) => $v === $word || str_starts_with($v, $word)) || $expansions->isNotEmpty()) {
                return $word;
            }
            $best = $vocab->map(fn ($v) => [$v, BanglaText::distance($word, $v, $allowed)])->filter(fn ($p) => $p[1] <= $allowed)->sortBy(1)->first();
            if ($best) {
                $changed = true;

                return $best[0];
            }

            return $word;
        }, $words);

        return $changed ? implode(' ', $fixed) : null;
    }

    /** Categories and brands matched by name (any language, Banglish, synonyms, typos): id => score. */
    public function matchCategories(QueryPlan $plan): array
    {
        return $this->matchLookup($this->lookups()['categories'], $plan);
    }

    public function matchBrands(QueryPlan $plan): array
    {
        return $this->matchLookup($this->lookups()['brands'], $plan);
    }

    public static function flushLookups(): void
    {
        Cache::forget(self::LOOKUP_CACHE_KEY);
    }

    public function lookups(): array
    {
        return Cache::rememberForever(self::LOOKUP_CACHE_KEY, function () {
            $terms = function (string ...$texts) {
                $text = implode(' ', array_filter($texts));
                $words = array_merge(BanglaText::words($text), BanglaText::words(Transliterator::toLatin($text)), explode(' ', Transliterator::key($text)));

                return array_values(array_unique(array_filter($words)));
            };

            $categories = Category::where('type', 'product')->get()->mapWithKeys(fn ($c) => [$c->id => [
                'id' => $c->id,
                'en' => $c->getTranslation('name', 'en', false),
                'bn' => $c->getTranslation('name', 'bn', false),
                'icon' => $c->icon,
                'terms' => $terms($c->getTranslation('name', 'en', false), $c->getTranslation('name', 'bn', false), ...$this->dictionary->banglaFor($c->getTranslation('name', 'en', false))),
            ]])->all();

            $brands = Brand::where('status', true)->get()->mapWithKeys(fn ($b) => [$b->id => [
                'id' => $b->id,
                'en' => $b->name,
                'slug' => $b->slug,
                'terms' => $terms($b->name, ...$this->dictionary->banglaFor($b->name)),
            ]])->all();

            return ['categories' => $categories, 'brands' => $brands];
        });
    }

    private function matchLookup(array $items, QueryPlan $plan): array
    {
        $queries = [];
        foreach ($plan->variants as $variant) {
            $queries[] = BanglaText::words($variant['q']);
            foreach ($this->dictionary->expand($variant['q'], 8) as $alt) {
                $queries[] = BanglaText::words($alt);
            }
        }

        $scores = [];
        foreach ($items as $id => $item) {
            foreach ($queries as $words) {
                $score = 0;
                foreach ($words as $word) {
                    $best = 0;
                    $allowed = BanglaText::allowedTypos($word);
                    foreach ($item['terms'] as $term) {
                        $term = (string) $term;
                        $best = max($best, match (true) {
                            $term === $word => 1.0,
                            mb_strlen($word) >= 2 && str_starts_with($term, $word) => 0.8,
                            $allowed > 0 && BanglaText::distance($word, $term, $allowed) <= $allowed => 0.6,
                            default => 0,
                        });
                    }
                    if (!$best) {
                        $score = 0;
                        break;
                    }
                    $score += $best;
                }
                if ($score && $words) {
                    $scores[$id] = max($scores[$id] ?? 0, $score / count($words));
                }
            }
        }

        arsort($scores);

        return $scores;
    }

    private function sortFacet(array $counts): array
    {
        arsort($counts);

        return $counts;
    }

    private function presentCategories(array $ids, string $locale, array $needles): array
    {
        $lookups = $this->lookups()['categories'];

        return collect($ids)->map(fn ($id) => $lookups[$id] ?? null)->filter()->map(function ($c) use ($locale, $needles) {
            $name = ($locale === 'bn' && $c['bn']) ? $c['bn'] : $c['en'];

            return [
                'id' => $c['id'],
                'name' => $name,
                'name_html' => $this->highlight($name, $needles),
                'icon' => $c['icon'] ?: 'bi-grid',
                'url' => route('search.index', ['locale' => $locale, 'category' => $c['id']]),
            ];
        })->values()->all();
    }

    private function presentBrands(array $ids, string $locale, array $needles): array
    {
        $lookups = $this->lookups()['brands'];

        return collect($ids)->map(fn ($id) => $lookups[$id] ?? null)->filter()->map(fn ($b) => [
            'id' => $b['id'],
            'name' => $b['en'],
            'name_html' => $this->highlight($b['en'], $needles),
            'url' => route('search.index', ['locale' => $locale, 'brand' => $b['id']]),
        ])->values()->all();
    }

    public function categoryIcon(?Category $category): string
    {
        return \App\Support\Storefront::categoryIcon($category);
    }
}
