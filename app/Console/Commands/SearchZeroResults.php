<?php

namespace App\Console\Commands;

use App\Models\SearchLog;
use Illuminate\Console\Command;

class SearchZeroResults extends Command
{
    protected $signature = 'search:zero-results {--days=30} {--limit=30}';

    protected $description = 'List the most frequent searches that found nothing (candidates for search/synonyms.json)';

    public function handle(): int
    {
        $rows = SearchLog::query()
            ->where('results_count', 0)
            ->where('created_at', '>=', now()->subDays((int) $this->option('days')))
            ->selectRaw('normalized, MAX(query) as example, MAX(script) as script, COUNT(*) as searches, MAX(created_at) as last_seen')
            ->groupBy('normalized')
            ->orderByDesc('searches')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No zero-result searches in this period.');

            return self::SUCCESS;
        }

        $this->table(['Normalized', 'Example', 'Script', 'Searches', 'Last seen'], $rows->map(fn ($r) => [
            $r->normalized, $r->example, $r->script, $r->searches, $r->last_seen,
        ])->all());

        $this->line('Add a matching group to search/synonyms.json, then run `php artisan search:settings`.');

        return self::SUCCESS;
    }
}
