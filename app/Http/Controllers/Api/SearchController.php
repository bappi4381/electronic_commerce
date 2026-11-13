<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Search\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Auto-suggest proxy. The browser never talks to Meilisearch, so the key stays on the server.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request, SearchService $search): JsonResponse
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:100',
            'category' => 'nullable|integer|min:1',
            'brand' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:12',
            'lang' => 'nullable|in:en,bn',
        ]);

        $locale = $data['lang'] ?? 'en';
        app()->setLocale($locale);

        $filters = array_filter([
            'category' => $data['category'] ?? null,
            'brand' => $data['brand'] ?? null,
        ]);

        $payload = $search->suggest((string) ($data['q'] ?? ''), $locale, $filters, $data['limit'] ?? null);

        if ($payload['total'] === 0 && $payload['normalized'] !== '') {
            $search->log($search->plan($payload['query']), 0, $request, 'suggest');
        }

        return response()->json($payload)->header('Cache-Control', 'public, max-age=30');
    }

    public function trending(SearchService $search): JsonResponse
    {
        return response()->json(['trending' => $search->trending()])->header('Cache-Control', 'public, max-age=300');
    }
}
