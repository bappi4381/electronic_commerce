<?php

/*
|--------------------------------------------------------------------------
| Product search
|--------------------------------------------------------------------------
|
| engine: "meilisearch" | "database" | "auto"
|   auto uses Meilisearch when MEILISEARCH_HOST is set and the server answers,
|   otherwise the in-process database engine (same normalisation and synonyms,
|   fine for small catalogs and tests).
|
*/

return [
    'engine' => env('SEARCH_ENGINE', 'auto'),

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST'),
        // Master/admin key: server-side only, never sent to the browser.
        'key' => env('MEILISEARCH_KEY'),
        'index' => env('MEILISEARCH_INDEX', 'products'),
        'timeout' => (float) env('MEILISEARCH_TIMEOUT', 2.0),
        // Phase 2: hybrid keyword + vector search. Keyword search stays the default.
        'hybrid' => [
            'enabled' => (bool) env('SEARCH_HYBRID', false),
            'embedder' => env('SEARCH_HYBRID_EMBEDDER', 'multilingual'),
            'semantic_ratio' => (float) env('SEARCH_HYBRID_RATIO', 0.3),
        ],
    ],

    'synonyms_path' => base_path('search/synonyms.json'),
    'stopwords_path' => base_path('search/stopwords.json'),

    // Seconds to cache identical suggestion requests.
    'cache_ttl' => (int) env('SEARCH_CACHE_TTL', 60),

    // Requests per minute per IP for /api/search.
    'rate_limit' => (int) env('SEARCH_RATE_LIMIT', 120),

    'suggest' => [
        'products' => 6,
        'categories' => 3,
        'brands' => 3,
    ],

    // Shown under the search box until real search logs exist.
    'default_trending' => ['Rice cooker', 'Smartphone', 'Earbuds', 'Pen drive', 'Electric kettle', 'Smart watch', 'T-shirt'],
];
