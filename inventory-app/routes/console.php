<?php

use App\Mail\LowStockAlert;
use App\Models\Inventory;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Daily digest: ringkasan semua stok rendah setiap hari jam 08:00 WIB ──────
// Real-time alert ditangani oleh StockWentLow event → SendLowStockNotification listener
Schedule::call(function () {
    $items = Inventory::with(['product.category', 'warehouse'])
        ->lowStock()
        ->get();

    if ($items->isNotEmpty()) {
        Mail::to('lananuranf@gmail.com')
            ->queue(new LowStockAlert($items, isRealtime: false));
    }
})
->dailyAt('08:00')
->timezone('Asia/Jakarta')
->name('daily-low-stock-digest')
->withoutOverlapping();
