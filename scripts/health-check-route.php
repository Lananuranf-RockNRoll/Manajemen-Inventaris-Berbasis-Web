<?php
// =============================================================
// InvenSys — Health Check Route
// Tambahkan kode ini ke: inventory-app/routes/api.php
// =============================================================
//
// CARA INTEGRASI:
// Buka file: inventory-app/routes/api.php
// Tambahkan route berikut di bagian PALING BAWAH file,
// SEBELUM tag penutup PHP (jika ada):
//
// PENTING: Route ini TIDAK boleh pakai middleware 'auth'
// karena diakses oleh GitHub Actions tanpa token!

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

Route::get('/health', function () {
    $checks = [];
    $overallStatus = 'ok';

    // Cek koneksi database
    try {
        DB::connection()->getPdo();
        $checks['database'] = 'ok';
    } catch (\Exception $e) {
        $checks['database'] = 'error';
        $overallStatus = 'degraded';
    }

    // Cek cache/Redis
    try {
        Cache::put('_healthcheck', true, now()->addSeconds(5));
        Cache::get('_healthcheck');
        $checks['cache'] = 'ok';
    } catch (\Exception $e) {
        $checks['cache'] = 'error';
        // Cache error tidak fatal, tidak ubah status ke degraded
    }

    // Cek storage writable
    try {
        $testFile = storage_path('app/.healthcheck_' . time());
        file_put_contents($testFile, 'ok');
        unlink($testFile);
        $checks['storage'] = 'ok';
    } catch (\Exception $e) {
        $checks['storage'] = 'error';
        $overallStatus = 'degraded';
    }

    $httpCode = $overallStatus === 'ok' ? 200 : 503;

    return response()->json([
        'status'    => $overallStatus,
        'app'       => config('app.name', 'InvenSys'),
        'env'       => config('app.env'),
        'timestamp' => now()->toISOString(),
        'checks'    => $checks,
    ], $httpCode);
})->name('health');
