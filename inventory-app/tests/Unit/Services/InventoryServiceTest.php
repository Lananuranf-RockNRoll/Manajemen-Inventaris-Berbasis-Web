<?php

namespace Tests\Unit\Services;

use App\Models\Inventory;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Services\InventoryService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InventoryService();
    }

    public function test_deduct_stock_reduces_qty(): void
    {
        $inventory = Inventory::factory()->create([
            'qty_on_hand'  => 100,
            'qty_reserved' => 0,
        ]);

        $transaction = Transaction::factory()->create([
            'warehouse_id' => $inventory->warehouse_id,
        ]);

        TransactionItem::factory()->create([
            'transaction_id' => $transaction->id,
            'product_id'     => $inventory->product_id,
            'quantity'       => 10,
            'unit_price'     => 100.00,
        ]);

        $transaction->load('items.product');

        $this->service->deductStock($transaction);

        $this->assertEquals(90, $inventory->fresh()->qty_on_hand);
    }

    public function test_deduct_stock_throws_when_insufficient(): void
    {
        $this->expectException(Exception::class);

        $inventory = Inventory::factory()->create([
            'qty_on_hand'  => 5,
            'qty_reserved' => 0,
        ]);

        $transaction = Transaction::factory()->create([
            'warehouse_id' => $inventory->warehouse_id,
        ]);

        TransactionItem::factory()->create([
            'transaction_id' => $transaction->id,
            'product_id'     => $inventory->product_id,
            'quantity'       => 10, // More than available
            'unit_price'     => 100.00,
        ]);

        $transaction->load('items.product');
        $this->service->deductStock($transaction);
    }

    public function test_restore_stock_increments_qty(): void
    {
        $inventory = Inventory::factory()->create([
            'qty_on_hand'  => 50,
            'qty_reserved' => 0,
        ]);

        $transaction = Transaction::factory()->create([
            'warehouse_id' => $inventory->warehouse_id,
        ]);

        TransactionItem::factory()->create([
            'transaction_id' => $transaction->id,
            'product_id'     => $inventory->product_id,
            'quantity'       => 10,
            'unit_price'     => 100.00,
        ]);

        $transaction->load('items');
        $this->service->restoreStock($transaction);

        $this->assertEquals(60, $inventory->fresh()->qty_on_hand);
    }

    public function test_transfer_stock_between_warehouses(): void
    {
        $sourceInventory = Inventory::factory()->create([
            'qty_on_hand'  => 100,
            'qty_reserved' => 0,
        ]);

        $destInventory = Inventory::factory()->create([
            'product_id'   => $sourceInventory->product_id,
            'qty_on_hand'  => 20,
            'qty_reserved' => 0,
        ]);

        $this->service->transferStock(
            productId:       $sourceInventory->product_id,
            fromWarehouseId: $sourceInventory->warehouse_id,
            toWarehouseId:   $destInventory->warehouse_id,
            qty:             30
        );

        $this->assertEquals(70, $sourceInventory->fresh()->qty_on_hand);
        $this->assertEquals(50, $destInventory->fresh()->qty_on_hand);
    }

    public function test_transfer_throws_when_same_warehouse(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('tidak boleh sama');

        $inventory = Inventory::factory()->create();

        $this->service->transferStock(
            productId:       $inventory->product_id,
            fromWarehouseId: $inventory->warehouse_id,
            toWarehouseId:   $inventory->warehouse_id,
            qty:             10
        );
    }
}
