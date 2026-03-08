<?php

namespace App\Services;

use App\Events\OrderCanceled;
use App\Events\OrderShipped;
use App\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    private const STATUS_PENDING = 'pending';
    private const STATUS_PROCESSING = 'processing';
    private const STATUS_SHIPPED = 'shipped';
    private const STATUS_DELIVERED = 'delivered';
    private const STATUS_CANCELED = 'canceled';

    private const ALLOWED_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_PROCESSING, self::STATUS_CANCELED],
        self::STATUS_PROCESSING => [self::STATUS_SHIPPED, self::STATUS_CANCELED],
        self::STATUS_SHIPPED => [self::STATUS_DELIVERED, self::STATUS_CANCELED],
        self::STATUS_DELIVERED => [],
        self::STATUS_CANCELED => [],
    ];

    /**
     * @throws Exception
     */
    public function createOrder(array $data): Transaction
    {
        return DB::transaction(function () use ($data): Transaction {
            $items = $data['items'];
            unset($data['items']);

            $transaction = Transaction::create([
                ...$data,
                'total_amount' => $this->calculateTotalAmount($items),
            ]);

            $this->createTransactionItems($transaction, $items);

            return $transaction;
        });
    }

    /**
     * @throws Exception
     */
    public function updateStatus(Transaction $transaction, string $newStatus): Transaction
    {
        $currentStatus = $transaction->status;

        if ($currentStatus === $newStatus) {
            return $transaction;
        }

        $this->ensureValidStatusTransition($currentStatus, $newStatus);

        DB::transaction(function () use ($transaction, $currentStatus, $newStatus): void {
            $transaction->update($this->buildStatusUpdatePayload($newStatus));
            $transaction->load('items.product');

            if ($newStatus === self::STATUS_SHIPPED) {
                event(new OrderShipped($transaction));
            }

            if ($currentStatus === self::STATUS_SHIPPED && $newStatus === self::STATUS_CANCELED) {
                event(new OrderCanceled($transaction));
            }
        });

        return $transaction->fresh();
    }

    private function calculateTotalAmount(array $items): float
    {
        return (float) collect($items)->sum(
            fn (array $item): float => $item['quantity'] * $item['unit_price']
        );
    }

    private function createTransactionItems(Transaction $transaction, array $items): void
    {
        foreach ($items as $item) {
            $transaction->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
            ]);
        }
    }

    /**
     * @throws Exception
     */
    private function ensureValidStatusTransition(string $currentStatus, string $newStatus): void
    {
        $allowedStatuses = self::ALLOWED_TRANSITIONS[$currentStatus] ?? [];

        if (! in_array($newStatus, $allowedStatuses, true)) {
            throw new Exception("Transisi status dari '{$currentStatus}' ke '{$newStatus}' tidak diizinkan.");
        }
    }

    private function buildStatusUpdatePayload(string $newStatus): array
    {
        $payload = ['status' => $newStatus];

        if ($newStatus === self::STATUS_SHIPPED) {
            $payload['shipped_date'] = now()->toDateString();
        }

        return $payload;
    }
}
