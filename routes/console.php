<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Popularity (units sold) changes with every order; refresh it in the search index nightly.
\Illuminate\Support\Facades\Schedule::command('search:reindex')->dailyAt('03:30')->withoutOverlapping();
