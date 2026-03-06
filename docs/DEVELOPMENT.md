# 💻 Panduan Pengembangan

Dokumen ini ditujukan untuk developer yang ingin mengembangkan, menambahkan fitur baru, atau berkontribusi pada Sistem Informasi Manajemen Inventaris.

---

## 1. Struktur Folder Backend (Laravel)

```
inventory-app/
├── app/
│   ├── Events/                     # Event classes
│   │   ├── OrderShipped.php
│   │   └── OrderCanceled.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/                # Controller API per resource
│   │   │       ├── AuthController.php
│   │   │       ├── ProductController.php
│   │   │       ├── CategoryController.php
│   │   │       ├── WarehouseController.php
│   │   │       ├── InventoryController.php
│   │   │       ├── CustomerController.php
│   │   │       ├── EmployeeController.php
│   │   │       ├── TransactionController.php
│   │   │       ├── DashboardController.php
│   │   │       └── ReportController.php
│   │   ├── Middleware/
│   │   │   └── CheckRole.php       # Middleware validasi role RBAC
│   │   └── Requests/               # Form Request untuk validasi input
│   │       ├── StoreProductRequest.php
│   │       └── StoreTransactionRequest.php
│   ├── Listeners/                  # Event listener untuk stok
│   │   ├── DeductInventoryOnShip.php
│   │   └── RestoreInventoryOnCancel.php
│   ├── Models/                     # Eloquent models
│   │   ├── User.php
│   │   ├── Product.php
│   │   ├── Category.php
│   │   ├── Warehouse.php
│   │   ├── Inventory.php
│   │   ├── Customer.php
│   │   ├── Employee.php
│   │   ├── Transaction.php
│   │   └── TransactionItem.php
│   ├── Providers/
│   │   └── EventServiceProvider.php  # Registrasi event-listener
│   └── Services/                   # Logika bisnis kompleks
│       ├── InventoryService.php
│       └── TransactionService.php
├── config/
│   ├── cors.php                    # Konfigurasi CORS
│   └── sanctum.php                 # Konfigurasi Sanctum
├── database/
│   ├── migrations/                 # File skema database
│   └── seeders/                    # Data awal / dummy data
├── routes/
│   └── api.php                     # Definisi semua endpoint API
└── tests/
    └── Feature/                    # Feature tests
```

---

## 2. Struktur Folder Frontend (Vue.js)

```
inventory-ui/
├── src/
│   ├── api/                        # Modul axios per resource
│   │   ├── index.ts                # Axios instance + interceptors
│   │   ├── auth.ts
│   │   ├── products.ts
│   │   ├── categories.ts
│   │   ├── warehouses.ts
│   │   ├── inventory.ts
│   │   ├── customers.ts
│   │   ├── employees.ts
│   │   ├── transactions.ts
│   │   └── dashboard.ts
│   ├── assets/
│   │   └── main.css                # Tailwind CSS entry + custom utilities
│   ├── components/
│   │   └── layout/
│   │       └── AppLayout.vue       # Sidebar + topbar layout
│   ├── router/
│   │   └── index.ts                # Definisi routes + navigation guard
│   ├── stores/
│   │   └── auth.ts                 # Pinia auth store (user, token, permissions)
│   ├── types/
│   │   └── index.ts                # TypeScript interfaces
│   ├── views/                      # Halaman per fitur
│   │   ├── LoginView.vue
│   │   ├── dashboard/
│   │   │   └── DashboardView.vue
│   │   ├── products/
│   │   │   └── ProductsView.vue
│   │   ├── categories/
│   │   │   └── CategoriesView.vue
│   │   ├── warehouses/
│   │   │   └── WarehousesView.vue
│   │   ├── inventory/
│   │   │   └── InventoryView.vue
│   │   ├── customers/
│   │   │   └── CustomersView.vue
│   │   ├── employees/
│   │   │   └── EmployeesView.vue
│   │   └── transactions/
│   │       └── TransactionsView.vue
│   ├── App.vue                     # Root component
│   └── main.ts                     # Entry point aplikasi
├── public/
├── index.html
├── vite.config.ts                  # Konfigurasi Vite + alias path
├── tsconfig.json
└── package.json
```

---

## 3. Cara Menambah API Endpoint Baru

Ikuti langkah berikut untuk menambahkan endpoint baru, misalnya untuk modul **Supplier**.

### Step 1: Buat Migration

```bash
php artisan make:migration create_suppliers_table
```

Edit file migration yang baru dibuat di `database/migrations/`:

```php
public function up(): void
{
    Schema::create('suppliers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->nullable();
        $table->string('phone', 30)->nullable();
        $table->text('address')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
        $table->softDeletes();
    });
}
```

Jalankan migration:

```bash
php artisan migrate
```

### Step 2: Buat Model

```bash
php artisan make:model Supplier
```

Edit `app/Models/Supplier.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'email', 'phone', 'address', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
```

### Step 3: Buat Form Request (Validasi)

```bash
php artisan make:request StoreSupplierRequest
```

Edit `app/Http/Requests/StoreSupplierRequest.php`:

```php
public function rules(): array
{
    return [
        'name'    => 'required|string|max:100',
        'email'   => 'nullable|email|max:100',
        'phone'   => 'nullable|string|max:30',
        'address' => 'nullable|string',
    ];
}
```

### Step 4: Buat Controller

```bash
php artisan make:controller Api/SupplierController --api
```

Edit `app/Http/Controllers/Api/SupplierController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Supplier::query()
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->paginate($request->per_page ?? 15);

        return response()->json(['status' => 'success', 'data' => $suppliers->items(), 'meta' => [...$suppliers->toArray()]]);
    }

    public function store(StoreSupplierRequest $request)
    {
        $supplier = Supplier::create($request->validated());
        return response()->json(['status' => 'success', 'message' => 'Supplier berhasil ditambahkan', 'data' => $supplier], 201);
    }

    public function show(Supplier $supplier)
    {
        return response()->json(['status' => 'success', 'data' => $supplier]);
    }

    public function update(StoreSupplierRequest $request, Supplier $supplier)
    {
        $supplier->update($request->validated());
        return response()->json(['status' => 'success', 'message' => 'Supplier berhasil diperbarui', 'data' => $supplier]);
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return response()->json(['status' => 'success', 'message' => 'Supplier berhasil dihapus']);
    }
}
```

### Step 5: Daftarkan Route

Buka `routes/api.php` dan tambahkan:

```php
// Di dalam middleware group auth:sanctum
Route::apiResource('suppliers', SupplierController::class);
```

Atau dengan pembatasan akses per method:

```php
Route::get('suppliers', [SupplierController::class, 'index']);
Route::post('suppliers', [SupplierController::class, 'store'])->middleware('role:staff,manager,admin');
Route::put('suppliers/{supplier}', [SupplierController::class, 'update'])->middleware('role:manager,admin');
Route::delete('suppliers/{supplier}', [SupplierController::class, 'destroy'])->middleware('role:admin');
```

---

## 4. Cara Menambah Halaman Frontend Baru

Contoh: menambahkan halaman **Supplier**.

### Step 1: Buat API Module

Buat file `src/api/suppliers.ts`:

```typescript
import api from './index'

export const suppliersApi = {
  list: (params?: Record<string, any>) =>
    api.get('/suppliers', { params }),

  show: (id: number) =>
    api.get(`/suppliers/${id}`),

  create: (data: Record<string, any>) =>
    api.post('/suppliers', data),

  update: (id: number, data: Record<string, any>) =>
    api.put(`/suppliers/${id}`, data),

  destroy: (id: number) =>
    api.delete(`/suppliers/${id}`),
}
```

### Step 2: Tambah Type Definition

Buka `src/types/index.ts` dan tambahkan:

```typescript
export interface Supplier {
  id: number
  name: string
  email: string | null
  phone: string | null
  address: string | null
  is_active: boolean
  created_at: string
  updated_at: string
}
```

### Step 3: Buat Halaman View

Buat folder dan file baru: `src/views/suppliers/SuppliersView.vue`

```vue
<template>
  <div class="space-y-5">
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-lg font-bold text-zinc-100">Supplier</h2>
        <p class="text-xs text-zinc-500">{{ meta?.total ?? 0 }} supplier</p>
      </div>
      <button v-if="auth.canCreate" @click="openCreate" class="btn-primary">
        <Plus class="w-4 h-4" /> Tambah Supplier
      </button>
    </div>
    <!-- Tabel dan modal di sini -->
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { suppliersApi } from '@/api/suppliers'
import { useAuthStore } from '@/stores/auth'
// ... logic
</script>
```

### Step 4: Daftarkan Route

Buka `src/router/index.ts` dan tambahkan di dalam children layout:

```typescript
{
  path: 'suppliers',
  name: 'suppliers',
  component: () => import('@/views/suppliers/SuppliersView.vue'),
},
```

### Step 5: Tambahkan ke Sidebar

Buka `src/components/layout/AppLayout.vue`, tambahkan ke array `navItems`:

```typescript
import { Truck } from 'lucide-vue-next'  // Import icon baru

const navItems = [
  // ... item lainnya
  { to: '/suppliers', label: 'Supplier', icon: Truck },
]
```

---

## 5. Cara Menambah Migration Database

### Membuat migration baru:

```bash
# Tabel baru
php artisan make:migration create_nama_tabel_table

# Modifikasi tabel yang sudah ada
php artisan make:migration add_kolom_baru_to_nama_tabel_table
```

### Contoh migration modifikasi:

```php
// Menambah kolom ke tabel products
public function up(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->string('barcode', 50)->nullable()->after('sku');
        $table->decimal('weight_kg', 8, 3)->nullable()->after('description');
    });
}

public function down(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->dropColumn(['barcode', 'weight_kg']);
    });
}
```

Jalankan:

```bash
php artisan migrate
```

Rollback jika terjadi kesalahan:

```bash
php artisan migrate:rollback
```

---

## 6. Standar Penulisan Kode

### Backend (PHP / Laravel)

```php
// ✅ Gunakan type hints dan return types
public function index(Request $request): JsonResponse
{
    // ✅ Gunakan query builder dengan kondisi dinamis
    $products = Product::query()
        ->with(['category'])
        ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
        ->when($request->category_id, fn($q) => $q->where('category_id', $request->category_id))
        ->paginate($request->per_page ?? 15);

    // ✅ Response konsisten dengan format standar
    return response()->json([
        'status' => 'success',
        'data'   => $products->items(),
        'meta'   => [
            'current_page' => $products->currentPage(),
            'last_page'    => $products->lastPage(),
            'per_page'     => $products->perPage(),
            'total'        => $products->total(),
        ],
    ]);
}
```

**Konvensi penamaan:**
| Element | Konvensi | Contoh |
|---------|----------|--------|
| Class | PascalCase | `ProductController` |
| Method | camelCase | `getActiveProducts()` |
| Variable | camelCase | `$productList` |
| Database column | snake_case | `created_at` |
| Route name | kebab-case | `products.store` |

### Frontend (TypeScript / Vue)

```typescript
// ✅ Gunakan Composition API dengan <script setup>
// ✅ Definisikan tipe data secara eksplisit
const products = ref<Product[]>([])
const loading = ref<boolean>(false)

// ✅ Gunakan async/await dengan try/catch
async function fetchProducts(page = 1): Promise<void> {
  loading.value = true
  try {
    const res = await productsApi.list({ page })
    products.value = res.data.data
  } catch (error) {
    console.error('Gagal memuat produk:', error)
  } finally {
    loading.value = false
  }
}

// ✅ Computed property untuk logika turunan
const activeProducts = computed(() =>
  products.value.filter(p => p.is_active)
)
```

**Konvensi penamaan:**
| Element | Konvensi | Contoh |
|---------|----------|--------|
| Component | PascalCase | `ProductsView.vue` |
| Composable | camelCase dengan use | `useAuthStore` |
| Variable reaktif | camelCase | `isLoading` |
| Event handler | camelCase dengan handle/on | `handleSubmit` |
| CSS class | kebab-case (Tailwind) | `bg-zinc-900` |

### Git Commit Convention

Gunakan format [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: tambah fitur manajemen supplier
fix: perbaiki bug kalkulasi stok saat transfer
refactor: pisahkan logika transaksi ke TransactionService
docs: perbarui dokumentasi API endpoint
chore: update dependency laravel/sanctum ke versi terbaru
```

---

## 7. Testing

### Backend Testing

```bash
# Jalankan semua test
php artisan test

# Jalankan test spesifik
php artisan test --filter ProductTest

# Dengan coverage report
php artisan test --coverage
```

Contoh feature test:

```php
// tests/Feature/ProductTest.php
public function test_authenticated_user_can_list_products(): void
{
    $user = User::factory()->create(['role' => 'viewer']);
    
    $response = $this->actingAs($user)->getJson('/api/products');
    
    $response->assertStatus(200)
             ->assertJsonStructure(['status', 'data', 'meta']);
}

public function test_viewer_cannot_create_product(): void
{
    $user = User::factory()->create(['role' => 'viewer']);
    
    $response = $this->actingAs($user)->postJson('/api/products', [
        'name' => 'Test Product',
        'sku'  => 'TEST-001',
    ]);
    
    $response->assertStatus(403);
}
```

### Frontend Testing

```bash
# Unit test dengan Vitest
npm run test

# E2E test dengan Playwright (jika dikonfigurasi)
npm run test:e2e
```
