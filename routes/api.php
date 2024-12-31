<?php

use App\Http\Controllers\V1\AuthController;
use App\Http\Controllers\V1\NewsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::prefix('/news')->middleware('auth:sanctum')->controller(NewsController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/preferences', 'getPreferences');
        Route::post('/preferences', 'updatePreferences');
        Route::get('/user/news', 'getNewsByPreferences');
    });
});
