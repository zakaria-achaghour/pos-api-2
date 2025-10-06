<?php

use App\Http\Controllers\Admin\AdminTenantController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MenuCategoryController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\TableController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\KitchenController;
use App\Http\Controllers\Api\TableAnalyticsController;
use App\Http\Controllers\Api\ScheduleController;
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

    // Basic Resources
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

    // Kitchen Management
    Route::prefix('kitchen')->group(function () {
        Route::get('tickets', [KitchenController::class, 'index']);
        Route::get('tickets/{kitchenTicket}', [KitchenController::class, 'show']);
        Route::post('tickets/{kitchenTicket}/assign', [KitchenController::class, 'assign']);
        Route::post('tickets/{kitchenTicket}/start', [KitchenController::class, 'start']);
        Route::post('tickets/{kitchenTicket}/complete', [KitchenController::class, 'complete']);
        Route::put('tickets/{kitchenTicket}/priority', [KitchenController::class, 'updatePriority']);
        Route::get('analytics', [KitchenController::class, 'analytics']);
    });

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('summary', [ReportController::class, 'summary']);
        Route::get('sales', [ReportController::class, 'sales']);
        Route::get('items', [ReportController::class, 'items']);
        Route::get('staff', [ReportController::class, 'staff']);
        Route::post('export', [ReportController::class, 'export']);
    });

    // Staff Management
    Route::apiResource('staff', StaffController::class);
    Route::get('staff/performance/summary', [StaffController::class, 'performance']);
    
    // Attendance Management
    Route::prefix('staff/attendance')->group(function () {
        Route::post('clock-in', [AttendanceController::class, 'clockIn']);
        Route::post('clock-out', [AttendanceController::class, 'clockOut']);
        Route::get('/', [AttendanceController::class, 'index']);
        Route::get('summary', [AttendanceController::class, 'summary']);
    });
    
    // Analytics & Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('metrics', [AnalyticsController::class, 'dashboardMetrics']);
        Route::get('charts', [AnalyticsController::class, 'salesCharts']);
        Route::get('top-items', [AnalyticsController::class, 'topItems']);
    });

    // Table Analytics
    Route::prefix('tables')->group(function () {
        Route::get('analytics', [TableAnalyticsController::class, 'index']);
        Route::get('{table}/analytics', [TableAnalyticsController::class, 'show']);
        Route::get('occupancy-rates', [TableAnalyticsController::class, 'occupancyRates']);
        Route::get('revenue-per-table', [TableAnalyticsController::class, 'revenuePerTable']);
        Route::put('layout', [TableAnalyticsController::class, 'updateLayout']);
    });
    
    // Staff Performance (separate from staff resource)
    Route::get('analytics/staff-performance', [StaffController::class, 'performance']);
});