# Sistem Informasi Manajemen Inventaris
**Laravel 11 + MySQL 8 | Agile Development**

---

## Persyaratan Sistem

| Software | Versi Minimum |
|----------|--------------|
| PHP      | 8.2+         |
| Laravel  | 11.x         |
| MySQL    | 8.0+         |
| Composer | 2.x          |
| Node.js  | 18+ (opsional, untuk frontend) |

---

## Langkah Instalasi

### 1. Clone & Install Dependencies
```bash
git clone <repo-url> inventory-system
cd inventory-system
composer install
```

### 2. Konfigurasi Environment
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` dan sesuaikan database:
```
DB_DATABASE=inventory_system
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 3. Buat Database
```sql
CREATE DATABASE inventory_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. Jalankan Migration + Seeder
```bash
# Jalankan semua migration
php artisan migrate

# Jalankan semua seeder (users, categories, warehouses, products, customers, employees, inventory)
php artisan db:seed

# Atau sekaligus:
php artisan migrate --seed
```

### 5. Install Laravel Sanctum
```bash
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

### 6. Jalankan Server
```bash
php artisan serve
# Server berjalan di http://localhost:8000
```

---

## Import Data dari CSV

Untuk import data langsung dari ML-Dataset.csv:
```bash
php artisan inventory:import /path/to/ML-Dataset.csv
```

---

## Akun Default (Setelah Seeding)

| Email                       | Password | Role    |
|-----------------------------|----------|---------|
| admin@inventory.test        | password | admin   |
| manager@inventory.test      | password | manager |
| staff@inventory.test        | password | staff   |
| viewer@inventory.test       | password | viewer  |

---

## Menjalankan Tests

```bash
# Jalankan semua test
php artisan test

# Atau menggunakan Pest
./vendor/bin/pest

# Test spesifik
php artisan test tests/Feature/Api/ProductTest.php
php artisan test tests/Unit/Services/InventoryServiceTest.php
```

---

## Dokumentasi API Endpoint

### Auth
| Method | Endpoint            | Deskripsi              | Auth     |
|--------|---------------------|------------------------|----------|
| POST   | /api/auth/register  | Registrasi user baru   | Public   |
| POST   | /api/auth/login     | Login & dapatkan token | Public   |
| POST   | /api/auth/logout    | Logout (revoke token)  | Required |
| GET    | /api/auth/me        | Info user login        | Required |

### Dashboard
| Method | Endpoint                      | Deskripsi                 | Auth     |
|--------|-------------------------------|---------------------------|----------|
| GET    | /api/dashboard/summary        | KPI ringkasan             | Required |
| GET    | /api/dashboard/top-products   | Top 10 produk by revenue  | Required |
| GET    | /api/dashboard/low-stock      | Produk stok rendah        | Required |

### Categories
| Method | Endpoint               | Deskripsi                | Auth          |
|--------|------------------------|--------------------------|---------------|
| GET    | /api/categories        | List kategori            | Required      |
| POST   | /api/categories        | Tambah kategori          | Admin         |
| GET    | /api/categories/{id}   | Detail kategori          | Required      |
| PUT    | /api/categories/{id}   | Update kategori          | Admin/Manager |
| DELETE | /api/categories/{id}   | Hapus kategori           | Admin         |

### Products
| Method | Endpoint             | Deskripsi                           | Auth          |
|--------|----------------------|-------------------------------------|---------------|
| GET    | /api/products        | List produk (search, filter, paging)| Required      |
| POST   | /api/products        | Tambah produk                       | Admin/Manager |
| GET    | /api/products/{id}   | Detail produk + stok per gudang     | Required      |
| PUT    | /api/products/{id}   | Update produk                       | Admin/Manager |
| DELETE | /api/products/{id}   | Hapus produk (soft delete)          | Admin         |

**Query Params:** `?search=xeon&category_id=1&per_page=15&active=1`

### Warehouses
| Method | Endpoint               | Deskripsi           | Auth          |
|--------|------------------------|---------------------|---------------|
| GET    | /api/warehouses        | List gudang         | Required      |
| POST   | /api/warehouses        | Tambah gudang       | Admin/Manager |
| GET    | /api/warehouses/{id}   | Detail gudang       | Required      |
| PUT    | /api/warehouses/{id}   | Update gudang       | Admin/Manager |
| DELETE | /api/warehouses/{id}   | Hapus gudang        | Admin         |

### Inventory
| Method | Endpoint                         | Deskripsi                 | Auth          |
|--------|----------------------------------|---------------------------|---------------|
| GET    | /api/inventory                   | List stok (filter gudang) | Required      |
| GET    | /api/inventory/{id}              | Detail stok               | Required      |
| PUT    | /api/inventory/{id}              | Update stok manual        | Manager       |
| POST   | /api/inventory/transfer          | Transfer stok antar gudang| Manager       |
| GET    | /api/inventory/alerts/low-stock  | Produk stok rendah        | Required      |

**Query Params:** `?warehouse_id=1&product_id=2&low_stock=true&per_page=20`

### Customers
| Method | Endpoint              | Deskripsi          | Auth          |
|--------|-----------------------|--------------------|---------------|
| GET    | /api/customers        | List customer      | Required      |
| POST   | /api/customers        | Tambah customer    | Admin/Manager |
| GET    | /api/customers/{id}   | Detail customer    | Required      |
| PUT    | /api/customers/{id}   | Update customer    | Admin/Manager |
| DELETE | /api/customers/{id}   | Hapus customer     | Admin         |

### Employees
| Method | Endpoint              | Deskripsi          | Auth          |
|--------|-----------------------|--------------------|---------------|
| GET    | /api/employees        | List karyawan      | Required      |
| POST   | /api/employees        | Tambah karyawan    | Admin/Manager |
| GET    | /api/employees/{id}   | Detail karyawan    | Required      |
| PUT    | /api/employees/{id}   | Update karyawan    | Admin/Manager |
| DELETE | /api/employees/{id}   | Hapus karyawan     | Admin         |

### Transactions
| Method | Endpoint                             | Deskripsi            | Auth     |
|--------|--------------------------------------|----------------------|----------|
| GET    | /api/transactions                    | List transaksi       | Required |
| POST   | /api/transactions                    | Buat transaksi baru  | Staff+   |
| GET    | /api/transactions/{id}               | Detail transaksi     | Required |
| PUT    | /api/transactions/{id}               | Update notes         | Staff+   |
| DELETE | /api/transactions/{id}               | Hapus (only pending) | Admin    |
| PATCH  | /api/transactions/{id}/status        | Update status        | Staff+   |

**Query Params:** `?status=pending&from=2024-01-01&to=2024-12-31&customer_id=1`

**Status Transitions:**
- pending → processing, canceled
- processing → shipped, canceled
- shipped → delivered, canceled
- delivered → (tidak bisa diubah)
- canceled → (tidak bisa diubah)

### Reports
| Method | Endpoint                  | Deskripsi              | Auth    |
|--------|---------------------------|------------------------|---------|
| GET    | /api/reports/sales        | Laporan penjualan      | Manager |
| GET    | /api/reports/inventory    | Laporan stok           | Manager |
| GET    | /api/reports/export       | Export Excel/PDF       | Manager |

**Query Params sales:** `?period=monthly&year=2024&month=6`
**Query Params export:** `?format=excel&type=sales&period=monthly&year=2024`

---

## Contoh Request dengan cURL

### Login
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@inventory.test", "password": "password"}'
```

### List Products
```bash
curl http://localhost:8000/api/products \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Buat Transaksi
```bash
curl -X POST http://localhost:8000/api/transactions \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_id": 1,
    "warehouse_id": 1,
    "items": [
      {"product_id": 1, "quantity": 5, "unit_price": 150.00}
    ]
  }'
```

### Transfer Stok
```bash
curl -X POST http://localhost:8000/api/inventory/transfer \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "product_id": 1,
    "from_warehouse_id": 1,
    "to_warehouse_id": 2,
    "quantity": 50
  }'
```

---

## Struktur Folder

```
app/
├── Console/Commands/
│   ├── ImportInventoryCSV.php    # php artisan inventory:import
│   └── SendLowStockAlerts.php   # php artisan inventory:low-stock-alerts
├── Events/
│   ├── OrderShipped.php
│   └── OrderCanceled.php
├── Exceptions/Handler.php
├── Http/
│   ├── Controllers/Api/         # 10 Controllers
│   ├── Middleware/              # ForceJsonResponse, CheckRole
│   ├── Requests/                # 4 Form Requests
│   └── Resources/               # 8 API Resources
├── Listeners/
│   ├── DeductInventoryOnShip.php
│   └── RestoreInventoryOnCancel.php
├── Mail/LowStockAlert.php
├── Models/                      # 9 Eloquent Models
└── Services/                    # InventoryService, TransactionService, ReportService

database/
├── migrations/                  # 8 migration files
└── seeders/                     # 7 seeder files (termasuk 275 produk & 400 customer)

routes/
├── api.php                      # Semua API routes
└── web.php

tests/
├── Feature/Api/
│   ├── ProductTest.php
│   └── TransactionTest.php
└── Unit/Services/
    └── InventoryServiceTest.php
```

---

## Catatan Penting

1. **Export Reports** (`/api/reports/export`) membutuhkan implementasi view Blade terlebih dahulu.
   Pasang `maatwebsite/excel` dan `barryvdh/laravel-dompdf` lalu buat view di `resources/views/reports/`.

2. **Email Low Stock** membutuhkan view `resources/views/emails/low-stock-alert.blade.php`.

3. **Queue Worker** untuk listener async:
   ```bash
   php artisan queue:work
   ```

4. **Scheduled Command** untuk low stock alert otomatis, tambahkan ke `routes/console.php`:
   ```php
   Schedule::command('inventory:low-stock-alerts')->dailyAt('08:00');
   ```
