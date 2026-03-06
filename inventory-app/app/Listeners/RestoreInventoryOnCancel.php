<?php

namespace App\Listeners;

use App\Events\OrderCanceled;
use App\Services\InventoryService;
use Illuminate\Contracts\Queue\ShouldQueue;

class RestoreInventoryOnCancel implements ShouldQueue
{
    public function __construct(private InventoryService $inventoryService)
    {
    }

    public function handle(OrderCanceled $event): void
    {
        $this->inventoryService->restoreStock($event->transaction);
    }
}
