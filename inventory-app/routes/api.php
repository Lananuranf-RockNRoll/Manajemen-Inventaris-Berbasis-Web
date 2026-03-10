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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

// ── Health Check (public, no auth) ───────────────────────────────────────────
Route::get('/health', function () {
    $checks = [];
    $status = 'ok';

    try {
        DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (\Exception $e) {
        $checks['database'] = 'error';
        $status = 'degraded';
    }

    try {
        $f = storage_path('app/.healthcheck');
        file_put_contents($f, 'ok');
        unlink($f);
        $checks['storage'] = 'ok';
    } catch (\Exception $e) {
        $checks['storage'] = 'error';
        $status = 'degraded';
    }

    return response()->json([
        'status'    => $status,
        'app'       => config('app.name'),
        'env'       => config('app.env'),
        'timestamp' => now()->toISOString(),
        'checks'    => $checks,
    ], $status === 'ok' ? 200 : 503);
});

// ── Public Routes ─────────────────────────────────────────────────────────────
Route::post('/auth/login', [AuthController::class, 'login']);

// ── Protected Routes (semua butuh Sanctum auth) ───────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // ── Auth ──────────────────────────────────────────────────────────────────
    Route::post('/auth/logout',      [AuthController::class, 'logout']);
    Route::get('/auth/me',           [AuthController::class, 'me']);
    Route::get('/auth/permissions',  [AuthController::class, 'permissions']); // daftar permission user aktif

    // ── Dashboard (semua role yang login) ─────────────────────────────────────
    Route::middleware('permission:dashboard.view')->prefix('dashboard')->group(function () {
        Route::get('/summary',      [DashboardController::class, 'summary']);
        Route::get('/top-products', [DashboardController::class, 'topProducts']);
        Route::get('/low-stock',    [DashboardController::class, 'lowStock']);
    });

    // ── Categories ────────────────────────────────────────────────────────────
    // viewer, staff, manager, admin: baca
    Route::middleware('permission:category.view')->group(function () {
        Route::get('categories',            [CategoryController::class, 'index']);
        Route::get('categories/{category}', [CategoryController::class, 'show']);
    });
    // manager, admin: tambah/edit/hapus
    Route::middleware('permission:category.create')->post('categories', [CategoryController::class, 'store']);
    Route::middleware('permission:category.update')->put('categories/{category}', [CategoryController::class, 'update']);
    Route::middleware('permission:category.delete')->delete('categories/{category}', [CategoryController::class, 'destroy']);

    // ── Products ──────────────────────────────────────────────────────────────
    // viewer, staff, manager, admin: baca
    Route::middleware('permission:product.view')->group(function () {
        Route::get('products',           [ProductController::class, 'index']);
        Route::get('products/{product}', [ProductController::class, 'show']);
    });
    // manager, admin: tambah/edit
    Route::middleware('permission:product.create')->post('products', [ProductController::class, 'store']);
    Route::middleware('permission:product.update')->put('products/{product}', [ProductController::class, 'update']);
    // admin only: hapus
    Route::middleware('permission:product.delete')->delete('products/{product}', [ProductController::class, 'destroy']);

    // ── Warehouses ────────────────────────────────────────────────────────────
    // viewer, staff, manager, admin: baca
    Route::middleware('permission:warehouse.view')->group(function () {
        Route::get('warehouses',             [WarehouseController::class, 'index']);
        Route::get('warehouses/{warehouse}', [WarehouseController::class, 'show']);
    });
    // manager, admin: tambah/edit
    Route::middleware('permission:warehouse.create')->post('warehouses', [WarehouseController::class, 'store']);
    Route::middleware('permission:warehouse.update')->put('warehouses/{warehouse}', [WarehouseController::class, 'update']);
    // admin only: hapus
    Route::middleware('permission:warehouse.delete')->delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy']);

    // ── Inventory ─────────────────────────────────────────────────────────────
    // viewer, staff, manager, admin: baca
    Route::middleware('permission:inventory.view')->prefix('inventory')->group(function () {
        Route::get('/',                 [InventoryController::class, 'index']);
        Route::get('/alerts/low-stock', [InventoryController::class, 'lowStock']);
        Route::get('/{inventory}',      [InventoryController::class, 'show']);
    });
    // manager, admin: update & transfer
    Route::middleware('permission:inventory.update')->put('inventory/{inventory}', [InventoryController::class, 'update']);
    Route::middleware('permission:inventory.transfer')->post('inventory/transfer', [InventoryController::class, 'transfer']);

    // ── Customers ─────────────────────────────────────────────────────────────
    // viewer, staff, manager, admin: baca
    Route::middleware('permission:customer.view')->group(function () {
        Route::get('customers',            [CustomerController::class, 'index']);
        Route::get('customers/{customer}', [CustomerController::class, 'show']);
    });
    // staff, manager, admin: tambah/edit
    Route::middleware('permission:customer.create')->post('customers', [CustomerController::class, 'store']);
    Route::middleware('permission:customer.update')->put('customers/{customer}', [CustomerController::class, 'update']);
    // admin only: hapus
    Route::middleware('permission:customer.delete')->delete('customers/{customer}', [CustomerController::class, 'destroy']);
    // manager, admin: kelola kredit
    Route::middleware('permission:customer.credit')->group(function () {
        Route::patch('customers/{customer}/credit',      [CustomerController::class, 'adjustCredit']);
        Route::post('customers/{customer}/reset-credit', [CustomerController::class, 'resetCreditUsed']);
    });

    // ── Employees ─────────────────────────────────────────────────────────────
    // viewer, staff, manager, admin: baca
    Route::middleware('permission:employee.view')->group(function () {
        Route::get('employees',            [EmployeeController::class, 'index']);
        Route::get('employees/{employee}', [EmployeeController::class, 'show']);
    });
    // manager, admin: tambah/edit/hapus
    Route::middleware('permission:employee.create')->post('employees', [EmployeeController::class, 'store']);
    Route::middleware('permission:employee.update')->put('employees/{employee}', [EmployeeController::class, 'update']);
    Route::middleware('permission:employee.delete')->delete('employees/{employee}', [EmployeeController::class, 'destroy']);

    // ── Transactions ──────────────────────────────────────────────────────────
    // viewer, staff, manager, admin: baca
    Route::middleware('permission:transaction.view')->group(function () {
        Route::get('transactions',               [TransactionController::class, 'index']);
        Route::get('transactions/{transaction}', [TransactionController::class, 'show']);
    });
    // staff, manager, admin: tambah transaksi baru
    Route::middleware('permission:transaction.create')->post('transactions', [TransactionController::class, 'store']);
    // staff, manager, admin: edit notes (controller akan batasi staff ke pending saja)
    Route::middleware('permission:transaction.update')->put('transactions/{transaction}', [TransactionController::class, 'update']);
    // manager, admin: ubah status (controller akan batasi manager tidak bisa sentuh delivered)
    Route::middleware('permission:transaction.update_status')->patch('transactions/{transaction}/status', [TransactionController::class, 'updateStatus']);
    // admin only: hapus (hanya pending)
    Route::middleware('permission:transaction.delete')->delete('transactions/{transaction}', [TransactionController::class, 'destroy']);

    // ── User Management ───────────────────────────────────────────────────────
    // admin only: semua operasi user
    Route::middleware('permission:user.manage')->prefix('users')->group(function () {
        Route::get('/',                       [UserController::class, 'index']);
        Route::post('/',                      [UserController::class, 'store']);
        Route::get('/{user}',                 [UserController::class, 'show']);
        Route::put('/{user}',                 [UserController::class, 'update']);
        Route::delete('/{user}',              [UserController::class, 'destroy']);
        Route::patch('/{user}/toggle-active', [UserController::class, 'toggleActive']);
    });

    // ── Reports ───────────────────────────────────────────────────────────────
    // manager, admin: lihat laporan
    Route::middleware('permission:report.view')->prefix('reports')->group(function () {
        Route::get('/inventory/excel',                    [ReportController::class, 'inventoryExcel']);
        Route::get('/sales/excel',                        [ReportController::class, 'salesExcel']);
        Route::get('/sales/pdf',                          [ReportController::class, 'salesPdf']);
        Route::get('/dashboard/pdf',                      [ReportController::class, 'dashboardPdf']);
        Route::get('/transactions/{transaction}/invoice', [ReportController::class, 'invoicePdf']);
    });
});
