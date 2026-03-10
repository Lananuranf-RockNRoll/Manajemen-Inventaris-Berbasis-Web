<?php

namespace App\Listeners;

use App\Events\StockWentLow;
use App\Mail\LowStockAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

/**
 * Listener: kirim email real-time saat stok turun di bawah min_stock.
 * Di-queue agar tidak blocking HTTP request.
 */
class SendLowStockNotification implements ShouldQueue
{
    public int    $tries   = 3;
    public int    $timeout = 30;
    public string $queue   = 'notifications';

    public function handle(StockWentLow $event): void
    {
        $inventory = $event->inventory->load(['product.category', 'warehouse']);

        // Kirim ke lananuranf@gmail.com
        Mail::to('lananuranf@gmail.com')
            ->queue(new LowStockAlert(new Collection([$inventory])));
    }
}
