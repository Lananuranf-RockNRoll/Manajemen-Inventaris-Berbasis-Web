<?php

namespace App\Providers;

use App\Events\OrderCanceled;
use App\Events\OrderShipped;
use App\Events\StockWentLow;
use App\Listeners\DeductInventoryOnShip;
use App\Listeners\RestoreInventoryOnCancel;
use App\Listeners\SendLowStockNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Event → Listener mappings
     */
    protected $listen = [
        OrderShipped::class => [
            DeductInventoryOnShip::class,
        ],
        OrderCanceled::class => [
            RestoreInventoryOnCancel::class,
        ],
        // Real-time low stock alert → email langsung saat stok turun
        StockWentLow::class => [
            SendLowStockNotification::class,
        ],
    ];

    public function register(): void {}

    public function boot(): void {}
}
