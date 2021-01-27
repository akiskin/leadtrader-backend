<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    \App\Http\Resources\User::withoutWrapping();
    return \App\Http\Resources\User::make($request->user());
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('products', \App\Http\Controllers\ProductController::class)->only('index');
    Route::apiResource('sellcampaigns', \App\Http\Controllers\SellCampaignController::class);
    Route::get('/sellcampaigns/{sellCampaign}/leads', [\App\Http\Controllers\SellCampaignController::class, 'leads']);

    Route::apiResource('buycampaigns', \App\Http\Controllers\BuyCampaignController::class);
    Route::get('/buycampaigns/{buyCampaign}/leads', [\App\Http\Controllers\BuyCampaignController::class, 'leads']);
    Route::get('/buycampaigns/{buyCampaign}/leads/export', [\App\Http\Controllers\BuyCampaignController::class, 'leadsForExport']);

    Route::post('/leads/bulk', [\App\Http\Controllers\LeadController::class, 'bulk']);
    Route::apiResource('leads', \App\Http\Controllers\LeadController::class)->only('store');

});

Route::post('/login', [\App\Http\Controllers\LoginController::class, 'authenticate']);
Route::post('/logout', [\App\Http\Controllers\LoginController::class, 'logout']);

Route::post('/register', [\App\Http\Controllers\LoginController::class, 'register'])
    ->middleware(['guest']);
