<?php

use App\Http\Controllers\Admin\AdminTenantController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
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
    // Restaurant CRUD operations
    Route::get('restaurants', [AdminTenantController::class, 'listRestaurants']);
    Route::post('restaurants', [AdminTenantController::class, 'createRestaurant']);
    Route::get('restaurants/{restaurant}', [AdminTenantController::class, 'showRestaurant']);
    Route::put('restaurants/{restaurant}', [AdminTenantController::class, 'updateRestaurant']);
    Route::delete('restaurants/{restaurant}', [AdminTenantController::class, 'deleteRestaurant']);
    Route::get('restaurants/{restaurant}/stats', [AdminTenantController::class, 'getRestaurantStats']);
    
    // Restaurant-specific views
    Route::get('restaurants/{restaurant}/overview', [AdminTenantController::class, 'overview']);
    Route::get('restaurants/{restaurant}/tables', [AdminTenantController::class, 'tables']);
    Route::get('restaurants/{restaurant}/menu/categories', [AdminTenantController::class, 'categories']);
    Route::get('restaurants/{restaurant}/menu/items', [AdminTenantController::class, 'items']);
    Route::get('restaurants/{restaurant}/orders', [AdminTenantController::class, 'orders']);
    Route::get('restaurants/{restaurant}/reports/summary', [AdminTenantController::class, 'dailySummary']);
    
    // Role & Permission Management
    Route::apiResource('roles', RoleController::class)->except(['create', 'edit']);
    Route::get('roles/{role}/users', [RoleController::class, 'users']);
    Route::apiResource('permissions', PermissionController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    
    // Optional: impersonation (returns JWT for that user; lock this down!)
    Route::post('impersonate/{user}', [AdminTenantController::class, 'impersonate']);
});

// ---------- PROTECTED ----------
Route::middleware(['auth:api','tenant'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    // Roles - For staff creation/filtering (accessible by Owner, Manager)
    Route::get('roles', [\App\Http\Controllers\Api\RoleController::class, 'index']);
    Route::get('roles/assignable', [\App\Http\Controllers\Api\RoleController::class, 'assignable']);

    // Table Analytics (must come before table resource routes)
    Route::prefix('tables')->group(function () {
        Route::get('analytics', [TableAnalyticsController::class, 'index']);
        Route::get('occupancy-rates', [TableAnalyticsController::class, 'occupancyRates']);
        Route::get('revenue-per-table', [TableAnalyticsController::class, 'revenuePerTable']);
        Route::put('layout', [TableAnalyticsController::class, 'updateLayout']);
        Route::match(['put', 'patch'], '{table}/status', [TableController::class, 'updateStatus']);
        Route::post('{id}/restore', [TableController::class, 'restore']);
        Route::delete('{id}/force', [TableController::class, 'forceDelete']);
        Route::get('{table}/analytics', [TableAnalyticsController::class, 'show']);
    });

    // Basic Resources
    Route::apiResource('tables', TableController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('items', MenuItemController::class);

    // Orders
    Route::get('orders', [OrderController::class,'index']);
    Route::post('orders', [OrderController::class,'store']);
    Route::get('orders/{order}', [OrderController::class,'show']);
    Route::put('orders/{order}', [OrderController::class,'update']);
    Route::post('orders/{order}/items', [OrderController::class,'addItem']);
    Route::put('orders/{order}/items/{orderItem}', [OrderController::class,'updateItem']);
    Route::delete('orders/{order}/items/{orderItem}', [OrderController::class,'removeItem']);
    Route::post('orders/{order}/close', [OrderController::class,'close']);
    Route::patch('orders/{order}/payment', [OrderController::class,'updatePayment']);
    Route::patch('orders/{order}/status', [OrderController::class,'updateStatus']);

    // Kitchen Management
    Route::prefix('kitchen')->group(function () {
        Route::get('tickets', [KitchenController::class, 'index']);
        Route::get('tickets/{kitchenTicket}', [KitchenController::class, 'show']);
        Route::post('tickets/{kitchenTicket}/assign', [KitchenController::class, 'assign']);
        Route::post('tickets/{kitchenTicket}/start', [KitchenController::class, 'start']);
        Route::post('tickets/{kitchenTicket}/complete', [KitchenController::class, 'complete']);
        Route::post('tickets/{kitchenTicket}/serve', [KitchenController::class, 'serve']);
        Route::put('tickets/{kitchenTicket}/priority', [KitchenController::class, 'updatePriority']);
        Route::get('analytics', [KitchenController::class, 'analytics']);
    });

     // Schedule Management
    Route::apiResource('schedules', ScheduleController::class);
    Route::get('schedules/weekly', [ScheduleController::class, 'weekly']);
    Route::post('schedules/bulk', [ScheduleController::class, 'bulk']);

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
    Route::post('staff/{id}/restore', [StaffController::class, 'restore']);
    Route::delete('staff/{id}/force', [StaffController::class, 'forceDelete']);
    Route::get('staff/performance/summary', [StaffController::class, 'performance']);
    
    // Attendance Management
    Route::prefix('staff/attendance')->group(function () {
        Route::post('clock-in', [AttendanceController::class, 'clockIn']);
        Route::post('clock-out', [AttendanceController::class, 'clockOut']);
        Route::get('/', [AttendanceController::class, 'index']);
        Route::get('summary', [AttendanceController::class, 'summary']);
        // PDF Reports
        Route::get('reports/summary-pdf', [AttendanceController::class, 'generateSummaryPdf']);
        Route::get('reports/detailed-pdf', [AttendanceController::class, 'generateDetailedPdf']);
    });
    
    // Analytics & Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('metrics', [AnalyticsController::class, 'dashboardMetrics']);
        Route::get('charts', [AnalyticsController::class, 'salesCharts']);
        Route::get('top-items', [AnalyticsController::class, 'topItems']);
    });

    
    // Staff Performance (separate from staff resource)
    Route::get('analytics/staff-performance', [StaffController::class, 'performance']);
});