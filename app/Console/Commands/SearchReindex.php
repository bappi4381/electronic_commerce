<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Search\Engines\DatabaseEngine;
use App\Search\Engines\MeilisearchEngine;
use App\Search\ProductDocument;
use App\Search\SearchService;
use Illuminate\Console\Command;
use Throwable;

class SearchReindex extends Command
{
    protected $signature = 'search:reindex {--queue : Dispatch Scout jobs instead of pushing documents directly} {--fresh : Delete all documents first}';

    protected $description = 'Rebuild the product search index (Meilisearch when configured, plus the database engine cache)';

    public function handle(SearchService $search, ProductDocument $documents): int
    {
        DatabaseEngine::flush();
        SearchService::flushLookups();

        $meili = $search->meilisearch();
        if (!$meili || !$search->meilisearchAvailable()) {
            $this->warn('Meilisearch is not configured or not reachable (MEILISEARCH_HOST / SCOUT_DRIVER=meilisearch). Rebuilding the database engine only.');
            $count = count(app(DatabaseEngine::class)->index()['rows']);
            $this->info("Database search index rebuilt: {$count} products.");

            return self::SUCCESS;
        }

        if ($this->option('queue')) {
            Product::makeAllSearchable();
            $this->info('Queued Scout import jobs. Run a queue worker to process them.');

            return self::SUCCESS;
        }

        try {
            $index = $meili->client()->index($meili->indexName());
            $meili->client()->createIndex($meili->indexName(), ['primaryKey' => 'id']);

            if ($this->option('fresh')) {
                $meili->client()->waitForTask($index->deleteAllDocuments()['taskUid']);
            }

            $total = 0;
            $bar = $this->output->createProgressBar(Product::count());
            Product::query()
                ->with(['brand', 'category.parent', 'images', 'allVariants'])
                ->withSum('orderItems', 'quantity')
                ->chunkById(500, function ($products) use ($index, $documents, &$total, $bar) {
                    $batch = $products->map(fn ($p) => $documents->make($p))->all();
                    $index->addDocuments($batch, 'id');
                    $total += count($batch);
                    $bar->advance(count($batch));
                });
            $bar->finish();
            $this->newLine();

            // Archived products must not linger in the index.
            $trashed = Product::onlyTrashed()->pluck('id')->all();
            if ($trashed) {
                $index->deleteDocuments($trashed);
            }

            $this->info("Sent {$total} products to Meilisearch index \"{$meili->indexName()}\". Run `php artisan search:settings` if settings or synonyms changed.");
        } catch (Throwable $e) {
            $this->error('Meilisearch import failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
