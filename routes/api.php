<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CourierWebhookController;
use App\Http\Controllers\Api\SearchController;

// Storefront search (proxies Meilisearch; the key never reaches the browser)
Route::get('/search', SearchController::class)->middleware('throttle:search')->name('api.search');
Route::get('/search/trending', [SearchController::class, 'trending'])->middleware('throttle:search')->name('api.search.trending');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Courier Webhooks
Route::post('/webhooks/steadfast', [CourierWebhookController::class, 'steadfastWebhook'])
    ->middleware(['throttle:60,1', 'courier.webhook:steadfast']);
Route::post('/webhooks/pathao', [CourierWebhookController::class, 'pathaoWebhook'])
    ->middleware(['throttle:60,1', 'courier.webhook:pathao']);
