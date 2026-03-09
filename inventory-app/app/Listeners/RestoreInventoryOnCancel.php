<?php

namespace App\Listeners;

use App\Events\OrderCanceled;
use App\Services\InventoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class RestoreInventoryOnCancel implements ShouldQueue
{
    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function handle(OrderCanceled $event): void
    {
        $this->inventoryService->restoreStock($event->transaction);
    }

    public function failed(OrderCanceled $event, \Throwable $exception): void
    {
        Log::error('RestoreInventoryOnCancel failed', [
            'transaction_id' => $event->transaction->id,
            'error'          => $exception->getMessage(),
        ]);
    }
}
