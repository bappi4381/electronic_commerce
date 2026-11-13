<?php

namespace App\Search\Contracts;

use App\Search\EngineResult;
use App\Search\QueryPlan;

interface SearchEngine
{
    public function name(): string;

    /**
     * @param array{category?:int, brand?:int, min_price?:float, max_price?:float, in_stock?:bool} $filters
     * @param string|null $sort relevance|price_asc|price_desc|popularity|rating|newest
     */
    public function search(QueryPlan $plan, array $filters = [], ?string $sort = null, int $offset = 0, int $limit = 20, bool $withFacets = false): EngineResult;

    /** Product ids ordered by popularity (used for zero-result fallbacks). */
    public function popular(int $limit = 6): array;
}
