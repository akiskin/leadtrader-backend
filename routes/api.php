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
});

Route::post('/login', [\App\Http\Controllers\LoginController::class, 'authenticate']);
Route::post('/logout', [\App\Http\Controllers\LoginController::class, 'logout']);
