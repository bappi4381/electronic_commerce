<?php

namespace App\Search;

final class EngineResult
{
    /**
     * @param array<int, array{id:int, score:float}> $hits  best first, unique ids
     * @param array{category_ids?: array<int,int>, brand_id?: array<int,int>} $facets
     */
    public function __construct(
        public readonly array $hits,
        public readonly int $total,
        public readonly array $facets = [],
        public readonly ?string $correctedQuery = null,
    ) {
    }

    public function ids(): array
    {
        return array_column($this->hits, 'id');
    }

    public static function empty(): self
    {
        return new self([], 0);
    }
}
