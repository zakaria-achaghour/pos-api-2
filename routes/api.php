<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ---------- AUTH ----------
Route::post('/register', [AuthController::class, 'register']); // optional
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/refresh',  [AuthController::class, 'refresh'])->middleware('auth:api');
Route::post('/logout',   [AuthController::class, 'logout'])->middleware('auth:api');

// ---------- PROTECTED ----------
Route::middleware(['auth:api','tenant'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    // Add your domain routes here, e.g.:
    // Route::apiResource('tables', TableController::class);
    // Route::apiResource('categories', MenuCategoryController::class);
    // Route::apiResource('items', MenuItemController::class);
});