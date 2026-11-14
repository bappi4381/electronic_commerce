<?php

namespace App\Search\Engines;

use App\Models\Product;
use App\Search\BanglaText;
use App\Search\Contracts\SearchEngine;
use App\Search\Dictionary;
use App\Search\EngineResult;
use App\Search\ProductDocument;
use App\Search\QueryPlan;
use App\Search\Transliterator;
use Illuminate\Support\Facades\Cache;

/**
 * In-process engine over cached product documents. It mirrors the Meilisearch setup (same fields,
 * attribute priority, typo rules, synonyms, "last" matching strategy) so the store works, and tests
 * run, without a Meilisearch server. Suitable for catalogs of a few thousand products.
 */
class DatabaseEngine implements SearchEngine
{
    public const CACHE_KEY = 'search.database.index.v2';

    /** Attribute priority, mirroring the Meilisearch searchableAttributes order. */
    private const WEIGHTS = [
        'name_en' => 10, 'name_bn' => 10, 'search_banglish' => 8, 'search_compact' => 8,
        'brand' => 7, 'brand_bn' => 7, 'model' => 6, 'sku' => 6,
        'category_names' => 5, 'tags' => 4, 'search_bn' => 4, 'description_short' => 1,
    ];

    private const NO_TYPOS = ['model', 'sku'];

    private static ?array $memory = null;

    /** @var array<string, array<string, array{0: float, 1: bool}>> per-request word => matching vocabulary terms */
    private array $candidates = [];

    public function __construct(private Dictionary $dictionary, private ProductDocument $documents)
    {
    }

    public function name(): string
    {
        return 'database';
    }

    public static function flush(): void
    {
        static::$memory = null;
        Cache::forget(self::CACHE_KEY);
    }

    public function search(QueryPlan $plan, array $filters = [], ?string $sort = null, int $offset = 0, int $limit = 20, bool $withFacets = false): EngineResult
    {
        $rows = array_filter($this->index()['rows'], fn ($row) => $this->passes($row['doc'], $filters));

        $variants = $this->variants($plan);
        [$scores, $typoFixes] = $this->score($rows, $variants, false);

        if (!$scores) {
            // Meilisearch "last" strategy: drop trailing words until something matches.
            [$scores, $typoFixes] = $this->score($rows, $variants, true);
        }

        $ids = array_keys($scores);
        usort($ids, fn ($a, $b) => $this->compare($rows[$a]['doc'], $rows[$b]['doc'], $scores[$a], $scores[$b], $sort));

        $facets = [];
        if ($withFacets) {
            foreach ($ids as $id) {
                foreach ($rows[$id]['doc']['category_ids'] as $categoryId) {
                    $facets['category_ids'][$categoryId] = ($facets['category_ids'][$categoryId] ?? 0) + 1;
                }
                if ($brandId = $rows[$id]['doc']['brand_id']) {
                    $facets['brand_id'][$brandId] = ($facets['brand_id'][$brandId] ?? 0) + 1;
                }
            }
        }

        $hits = array_map(fn ($id) => ['id' => $id, 'score' => round($scores[$id], 4)], array_slice($ids, $offset, $limit));
        $corrected = $ids ? ($typoFixes[$ids[0]] ?? null) : null;

        return new EngineResult($hits, count($ids), $facets, $corrected);
    }

    public function popular(int $limit = 6): array
    {
        $docs = array_column($this->index()['rows'], 'doc');
        $docs = array_filter($docs, fn ($d) => $d['in_stock']);
        usort($docs, fn ($a, $b) => [$b['popularity'], $b['rating'], $b['created_at']] <=> [$a['popularity'], $a['rating'], $a['created_at']]);

        return array_column(array_slice($docs, 0, $limit), 'id');
    }

    /**
     * rows: product id => [doc, terms: token => [w => best weight, tw => best weight where typos are allowed]]
     * vocab: every distinct token
     */
    public function index(): array
    {
        return static::$memory ??= Cache::rememberForever(self::CACHE_KEY, function () {
            $rows = [];
            $vocab = [];
            Product::query()
                ->with(['brand', 'category.parent', 'images', 'allVariants'])
                ->withSum('orderItems', 'quantity')
                ->chunkById(200, function ($products) use (&$rows, &$vocab) {
                    foreach ($products as $product) {
                        $doc = $this->documents->make($product);
                        $terms = $this->terms($doc);
                        $rows[$doc['id']] = ['doc' => $doc, 'terms' => $terms];
                        $vocab += array_fill_keys(array_map('strval', array_keys($terms)), true);
                    }
                });

            $vocab = array_map('strval', array_keys($vocab));
            sort($vocab, SORT_STRING);
            $byLength = [];
            $byFirst = [];
            foreach ($vocab as $token) {
                $byLength[mb_strlen($token)][] = $token;
                $byFirst[mb_substr($token, 0, 1)][] = $token;
            }

            return ['rows' => $rows, 'vocab' => $vocab, 'byLength' => $byLength, 'byFirst' => $byFirst];
        });
    }

    private function terms(array $doc): array
    {
        $terms = [];
        foreach (self::WEIGHTS as $field => $weight) {
            $text = is_array($doc[$field]) ? implode(' ', $doc[$field]) : (string) $doc[$field];
            $words = BanglaText::words($text);
            if (in_array($field, ['search_banglish', 'name_en', 'brand'], true)) {
                $words = array_merge($words, explode(' ', Transliterator::key($text)));
            }
            $typoWeight = in_array($field, self::NO_TYPOS, true) ? 0 : $weight;
            foreach ($words as $word) {
                if ($word === '') {
                    continue;
                }
                $current = $terms[$word] ?? ['w' => 0, 'tw' => 0];
                $terms[$word] = ['w' => max($current['w'], $weight), 'tw' => max($current['tw'], $typoWeight)];
            }
        }

        return $terms;
    }

    /** Plan variants plus their synonym expansions, each as a list of words. */
    private function variants(QueryPlan $plan): array
    {
        $variants = [];
        foreach ($plan->variants as $variant) {
            $variants[] = ['words' => BanglaText::words($variant['q']), 'weight' => $variant['weight'], 'original' => $variant['kind'] === 'original', 'typos' => true];
            // Synonyms are dictionary spellings: matched exactly or by prefix, never with typos.
            foreach ($this->dictionary->expand($variant['q'], 8) as $alt) {
                $variants[] = ['words' => BanglaText::words($alt), 'weight' => $variant['weight'] * 0.95, 'original' => false, 'typos' => false];
            }
        }

        return array_slice(array_values(array_filter($variants, fn ($v) => $v['words'])), 0, 24);
    }

    /** @return array{0: array<int, float>, 1: array<int, string>} scores and typo-corrected queries by id */
    private function score(array $rows, array $variants, bool $dropTrailing): array
    {
        $scores = [];
        $fixes = [];

        foreach ($variants as $variant) {
            $words = $variant['words'];
            $attempts = $dropTrailing
                ? (count($words) > 1 ? array_map(fn ($n) => array_slice($words, 0, $n), range(count($words) - 1, 1)) : [])
                : [$words];

            foreach ($attempts as $attemptWords) {
                $penalty = count($attemptWords) / max(count($words), 1);
                $matched = false;
                foreach ($rows as $id => $row) {
                    $match = $this->matchDocument($row['terms'], $attemptWords, $variant['typos']);
                    if ($match === null) {
                        continue;
                    }
                    $matched = true;
                    $score = $match['score'] * $variant['weight'] * $penalty;
                    if ($score > ($scores[$id] ?? 0)) {
                        $scores[$id] = $score;
                        if ($variant['original'] && $match['fixed'] !== null) {
                            $fixes[$id] = $match['fixed'];
                        } else {
                            unset($fixes[$id]);
                        }
                    }
                }
                if ($matched) {
                    break;
                }
            }
        }

        return [$scores, $fixes];
    }

    /** Every query word must match a term of the document; returns the weighted score or null. */
    private function matchDocument(array $terms, array $words, bool $typos = true): ?array
    {
        $total = 0.0;
        $corrected = [];
        $usedTypo = false;
        $last = count($words) - 1;

        foreach ($words as $i => $word) {
            $best = 0.0;
            $bestToken = $word;
            $bestIsTypo = false;

            if (isset($terms[$word])) {
                $best = (float) $terms[$word]['w'];
            }
            foreach ($this->candidatesFor($word, $i === $last, $typos) as $token => [$quality, $isTypo]) {
                if (!isset($terms[$token])) {
                    continue;
                }
                $weight = $isTypo ? $terms[$token]['tw'] : $terms[$token]['w'];
                if ($quality * $weight > $best) {
                    $best = $quality * $weight;
                    $bestToken = $token;
                    $bestIsTypo = $isTypo;
                }
            }

            if ($best <= 0) {
                return null;
            }
            $total += $best;
            $usedTypo = $usedTypo || $bestIsTypo;
            $corrected[] = $bestIsTypo ? $bestToken : $word;
        }

        return ['score' => $total / count($words), 'fixed' => $usedTypo ? implode(' ', $corrected) : null];
    }

    /** Vocabulary terms a query word matches by prefix or within the allowed typos (computed once per word). */
    private function candidatesFor(string $word, bool $isLast, bool $typos = true): array
    {
        $key = $word . ($isLast ? '|last' : '') . ($typos ? '|typos' : '');
        if (isset($this->candidates[$key])) {
            return $this->candidates[$key];
        }

        $index = $this->index();
        $length = mb_strlen($word);
        $found = [];

        // Prefix matches: a contiguous run in the sorted vocabulary.
        if ($length >= (BanglaText::hasBangla($word) ? 1 : 2)) {
            $vocab = $index['vocab'];
            $lo = 0;
            $hi = count($vocab);
            while ($lo < $hi) {
                $mid = intdiv($lo + $hi, 2);
                strcmp($vocab[$mid], $word) < 0 ? $lo = $mid + 1 : $hi = $mid;
            }
            for ($i = $lo; $i < count($vocab) && str_starts_with($vocab[$i], $word); $i++) {
                if ($vocab[$i] !== $word) {
                    $found[$vocab[$i]] = [$isLast ? 0.85 : 0.75, false];
                }
            }
        }

        $allowed = $typos ? BanglaText::allowedTypos($word) : 0;
        if ($allowed > 0) {
            for ($len = $length - $allowed; $len <= $length + $allowed; $len++) {
                foreach ($index['byLength'][$len] ?? [] as $token) {
                    if (!isset($found[$token]) && $token !== $word && BanglaText::distance($word, $token, $allowed) <= $allowed) {
                        $found[$token] = [0.6, true];
                    }
                }
            }

            // Typo inside a word still being typed ("kettl" => "kettle"): same first letter, longer token.
            if ($isLast) {
                foreach ($index['byFirst'][mb_substr($word, 0, 1)] ?? [] as $token) {
                    if (!isset($found[$token]) && mb_strlen($token) > $length + $allowed
                        && BanglaText::distance($word, mb_substr($token, 0, $length), $allowed) <= $allowed) {
                        $found[$token] = [0.5, true];
                    }
                }
            }
        }

        return $this->candidates[$key] = $found;
    }

    private function passes(array $doc, array $filters): bool
    {
        return (empty($filters['category']) || array_intersect(\App\Models\Category::branchIds((int) $filters['category']), array_map('intval', $doc['category_ids'] ?? [])))
            && (empty($filters['brand']) || (int) $doc['brand_id'] === (int) $filters['brand'])
            && (!isset($filters['min_price']) || $doc['sale_price'] >= (float) $filters['min_price'])
            && (!isset($filters['max_price']) || $doc['sale_price'] <= (float) $filters['max_price'])
            && (empty($filters['in_stock']) || $doc['in_stock']);
    }

    private function compare(array $a, array $b, float $scoreA, float $scoreB, ?string $sort): int
    {
        return match ($sort) {
            'price_asc' => [$a['sale_price'], -$scoreA] <=> [$b['sale_price'], -$scoreB],
            'price_desc' => [$b['sale_price'], $scoreB] <=> [$a['sale_price'], $scoreA],
            'popularity' => [$b['popularity'], $scoreB] <=> [$a['popularity'], $scoreA],
            'rating' => [$b['rating'], $b['rating_count'], $scoreB] <=> [$a['rating'], $a['rating_count'], $scoreA],
            'newest' => [$b['created_at'], $scoreB] <=> [$a['created_at'], $scoreA],
            default => [round($scoreB, 3), $b['popularity']] <=> [round($scoreA, 3), $a['popularity']],
        };
    }
}
