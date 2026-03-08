<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Kurangi stok saat order di-ship.
     *
     * @throws Exception
     */
public function deductStock(Transaction $transaction): void
{
    DB::transaction(function () use ($transaction) {
        foreach ($transaction->items as $item) {
            $inventory = Inventory::where([
                'product_id'   => $item->product_id,
                'warehouse_id' => $transaction->warehouse_id,
            ])->lockForUpdate()->first();

            // Kalau inventory tidak ada, throw pesan yang jelas
            if (!$inventory) {
                throw new Exception(
                    "Produk tidak tersedia di gudang ini. " .
                    "Silakan cek inventaris terlebih dahulu."
                );
            }

            if ($inventory->qty_available < $item->quantity) {
                throw new Exception(
                    "Stok tidak cukup untuk produk: {$item->product->name}. " .
                    "Tersedia: {$inventory->qty_available}, Dibutuhkan: {$item->quantity}"
                );
            }

            $inventory->decrement('qty_on_hand', $item->quantity);
        }
    });
}

    /**
     * Kembalikan stok saat order dibatalkan.
     */
    public function restoreStock(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            foreach ($transaction->items as $item) {
                Inventory::where([
                    'product_id'   => $item->product_id,
                    'warehouse_id' => $transaction->warehouse_id,
                ])->increment('qty_on_hand', $item->quantity);
            }
        });
    }

    /**
     * Transfer stok antar gudang.
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

        DB::transaction(function () use ($productId, $fromWarehouseId, $toWarehouseId, $qty) {
            $source = Inventory::where([
                'product_id'   => $productId,
                'warehouse_id' => $fromWarehouseId,
            ])->lockForUpdate()->firstOrFail();

            if ($source->qty_available < $qty) {
                throw new Exception(
                    "Stok tidak cukup untuk transfer. " .
                    "Tersedia: {$source->qty_available}, Dibutuhkan: {$qty}"
                );
            }

            $source->decrement('qty_on_hand', $qty);

            $destination = Inventory::where([
                'product_id'   => $productId,
                'warehouse_id' => $toWarehouseId,
            ])->first();

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
}
