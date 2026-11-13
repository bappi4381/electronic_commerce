<?php

namespace App\Search\Engines;

use App\Search\Contracts\SearchEngine;
use App\Search\Dictionary;
use App\Search\EngineResult;
use App\Search\QueryPlan;
use Meilisearch\Client;
use Meilisearch\Contracts\FederationOptions;
use Meilisearch\Contracts\HybridSearchOptions;
use Meilisearch\Contracts\MultiSearchFederation;
use Meilisearch\Contracts\SearchQuery;
use Throwable;

class MeilisearchEngine implements SearchEngine
{
    public const SEARCHABLE = [
        'name_en', 'name_bn', 'search_banglish', 'search_compact', 'brand', 'brand_bn',
        'model', 'sku', 'category_names', 'tags', 'search_bn', 'description_short',
    ];

    private const SORTS = [
        'price_asc' => ['sale_price:asc'],
        'price_desc' => ['sale_price:desc'],
        'popularity' => ['popularity:desc'],
        'rating' => ['rating:desc'],
        'newest' => ['created_at:desc'],
    ];

    public function __construct(private Client $client, private string $index)
    {
    }

    public function name(): string
    {
        return 'meilisearch';
    }

    public function client(): Client
    {
        return $this->client;
    }

    public function indexName(): string
    {
        return $this->index;
    }

    public function search(QueryPlan $plan, array $filters = [], ?string $sort = null, int $offset = 0, int $limit = 20, bool $withFacets = false): EngineResult
    {
        $filter = $this->filter($filters);
        $sortRule = self::SORTS[$sort] ?? null;

        // Explicit sorts compare sort values, which federation cannot merge across queries: use the main variant.
        if ($sortRule) {
            return $this->single($plan->variants[0]['q'], $filter, $sortRule, $offset, $limit, $withFacets);
        }

        try {
            return $this->federated($plan, $filter, $offset, $limit, $withFacets);
        } catch (Throwable $e) {
            report($e);

            return $this->merged($plan, $filter, $offset, $limit, $withFacets);
        }
    }

    public function popular(int $limit = 6): array
    {
        $result = $this->single('', ['in_stock = true'], ['popularity:desc', 'rating:desc'], 0, $limit, false);

        return $result->ids();
    }

    private function federated(QueryPlan $plan, array $filter, int $offset, int $limit, bool $withFacets): EngineResult
    {
        $queries = array_map(fn ($variant) => $this->query($variant['q'], $filter)
            ->setFederationOptions((new FederationOptions())->setWeight($variant['weight'])), $plan->variants);

        $federation = (new MultiSearchFederation())->setOffset($offset)->setLimit($limit);
        if ($withFacets) {
            $federation->setFacetsByIndex([$this->index => ['category_ids', 'brand_id']])->setMergeFacets([]);
        }

        $response = $this->client->multiSearch($queries, $federation);

        return new EngineResult(
            array_map(fn ($hit) => ['id' => (int) $hit['id'], 'score' => (float) ($hit['_federation']['weightedRankingScore'] ?? 0)], $response['hits'] ?? []),
            (int) ($response['estimatedTotalHits'] ?? $response['totalHits'] ?? count($response['hits'] ?? [])),
            $this->facets($response['facetDistribution'] ?? []),
        );
    }

    /** Older servers without federation: one query per variant, merged and deduplicated here. */
    private function merged(QueryPlan $plan, array $filter, int $offset, int $limit, bool $withFacets): EngineResult
    {
        $queries = array_map(function ($variant) use ($filter, $offset, $limit, $withFacets) {
            $query = $this->query($variant['q'], $filter)->setLimit($offset + $limit)->setShowRankingScore(true);

            return $withFacets ? $query->setFacets(['category_ids', 'brand_id']) : $query;
        }, $plan->variants);

        $response = $this->client->multiSearch($queries);
        $scores = [];
        $total = 0;
        $facets = [];

        foreach ($response['results'] ?? [] as $i => $result) {
            $weight = $plan->variants[$i]['weight'] ?? 1;
            $total = max($total, (int) ($result['estimatedTotalHits'] ?? 0));
            foreach ($result['hits'] as $hit) {
                $scores[$hit['id']] = max($scores[$hit['id']] ?? 0, $weight * (float) ($hit['_rankingScore'] ?? 0));
            }
            if (!$facets && !empty($result['facetDistribution'])) {
                $facets = $this->facets($result['facetDistribution']);
            }
        }

        arsort($scores);
        $hits = array_map(fn ($id, $score) => ['id' => (int) $id, 'score' => $score], array_keys($scores), $scores);

        return new EngineResult(array_slice($hits, $offset, $limit), max($total, count($hits)), $facets);
    }

    private function single(string $q, array $filter, ?array $sort, int $offset, int $limit, bool $withFacets): EngineResult
    {
        $query = $this->query($q, $filter)->setOffset($offset)->setLimit($limit);
        if ($sort) {
            $query->setSort($sort);
        }
        if ($withFacets) {
            $query->setFacets(['category_ids', 'brand_id']);
        }

        $result = $this->client->multiSearch([$query])['results'][0] ?? [];

        return new EngineResult(
            array_map(fn ($hit) => ['id' => (int) $hit['id'], 'score' => 1.0], $result['hits'] ?? []),
            (int) ($result['estimatedTotalHits'] ?? 0),
            $this->facets($result['facetDistribution'] ?? []),
        );
    }

    private function query(string $q, array $filter): SearchQuery
    {
        $query = (new SearchQuery())
            ->setIndexUid($this->index)
            ->setQuery($q)
            ->setAttributesToRetrieve(['id'])
            ->setMatchingStrategy('last');

        if ($filter) {
            $query->setFilter($filter);
        }

        if ($q !== '' && config('search.meilisearch.hybrid.enabled')) {
            $query->setHybrid((new HybridSearchOptions())
                ->setEmbedder(config('search.meilisearch.hybrid.embedder'))
                ->setSemanticRatio(config('search.meilisearch.hybrid.semantic_ratio')));
        }

        return $query;
    }

    private function filter(array $filters): array
    {
        $out = [];
        if (!empty($filters['category'])) {
            $out[] = 'category_ids = ' . (int) $filters['category'];
        }
        if (!empty($filters['brand'])) {
            $out[] = 'brand_id = ' . (int) $filters['brand'];
        }
        if (isset($filters['min_price'])) {
            $out[] = 'sale_price >= ' . (float) $filters['min_price'];
        }
        if (isset($filters['max_price'])) {
            $out[] = 'sale_price <= ' . (float) $filters['max_price'];
        }
        if (!empty($filters['in_stock'])) {
            $out[] = 'in_stock = true';
        }

        return $out;
    }

    private function facets(array $distribution): array
    {
        $facets = [];
        foreach (['category_ids', 'brand_id'] as $facet) {
            foreach ($distribution[$facet] ?? [] as $value => $count) {
                $facets[$facet][(int) $value] = (int) $count;
            }
        }

        return $facets;
    }

    /** Index settings pushed by `php artisan search:settings`. */
    public static function settings(Dictionary $dictionary): array
    {
        $settings = [
            'searchableAttributes' => self::SEARCHABLE,
            'filterableAttributes' => ['category', 'category_ids', 'brand', 'brand_id', 'brand_slug', 'price', 'sale_price', 'in_stock', 'discount_percent'],
            'sortableAttributes' => ['price', 'sale_price', 'popularity', 'rating', 'created_at'],
            'rankingRules' => ['words', 'sort', 'typo', 'proximity', 'attribute', 'exactness', 'popularity:desc'],
            'typoTolerance' => [
                'enabled' => true,
                'minWordSizeForTypos' => ['oneTypo' => 4, 'twoTypos' => 8],
                'disableOnAttributes' => ['sku', 'model'],
            ],
            'synonyms' => $dictionary->forMeilisearch(),
            'stopWords' => $dictionary->stopWords(),
            'pagination' => ['maxTotalHits' => 2000],
            'faceting' => ['maxValuesPerFacet' => 200],
        ];

        if (config('search.meilisearch.hybrid.enabled')) {
            $settings['embedders'] = [
                config('search.meilisearch.hybrid.embedder') => [
                    'source' => 'huggingFace',
                    'model' => 'sentence-transformers/paraphrase-multilingual-MiniLM-L12-v2',
                    'documentTemplate' => '{{doc.name_en}} {{doc.name_bn}} {{doc.brand}} {{doc.category_names}} {{doc.description_short}}',
                ],
            ];
        }

        return $settings;
    }
}
