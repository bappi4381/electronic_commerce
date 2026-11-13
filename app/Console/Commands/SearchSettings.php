<?php

namespace App\Console\Commands;

use App\Search\Dictionary;
use App\Search\Engines\DatabaseEngine;
use App\Search\Engines\MeilisearchEngine;
use App\Search\SearchService;
use Illuminate\Console\Command;
use Throwable;

class SearchSettings extends Command
{
    protected $signature = 'search:settings {--show : Print the settings instead of pushing them}';

    protected $description = 'Push index settings, synonyms (search/synonyms.json) and stop words to Meilisearch';

    public function handle(SearchService $search): int
    {
        $dictionary = new Dictionary();
        app()->instance(Dictionary::class, $dictionary);
        $settings = MeilisearchEngine::settings($dictionary);

        $this->info(sprintf('%d synonym groups (%d terms), %d stop words.', count($dictionary->groups()), count($settings['synonyms']), count($settings['stopWords'])));

        if ($this->option('show')) {
            $this->line(json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        DatabaseEngine::flush();
        SearchService::flushLookups();

        $meili = $search->meilisearch();
        if (!$meili || !$search->meilisearchAvailable()) {
            $this->warn('Meilisearch is not configured or not reachable; the database engine reads the JSON files directly (caches cleared).');

            return self::SUCCESS;
        }

        try {
            $meili->client()->createIndex($meili->indexName(), ['primaryKey' => 'id']);
            $task = $meili->client()->index($meili->indexName())->updateSettings($settings);
            $result = $meili->client()->waitForTask($task['taskUid'], 60000);
        } catch (Throwable $e) {
            $this->error('Could not update settings: ' . $e->getMessage());

            return self::FAILURE;
        }

        if (($result['status'] ?? null) !== 'succeeded') {
            $this->error('Settings task ' . ($result['status'] ?? 'unknown') . ': ' . json_encode($result['error'] ?? null));

            return self::FAILURE;
        }

        $this->info("Settings applied to \"{$meili->indexName()}\".");

        return self::SUCCESS;
    }
}
