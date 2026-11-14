<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Popularity (units sold) changes with every order; refresh it in the search index nightly.
\Illuminate\Support\Facades\Schedule::command('search:reindex')->dailyAt('03:30')->withoutOverlapping();

// Flash deal start/end: swaps flash discounts in and out on time.
Artisan::command('flash-sale:sync', function (\App\Services\FlashSale $flashSale) {
    $result = $flashSale->sync();
    $this->info("Applied {$result['applied']}, restored {$result['restored']}, closed {$result['closed']}.");
})->purpose('Apply or restore flash deal prices according to the campaign schedule');
\Illuminate\Support\Facades\Schedule::command('flash-sale:sync')->everyMinute()->withoutOverlapping();

// Fallback for missed courier webhooks.
\Illuminate\Support\Facades\Schedule::command('courier:sync-status')->everyThirtyMinutes()->withoutOverlapping(25);
