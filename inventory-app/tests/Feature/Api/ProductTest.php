<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $viewer;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin   = User::factory()->create(['role' => 'admin']);
        $this->viewer  = User::factory()->create(['role' => 'viewer']);
        $this->category = Category::factory()->create(['name' => 'CPU', 'slug' => 'cpu']);
    }

    public function test_can_list_products(): void
    {
        Sanctum::actingAs($this->viewer);

        Product::factory(5)->create(['category_id' => $this->category->id]);

        $response = $this->getJson('/api/products');

        $response->assertOk()
                 ->assertJsonStructure([
                     'data' => [['id', 'sku', 'name', 'list_price', 'category']],
                     'meta' => ['current_page', 'total'],
                 ]);
    }

    public function test_can_search_products(): void
    {
        Sanctum::actingAs($this->viewer);

        Product::factory()->create([
            'category_id' => $this->category->id,
            'name'        => 'Intel Xeon E5-2699',
            'sku'         => 'CPU-0001',
        ]);

        Product::factory()->create([
            'category_id' => $this->category->id,
            'name'        => 'AMD Ryzen 9',
            'sku'         => 'CPU-0002',
        ]);

        $response = $this->getJson('/api/products?search=Xeon');

        $response->assertOk();
        $this->assertEquals(1, $response->json('meta.total'));
    }

    public function test_admin_can_create_product(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/products', [
            'category_id'   => $this->category->id,
            'sku'           => 'CPU-TEST-001',
            'name'          => 'Test Product',
            'standard_cost' => 100.00,
            'list_price'    => 150.00,
        ]);

        $response->assertCreated()
                 ->assertJsonPath('data.name', 'Test Product');

        // profit_margin = 150 - 100 = 50 (integer dari JSON, bukan float)
        $this->assertEquals(50, $response->json('data.profit_margin'));

        $this->assertDatabaseHas('products', ['sku' => 'CPU-TEST-001']);
    }

    public function test_viewer_cannot_create_product(): void
    {
        Sanctum::actingAs($this->viewer);

        $response = $this->postJson('/api/products', [
            'category_id'   => $this->category->id,
            'sku'           => 'CPU-UNAUTHORIZED',
            'name'          => 'Unauthorized Product',
            'standard_cost' => 100.00,
            'list_price'    => 150.00,
        ]);

        $response->assertForbidden();
    }

    public function test_sku_must_be_unique(): void
    {
        Sanctum::actingAs($this->admin);

        Product::factory()->create([
            'category_id' => $this->category->id,
            'sku'         => 'CPU-DUPLICATE',
        ]);

        $response = $this->postJson('/api/products', [
            'category_id'   => $this->category->id,
            'sku'           => 'CPU-DUPLICATE',
            'name'          => 'Another Product',
            'standard_cost' => 100.00,
            'list_price'    => 150.00,
        ]);

        $response->assertUnprocessable()
                 ->assertJsonValidationErrors(['sku']);
    }

    public function test_list_price_must_be_gte_standard_cost(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/products', [
            'category_id'   => $this->category->id,
            'sku'           => 'CPU-INVALID-PRICE',
            'name'          => 'Invalid Price Product',
            'standard_cost' => 200.00,
            'list_price'    => 100.00,
        ]);

        $response->assertUnprocessable()
                 ->assertJsonValidationErrors(['list_price']);
    }

    public function test_can_soft_delete_product(): void
    {
        Sanctum::actingAs($this->admin);

        $product = Product::factory()->create(['category_id' => $this->category->id]);

        $this->deleteJson("/api/products/{$product->id}")->assertOk();

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_unauthenticated_user_cannot_access(): void
    {
        $this->getJson('/api/products')->assertUnauthorized();
    }
}
