<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schedule;
use App\Mail\LowStockAlert;
use App\Models\Inventory;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cek stok rendah dan kirim email setiap hari jam 08.00
Schedule::call(function () {
    $items = Inventory::with(['product', 'warehouse'])
        ->whereRaw('qty_on_hand - qty_reserved <= min_stock')
        ->get();

    if ($items->isNotEmpty()) {
        Mail::to('lananuranf@gmail.com')->send(new LowStockAlert($items));
    }
})->dailyAt('08:00')->name('check-low-stock');
