<?php

use App\Http\Controllers\Admin\AdminTenantController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MenuCategoryController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\TableController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ---------- AUTH ----------
Route::post('/register', [AuthController::class, 'register']); // optional
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/refresh',  [AuthController::class, 'refresh'])->middleware('auth:api');
Route::post('/logout',   [AuthController::class, 'logout'])->middleware('auth:api');

Route::prefix('admin')
  ->middleware(['auth:api','role:SuperAdmin'])
  ->group(function () {
    Route::get('restaurants', [AdminTenantController::class, 'listRestaurants']);
    Route::get('restaurants/{restaurant}/overview', [AdminTenantController::class, 'overview']);
    Route::get('restaurants/{restaurant}/tables', [AdminTenantController::class, 'tables']);
    Route::get('restaurants/{restaurant}/menu/categories', [AdminTenantController::class, 'categories']);
    Route::get('restaurants/{restaurant}/menu/items', [AdminTenantController::class, 'items']);
    Route::get('restaurants/{restaurant}/orders', [AdminTenantController::class, 'orders']);
    Route::get('restaurants/{restaurant}/reports/summary', [AdminTenantController::class, 'dailySummary']);
    
    // Optional: impersonation (returns JWT for that user; lock this down!)
    Route::post('impersonate/{user}', [AdminTenantController::class, 'impersonate']);
});

// ---------- PROTECTED ----------
Route::middleware(['auth:api','tenant'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    Route::apiResource('tables', TableController::class);
    Route::apiResource('categories', MenuCategoryController::class);
    Route::apiResource('items', MenuItemController::class);


    // Orders
    Route::get('orders', [OrderController::class,'index']);
    Route::post('orders', [OrderController::class,'store']);
    Route::get('orders/{order}', [OrderController::class,'show']);
    Route::post('orders/{order}/items', [OrderController::class,'addItem']);
    Route::put('orders/{order}/items/{orderItem}', [OrderController::class,'updateItem']);
    Route::delete('orders/{order}/items/{orderItem}', [OrderController::class,'removeItem']);
    Route::post('orders/{order}/close', [OrderController::class,'close']);

    // Reports
    Route::get('reports/summary', [ReportController::class,'summary']);
});