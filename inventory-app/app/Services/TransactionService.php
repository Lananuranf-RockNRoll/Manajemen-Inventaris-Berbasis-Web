<?php

namespace App\Services;

use App\Events\OrderCanceled;
use App\Events\OrderShipped;
use App\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    /**
     * Buat order baru beserta item-itemnya.
     *
     * @throws Exception
     */
    public function createOrder(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $items = $data['items'];
            unset($data['items']);

            // Hitung total_amount dari items
            $totalAmount = collect($items)->sum(
                fn ($item) => $item['quantity'] * $item['unit_price']
            );

            $transaction = Transaction::create(array_merge($data, [
                'total_amount' => $totalAmount,
            ]));

            foreach ($items as $item) {
                $transaction->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                ]);
            }

            return $transaction;
        });
    }

    /**
     * Update status transaksi dengan business logic:
     * - shipped  → kurangi stok inventory
     * - canceled → kembalikan stok inventory (hanya jika sebelumnya shipped)
     *
     * @throws Exception
     */
    public function updateStatus(Transaction $transaction, string $newStatus): Transaction
    {
        $oldStatus = $transaction->status;

        if ($oldStatus === $newStatus) {
            return $transaction;
        }

        // Validasi transisi status yang diizinkan
        $allowedTransitions = [
            'pending'    => ['processing', 'canceled'],
            'processing' => ['shipped', 'canceled'],
            'shipped'    => ['delivered', 'canceled'],
            'delivered'  => [],
            'canceled'   => [],
        ];

        if (! in_array($newStatus, $allowedTransitions[$oldStatus] ?? [])) {
            throw new Exception(
                "Transisi status dari '{$oldStatus}' ke '{$newStatus}' tidak diizinkan."
            );
        }

        DB::transaction(function () use ($transaction, $newStatus, $oldStatus) {
            $updateData = ['status' => $newStatus];

            if ($newStatus === 'shipped') {
                $updateData['shipped_date'] = now()->toDateString();
            }

            $transaction->update($updateData);
            $transaction->load('items.product');

            if ($newStatus === 'shipped') {
                event(new OrderShipped($transaction));
            }

            if ($newStatus === 'canceled' && $oldStatus === 'shipped') {
                event(new OrderCanceled($transaction));
            }
        });

        return $transaction->fresh();
    }
}
