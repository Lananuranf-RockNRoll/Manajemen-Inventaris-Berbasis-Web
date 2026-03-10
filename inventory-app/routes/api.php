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
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WarehouseController;
use Illuminate\Support\Facades\Route;

// ── Public Routes ─────────────────────────────────────────────────────────────
Route::post('/auth/login',    [AuthController::class, 'login']);

// ── Protected Routes ──────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me',      [AuthController::class, 'me']);

    // ── Dashboard ────────────────────────────────────────────────────────────
    Route::prefix('dashboard')->group(function () {
        Route::get('/summary',      [DashboardController::class, 'summary']);
        Route::get('/top-products', [DashboardController::class, 'topProducts']);
        Route::get('/low-stock',    [DashboardController::class, 'lowStock']);
    });

    // ── Categories (viewer+) ─────────────────────────────────────────────────
    Route::get('categories',          [CategoryController::class, 'index']);
    Route::get('categories/{category}',[CategoryController::class, 'show']);
    Route::middleware('role:admin,manager,staff')->group(function () {
        Route::post('categories',              [CategoryController::class, 'store']);
        Route::put('categories/{category}',    [CategoryController::class, 'update']);
        Route::delete('categories/{category}', [CategoryController::class, 'destroy']);
    });

    // ── Products (viewer+) ────────────────────────────────────────────────────
    Route::get('products',         [ProductController::class, 'index']);
    Route::get('products/{product}',[ProductController::class, 'show']);
    Route::middleware('role:admin,manager')->group(function () {
        Route::post('products',              [ProductController::class, 'store']);
        Route::put('products/{product}',     [ProductController::class, 'update']);
        Route::delete('products/{product}',  [ProductController::class, 'destroy']);
    });

    // ── Warehouses (viewer+) ──────────────────────────────────────────────────
    Route::get('warehouses',           [WarehouseController::class, 'index']);
    Route::get('warehouses/{warehouse}',[WarehouseController::class, 'show']);
    Route::middleware('role:admin,manager')->group(function () {
        Route::post('warehouses',               [WarehouseController::class, 'store']);
        Route::put('warehouses/{warehouse}',    [WarehouseController::class, 'update']);
        Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy']);
    });

    // ── Inventory (viewer: read, staff+: write) ───────────────────────────────
    Route::prefix('inventory')->group(function () {
        Route::get('/',                 [InventoryController::class, 'index']);
        Route::get('/alerts/low-stock', [InventoryController::class, 'lowStock']);
        Route::get('/{inventory}',      [InventoryController::class, 'show']);

        Route::middleware('role:admin,manager,staff')->group(function () {
            Route::post('/transfer',    [InventoryController::class, 'transfer']);
            Route::put('/{inventory}',  [InventoryController::class, 'update']);
        });
    });

    // ── Customers ─────────────────────────────────────────────────────────────
    Route::get('customers',           [CustomerController::class, 'index']);
    Route::get('customers/{customer}',[CustomerController::class, 'show']);
    Route::middleware('role:admin,manager,staff')->group(function () {
        Route::post('customers',               [CustomerController::class, 'store']);
        Route::put('customers/{customer}',     [CustomerController::class, 'update']);
    });
    // Credit management: manager+
    Route::middleware('role:admin,manager')->group(function () {
        Route::patch('customers/{customer}/credit',       [CustomerController::class, 'adjustCredit']);
        Route::post('customers/{customer}/reset-credit',  [CustomerController::class, 'resetCreditUsed']);
    });
    Route::middleware('role:admin')->group(function () {
        Route::delete('customers/{customer}', [CustomerController::class, 'destroy']);
    });

    // ── Employees ─────────────────────────────────────────────────────────────
    Route::get('employees',           [EmployeeController::class, 'index']);
    Route::get('employees/{employee}',[EmployeeController::class, 'show']);
    Route::middleware('role:admin,manager')->group(function () {
        Route::post('employees',               [EmployeeController::class, 'store']);
        Route::put('employees/{employee}',     [EmployeeController::class, 'update']);
        Route::delete('employees/{employee}',  [EmployeeController::class, 'destroy']);
    });

    // ── Transactions ──────────────────────────────────────────────────────────
    Route::get('transactions',              [TransactionController::class, 'index']);
    Route::get('transactions/{transaction}',[TransactionController::class, 'show']);
    Route::middleware('role:admin,manager,staff')->group(function () {
        Route::post('transactions',                              [TransactionController::class, 'store']);
        Route::put('transactions/{transaction}',                 [TransactionController::class, 'update']);
        Route::patch('transactions/{transaction}/status',        [TransactionController::class, 'updateStatus']);
    });
    Route::middleware('role:admin')->group(function () {
        Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy']);
    });

    // ── User Management (admin only) ──────────────────────────────────────────
    Route::middleware('role:admin')->prefix('users')->group(function () {
        Route::get('/',              [UserController::class, 'index']);
        Route::post('/',             [UserController::class, 'store']);
        Route::get('/{user}',        [UserController::class, 'show']);
        Route::put('/{user}',        [UserController::class, 'update']);
        Route::delete('/{user}',     [UserController::class, 'destroy']);
        Route::patch('/{user}/toggle-active', [UserController::class, 'toggleActive']);
    });

    // ── Reports ───────────────────────────────────────────────────────────────
    Route::prefix('reports')->group(function () {
        Route::get('/inventory/excel', [ReportController::class, 'inventoryExcel']);
        Route::get('/inventory/pdf',   [ReportController::class, 'inventoryPdf']);
        Route::get('/sales/excel',     [ReportController::class, 'salesExcel']);
        Route::get('/sales/pdf',       [ReportController::class, 'salesPdf']);
        Route::get('/dashboard/pdf',   [ReportController::class, 'dashboardPdf']);
    });
});
