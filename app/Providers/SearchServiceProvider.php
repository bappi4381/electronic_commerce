<?php

namespace App\Providers;

use App\Search\Dictionary;
use App\Search\Engines\DatabaseEngine;
use App\Search\Engines\MeilisearchEngine;
use App\Search\IndexSync;
use App\Search\ProductDocument;
use App\Search\SearchService;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Meilisearch\Client;

class SearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Dictionary::class, fn () => new Dictionary());
        $this->app->singleton(ProductDocument::class);
        $this->app->singleton(DatabaseEngine::class);

        $this->app->singleton(MeilisearchEngine::class, function () {
            $host = config('search.meilisearch.host');
            if (!$host) {
                return null;
            }

            $http = new GuzzleClient([
                'timeout' => config('search.meilisearch.timeout', 2.0),
                'connect_timeout' => 1.0,
            ]);

            return new MeilisearchEngine(
                new Client($host, config('search.meilisearch.key'), $http),
                config('scout.prefix', '') . config('search.meilisearch.index', 'products'),
            );
        });

        $this->app->singleton(SearchService::class, fn ($app) => new SearchService(
            $app->make(Dictionary::class),
            $app->make(DatabaseEngine::class),
            $app->make(MeilisearchEngine::class),
        ));
    }

    public function boot(): void
    {
        IndexSync::register();

        \Illuminate\Support\Facades\View::composer('frontend.layout', \App\View\Composers\StorefrontComposer::class);
        \App\Models\Category::saved(fn () => \Illuminate\Support\Facades\Cache::forget(\App\View\Composers\StorefrontComposer::DEPARTMENTS_CACHE_KEY));
        \App\Models\Category::deleted(fn () => \Illuminate\Support\Facades\Cache::forget(\App\View\Composers\StorefrontComposer::DEPARTMENTS_CACHE_KEY));

        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(config('search.rate_limit', 120))->by($request->ip()));
    }
}
