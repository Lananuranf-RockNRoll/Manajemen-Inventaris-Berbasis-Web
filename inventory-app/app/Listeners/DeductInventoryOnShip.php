<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use App\Services\InventoryService;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeductInventoryOnShip implements ShouldQueue
{
    public function __construct(private InventoryService $inventoryService)
    {
    }

    public function handle(OrderShipped $event): void
    {
        $this->inventoryService->deductStock($event->transaction);
    }
}
