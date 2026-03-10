<?php

namespace App\Services;

use App\Events\OrderCanceled;
use App\Events\OrderShipped;
use App\Models\Customer;
use App\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    private const STATUS_PENDING    = 'pending';
    private const STATUS_PROCESSING = 'processing';
    private const STATUS_SHIPPED    = 'shipped';
    private const STATUS_DELIVERED  = 'delivered';
    private const STATUS_CANCELED   = 'canceled';

    private const ALLOWED_TRANSITIONS = [
        self::STATUS_PENDING    => [self::STATUS_PROCESSING, self::STATUS_CANCELED],
        self::STATUS_PROCESSING => [self::STATUS_SHIPPED, self::STATUS_CANCELED],
        self::STATUS_SHIPPED    => [self::STATUS_DELIVERED, self::STATUS_CANCELED],
        self::STATUS_DELIVERED  => [],
        self::STATUS_CANCELED   => [],
    ];

    /**
     * @throws Exception
     */
    public function createOrder(array $data): Transaction
    {
        return DB::transaction(function () use ($data): Transaction {
            $items = $data['items'];
            unset($data['items']);

            $totalAmount = $this->calculateTotalAmount($items);

            // ── Credit validation ─────────────────────────────────────────────
            if (isset($data['customer_id'])) {
                $this->validateCustomerCredit($data['customer_id'], $totalAmount);
            }

            $transaction = Transaction::create([
                ...$data,
                'total_amount' => $totalAmount,
            ]);

            $this->createTransactionItems($transaction, $items);

            // Tambah credit_used pada customer
            if ($transaction->customer_id) {
                Customer::where('id', $transaction->customer_id)
                    ->increment('credit_used', $totalAmount);
            }

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

            if ($newStatus === self::STATUS_CANCELED) {
                // Kembalikan credit_used ke customer
                Customer::where('id', $transaction->customer_id)
                    ->decrement('credit_used', (float) $transaction->total_amount);

                if ($currentStatus === self::STATUS_SHIPPED) {
                    event(new OrderCanceled($transaction));
                }
            }
        });

        return $transaction->fresh();
    }

    // ── Private ───────────────────────────────────────────────────────────────

    /**
     * Validate customer credit availability (in USD)
     * @throws Exception
     */
    private function validateCustomerCredit(int $customerId, float $totalAmount): void
    {
        $customer = Customer::findOrFail($customerId);

        if ($customer->status !== 'active') {
            throw new Exception("Customer berstatus '{$customer->status}' tidak dapat melakukan pembelian.");
        }

        $creditAvailable = (float) $customer->credit_limit - (float) $customer->credit_used;

        if ($totalAmount > $creditAvailable) {
            throw new Exception(
                "Kredit customer tidak mencukupi. " .
                "Tersedia: \${$this->fmt($creditAvailable)}, " .
                "Dibutuhkan: \${$this->fmt($totalAmount)}."
            );
        }
    }

    private function fmt(float $val): string
    {
        return number_format($val, 2);
    }

    private function calculateTotalAmount(array $items): float
    {
        return (float) collect($items)->sum(
            fn(array $item): float => $item['quantity'] * $item['unit_price']
        );
    }

    private function createTransactionItems(Transaction $transaction, array $items): void
    {
        foreach ($items as $item) {
            $transaction->items()->create([
                'product_id' => $item['product_id'],
                'quantity'   => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'subtotal'   => $item['quantity'] * $item['unit_price'],
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
