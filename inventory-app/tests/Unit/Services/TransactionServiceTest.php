<?php

namespace Tests\Unit\Services;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\Warehouse;
use App\Services\TransactionService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransactionService $service;
    private Customer $customer;
    private Warehouse $warehouse;
    private Product $product;
    private Inventory $inventory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TransactionService();

        $category = Category::factory()->create(['name' => 'Electronics', 'slug' => 'electronics']);

        $this->product = Product::factory()->create([
            'category_id'   => $category->id,
            'standard_cost' => 50.00,
            'list_price'    => 100.00,
        ]);

        $this->warehouse = Warehouse::factory()->create();

        $this->customer = Customer::factory()->create([
            'credit_limit' => 1000.00,
            'credit_used'  => 0.00,
            'status'       => 'active',
        ]);

        $this->inventory = Inventory::factory()->create([
            'product_id'   => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'qty_on_hand'  => 100,
            'qty_reserved' => 0,
            'min_stock'    => 5,
        ]);
    }

    // ── createOrder ───────────────────────────────────────────────────────────

    public function test_create_order_calculates_total_correctly(): void
    {
        $transaction = $this->service->createOrder([
            'customer_id'  => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'items'        => [
                ['product_id' => $this->product->id, 'quantity' => 3, 'unit_price' => 100.00],
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 50.00],
            ],
        ]);

        // (3 * 100) + (2 * 50) = 400
        $this->assertEquals(400.00, (float) $transaction->total_amount);
        $this->assertEquals('pending', $transaction->status);
    }

    public function test_create_order_increments_customer_credit_used(): void
    {
        $this->service->createOrder([
            'customer_id'  => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'items'        => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 100.00],
            ],
        ]);

        $this->assertEquals(200.00, (float) $this->customer->fresh()->credit_used);
    }

    public function test_create_order_throws_when_credit_exceeded(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Kredit customer tidak mencukupi/');

        // Total 1500 > credit_limit 1000
        $this->service->createOrder([
            'customer_id'  => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'items'        => [
                ['product_id' => $this->product->id, 'quantity' => 15, 'unit_price' => 100.00],
            ],
        ]);
    }

    public function test_create_order_throws_when_customer_inactive(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/tidak dapat melakukan pembelian/');

        $inactiveCustomer = Customer::factory()->create([
            'credit_limit' => 9999.00,
            'status'       => 'inactive',
        ]);

        $this->service->createOrder([
            'customer_id'  => $inactiveCustomer->id,
            'warehouse_id' => $this->warehouse->id,
            'items'        => [
                ['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 100.00],
            ],
        ]);
    }

    public function test_create_order_creates_transaction_items(): void
    {
        $transaction = $this->service->createOrder([
            'customer_id'  => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'items'        => [
                ['product_id' => $this->product->id, 'quantity' => 5, 'unit_price' => 100.00],
            ],
        ]);

        $this->assertCount(1, $transaction->items);
        $this->assertEquals(500.00, (float) $transaction->items->first()->subtotal);
    }

    // ── updateStatus ──────────────────────────────────────────────────────────

    public function test_update_status_pending_to_processing(): void
    {
        $transaction = Transaction::factory()->create([
            'customer_id'  => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'status'       => 'pending',
        ]);

        $updated = $this->service->updateStatus($transaction, 'processing');

        $this->assertEquals('processing', $updated->status);
    }

    public function test_update_status_processing_to_shipped_sets_shipped_date(): void
    {
        $transaction = Transaction::factory()->create([
            'customer_id'  => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'status'       => 'processing',
        ]);

        $updated = $this->service->updateStatus($transaction, 'shipped');

        $this->assertEquals('shipped', $updated->status);
        $this->assertNotNull($updated->shipped_date);
    }

    public function test_update_status_returns_same_when_no_change(): void
    {
        $transaction = Transaction::factory()->create([
            'customer_id'  => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'status'       => 'pending',
        ]);

        $result = $this->service->updateStatus($transaction, 'pending');

        $this->assertEquals('pending', $result->status);
    }

    public function test_update_status_throws_on_invalid_transition(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/tidak diizinkan/');

        $transaction = Transaction::factory()->create([
            'customer_id'  => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'status'       => 'delivered',
        ]);

        $this->service->updateStatus($transaction, 'canceled');
    }

    public function test_cancel_shipped_order_restores_credit(): void
    {
        $this->customer->update(['credit_used' => 500.00]);

        $transaction = Transaction::factory()->create([
            'customer_id'  => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'status'       => 'shipped',
            'total_amount' => 500.00,
        ]);

        $this->service->updateStatus($transaction, 'canceled');

        $this->assertEquals(0.00, (float) $this->customer->fresh()->credit_used);
    }
}
