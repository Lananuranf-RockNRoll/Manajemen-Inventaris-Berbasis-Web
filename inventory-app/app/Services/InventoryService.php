<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * @throws Exception
     */
    public function deductStock(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction): void {
            foreach ($transaction->items as $item) {
                $inventory = $this->findInventoryOrFail(
                    productId: $item->product_id,
                    warehouseId: $transaction->warehouse_id,
                    notFoundMessage: 'Produk tidak tersedia di gudang ini. Silakan cek inventaris terlebih dahulu.'
                );

                $this->ensureSufficientStock(
                    inventory: $inventory,
                    requiredQty: $item->quantity,
                    errorMessage: "Stok tidak cukup untuk produk: {$item->product->name}. Tersedia: {$inventory->qty_available}, Dibutuhkan: {$item->quantity}"
                );

                $inventory->decrement('qty_on_hand', $item->quantity);
            }
        });
    }

    public function restoreStock(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction): void {
            foreach ($transaction->items as $item) {
                Inventory::query()
                    ->where('product_id', $item->product_id)
                    ->where('warehouse_id', $transaction->warehouse_id)
                    ->increment('qty_on_hand', $item->quantity);
            }
        });
    }

    /**
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

        DB::transaction(function () use ($productId, $fromWarehouseId, $toWarehouseId, $qty): void {
            $sourceInventory = $this->findInventoryOrFail(
                productId: $productId,
                warehouseId: $fromWarehouseId,
                notFoundMessage: 'Stok sumber tidak ditemukan.'
            );

            $this->ensureSufficientStock(
                inventory: $sourceInventory,
                requiredQty: $qty,
                errorMessage: "Stok tidak cukup untuk transfer. Tersedia: {$sourceInventory->qty_available}, Dibutuhkan: {$qty}"
            );

            $sourceInventory->decrement('qty_on_hand', $qty);

            $destinationInventory = Inventory::query()
                ->where('product_id', $productId)
                ->where('warehouse_id', $toWarehouseId)
                ->first();

            if ($destinationInventory) {
                $destinationInventory->increment('qty_on_hand', $qty);

                return;
            }

            Inventory::create([
                'product_id' => $productId,
                'warehouse_id' => $toWarehouseId,
                'qty_on_hand' => $qty,
                'qty_reserved' => 0,
                'min_stock' => $sourceInventory->min_stock,
                'max_stock' => $sourceInventory->max_stock,
            ]);
        });
    }

    /**
     * @throws Exception
     */
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

    /**
     * @throws Exception
     */
    private function ensureSufficientStock(Inventory $inventory, int $requiredQty, string $errorMessage): void
    {
        if ($inventory->qty_available < $requiredQty) {
            throw new Exception($errorMessage);
        }
    }
}
