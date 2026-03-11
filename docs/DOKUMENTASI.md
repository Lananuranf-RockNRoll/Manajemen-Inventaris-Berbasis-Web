# 📦 InvenSys — Dokumentasi Lengkap

> Dokumentasi ini ditulis untuk developer yang ingin **memahami, mempelajari, dan mengembangkan** project InvenSys dari nol. Setiap bagian dijelaskan secara bertahap dan mudah dipahami.

---

## Daftar Isi

1. [Gambaran Umum Aplikasi](#1-gambaran-umum-aplikasi)
2. [Arsitektur Aplikasi](#2-arsitektur-aplikasi)
3. [Struktur Folder Project](#3-struktur-folder-project)
4. [Penjelasan File Penting](#4-penjelasan-file-penting)
5. [Penjelasan Kode Detail](#5-penjelasan-kode-detail)
6. [Alur Fitur Aplikasi](#6-alur-fitur-aplikasi)
7. [Sistem Role dan Hak Akses (RBAC)](#7-sistem-role-dan-hak-akses-rbac)
8. [Database](#8-database)
9. [Routing dan API](#9-routing-dan-api)
10. [Alur Proses Data](#10-alur-proses-data)
11. [Debugging Guide](#11-debugging-guide)
12. [Cara Menjalankan Project](#12-cara-menjalankan-project)
13. [Deployment di EC2](#13-deployment-di-ec2)
14. [Analisis Clean Code](#14-analisis-clean-code)
15. [Diagram Alur Aplikasi](#15-diagram-alur-aplikasi)
16. [Penjelasan Istilah Programming](#16-penjelasan-istilah-programming)
17. [Panduan Belajar Project Ini](#17-panduan-belajar-project-ini)

---

## 1. Gambaran Umum Aplikasi

### Nama Aplikasi
**InvenSys** — Sistem Manajemen Inventaris Berbasis Web

### Tujuan Aplikasi
InvenSys adalah aplikasi untuk mengelola inventaris gudang secara digital. Aplikasi ini menggantikan pencatatan manual (kertas/Excel) dengan sistem terpusat yang bisa diakses oleh banyak pengguna sekaligus.

### Masalah yang Diselesaikan
- ❌ Tidak ada pencatatan stok real-time → ✅ Stok selalu update otomatis saat transaksi
- ❌ Tidak ada kontrol siapa yang bisa akses data apa → ✅ Sistem Role (RBAC) 4 level
- ❌ Laporan harus dibuat manual → ✅ Export Excel dan PDF otomatis
- ❌ Tidak ada notifikasi stok menipis → ✅ Email otomatis saat stok di bawah minimum
- ❌ Data tersebar di banyak file → ✅ Satu database terpusat

### Target Pengguna
| Role | Siapa |
|---|---|
| Admin | Pemilik / IT Manager |
| Manager | Kepala Gudang |
| Staff | Karyawan Gudang / Sales |
| Viewer | Manajemen / Auditor (hanya lihat) |

### Fitur Utama
- **Dashboard** — Ringkasan revenue, order, stok rendah, top produk
- **Manajemen Produk** — CRUD produk dengan SKU, harga modal, harga jual, margin otomatis
- **Manajemen Kategori** — Pengelompokan produk
- **Manajemen Gudang** — Kelola beberapa lokasi gudang
- **Inventaris** — Stok per produk per gudang, transfer antar gudang, alert stok rendah
- **Manajemen Customer** — Data pelanggan + sistem kredit (credit limit)
- **Manajemen Karyawan** — Data pegawai terhubung ke akun user dan gudang
- **Transaksi / Order** — Buat pesanan, ubah status (pending → processing → shipped → delivered), download invoice PDF
- **Manajemen User** — Tambah/edit/hapus user, atur role (admin only)
- **Laporan** — Export Excel inventaris, Excel penjualan, PDF dashboard, invoice PDF per transaksi
- **Notifikasi Email** — Otomatis kirim email saat stok menipis (dijalankan via Queue)

### Teknologi yang Digunakan

**Backend:**
| Teknologi | Versi | Fungsi |
|---|---|---|
| PHP | 8.2 | Bahasa pemrograman backend |
| Laravel | 12 | Framework backend (MVC) |
| Laravel Sanctum | - | Autentikasi API berbasis token |
| MySQL | 8.0 | Database utama |
| Redis | 7 | Cache, Session, Queue |
| Laravel Queue | - | Proses background (kirim email) |
| Laravel Scheduler | - | Jadwal otomatis (cron job) |
| Maatwebsite Excel | - | Export file Excel (.xlsx) |
| DomPDF (barryvdh) | - | Generate file PDF |

**Frontend:**
| Teknologi | Versi | Fungsi |
|---|---|---|
| Vue 3 | - | Framework frontend (SPA) |
| TypeScript | - | Type-safe JavaScript |
| Vite | - | Build tool frontend |
| Pinia | - | State management (pengganti Vuex) |
| Vue Router | - | Navigasi halaman (SPA routing) |
| Axios | - | HTTP client untuk panggil API |
| Tailwind CSS | - | Styling komponen |
| Lucide Vue | - | Library icon |

**Infrastruktur:**
| Teknologi | Fungsi |
|---|---|
| Docker | Containerisasi semua service |
| Docker Compose | Orkestrasi multi-container |
| Nginx | Web server + reverse proxy |
| AWS EC2 | Server produksi |

---

## 2. Arsitektur Aplikasi

### Arsitektur Sistem

InvenSys menggunakan arsitektur **SPA + REST API** yang dipisah menjadi dua bagian:

```
┌─────────────────────────────────────────────────────────┐
│                    Browser (User)                        │
└───────────────────────┬─────────────────────────────────┘
                        │ HTTP request ke port 8888
                        ▼
┌─────────────────────────────────────────────────────────┐
│                   Nginx (Port 80/8888)                   │
│  - Serve file Vue (HTML/JS/CSS) untuk halaman           │
│  - Forward /api/* ke PHP-FPM (Laravel backend)          │
└──────────┬──────────────────────────────────────────────┘
           │ /api/* → proxy ke php:9000
           ▼
┌──────────────────────────────┐    ┌─────────────────────┐
│   PHP-FPM (Laravel)          │◄──►│  MySQL 8.0          │
│   Port 9000 (internal)       │    │  (Database)         │
│   - REST API handler         │    └─────────────────────┘
│   - Business logic           │    ┌─────────────────────┐
│   - RBAC/Permission check    │◄──►│  Redis 7            │
└──────────────────────────────┘    │  (Cache/Queue/Sess) │
                                    └─────────────────────┘
┌──────────────────────────────┐
│   Queue Worker (background)  │
│   - Kirim email notifikasi   │
│   - Proses job async         │
└──────────────────────────────┘
┌──────────────────────────────┐
│   Scheduler                  │
│   - Jalankan cron job Laravel│
└──────────────────────────────┘
```

### Alur Request dari User ke Database

```
1. User klik tombol di browser (Vue)
2. Vue memanggil Axios → HTTP request ke /api/produk (JSON)
3. Nginx menerima request → forward ke PHP-FPM
4. Laravel Router mencocokkan URL dengan route
5. Middleware auth:sanctum → cek token
6. Middleware permission:xxx → cek hak akses
7. Controller menerima request → jalankan logic
8. FormRequest → validasi input
9. Model / Service → query ke database MySQL
10. Response JSON dikirim balik ke Vue
11. Vue update tampilan
```

### Bagaimana Frontend dan Backend Berkomunikasi

Frontend (Vue) dan backend (Laravel) adalah **dua aplikasi terpisah** yang bicara lewat **REST API**:

- Frontend mengirim HTTP request dengan header `Authorization: Bearer TOKEN`
- Backend memvalidasi token → proses request → kirim JSON response
- Frontend menampilkan data dari JSON ke layar

---

## 3. Struktur Folder Project

```
Project-pi/                          ← Root project
├── .env                             ← Variabel environment (rahasia!)
├── docker-compose.yml               ← Compose untuk development lokal
├── docker-compose.ec2.yml           ← Compose untuk production EC2
├── Dockerfile                       ← Resep build Docker image
├── docker/
│   ├── nginx.conf                   ← Konfigurasi Nginx
│   └── entrypoint.sh                ← Script startup container PHP
├── scripts/
│   ├── update-ec2.sh                ← Script deploy otomatis ke EC2
│   └── rollback-ec2.sh              ← Script rollback (auto-generated)
├── inventory-app/                   ← Backend Laravel
└── inventory-ui/                    ← Frontend Vue 3
```

### Struktur Backend (`inventory-app/`)

```
inventory-app/
├── app/
│   ├── Enums/
│   │   ├── Permission.php           ← Daftar 33 permission sebagai PHP Enum
│   │   └── Role.php                 ← Definisi role + permission per role
│   ├── Events/
│   │   ├── OrderShipped.php         ← Event: order dikirim
│   │   ├── OrderCanceled.php        ← Event: order dibatalkan
│   │   └── StockWentLow.php         ← Event: stok menipis
│   ├── Listeners/
│   │   ├── DeductStockOnShip.php    ← Listener: kurangi stok saat shipped
│   │   ├── RestoreStockOnCancel.php ← Listener: kembalikan stok saat cancel
│   │   └── SendLowStockNotification.php ← Listener: kirim email stok rendah
│   ├── Http/
│   │   ├── Controllers/Api/         ← Controller untuk setiap resource
│   │   │   ├── AuthController.php
│   │   │   ├── ProductController.php
│   │   │   ├── CategoryController.php
│   │   │   ├── WarehouseController.php
│   │   │   ├── InventoryController.php
│   │   │   ├── CustomerController.php
│   │   │   ├── EmployeeController.php
│   │   │   ├── TransactionController.php
│   │   │   ├── UserController.php
│   │   │   ├── DashboardController.php
│   │   │   └── ReportController.php
│   │   ├── Middleware/
│   │   │   └── CheckPermission.php  ← Middleware cek permission
│   │   ├── Requests/                ← FormRequest (validasi + otorisasi)
│   │   │   ├── StoreProductRequest.php
│   │   │   ├── UpdateProductRequest.php
│   │   │   ├── StoreTransactionRequest.php
│   │   │   └── TransferInventoryRequest.php
│   │   └── Resources/               ← API Resource (format JSON response)
│   │       ├── ProductResource.php
│   │       ├── InventoryResource.php
│   │       ├── TransactionResource.php
│   │       └── ... (satu per model)
│   ├── Models/
│   │   ├── User.php
│   │   ├── Product.php
│   │   ├── Category.php
│   │   ├── Warehouse.php
│   │   ├── Inventory.php
│   │   ├── Customer.php
│   │   ├── Employee.php
│   │   ├── Transaction.php
│   │   └── TransactionItem.php
│   ├── Services/
│   │   ├── TransactionService.php   ← Logic bisnis transaksi
│   │   └── InventoryService.php     ← Logic bisnis stok
│   ├── Exports/
│   │   ├── InventoryExport.php      ← Excel inventaris
│   │   └── SalesExport.php          ← Excel penjualan
│   ├── Mail/
│   │   └── LowStockAlert.php        ← Template email stok rendah
│   └── Traits/
│       └── HasPermissions.php       ← Trait RBAC untuk model User
├── routes/
│   └── api.php                      ← Semua route API
├── database/
│   ├── migrations/                  ← Definisi struktur tabel database
│   └── seeders/                     ← Data awal (dummy data)
├── resources/
│   └── views/reports/               ← Template PDF (Blade)
│       ├── invoice-pdf.blade.php
│       ├── sales-pdf.blade.php
│       └── dashboard-pdf.blade.php
└── config/                          ← Konfigurasi Laravel
```

### Struktur Frontend (`inventory-ui/`)

```
inventory-ui/
├── src/
│   ├── main.ts                      ← Entry point aplikasi Vue
│   ├── App.vue                      ← Komponen root
│   ├── router/
│   │   └── index.ts                 ← Definisi semua route halaman
│   ├── stores/
│   │   └── auth.ts                  ← State global: user, token, permissions
│   ├── api/                         ← Fungsi HTTP request ke backend
│   │   ├── auth.ts
│   │   ├── products.ts
│   │   ├── categories.ts
│   │   ├── warehouses.ts
│   │   ├── inventory.ts
│   │   ├── customers.ts
│   │   ├── employees.ts
│   │   ├── transactions.ts
│   │   └── reports.ts
│   ├── views/                       ← Halaman-halaman aplikasi
│   │   ├── LoginView.vue
│   │   ├── dashboard/DashboardView.vue
│   │   ├── products/ProductsView.vue
│   │   ├── categories/CategoriesView.vue
│   │   ├── warehouses/WarehousesView.vue
│   │   ├── inventory/InventoryView.vue
│   │   ├── customers/CustomersView.vue
│   │   ├── employees/EmployeesView.vue
│   │   ├── transactions/TransactionsView.vue
│   │   └── users/UsersView.vue
│   ├── components/
│   │   └── layout/AppLayout.vue     ← Layout utama (sidebar + topbar)
│   └── types/
│       └── index.ts                 ← TypeScript type definitions
└── index.html                       ← HTML shell (entry point)
```

---

## 4. Penjelasan File Penting

### Backend

#### `app/Enums/Permission.php`
**Fungsi:** Mendefinisikan semua hak akses yang ada dalam sistem sebagai PHP Enum.

Enum adalah tipe data yang nilainya sudah terbatas dan tetap. Daripada menulis string hardcoded seperti `'product.create'` di mana-mana (rawan typo), kita tulis `Permission::PRODUCT_CREATE`. Jika typo, PHP langsung error waktu compile.

Ada 33 permission, dikelompokkan per resource: dashboard, product, category, warehouse, inventory, customer, employee, transaction, user, report.

**Dipanggil oleh:** `Role.php`, `HasPermissions.php`, `CheckPermission.php`, setiap Controller dan FormRequest.

---

#### `app/Enums/Role.php`
**Fungsi:** Mendefinisikan 4 role (`viewer`, `staff`, `manager`, `admin`) dan menentukan permission apa saja yang dimiliki masing-masing role.

Cara kerjanya: setiap role punya method `permissions()` yang mengembalikan array Permission. Admin mendapat semua permission (`Permission::cases()`). Manager mendapat permission staff + tambahan. Staff mendapat permission viewer + tambahan.

**Dipanggil oleh:** `HasPermissions.php` (di method `permissions()` pada trait).

---

#### `app/Traits/HasPermissions.php`
**Fungsi:** Sebuah "mixin" yang ditambahkan ke model `User` untuk memberi kemampuan cek permission.

Trait di PHP adalah cara untuk berbagi kode antara beberapa class tanpa inheritance (pewarisan). Dengan `use HasPermissions` di `User.php`, model User langsung punya method `hasPermission()`, `hasAnyPermission()`, `isAdmin()`, dll.

Method penting:
- `permissions()` → ambil daftar permission user berdasarkan role-nya (dengan cache agar tidak dihitung ulang)
- `hasPermission(Permission $p)` → cek apakah user punya permission tertentu
- `isAdmin()`, `isManager()`, `isStaff()`, `isViewer()` → cek role

---

#### `app/Models/User.php`
**Fungsi:** Representasi tabel `users` di database. Di sini juga ada override method `can()` dari Laravel.

Yang penting: method `can($permission)` di-override sehingga jika diberi `Permission` enum, akan pakai RBAC kita. Jika diberi string biasa (untuk Sanctum, dll), dikembalikan ke Laravel bawaan.

---

#### `app/Http/Middleware/CheckPermission.php`
**Fungsi:** Middleware yang berjalan sebelum controller, mengecek apakah user punya permission yang dibutuhkan untuk mengakses route tersebut.

Cara pakai di route: `->middleware('permission:product.create')`.

Middleware ini menerima permission yang dibutuhkan, lalu memanggil `$user->can($permission)` untuk mengeceknya. Jika tidak punya, langsung return HTTP 403 Forbidden.

---

#### `app/Services/TransactionService.php`
**Fungsi:** Berisi semua business logic pembuatan dan perubahan status transaksi.

Ini adalah contoh **Service Layer Pattern** — logika bisnis kompleks tidak diletakkan di controller, melainkan di service class tersendiri. Kenapa? Agar controller tetap tipis dan logic mudah ditest.

Method penting:
- `createOrder()` → validasi kredit customer → buat transaksi + item → update credit_used customer
- `updateStatus()` → validasi transisi status (finite state machine) → update → fire event

---

#### `app/Services/InventoryService.php`
**Fungsi:** Business logic untuk pengurangan stok (saat shipped), pengembalian stok (saat canceled), dan transfer stok antar gudang.

Menggunakan `DB::transaction()` agar operasi database bersifat atomic — jika ada yang gagal, semua dibatalkan (rollback). Juga memakai `lockForUpdate()` agar tidak ada race condition saat banyak request bersamaan.

---

#### `routes/api.php`
**Fungsi:** Mendaftarkan semua endpoint API. Setiap route dipasangi middleware untuk auth dan permission.

Struktur middleware berlapis:
1. `auth:sanctum` → wajib login (semua route protected)
2. `permission:xxx.yyy` → wajib punya permission spesifik

---

### Frontend

#### `src/stores/auth.ts`
**Fungsi:** Menyimpan state global login — data user, token, dan daftar permission.

Menggunakan Pinia (state management). Data disimpan di `localStorage` agar tidak hilang saat halaman direfresh.

Berisi computed properties seperti `canCreateProduct`, `canEditInventory`, dll. yang dipakai di setiap halaman untuk menyembunyikan/menampilkan tombol sesuai hak akses.

---

#### `src/router/index.ts`
**Fungsi:** Mendefinisikan semua halaman (routes) dan navigation guard.

Navigation guard `beforeEach` berjalan setiap kali pindah halaman, mengecek:
- Jika halaman butuh login (`requiresAuth`) tapi belum login → redirect ke `/login`
- Jika halaman untuk guest tapi sudah login → redirect ke `/`
- Jika halaman admin-only tapi bukan admin → redirect ke `/`

---

## 5. Penjelasan Kode Detail

### Sistem RBAC (Role-Based Access Control)

Sistem RBAC terdiri dari 3 lapis keamanan:

```
Request masuk
    │
    ▼
[Lapisan 1] Middleware CheckPermission
    │  Cek: apakah user punya permission?
    │  Jika tidak → 403 Forbidden
    ▼
[Lapisan 2] FormRequest::authorize()
    │  Cek ulang permission di level validasi input
    │  Jika tidak → 403 Forbidden
    ▼
[Lapisan 3] Controller logic
    │  Defense in depth: cek ulang di skenario tertentu
    ▼
    Database
```

**Kenapa 3 lapis?** Karena keamanan berlapis-lapis lebih aman (defense in depth). Jika satu lapisan terlewat karena bug konfigurasi route, masih ada lapisan berikutnya.

---

### Kode: `CheckPermission.php` — Bagaimana Middleware Bekerja

```php
public function handle(Request $request, Closure $next, string ...$permissions): Response
{
    $user = $request->user();             // Ambil user yang sedang login

    if (! $user) {                        // Jika belum login
        return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    foreach ($permissions as $permissionValue) {
        $permission = Permission::tryFrom($permissionValue); // String → Enum

        if ($permission && $user->can($permission)) {   // Cek permission
            return $next($request);                     // Lanjut ke controller
        }
    }

    return response()->json([            // Tidak punya permission → 403
        'message' => 'Anda tidak memiliki izin...',
    ], 403);
}
```

**Step by step:**
1. Ambil user dari token Sanctum
2. Jika tidak ada user → 401 (belum login)
3. Loop setiap permission yang dibutuhkan route ini
4. Konversi string `'product.create'` ke enum `Permission::PRODUCT_CREATE` menggunakan `tryFrom()`
5. Panggil `$user->can($permission)` — ini memanggil override di `User.php` → `HasPermissions::hasPermission()` → cek apakah permission ada di daftar role user
6. Jika punya salah satu permission → lanjut ke controller
7. Jika tidak ada satu pun → 403

---

### Kode: `TransactionService::createOrder()` — Membuat Order Baru

```php
public function createOrder(array $data): Transaction
{
    return DB::transaction(function () use ($data): Transaction {
        // Step 1: Pisahkan items dari data utama
        $items = $data['items'];
        unset($data['items']);

        // Step 2: Hitung total dari semua item (qty × harga)
        $totalAmount = $this->calculateTotalAmount($items);

        // Step 3: Cek kredit customer (jika ada customer)
        if (isset($data['customer_id'])) {
            $this->validateCustomerCredit($data['customer_id'], $totalAmount);
        }

        // Step 4: Buat record transaksi
        $transaction = Transaction::create([
            ...$data,
            'status'       => 'pending',
            'total_amount' => $totalAmount,
        ]);

        // Step 5: Buat item-item transaksi
        $this->createTransactionItems($transaction, $items);

        // Step 6: Update credit_used customer (naik sebesar total order)
        if ($transaction->customer_id) {
            Customer::where('id', $transaction->customer_id)
                ->increment('credit_used', $totalAmount);
        }

        return $transaction->fresh();
    });
}
```

`DB::transaction()` memastikan semua operasi database di atas dilakukan sebagai satu unit. Jika step 5 gagal (misalnya produk tidak ada), maka step 4 (buat transaksi) juga dibatalkan otomatis. Tidak ada data yang setengah tersimpan.

---

### Kode: Status Transition (Finite State Machine)

Transaksi tidak bisa sembarang pindah status. Aturannya ketat:

```php
private const ALLOWED_TRANSITIONS = [
    'pending'    => ['processing', 'canceled'],
    'processing' => ['shipped',    'canceled'],
    'shipped'    => ['delivered',  'canceled'],
    'delivered'  => [],          // FINAL — tidak bisa berubah
    'canceled'   => [],          // FINAL — tidak bisa berubah
];
```

Artinya:
- `pending` → boleh ke `processing` atau `canceled`
- `processing` → boleh ke `shipped` atau `canceled`
- `shipped` → boleh ke `delivered` atau `canceled`
- `delivered` atau `canceled` → tidak bisa ke mana-mana (final state)

Jika mencoba transisi yang tidak diizinkan (misal: `pending` langsung ke `delivered`), sistem akan throw Exception.

---

### Kode: `auth.ts` — Computed Permission di Frontend

```typescript
// Contoh untuk produk
const canCreateProduct = computed(() => can('product.create'))
const canEditProduct   = computed(() => can('product.update'))
const canDeleteProduct = computed(() => can('product.delete'))

// Fungsi can() mengecek array permissions dari backend
function can(permission: string): boolean {
  return permissions.value.includes(permission)
}
```

Saat login, backend mengirim array permission seperti:
```json
["product.view", "product.create", "product.update", "category.view", ...]
```

Array ini disimpan di `localStorage` dan di-load ke `permissions.value`. Setiap kali komponen Vue menggunakan `auth.canCreateProduct`, Vue otomatis reaktif — jika permission berubah, UI langsung update.

Di template Vue digunakan seperti ini:
```html
<button v-if="auth.canCreateProduct" @click="openCreate">
  Tambah Produk
</button>
```

Jika user tidak punya permission `product.create`, tombol tidak akan muncul sama sekali di HTML.

---

### Kode: `Product.php` — Model dengan Computed Attribute dan Scope

```php
// Computed attribute: dihitung otomatis dari field lain, tidak disimpan di DB
public function getProfitMarginAttribute(): float
{
    return round($this->list_price - $this->standard_cost, 2);
}

public function getProfitPercentageAttribute(): float
{
    if ($this->standard_cost === 0.0) return 0.0;
    return round(($this->profit_margin / $this->standard_cost) * 100, 2);
}

// Scope: query builder yang bisa dipanggil dengan syntax bersih
public function scopeSearch(Builder $query, string $term): Builder
{
    return $query->where(function ($q) use ($term) {
        $q->where('name', 'LIKE', "%{$term}%")
          ->orWhere('sku', 'LIKE', "%{$term}%")
          ->orWhere('description', 'LIKE', "%{$term}%");
    });
}
```

`getProfitMarginAttribute()` adalah **accessor** Laravel — otomatis bisa diakses sebagai `$product->profit_margin` meski tidak ada kolom tersebut di database.

`scopeSearch()` adalah **local scope** — bisa dipanggil seperti `Product::search('laptop')` di controller.

---

### Kode: `InventoryService::deductStock()` — Pengurangan Stok

```php
public function deductStock(Transaction $transaction): void
{
    DB::transaction(function () use ($transaction): void {
        foreach ($transaction->items as $item) {
            // Step 1: Cari inventory dengan lock (hindari race condition)
            $inventory = Inventory::query()
                ->where('product_id', $item->product_id)
                ->where('warehouse_id', $transaction->warehouse_id)
                ->lockForUpdate()   // ← Kunci row ini agar tidak ada request lain yang edit
                ->first();

            // Step 2: Cek stok cukup
            if ($inventory->qty_available < $item->quantity) {
                throw new Exception("Stok tidak cukup...");
            }

            // Step 3: Kurangi stok
            $previousQty = $inventory->qty_on_hand;
            $inventory->decrement('qty_on_hand', $item->quantity);
            $inventory->refresh();

            // Step 4: Cek apakah baru saja turun ke bawah minimum
            $wasOk = ($previousQty - $inventory->qty_reserved) > $inventory->min_stock;
            $isLow = $inventory->qty_available <= $inventory->min_stock;

            if ($wasOk && $isLow) {
                event(new StockWentLow($inventory, ...)); // Kirim notifikasi email
            }
        }
    });
}
```

`lockForUpdate()` penting untuk mencegah **race condition**: tanpa lock, jika dua order diterima bersamaan, keduanya bisa membaca stok yang sama dan keduanya berhasil meskipun stok sebenarnya tidak cukup.

---

## 6. Alur Fitur Aplikasi

### Alur Login

```
Step 1: User buka /login → LoginView.vue ditampilkan
Step 2: User isi email + password → klik "Login"
Step 3: Vue memanggil authApi.login(email, password)
Step 4: Axios POST /api/auth/login dengan body JSON
Step 5: Laravel terima request → AuthController::login()
Step 6: Validasi format email + password (required)
Step 7: Cari user di DB: User::where('email', ...)->first()
Step 8: Cek password: Hash::check(input, db_hash)
Step 9: Cek is_active: apakah akun aktif?
Step 10: Buat token Sanctum: $user->createToken('auth_token')
Step 11: Format permissions: array_map(fn($p) => $p->value, $user->permissions())
Step 12: Return JSON {user, token, permissions}
Step 13: Vue simpan token + user + permissions ke localStorage
Step 14: Vue redirect ke halaman dashboard (/)
```

### Alur Tambah Produk

```
Step 1: User klik "Tambah Produk" (hanya muncul jika auth.canCreateProduct)
Step 2: Modal form terbuka
Step 3: User isi nama, SKU, kategori, harga modal, harga jual
Step 4: Klik "Simpan" → productsApi.create(form)
Step 5: Axios POST /api/products dengan Bearer token
Step 6: Middleware auth:sanctum → validasi token
Step 7: Middleware permission:product.create → cek permission
Step 8: StoreProductRequest::authorize() → cek permission (lapis 2)
Step 9: StoreProductRequest::rules() → validasi input
   - SKU harus unik
   - list_price >= standard_cost
   - category_id harus ada di DB
Step 10: ProductController::store() → Product::create($validated)
Step 11: Return ProductResource (JSON formatted)
Step 12: Vue tutup modal, refresh daftar produk
```

### Alur Transaksi (Buat Order)

```
Step 1: User klik "Buat Order"
Step 2: Isi customer, gudang, item produk + qty + harga
Step 3: Axios POST /api/transactions
Step 4: Middleware cek auth + permission:transaction.create
Step 5: StoreTransactionRequest validasi input
Step 6: TransactionController::store() → TransactionService::createOrder()
Step 7: Service hitung total
Step 8: Cek kredit customer (credit_available >= total)
Step 9: Buat record Transaction (status: pending)
Step 10: Buat record TransactionItem per produk
Step 11: Update credit_used customer += total
Step 12: Return response 201 Created
```

### Alur Update Status → Shipped (Stok Berkurang)

```
Step 1: Manager klik "Update Status" → pilih "Shipped"
Step 2: PATCH /api/transactions/{id}/status {status: "shipped"}
Step 3: TransactionService::updateStatus() validasi transisi
Step 4: Update status → "shipped", simpan shipped_date
Step 5: event(new OrderShipped($transaction)) di-fire
Step 6: Listener DeductStockOnShip::handle() dipanggil
Step 7: InventoryService::deductStock() kurangi stok
Step 8: Jika stok turun di bawah minimum → event(new StockWentLow)
Step 9: Listener SendLowStockNotification dipush ke Queue
Step 10: Queue Worker (container terpisah) proses job
Step 11: Kirim email ke admin: LowStockAlert
```

---

## 7. Sistem Role dan Hak Akses (RBAC)

### Tabel Permission per Role

| Fitur | Viewer | Staff | Manager | Admin |
|---|:---:|:---:|:---:|:---:|
| **Dashboard** | ✅ | ✅ | ✅ | ✅ |
| Lihat produk | ✅ | ✅ | ✅ | ✅ |
| Tambah/Edit produk | ❌ | ❌ | ✅ | ✅ |
| Hapus produk | ❌ | ❌ | ❌ | ✅ |
| Lihat kategori | ✅ | ✅ | ✅ | ✅ |
| Tambah/Edit kategori | ❌ | ❌ | ✅ | ✅ |
| Hapus kategori | ❌ | ❌ | ❌ | ✅ |
| Lihat gudang | ✅ | ✅ | ✅ | ✅ |
| Tambah/Edit gudang | ❌ | ❌ | ✅ | ✅ |
| Hapus gudang | ❌ | ❌ | ❌ | ✅ |
| Lihat inventaris | ✅ | ✅ | ✅ | ✅ |
| Update/Transfer stok | ❌ | ❌ | ✅ | ✅ |
| Lihat customer | ✅ | ✅ | ✅ | ✅ |
| Tambah/Edit customer | ❌ | ✅ | ✅ | ✅ |
| Hapus customer | ❌ | ❌ | ❌ | ✅ |
| Kelola kredit customer | ❌ | ❌ | ✅ | ✅ |
| Lihat karyawan | ✅ | ✅ | ✅ | ✅ |
| Tambah/Edit/Hapus karyawan | ❌ | ❌ | ✅ | ✅ |
| Lihat transaksi | ✅ | ✅ | ✅ | ✅ |
| Buat transaksi | ❌ | ✅ | ✅ | ✅ |
| Edit notes transaksi | ❌ | ✅* | ✅ | ✅ |
| Ubah status transaksi | ❌ | ❌ | ✅** | ✅ |
| Hapus transaksi | ❌ | ❌ | ❌ | ✅ |
| Lihat laporan | ❌ | ❌ | ✅ | ✅ |
| Kelola user | ❌ | ❌ | ❌ | ✅ |

\* Staff hanya bisa edit notes transaksi miliknya sendiri yang masih `pending`
\** Manager tidak bisa mengubah status transaksi yang sudah `delivered`

### Bagaimana RBAC Bekerja (Teknis)

```
1. Login → backend kirim array permissions ke frontend
   contoh: ["product.view", "product.create", "transaction.view"]

2. Frontend simpan di localStorage + Pinia store

3. Di UI → v-if="auth.canCreateProduct" → cek computed property
   yang memanggil can('product.create') → cek array permissions

4. Di backend → setiap request difilter oleh middleware:
   middleware('permission:product.create')
   → CheckPermission::handle()
   → $user->can(Permission::PRODUCT_CREATE)
   → HasPermissions::hasPermission()
   → in_array(permission, $this->permissions())
   → Role::MANAGER->permissions() → array Permission[]
```

---

## 8. Database

### Daftar Tabel dan Fungsinya

| Tabel | Fungsi |
|---|---|
| `users` | Akun login pengguna sistem |
| `employees` | Data detail karyawan (terhubung ke user) |
| `categories` | Kategori produk (misal: Elektronik, Furniture) |
| `warehouses` | Data lokasi gudang |
| `products` | Data produk dengan harga dan SKU |
| `inventory` | Stok produk per gudang (many-to-many products × warehouses) |
| `customers` | Data pelanggan dengan sistem kredit |
| `transactions` | Header order penjualan |
| `transaction_items` | Baris item dari setiap order |
| `personal_access_tokens` | Token Sanctum untuk autentikasi API |
| `cache` | Penyimpanan cache Laravel |
| `jobs` | Antrian job (Queue) |
| `sessions` | Session user |

### Relasi Antar Tabel

```
users ──────────────────── employees (1 user bisa punya 1 employee)
       1:1

categories ─────────────── products (1 kategori punya banyak produk)
           1:N

products ─── inventory ─── warehouses
         N:N           N:1
    (produk bisa ada di banyak gudang, via tabel inventory)

customers ──────────────── transactions (1 customer bisa punya banyak transaksi)
          1:N

employees ──────────────── transactions (1 employee bisa handle banyak transaksi)
          1:N

warehouses ─────────────── transactions (1 gudang sumber untuk transaksi)
           1:N

transactions ───────────── transaction_items (1 transaksi punya banyak item)
             1:N

products ───────────────── transaction_items (1 produk bisa muncul di banyak item)
         1:N
```

### Struktur Tabel Kunci

**Tabel `inventory`**
```sql
id               INT AUTO_INCREMENT PRIMARY KEY
product_id       FK → products.id (CASCADE DELETE)
warehouse_id     FK → warehouses.id (CASCADE DELETE)
qty_on_hand      INT DEFAULT 0        -- Stok fisik yang ada
qty_reserved     INT DEFAULT 0        -- Stok yang sudah dipesan tapi belum dikirim
min_stock        INT DEFAULT 10       -- Batas minimum (trigger alert)
max_stock        INT DEFAULT 1000     -- Batas maksimum
last_restocked_at TIMESTAMP NULL
UNIQUE(product_id, warehouse_id)      -- 1 produk hanya 1 baris per gudang
```

`qty_available` = `qty_on_hand` - `qty_reserved` (dihitung di PHP, tidak disimpan di DB)

**Tabel `transactions`**
```sql
id            INT AUTO_INCREMENT PRIMARY KEY
order_number  VARCHAR(20) UNIQUE    -- Format: ORD-XXXXXXXXX (auto-generated)
customer_id   FK → customers.id
employee_id   FK → employees.id (nullable)
warehouse_id  FK → warehouses.id
status        ENUM('pending','processing','shipped','delivered','canceled')
order_date    DATE
shipped_date  DATE nullable
total_amount  DECIMAL(14,2)
notes         TEXT nullable
```

**Tabel `customers`**
```sql
id            INT PRIMARY KEY
name          VARCHAR(100)
email         VARCHAR(100) nullable unique
phone         VARCHAR(20) nullable
address       TEXT nullable
credit_limit  DECIMAL(10,2) DEFAULT 0.00   -- Batas kredit maksimum
credit_used   DECIMAL(10,2) DEFAULT 0.00   -- Kredit yang sudah terpakai
status        ENUM('active','inactive','blacklisted')
```

---

## 9. Routing dan API

### Endpoint Publik (Tanpa Login)

| Method | Endpoint | Fungsi |
|---|---|---|
| GET | `/api/health` | Health check (cek DB + storage) |
| POST | `/api/auth/login` | Login, dapat token |

### Endpoint Protected (Butuh Login)

**Auth:**
| Method | Endpoint | Permission | Fungsi |
|---|---|---|---|
| POST | `/api/auth/logout` | Login | Logout, hapus token |
| GET | `/api/auth/me` | Login | Profil user saat ini |
| GET | `/api/auth/permissions` | Login | Daftar permission user |

**Dashboard:**
| Method | Endpoint | Permission | Fungsi |
|---|---|---|---|
| GET | `/api/dashboard/summary` | dashboard.view | Statistik utama |
| GET | `/api/dashboard/top-products` | dashboard.view | Top 10 produk terlaris |
| GET | `/api/dashboard/low-stock` | dashboard.view | Alert stok rendah |

**Products:**
| Method | Endpoint | Permission | Fungsi |
|---|---|---|---|
| GET | `/api/products` | product.view | Daftar produk (paginasi) |
| GET | `/api/products/{id}` | product.view | Detail produk |
| POST | `/api/products` | product.create | Tambah produk baru |
| PUT | `/api/products/{id}` | product.update | Edit produk |
| DELETE | `/api/products/{id}` | product.delete | Hapus produk (soft delete) |

**Inventory:**
| Method | Endpoint | Permission | Fungsi |
|---|---|---|---|
| GET | `/api/inventory` | inventory.view | Daftar stok |
| GET | `/api/inventory/{id}` | inventory.view | Detail stok |
| PUT | `/api/inventory/{id}` | inventory.update | Manual update stok |
| POST | `/api/inventory/transfer` | inventory.transfer | Transfer stok antar gudang |
| GET | `/api/inventory/alerts/low-stock` | inventory.view | Daftar stok rendah |

**Transactions:**
| Method | Endpoint | Permission | Fungsi |
|---|---|---|---|
| GET | `/api/transactions` | transaction.view | Daftar transaksi |
| GET | `/api/transactions/{id}` | transaction.view | Detail transaksi |
| POST | `/api/transactions` | transaction.create | Buat order baru |
| PUT | `/api/transactions/{id}` | transaction.update | Edit notes |
| PATCH | `/api/transactions/{id}/status` | transaction.update_status | Ubah status |
| DELETE | `/api/transactions/{id}` | transaction.delete | Hapus (hanya pending) |

**Reports:**
| Method | Endpoint | Permission | Fungsi |
|---|---|---|---|
| GET | `/api/reports/inventory/excel` | report.view | Download Excel inventaris |
| GET | `/api/reports/sales/excel` | report.view | Download Excel penjualan |
| GET | `/api/reports/sales/pdf` | report.view | Download PDF penjualan |
| GET | `/api/reports/dashboard/pdf` | report.view | Download PDF dashboard |
| GET | `/api/reports/transactions/{id}/invoice` | report.view | Download invoice PDF |

---

## 10. Alur Proses Data

### Transfer Stok Antar Gudang

```
Step 1: Manager klik "Transfer Stok" di InventoryView.vue
Step 2: Isi form: produk, gudang asal, gudang tujuan, jumlah
Step 3: inventoryApi.transfer(form) → POST /api/inventory/transfer
Step 4: Middleware: auth:sanctum + permission:inventory.transfer
Step 5: TransferInventoryRequest::authorize() → cek permission (lapis 2)
Step 6: TransferInventoryRequest::rules() → validasi input
Step 7: InventoryController::transfer() → InventoryService::transferStock()
Step 8: Validasi: gudang asal ≠ gudang tujuan, qty > 0
Step 9: DB::transaction() dimulai
Step 10: Cari inventory sumber + lockForUpdate()
Step 11: Cek stok cukup (qty_available >= qty)
Step 12: Kurangi stok sumber: decrement('qty_on_hand', qty)
Step 13: Cek apakah stok sumber turun ke bawah minimum
Step 14: Jika ya → event(StockWentLow) → Queue → email notifikasi
Step 15: Cari inventory tujuan → jika ada, increment; jika tidak ada, create baru
Step 16: DB::transaction() commit
Step 17: Return JSON {message: "Transfer stok berhasil."}
Step 18: Vue refresh daftar inventaris
```

### Export Excel Laporan

```
Step 1: Manager klik "Excel" di InventoryView.vue
Step 2: reportsApi.inventoryExcel() → GET /api/reports/inventory/excel
Step 3: Middleware: auth:sanctum + permission:report.view
Step 4: ReportController::inventoryExcel()
Step 5: Excel::download(new InventoryExport($warehouseId), 'nama-file.xlsx')
Step 6: InventoryExport::query() → ambil data dari DB
Step 7: InventoryExport::headings() → baris header kolom
Step 8: InventoryExport::map() → format setiap baris
Step 9: Library Maatwebsite menggenerate file .xlsx
Step 10: Return response dengan header Content-Type: application/vnd.openxml...
Step 11: Browser otomatis download file
Step 12: Vue: downloadBlob(response.data, 'laporan-inventaris.xlsx')
```

---

## 11. Debugging Guide

### Lokasi Log

| Log | Lokasi | Isi |
|---|---|---|
| Laravel log | `inventory-app/storage/logs/laravel.log` | Error PHP, exception, custom log |
| Nginx log | `docker logs invensys_nginx` | HTTP access + error log |
| PHP-FPM log | `docker logs invensys_php` | PHP error + entrypoint log |
| Queue log | `docker logs invensys_queue` | Job queue error |
| Update log | `storage/logs/update-ec2.log` | Log script deploy |

### Cara Membaca Laravel Log

```
[2026-03-11 10:30:00] production.ERROR: Tidak ada permission...
  {
    "exception": "Symfony\\Component\\HttpKernel\\Exception\\AccessDeniedHttpException",
    "file": ".../CheckPermission.php",
    "line": 42
  }
```

Format: `[timestamp] environment.LEVEL: pesan {context}`

### Perintah Debug Cepat di EC2

```bash
# Cek log Laravel terbaru
docker exec invensys_php tail -50 storage/logs/laravel.log

# Cek log error spesifik
docker exec invensys_php cat storage/logs/laravel.log | grep "ERROR"

# Cek status container
docker compose -f docker-compose.ec2.yml ps

# Test API health
curl -s http://localhost:8888/api/health | python3 -m json.tool

# Test login
curl -s -X POST http://localhost:8888/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@inventory.test","password":"password"}' | python3 -m json.tool

# Restart hanya PHP (setelah fix kode)
docker compose -f docker-compose.ec2.yml restart php

# Clear cache Laravel
docker exec invensys_php php artisan optimize:clear
```

### Error Umum dan Solusinya

| Error | Penyebab | Solusi |
|---|---|---|
| `500 Server Error` saat login | Method conflict di PHP trait | Cek `HasPermissions.php`, hapus method yang bentrok dengan Laravel |
| `403 Forbidden` | User tidak punya permission | Cek role user di DB, pastikan permission benar |
| `401 Unauthenticated` | Token expired atau tidak ada | Login ulang, cek header Authorization |
| `422 Unprocessable` | Validasi input gagal | Cek response `errors` untuk detail field yang salah |
| Tombol tidak muncul di UI | Salah nama computed auth store | Pastikan pakai `auth.canCreateProduct` bukan `auth.canCreate` |
| Email tidak terkirim | SMTP limit atau konfigurasi salah | Cek `MAIL_USERNAME` dan `MAIL_PASSWORD` di `.env`, cek queue worker running |
| Container exit terus | Error di entrypoint atau konfigurasi | `docker logs invensys_php --tail=100` untuk lihat error |
| MySQL tidak mau connect | DB belum ready atau password salah | Cek `DB_PASSWORD` di `.env`, cek healthcheck container db |

---

## 12. Cara Menjalankan Project

### Prasyarat
- Docker Desktop terinstall
- Git terinstall

### Langkah-langkah

**Step 1: Clone repository**
```bash
git clone https://github.com/Lananuranf-RockNRoll/Manajemen-Inventaris-Berbasis-Web.git
cd Manajemen-Inventaris-Berbasis-Web
```

**Step 2: Setup environment**
```bash
# Copy .env sudah ada, tapi pastikan APP_KEY diisi
# Generate APP_KEY jika belum ada:
docker run --rm php:8.2-cli php -r "echo 'base64:'.base64_encode(random_bytes(32));"
# Paste hasilnya ke .env → APP_KEY=base64:xxxxx
```

**Step 3: Build dan jalankan Docker**
```bash
# Development (lokal):
docker compose up -d --build

# Production EC2:
docker compose -f docker-compose.ec2.yml --env-file .env up -d --build
```

**Step 4: Jalankan migration + seeder**
```bash
docker exec invensys_php php artisan migrate --seed
```

**Step 5: Akses aplikasi**
- Buka browser: `http://localhost:8888`
- Login dengan: `admin@inventory.test` / `password`

### Container yang Berjalan

| Container | Fungsi | Port |
|---|---|---|
| `invensys_nginx` | Web server, serve frontend, proxy API | 8888 (host) → 80 (internal) |
| `invensys_php` | Laravel backend PHP-FPM | 9000 (internal only) |
| `invensys_db` | MySQL database | Internal only |
| `invensys_redis` | Redis cache + queue | Internal only |
| `invensys_queue` | Queue worker (email notifikasi) | - |
| `invensys_scheduler` | Cron job Laravel | - |

---

## 13. Deployment di EC2

### Proses Deploy (Update Kode Baru)

```bash
# Di EC2, dari folder repo:
cd ~/Manajemen-Inventaris-Berbasis-Web
git pull origin main
bash scripts/update-ec2.sh
```

### Apa yang Dilakukan `update-ec2.sh`

Script ini melakukan 11 langkah otomatis:
1. Validasi Docker, Git, dan `.env` tersedia
2. Simpan commit saat ini untuk rollback
3. `git pull origin main` — tarik kode terbaru
4. Build Docker image baru (backend + nginx)
5. Restart container PHP, Queue, Scheduler (DB + Redis tidak disentuh)
6. Tunggu MySQL ready
7. `php artisan migrate --force` — jalankan migration baru
8. Rebuild cache (config, route, view, event)
9. Health check ke `/api/health`
10. Generate script rollback otomatis
11. Cleanup image lama (>7 hari)

### Rollback

Jika deploy bermasalah:
```bash
bash scripts/rollback-ec2.sh
```

Script rollback di-generate otomatis setiap deploy berhasil.

### Akses Aplikasi

- URL: `http://[EC2-IP]:8888`
- Port 8888 dipilih agar tidak konflik dengan project lain di server yang sama (ecommerce di 8080, school di 3001)

---

## 14. Analisis Clean Code

### Yang Sudah Bagus ✅

1. **Separation of Concerns** — Logic bisnis di Service layer, bukan di controller. Controller tipis, mudah dibaca.
2. **RBAC yang Berlapis** — 3 lapis keamanan (middleware + FormRequest + controller) mencegah bypass.
3. **PHP Enum untuk Permission** — Menghindari typo string, memberikan type safety.
4. **Computed Attributes di Model** — `profit_margin`, `profit_percentage`, `qty_available`, `is_low_stock` dihitung di PHP, tidak perlu query tambahan.
5. **Finite State Machine untuk Status Transaksi** — Transisi status terkontrol, tidak bisa skip step.
6. **Event-Driven untuk Side Effects** — Pengurangan stok dan notifikasi email di-handle oleh Event/Listener, bukan di controller. Controller tidak perlu tahu detail implementasi email.
7. **DB::transaction() untuk Operasi Atomik** — Tidak ada data setengah tersimpan.
8. **lockForUpdate() di Inventory** — Mencegah race condition.
9. **Soft Deletes** — Data tidak benar-benar dihapus, bisa di-recover.
10. **Docker Multi-Stage Build** — Image production lebih kecil, build lebih aman.

### Potensi Perbaikan ⚠️

1. **Variabel environment sensitif di `.env` yang di-commit** — `MAIL_PASSWORD` dan `DB_PASSWORD` tidak boleh di-commit ke GitHub. Gunakan GitHub Secrets atau environment injection di EC2.

2. **Environment variables duplikat di docker-compose** — Variabel DB, Redis, Mail ditulis ulang di setiap service (php, queue, scheduler). Lebih baik gunakan satu `env_file` reference atau YAML anchors (`&default-env`).

3. **Queue Worker tidak ada supervisor** — Jika queue worker crash, tidak otomatis restart dengan retry backoff. Pertimbangkan Supervisor process manager.

4. **Tidak ada API versioning** — Semua endpoint di `/api/...` tanpa versi. Jika ada breaking change di masa depan, akan sulit. Lebih baik `/api/v1/...`.

5. **TransactionController::update() belum pakai FormRequest** — Method `update()` masih validasi manual inline, tidak konsisten dengan method lain yang pakai FormRequest.

6. **Beberapa controller menggunakan inline permission check** — Campuran antara middleware dan manual `$request->user()->can()` di dalam controller. Pilih satu pola yang konsisten.

7. **Tidak ada rate limiting di endpoint login** — Endpoint `POST /api/auth/login` bisa diserang brute force. Tambahkan `throttle:5,1` middleware.

---

## 15. Diagram Alur Aplikasi

### Login Flow
```
User → [Isi email+password] → LoginView.vue
  → Axios POST /api/auth/login
  → AuthController::login()
  → Cek email di DB → Cek password hash → Cek is_active
  → Buat Sanctum token
  → Return {user, token, permissions}
  → Simpan ke localStorage + Pinia store
  → Redirect ke Dashboard
```

### Request API (Semua Request Setelah Login)
```
Vue Component
  → Axios + header "Authorization: Bearer TOKEN"
  → Nginx (proxy)
  → Laravel Router
  → Middleware auth:sanctum (cek token)
  → Middleware permission:xxx (cek hak akses)
  → FormRequest (validasi + otorisasi)
  → Controller (koordinasi)
  → Service (business logic)
  → Model (query database)
  → Database MySQL
  → Model return data
  → API Resource (format JSON)
  → JSON Response
  → Vue update tampilan
```

### Event Flow (Stok Berkurang)
```
Order status → "shipped"
  → TransactionService::updateStatus()
  → event(new OrderShipped($transaction))
  → Listener: DeductStockOnShip::handle()
  → InventoryService::deductStock()
  → Stok berkurang di DB
  → Cek: apakah baru saja turun di bawah minimum?
  → Ya → event(new StockWentLow)
  → Listener: SendLowStockNotification
  → Queue: push job ke Redis
  → Queue Worker (container terpisah) proses job
  → Mail::to('admin')->queue(new LowStockAlert)
  → Email terkirim ke admin
```

---

## 16. Penjelasan Istilah Programming

| Istilah | Penjelasan Sederhana |
|---|---|
| **MVC** | Model-View-Controller. Pola arsitektur: Model = data, View = tampilan, Controller = penghubung |
| **SPA** | Single Page Application. Aplikasi web yang tidak reload halaman, navigasi via JavaScript |
| **REST API** | Cara komunikasi antara frontend dan backend menggunakan HTTP + JSON |
| **Middleware** | Kode yang berjalan sebelum request sampai ke controller. Seperti "petugas keamanan" di pintu masuk |
| **Controller** | Kelas yang menerima request HTTP dan menentukan apa yang harus dilakukan |
| **Model** | Kelas yang merepresentasikan tabel database dan berinteraksi dengan data |
| **Migration** | File PHP yang mendefinisikan struktur tabel database. Seperti "blueprint" tabel |
| **ORM** | Object-Relational Mapping. Library yang memungkinkan kita query database menggunakan objek PHP, bukan SQL mentah |
| **Eloquent** | ORM bawaan Laravel. `Product::where('is_active', true)->get()` = `SELECT * FROM products WHERE is_active = 1` |
| **Trait** | Cara berbagi kode antara beberapa class di PHP tanpa pewarisan (inheritance) |
| **Enum** | Tipe data dengan nilai yang sudah ditentukan. Seperti konstanta yang dikelompokkan |
| **Computed Property** | Nilai yang dihitung otomatis dari data lain, tidak disimpan di database |
| **Scope** | Query builder yang bisa dipanggil dengan nama singkat: `Product::active()` |
| **FormRequest** | Kelas validasi input yang juga mengecek otorisasi |
| **API Resource** | Kelas yang memformat data model menjadi JSON yang dikembalikan ke frontend |
| **Event** | "Pengumuman" bahwa sesuatu telah terjadi di aplikasi |
| **Listener** | Kode yang bereaksi terhadap event |
| **Queue** | Antrian job yang diproses di background, tidak memblok HTTP request |
| **Soft Delete** | Hapus logis — data ditandai `deleted_at`, tidak benar-benar dihapus dari DB |
| **Sanctum** | Library Laravel untuk autentikasi API menggunakan token |
| **RBAC** | Role-Based Access Control. Sistem hak akses berdasarkan role |
| **Race Condition** | Bug yang terjadi ketika dua request bersamaan mengakses data yang sama dan menghasilkan data yang salah |
| **Pinia** | Library state management untuk Vue 3. Menyimpan data yang perlu diakses dari banyak komponen |
| **Computed** | Di Vue, nilai yang otomatis diperbarui ketika data yang bergantung padanya berubah |
| **Reactive** | Data yang ketika berubah, UI otomatis update tanpa reload |
| **Docker** | Platform yang mengemas aplikasi beserta semua dependensinya dalam "container" |
| **Container** | Lingkungan terisolasi yang menjalankan satu service (PHP, MySQL, Nginx, dll) |
| **Nginx** | Web server yang bisa serve file statis dan forward request ke backend |
| **PHP-FPM** | PHP FastCGI Process Manager. Cara menjalankan PHP di belakang Nginx |
| **Redis** | Database in-memory yang super cepat, dipakai untuk cache, queue, dan session |

---

## 17. Panduan Belajar Project Ini

### Urutan Membaca File (Dari Paling Dasar)

**Minggu 1 — Pahami Struktur dan RBAC**
1. `app/Enums/Permission.php` — Pahami semua hak akses yang ada
2. `app/Enums/Role.php` — Pahami siapa dapat apa
3. `app/Traits/HasPermissions.php` — Bagaimana permission dicek
4. `app/Models/User.php` — Bagaimana user + permission terhubung
5. `app/Http/Middleware/CheckPermission.php` — Bagaimana middleware kerja

**Minggu 2 — Pahami Database dan Model**
1. Baca semua file di `database/migrations/` — Pahami struktur setiap tabel
2. Baca semua Model di `app/Models/` — Pahami relasi antar tabel
3. Coba jalankan di Laragon lokal, lihat tabel di phpMyAdmin/TablePlus

**Minggu 3 — Pahami API dan Controller**
1. `routes/api.php` — Peta semua endpoint
2. `AuthController.php` — Mulai dari login
3. `ProductController.php` — Controller paling sederhana
4. `TransactionController.php` — Controller paling kompleks

**Minggu 4 — Pahami Service dan Business Logic**
1. `TransactionService.php` — Pelajari pola Service Layer
2. `InventoryService.php` — Pahami DB transaction dan lock
3. Event & Listener di `app/Events/` dan `app/Listeners/`

**Minggu 5 — Pahami Frontend**
1. `inventory-ui/src/stores/auth.ts` — State management
2. `inventory-ui/src/router/index.ts` — Routing + navigation guard
3. `inventory-ui/src/api/` — Bagaimana frontend panggil backend
4. Satu view, mulai dari `ProductsView.vue`

### Cara Mencoba Modifikasi Kode

**Latihan 1 — Tambah Field Baru**
Coba tambah field `barcode` di tabel products:
1. Buat migration baru: `php artisan make:migration add_barcode_to_products_table`
2. Tambah `$table->string('barcode')->nullable()` di migration
3. Jalankan: `php artisan migrate`
4. Tambah `'barcode'` ke `$fillable` di `Product.php`
5. Tambah ke `ProductResource.php`
6. Tambah ke `StoreProductRequest::rules()`
7. Tambah field di form modal `ProductsView.vue`

**Latihan 2 — Tambah Permission Baru**
Coba tambah permission `report.download`:
1. Tambah `case REPORT_DOWNLOAD = 'report.download';` di `Permission.php`
2. Tambah ke array di `Role::managerPermissions()`
3. Proteksi route: `->middleware('permission:report.download')`
4. Tambah computed di `auth.ts`: `const canDownloadReport = computed(() => can('report.download'))`
5. Gunakan di template: `v-if="auth.canDownloadReport"`

**Latihan 3 — Trace sebuah Request**
Pilih satu tombol di UI (misal "Tambah Produk"), lalu trace:
- File Vue mana yang handle klik
- API call apa yang dikirim
- Route mana yang cocok
- Middleware apa yang berjalan
- Controller method mana yang dipanggil
- Model mana yang diquery
- Response JSON seperti apa

Ini cara terbaik untuk memahami alur aplikasi secara menyeluruh.

### Checklist Pemahaman

Sebelum bilang "sudah paham project ini", pastikan bisa menjawab:

- [ ] Apa bedanya `qty_on_hand` dan `qty_available`?
- [ ] Kenapa `lockForUpdate()` penting saat deduct stock?
- [ ] Bagaimana Manager tahu dia tidak boleh hapus produk tapi boleh edit?
- [ ] Apa yang terjadi di backend saat order berpindah ke status "shipped"?
- [ ] Mengapa email notifikasi tidak diproses langsung di HTTP request, tapi lewat Queue?
- [ ] Bagaimana Vue tahu tombol "Tambah Produk" perlu disembunyikan untuk viewer?
- [ ] Apa keuntungan menggunakan Service Layer dibanding menulis semua logic di Controller?
- [ ] Mengapa `DB::transaction()` digunakan saat buat transaksi baru?
- [ ] Apa perbedaan Event dan Listener? Mengapa dipisah?
- [ ] Bagaimana Nginx tahu request mana yang harus diteruskan ke Laravel dan mana yang langsung serve file Vue?

---

*Dokumentasi ini dibuat berdasarkan analisis source code InvenSys per Maret 2026.*
*Commit terakhir: `67ea634` — fix RBAC UI semua views.*
