<?php

namespace App\Events;

use App\Models\Inventory;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired immediately after stock drops below min_stock threshold.
 * Digunakan untuk trigger real-time email alert ke admin.
 */
class StockWentLow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Inventory $inventory,
        public readonly int       $previousQty,
        public readonly int       $newQty,
    ) {}
}
