<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\WarehouseController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ── Public Routes ─────────────────────────────────────────────────────────────
Route::post('/auth/login',    [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// ── Protected Routes ──────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/summary',      [DashboardController::class, 'summary']);
        Route::get('/top-products', [DashboardController::class, 'topProducts']);
        Route::get('/low-stock',    [DashboardController::class, 'lowStock']);
    });

    // Categories
    Route::apiResource('categories', CategoryController::class);

    // Products
    Route::apiResource('products', ProductController::class);

    // Warehouses
    Route::apiResource('warehouses', WarehouseController::class);

    // Inventory
    Route::prefix('inventory')->group(function () {
        Route::get('/',                    [InventoryController::class, 'index']);
        Route::get('/alerts/low-stock',    [InventoryController::class, 'lowStock']);
        Route::post('/transfer',           [InventoryController::class, 'transfer']);
        Route::get('/{inventory}',         [InventoryController::class, 'show']);
        Route::put('/{inventory}',         [InventoryController::class, 'update']);
    });

    // Customers
    Route::apiResource('customers', CustomerController::class);

    // Employees
    Route::apiResource('employees', EmployeeController::class);

    // Transactions
    Route::apiResource('transactions', TransactionController::class);
    Route::patch('transactions/{transaction}/status', [TransactionController::class, 'updateStatus']);

    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('/sales',     [ReportController::class, 'sales']);
        Route::get('/inventory', [ReportController::class, 'inventory']);
        Route::get('/export',    [ReportController::class, 'export']);
    });
});
