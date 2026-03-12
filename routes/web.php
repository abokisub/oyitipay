<?php

use App\Http\Controllers\API\PaymentController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/*
 |--------------------------------------------------------------------------
 | Web Routes
 |--------------------------------------------------------------------------
 |
 | Here is where you can register web routes for your application. These
 | routes are loaded by the RouteServiceProvider within a group which
 | contains the "web" middleware group. Now create something great!
 |
 */

Route::any('vdf_auto_fund_habukhan', [PaymentController::class , 'VDFWEBHOOK']);

// PointWave Webhook Route (must be before catch-all route)
Route::post('webhooks/pointwave', [\App\Http\Controllers\API\PointWaveWebhookController::class, 'handleWebhook']);

// Catch-all route for React SPA (MUST exclude /api/* paths)
Route::get('/{any}', function () {
    // Disable all caching for the HTML page
    return response()
        ->view('index')
        ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0')
        ->header('Pragma', 'no-cache')
        ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
})->where('any', '^(?!api).*$'); // Exclude paths starting with 'api'

Route::get('/cache', function () {
    return
    Cache::flush();
});