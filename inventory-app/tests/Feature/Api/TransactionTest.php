<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;
    private Customer $customer;
    private Warehouse $warehouse;
    private Product $product;
    private Inventory $inventory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staff = User::factory()->create(['role' => 'staff']);

        $category = Category::factory()->create(['name' => 'CPU', 'slug' => 'cpu']);

        $this->product = Product::factory()->create([
            'category_id'   => $category->id,
            'standard_cost' => 100.00,
            'list_price'    => 150.00,
        ]);

        $this->warehouse = Warehouse::factory()->create();
        $this->customer  = Customer::factory()->create(['credit_limit' => 5000.00]);

        $this->inventory = Inventory::factory()->create([
            'product_id'   => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'qty_on_hand'  => 100,
            'qty_reserved' => 0,
            'min_stock'    => 10,
        ]);
    }

    public function test_can_create_transaction(): void
    {
        Sanctum::actingAs($this->staff);

        $response = $this->postJson('/api/transactions', [
            'customer_id'  => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'items'        => [
                [
                    'product_id' => $this->product->id,
                    'quantity'   => 5,
                    'unit_price' => 150.00,
                ],
            ],
        ]);

        $response->assertCreated()
                 ->assertJsonPath('data.total_amount', '750.00')
                 ->assertJsonPath('data.status', 'pending');
    }

    public function test_transaction_requires_items(): void
    {
        Sanctum::actingAs($this->staff);

        $response = $this->postJson('/api/transactions', [
            'customer_id'  => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'items'        => [],
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['items']);
    }

    public function test_can_update_status_to_processing(): void
    {
        Sanctum::actingAs($this->staff);

        $transaction = Transaction::factory()->create([
            'customer_id'  => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'status'       => 'pending',
        ]);

        $response = $this->patchJson("/api/transactions/{$transaction->id}/status", [
            'status' => 'processing',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'processing');
    }

    public function test_cannot_transition_from_delivered(): void
    {
        Sanctum::actingAs($this->staff);

        $transaction = Transaction::factory()->create([
            'customer_id'  => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'status'       => 'delivered',
        ]);

        $response = $this->patchJson("/api/transactions/{$transaction->id}/status", [
            'status' => 'canceled',
        ]);

        // Controller menangkap Exception dan return 422
        $response->assertUnprocessable();
    }

    public function test_can_list_transactions_by_status(): void
    {
        Sanctum::actingAs($this->staff);

        Transaction::factory(3)->create([
            'customer_id'  => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'status'       => 'pending',
        ]);

        Transaction::factory(2)->create([
            'customer_id'  => $this->customer->id,
            'warehouse_id' => $this->warehouse->id,
            'status'       => 'shipped',
        ]);

        $response = $this->getJson('/api/transactions?status=pending');
        $response->assertOk();
        $this->assertEquals(3, $response->json('meta.total'));
    }
}
