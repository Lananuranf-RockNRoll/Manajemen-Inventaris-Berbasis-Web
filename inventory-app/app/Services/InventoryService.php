<?php

namespace App\Services;

use App\Events\StockWentLow;
use App\Models\Inventory;
use App\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    /**
     * Deduct stock for each item in a shipped transaction.
     * Fires StockWentLow event immediately when stock crosses min threshold.
     *
     * @throws Exception
     */
    public function deductStock(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction): void {
            foreach ($transaction->items as $item) {
                $inventory = $this->findInventoryOrFail(
                    productId: $item->product_id,
                    warehouseId: $transaction->warehouse_id,
                    notFoundMessage: "Produk '{$item->product->name}' tidak tersedia di gudang ini."
                );

                $this->ensureSufficientStock(
                    inventory: $inventory,
                    requiredQty: $item->quantity,
                    errorMessage: "Stok tidak cukup untuk '{$item->product->name}'. "
                        . "Tersedia: {$inventory->qty_available}, Dibutuhkan: {$item->quantity}."
                );

                $previousQty = $inventory->qty_on_hand;
                $inventory->decrement('qty_on_hand', $item->quantity);
                $inventory->refresh();

                // ── Real-time alert: fire event jika baru saja turun ke bawah min_stock ──
                // Cek: sebelumnya di atas threshold, sekarang di bawah atau sama
                $wasOk  = ($previousQty - $inventory->qty_reserved) > $inventory->min_stock;
                $isLow  = $inventory->qty_available <= $inventory->min_stock;

                if ($wasOk && $isLow) {
                    event(new StockWentLow(
                        inventory:   $inventory,
                        previousQty: $previousQty,
                        newQty:      $inventory->qty_on_hand,
                    ));
                }
            }
        });
    }

    /**
     * Restore stock when a shipped transaction is canceled.
     */
    public function restoreStock(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction): void {
            foreach ($transaction->items as $item) {
                $affected = Inventory::query()
                    ->where('product_id', $item->product_id)
                    ->where('warehouse_id', $transaction->warehouse_id)
                    ->increment('qty_on_hand', $item->quantity);

                if ($affected === 0) {
                    Log::warning('InventoryService::restoreStock — inventory record not found', [
                        'product_id'   => $item->product_id,
                        'warehouse_id' => $transaction->warehouse_id,
                    ]);
                }
            }
        });
    }

    /**
     * Transfer stock between two warehouses.
     * Also checks low stock threshold on source warehouse after transfer.
     *
     * @throws Exception
     */
    public function transferStock(
        int $productId,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $qty
    ): void {
        if ($fromWarehouseId === $toWarehouseId) {
            throw new Exception('Gudang asal dan tujuan tidak boleh sama.');
        }
        if ($qty <= 0) {
            throw new Exception('Jumlah transfer harus lebih dari 0.');
        }

        DB::transaction(function () use ($productId, $fromWarehouseId, $toWarehouseId, $qty): void {
            $source = $this->findInventoryOrFail(
                productId: $productId,
                warehouseId: $fromWarehouseId,
                notFoundMessage: 'Stok sumber tidak ditemukan di gudang asal.'
            );

            $this->ensureSufficientStock(
                inventory: $source,
                requiredQty: $qty,
                errorMessage: "Stok tidak cukup untuk transfer. "
                    . "Tersedia: {$source->qty_available}, Dibutuhkan: {$qty}."
            );

            $previousQty = $source->qty_on_hand;
            $source->decrement('qty_on_hand', $qty);
            $source->refresh();

            // Cek low stock setelah transfer
            $wasOk = ($previousQty - $source->qty_reserved) > $source->min_stock;
            $isLow = $source->qty_available <= $source->min_stock;
            if ($wasOk && $isLow) {
                event(new StockWentLow(
                    inventory:   $source,
                    previousQty: $previousQty,
                    newQty:      $source->qty_on_hand,
                ));
            }

            $destination = Inventory::query()
                ->where('product_id', $productId)
                ->where('warehouse_id', $toWarehouseId)
                ->first();

            if ($destination) {
                $destination->increment('qty_on_hand', $qty);
            } else {
                Inventory::create([
                    'product_id'   => $productId,
                    'warehouse_id' => $toWarehouseId,
                    'qty_on_hand'  => $qty,
                    'qty_reserved' => 0,
                    'min_stock'    => $source->min_stock,
                    'max_stock'    => $source->max_stock,
                ]);
            }
        });
    }

    // ── Private helpers ─────────────────────────────────────────────────────────

    /** @throws Exception */
    private function findInventoryOrFail(int $productId, int $warehouseId, string $notFoundMessage): Inventory
    {
        $inventory = Inventory::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first();

        if (! $inventory) {
            throw new Exception($notFoundMessage);
        }

        return $inventory;
    }

    /** @throws Exception */
    private function ensureSufficientStock(Inventory $inventory, int $requiredQty, string $errorMessage): void
    {
        if ($inventory->qty_available < $requiredQty) {
            throw new Exception($errorMessage);
        }
    }
}
