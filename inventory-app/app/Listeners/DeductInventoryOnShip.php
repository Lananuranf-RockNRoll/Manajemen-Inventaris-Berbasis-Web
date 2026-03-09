<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use App\Models\Transaction;
use App\Services\InventoryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class DeductInventoryOnShip implements ShouldQueue
{
    public int $tries = 3;
    public int $timeout = 60;

    public function __construct(private readonly InventoryService $inventoryService)
    {
    }

    public function handle(OrderShipped $event): void
    {
        $this->inventoryService->deductStock($event->transaction);
    }

    /**
     * Handle a failed job — revert the transaction status back to processing
     * so the issue becomes visible and does not leave inventory inconsistent.
     */
    public function failed(OrderShipped $event, \Throwable $exception): void
    {
        Log::error('DeductInventoryOnShip failed', [
            'transaction_id' => $event->transaction->id,
            'error'          => $exception->getMessage(),
        ]);

        // Revert status to processing so the order can be re-shipped
        Transaction::where('id', $event->transaction->id)
            ->where('status', 'shipped')
            ->update(['status' => 'processing', 'shipped_date' => null]);
    }
}
